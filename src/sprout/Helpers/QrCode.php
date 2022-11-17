<?php
namespace Sprout\Helpers;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;


class QrCode
{
    /**
     * Render QR code as PNG
     *
     * @param string $payload The content to encode in the QR code
     * @param int $size The desired output size in pixels (square)
     * @return void Echos PNG directly
     */
    public static function render($payload, int $size = 500)
    {
        $image = self::getString($payload, $size);

        $size = strlen($image);
        header("Content-type: image/png");
        header("Content-length: $size");
        echo $image;
    }


    /**
     * Return QR code as PNG image string
     *
     * @param string $payload The content to encode in the QR code
     * @param int $size The desired output size in pixels (square)
     * @return string The PNG image as a string
     */
    public static function getString($payload, int $size = 500)
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new ImagickImageBackEnd()
        );
        $writer = new Writer($renderer);

        return $writer->writeString($payload);
    }


    /**
     * Create a QR code and write to a specified file
     *
     * @param string $payload The content to encode in the QR code
     * @param string $file_path The path and filename to write to
     * @param int $size The desired output size in pixels (square)
     * @return string The PNG image as a string
     */
    public static function create(string $payload, string $file_path, int $size = 500)
    {
        // Extract the extension type from the path. PNG is recommended
        $ext = File::getExt($file_path);

        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd($ext, 100)
        );
        $writer = new Writer($renderer);
        $writer->writeFile($payload, $file_path);
    }
}
