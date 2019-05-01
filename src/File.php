<?php

/**
 * @package    jodit
 *
 * @author     Valeriy Chupurnov <chupurnov@gmail.com>
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       https://xdsoft.net/jodit/
 */

namespace Jodit;

use League\Flysystem\Filesystem;
use SebastianBergmann\CodeCoverage\Report\PHP;

/**
 * Class Files
 */
class File {
	private $path = '';
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param Filesystem $filesystem
     * @param {string} $path
     * @throws \Exception
     */
	function __construct(Filesystem $filesystem, $path) {
	    $this->initExifImageType();

		if (!$filesystem->has($path)) {
			throw new \Exception('File not exists', Consts::ERROR_CODE_NOT_EXISTS);
		}

		$this->path = $path;
        $this->filesystem = $filesystem;
    }

    /**
     * Check file extension
     *
     * @param {Source} $source
     * @return bool
     * @throws \Exception
     */
	public function isGoodFile(Config $source) {
		$info = pathinfo($this->path);

		if (!isset($info['extension']) or (!in_array(strtolower($info['extension']), $source->extensions))) {
			return false;
		}

        $isImage = false;
        try {
            $img = new SimpleImage($source->getFilesystem(), $this->path);
            $isImage = $img->isImage();
        } catch (\Exception $exception) {}

        if (in_array(strtolower($info['extension']), $source->imageExtensions) and !$isImage) {
            return false;
        }

		return true;
	}

	/**
	 * Remove file
	 */
	public function remove() {
	    $thumbFolder = Jodit::$app->getSource()->thumbFolderName . Consts::DS;
		$thumb = $thumbFolder . $this->path;

		if ($this->filesystem->has($thumb)) {
			$this->filesystem->delete($thumb);

            if (0 === count($this->filesystem->listContents($thumbFolder))) {
                $this->filesystem->deleteDir($thumbFolder);
            }
		}

		return $this->filesystem->delete($this->path);
	}

	/**
	 * @return string
	 */
	public function getPath() {
		$path = str_replace('\\', Consts::DS, $this->path);
		return $path;
	}

	/**
	 * @return string
	 */
	public function getFolder() {
		return dirname($this->getPath()) . Consts::DS;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return basename($this->path);
	}

	/**
	 * @return int
	 */
	public function getSize() {
		return filesize($this->getPath());
	}

	public function getTime() {
		return filemtime($this->getPath());
	}

	/**
	 * Get file extension
	 *
	 * @return string
	 */
	public function getExtension() {
		return pathinfo($this->getPath(), PATHINFO_EXTENSION);
	}

	function getPathByRoot(Config $source) {
		$path = preg_replace('#[\\\\/]#', '/', $this->getPath());
		$root = preg_replace('#[\\\\/]#', '/',  $source->getPath());

		return str_replace($root, '', $path);
	}

	/**
	 * Check by mimetype what file is image
	 *
	 * @return bool
     */
    public function isImage(): bool
    {
        $allowedMimeTypes = [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP];

        try {
            $mimeType = exif_imagetype(
                (new SimpleImage($this->filesystem, $this->getPath()))->localFilename()
            );

            return in_array($mimeType, $allowedMimeTypes);
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * Initialize the exif_imagetype function. As it might not be available
     */
    private function initExifImageType(): void
    {
        if (!function_exists('exif_imagetype') && !function_exists('Jodit\exif_imagetype')) {
            function exif_imagetype($filename)
            {
                if ((list(, , $type) = getimagesize($filename)) !== false) {
                    return $type;
                }

                return false;
            }
        }
    }
}