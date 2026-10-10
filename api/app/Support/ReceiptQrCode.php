<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Renders the platform QR code stamped on receipt PDFs.
 *
 * Kept out of the controller so it can be tested directly. The controller wraps
 * the call in a catch-all so a QR failure never blocks a receipt download — which
 * also means a broken QR would otherwise go unnoticed.
 */
class ReceiptQrCode
{
    public static function svgDataUri(string $content, int $size = 150): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd
        );

        $svg = (new Writer($renderer))->writeString($content);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
