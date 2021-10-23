<?php
namespace Sprout\Helpers;
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'phpqrcode/qrlib.php');

use QRcode as QrCodeLib;


class QrCode
{
    /**
     * Render QR code as PNG
     *
     * @param string $payload
     * @return void Echos PNG directly
     */
    public static function render($payload)
    {
        QrCodeLib::png($payload, false, 'H', 16, 2);
    }
}
