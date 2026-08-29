<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OgImageGenerator
{
    /**
     * Target size social platforms expect for og:image / twitter:image
     * (1.91:1). Anything else risks being cropped or letterboxed by the
     * platform itself, which is what sent us down this path.
     */
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /**
     * Read an already-processed listing photo off disk, cover-crop it to
     * exactly WIDTHxHEIGHT, and store the result as a JPEG (broader social
     * crawler support than WebP). Returns the new file's storage path.
     */
    public static function generate(string $sourcePath, string $disk = 'public'): ?string
    {
        $binary = Storage::disk($disk)->get($sourcePath);
        if ($binary === null) {
            return null;
        }

        $im = @imagecreatefromstring($binary);
        if ($im === false) {
            return null;
        }

        imagepalettetotruecolor($im);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        $cropped = self::coverCrop($im, self::WIDTH, self::HEIGHT);

        ob_start();
        imagejpeg($cropped, null, 85);
        $result = ob_get_clean();

        if ($result === false || $result === '') {
            return null;
        }

        $targetPath = 'og/'.Str::random(40).'.jpg';
        Storage::disk($disk)->put($targetPath, $result);

        return $targetPath;
    }

    /**
     * Scale the source image up/down so it fully covers a WIDTHxHEIGHT
     * canvas, then crop the overhang from the centre. Same idea as CSS
     * `object-fit: cover` - fills the frame with no bars, at the cost of
     * trimming the long edge.
     */
    public static function coverCrop(\GdImage $im, int $width, int $height): \GdImage
    {
        $srcW = imagesx($im);
        $srcH = imagesy($im);

        $scale = max($width / $srcW, $height / $srcH);
        $scaledW = (int) round($srcW * $scale);
        $scaledH = (int) round($srcH * $scale);

        $resized = imagecreatetruecolor($scaledW, $scaledH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $im, 0, 0, 0, 0, $scaledW, $scaledH, $srcW, $srcH);

        $srcX = (int) round(($scaledW - $width) / 2);
        $srcY = (int) round(($scaledH - $height) / 2);

        $canvas = imagecreatetruecolor($width, $height);
        imagecopy($canvas, $resized, 0, 0, max(0, $srcX), max(0, $srcY), $width, $height);

        return $canvas;
    }
}
