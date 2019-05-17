<?php

/**
 * @package    jodit
 *
 * @author     Valeriy Chupurnov <chupurnov@gmail.com>
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       https://xdsoft.net/jodit/
 */
namespace Jodit;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Filesystem;

/**
 * Class Config
 * @property string $thumbFolderName
 * @property bool $allowCrossOrigin
 * @property \Jodit\AccessControl $access
 * @property bool $createThumb
 * @property bool $debug
 * @property string[] $excludeDirectoryNames
 * @property number $quality
 * @property string $datetimeFormat
 * @property string $baseurl
 * @package jodit
 */
class Config {

	/**
	 * @var Config | false
	 */
	private $parent;

	static $defaultOptions = [
		'defaultFilesKey' => 'files',
		'debug' => true, // must be true
		'sources' => [],
		'datetimeFormat' => 'm/d/Y g:i A',
		'quality' => 90,
		'defaultPermission' => 0775,
		'createThumb' => true,
		'thumbFolderName' => '_thumbs',
		'excludeDirectoryNames' => ['.tmb', '.quarantine'],
		'maxFileSize' => '8mb',
		'allowCrossOrigin' => false,

		/**
		 * @var array
		 * @see https://github.com/xdan/jodit-connectors#access-control
		 */
		'accessControl' => [],
		'roleSessionVar' => 'JoditUserRole',
		'defaultRole' => 'guest',
		'allowReplaceSourceFile' => true,
		'baseurl' => '',
		'root' => '',
		'extensions' => [
			'jpg', 'png', 'gif', 'jpeg', 'bmp', 'ico', 'jpeg', 'psd', 'svg', 'ttf', 'tif', 'ai',
			'txt', 'css', 'html', 'js', 'htm', 'ini', 'xml',
			'zip', 'rar', '7z', 'gz', 'tar',
			'pps', 'ppt', 'pptx', 'odp', 'xls', 'xlsx', 'csv',
			'doc', 'docx', 'pdf', 'rtf', '', '', '',
			'avi', 'flv', '3gp', 'mov', 'mkv', 'mp4', 'wmv',
		],
		'imageExtensions' => ['jpg', 'png', 'gif', 'jpeg', 'bmp', 'svg', 'ico'],
		'maxImageWidth' => 1900,
		'maxImageHeight' => 1900
	];

	private $data = [];

	/**
     * @var Filesystem
     */
	private $filesystem;

	/**
	 * @var Config[]
	 */
	public $sources = [];

	function __set($key, $value) {
		$this->data->{$key} = $value;
	}

	function __get($key) {
		if (isset($this->data->{$key})) {
			return $this->data->{$key};
		}

		return $this->parent ? $this->parent->{$key} : null;
	}

    /**
     * Config constructor.
     *
     * @param array $data
     * @param null | false | Config $parent
     * @throws \Exception
     */
	function __construct($data, $parent = null)
    {
        $this->parent = $parent;
        $data = (object)$data;
        $this->data = $data;

        if (null !== $parent && false !== $parent) {
            $this->filesystem = $parent->filesystem;
        } else {
            if (!isset($this->data->adapter)
                || (!($this->data->adapter instanceOf AbstractAdapter) && !($this->data->adapter instanceof CachedAdapter))) {
                throw new \Exception('Invalid or no Flysystem adapter specified');
            }

            $this->filesystem = new Filesystem($data->adapter);
        }

		if ($parent === null) {
			if (!$this->baseurl) {
				$this->baseurl = ((isset($_SERVER['HTTPS']) and $_SERVER['HTTPS']) ? 'https://' : 'http://') . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . '/';
			}

			$options = array_merge(self::$defaultOptions, [
			    'adapter' => $this->data->adapter,
                'filesystem' => $this->filesystem,
            ]);

			$this->parent = new Config($options, false);
		}

		if (isset($data->sources) and is_array($data->sources) and count($data->sources)) {
			foreach ($data->sources as $key => $source) {
				$this->sources[$key] = new Config($source, $this);
			}
		} else {
			$this->sources['default'] = $this;
		}
	}

    /**
     * @return Filesystem
     */
	public function getFilesystem() {
	    return $this->filesystem;
    }

	/**
	 * Get full path for $source->root with trailing slash
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getRoot() {
		if ($this->root) {
		    /*
            if (!$this->filesystem->has($this->root)) {
				throw new \Exception('Root directory not exists ' . $this->root, Consts::ERROR_CODE_NOT_EXISTS);
			}
		    */

			//return realpath($this->root) . Consts::DS;
            return $this->root;
		}

		throw new \Exception('Set root directory for source', Consts::ERROR_CODE_NOT_IMPLEMENTED);
	}

    /**
     * Get full path for $_REQUEST[$name] relative path with trailing slash(if directory)
     *
     * @param bool $relativePath
     * @return bool|string
     * @throws \Exception
     */
	public function getPath ($relativePath = false) {
		$root = $this->getRoot();

		if ($relativePath === false) {
			$relativePath = Jodit::$app->request->path ?: '';
		}

        $root = $root . $relativePath;

		return $root;
	}

    /**
     * @return string
     * @throws \Exception
     */
    public function getRelativePath(): string
    {
        $relative = str_replace($this->getRoot(), '', $this->getPath());

        if (!empty($relative) && DIRECTORY_SEPARATOR !== substr($relative, -1)) {
            $relative = $relative . DIRECTORY_SEPARATOR;
        }

        if (DIRECTORY_SEPARATOR === $relative) {
            $relative = '';
        }

        return $relative;
    }

	/**
	 * Get source by name
	 *
	 * @param string  $sourceName
	 *
	 * @return \Jodit\Config | null
	 */
	public function getSource($sourceName = null) {
		if ($sourceName === 'default') {
			$sourceName = null;
		}

		foreach ($this->sources as $key => $item) {
			if ((!$sourceName || $sourceName === $key)) {
				return $item;
			}

			$source = $item !== $this ? $item->getSource($sourceName) : null;

			if ($source) {
				return $source;
			}
		}

		if ($sourceName) {
			return null;
		}

		return $this;
	}

	public function getCompatibleSource($sourceName = null) {
		if ($sourceName === 'default') {
			$sourceName = null;
		}

		if ($sourceName) {
			$source = $this->getSource($sourceName);

			if (!$source) {
				throw new \Exception('Source not found', Consts::ERROR_CODE_NOT_EXISTS);
			}

			Jodit::$app->accessControl->checkPermission(Jodit::$app->getUserRole(), Jodit::$app->action, $source->getPath());

			return $source;
		}

		if ($sourceName === null) {
			foreach ($this->sources as $key => $item) {
				try {
					$source = $item->getCompatibleSource(false);
					return $source;
				} catch (\Exception $e) {}
			}
		}

		Jodit::$app->accessControl->checkPermission(Jodit::$app->getUserRole(), Jodit::$app->action, $this->getPath());

		return $this;
	}
}