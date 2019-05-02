<?php declare(strict_types=1);

namespace Jodit;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class WorkFile
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $localFilename;

    /**
     * @param Filesystem $filesystem
     * @param string $path relative path
     * @param string|null $localFilename When a localFilename is set, the file will not be fetched from the filesystem
     *                                   but it will take a local file (i.e. an uploaded file) as its source. This will
     *                                   not generate a new tmp file!
     * @throws FileNotFoundException
     */
    public function __construct(Filesystem $filesystem, string $path, string $localFilename = null)
    {
        // initialize exif_imagetype function @todo investigate why? :)
        $this->initExifImageType();

        $this->filesystem = $filesystem;
        $this->path = $path;

        if (null !== $localFilename) {
            if (!file_exists($localFilename)) {
                throw new FileNotFoundException($localFilename);
            }

            $this->localFilename = $localFilename;
        } else {
            $this->download();
        }
    }

    /**
     * @param string|null $path
     */
    public function save(string $path = null): void
    {
        if (null !== $path) {
            $this->path = $path;
        }

        $this->upload();
    }

    /**
     * @return string
     */
    private function generateLocalFilename(): string
    {
        $dir = sys_get_temp_dir();
        $prefix = '_file'.(string)microtime(true);
        $extension = $this->getExtension();

        return $dir.DIRECTORY_SEPARATOR.$prefix.'.'.$extension;
    }

    /**
     * @throws FileNotFoundException
     */
    private function download(): void
    {
        $this->localFilename = $this->generateLocalFilename();

        file_put_contents($this->localFilename, $this->filesystem->read($this->path));
    }

    private function upload(): void
    {
        $this->filesystem->put($this->path, file_get_contents($this->localFilename));
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
            return in_array(exif_imagetype($this->localFilename), $allowedMimeTypes);
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

    /**
     * Check file extension
     *
     * @param Config $source
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
            $isImage = $this->isImage();
        } catch (\Exception $exception) {}

        if (in_array(strtolower($info['extension']), $source->imageExtensions) and !$isImage) {
            return false;
        }

        return true;
    }

    /**
     * Remove file
     * @throws \Exception
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
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    /**
     * @return string
     */
    public function getFolder() {
        return dirname($this->path) . Consts::DS;
    }

    /**
     * @return string
     */
    public function getName() {
        return basename($this->path);
    }

    /**
     * @return string
     */
    public function getPath() {
        $path = str_replace('\\', Consts::DS, $this->path);
        return $path;
    }

    /**
     * @param Config $source
     * @return mixed
     * @throws \Exception
     */
    function getPathByRoot(Config $source) {
        $path = preg_replace('#[\\\\/]#', '/', $this->getPath());
        $root = preg_replace('#[\\\\/]#', '/',  $source->getPath());

        return str_replace($root, '', $path);
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return filesize($this->localFilename);
    }

    /**
     * @return string
     */
    public function localFilename(): string
    {
        return $this->localFilename;
    }
}