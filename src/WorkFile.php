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
     * @return string
     */
    private function getExtension(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
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
     * @return string
     */
    public function localFilename(): string
    {
        return $this->localFilename;
    }
}