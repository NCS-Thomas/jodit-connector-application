<?php declare(strict_types=1);

namespace Jodit;

use abeautifulsite\SimpleImage as BaseSimpleImage;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use PHPUnit\Runner\Exception;

class SimpleImage extends BaseSimpleImage
{
    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var WorkFile
     */
    private $file;

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
            $this->file = new WorkFile($filesystem, $filename);
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
        $this->file = new WorkFile($this->filesystem, $filename);

        parent::load($this->file->localFilename());

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

        $this->file->save($filename);
        return $this;
    }

    /**
     * @return bool
     */
    public function isImage(): bool
    {
        if (!in_array($this->file->getExtension(), ['jpg', 'gif', 'png', 'bmp'])) {
            return false;
        }

        try {
            if (!function_exists('exif_imagetype') && !function_exists('Jodit\exif_imagetype')) {
                function exif_imagetype($filename)
                {
                    if ((list(, , $type) = getimagesize($filename)) !== false) {
                        return $type;
                    }

                    return false;
                }
            }

            return in_array(
                exif_imagetype($this->file->localFilename()),
                [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP]
            );
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function localFilename(): string
    {
        return $this->file->localFilename();
    }
}