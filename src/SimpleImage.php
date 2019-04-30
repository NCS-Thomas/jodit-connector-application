<?php declare(strict_types=1);

namespace Jodit;

use abeautifulsite\SimpleImage as BaseSimpleImage;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;

class SimpleImage extends BaseSimpleImage
{
    /**
     * @var Filesystem
     */
    private $filesystem;


    /**
     * @var string
     */
    private $localFilename;

    /**
     * @param Filesystem $filesystem
     * @param string|null $filename
     * @param int|null $width
     * @param int|null $height
     * @param string|null $color
     * @throws \Exception
     */
    public function __construct(
        Filesystem $filesystem,
        string $filename = null,
        int $width = null,
        int $height = null,
        string $color = null
    ) {
        $this->filesystem = $filesystem;

        if (null !== $filename) {
            $this->download($filename);
        }

        parent::__construct($filename, $width, $height, $color);
    }

    /**
     * @param string $filename
     * @return SimpleImage
     * @throws FileNotFoundException
     * @throws \Exception
     */
    public function load($filename): SimpleImage
    {
        $this->download($filename);
        parent::load($this->localFilename);

        return $this;
    }

    /**
     * @param null|string $filename If omitted - original file will be overwritten
     * @param null|int $quality Output image quality in percents 0-100
     * @param null|string $format The format to use; determined by file extension if null
     *
     * @return SimpleImage
     * @throws \Exception
     */
    public function save($filename = null, $quality = null, $format = null): SimpleImage
    {
        parent::save(null, $quality, $format);

        $this->upload($filename);
        return $this;
    }

    /**
     * @param $filename
     * @return string
     */
    private function generateLocalFilename($filename): string
    {
        $dir = sys_get_temp_dir();
        $prefix = '_simpleImage'.(string)microtime(true);
        $extension = $this->getExtension($filename);

        return $dir.DIRECTORY_SEPARATOR.$prefix.'.'.$extension;
    }

    /**
     * @param $filename
     * @return string
     */
    private function getExtension($filename): string
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    /**
     * @param $filename
     * @throws FileNotFoundException
     */
    private function download($filename): void
    {
        $this->localFilename = $this->generateLocalFilename($filename);

        file_put_contents($this->localFilename, $this->filesystem->read($filename));
    }

    /**
     * @param $filename
     * @throws FileExistsException
     */
    private function upload($filename): void
    {
        $this->filesystem->put($filename, file_get_contents($this->localFilename));
    }
}