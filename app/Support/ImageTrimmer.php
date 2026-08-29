<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageTrimmer
{
    /**
     * Longest edge (px) a stored photo is scaled down to. Full-resolution
     * phone photos are multi-megabyte and stall the gallery; this keeps them
     * web-sized while staying sharp on any listing page.
     */
    public const MAX_DIMENSION = 1600;

    /**
     * Process an image: crop black borders, downscale to a web size, add the
     * "CRXFARM" watermark, and convert to WebP.
     */
    public static function process(string $binaryData, int $quality = 82): string
    {
        $im = @imagecreatefromstring($binaryData);
        if ($im === false) {
            return $binaryData;
        }

        imagepalettetotruecolor($im);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        $trimmed = self::trimGdImage($im);
        $sized = self::downscale($trimmed, self::MAX_DIMENSION);
        $watermarked = self::watermark($sized, 'CRXFARM');

        return self::convertToWebp($watermarked, $quality);
    }

    /**
     * Scale an image down so its longest edge is at most $maxDimension.
     * Returns the original untouched when it is already small enough.
     */
    public static function downscale(GdImage $im, int $maxDimension): GdImage
    {
        $w = imagesx($im);
        $h = imagesy($im);
        $longest = max($w, $h);

        if ($longest <= $maxDimension) {
            return $im;
        }

        $scale = $maxDimension / $longest;
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    /**
     * A transparent PNG of the given size carrying just the tiled CRXFARM
     * watermark, for overlaying onto video frames with ffmpeg.
     */
    public static function watermarkOverlay(int $width, int $height): string
    {
        $im = imagecreatetruecolor($width, $height);
        imagesavealpha($im, true);
        imagealphablending($im, false);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127)); // fully transparent
        imagealphablending($im, true); // let the mark blend onto the clear canvas

        self::watermark($im, 'CRXFARM');

        ob_start();
        imagepng($im);

        return (string) ob_get_clean();
    }

    /**
     * Trim black borders from an image in binary string form.
     */
    public static function trim(string $binaryData, int $threshold = 30): string
    {
        $im = @imagecreatefromstring($binaryData);
        if ($im === false) {
            return $binaryData;
        }

        $cropped = self::trimGdImage($im, $threshold);
        if ($cropped === $im) {
            return $binaryData;
        }

        ob_start();
        imagejpeg($cropped, null, 92);
        $result = ob_get_clean();

        return $result !== false ? $result : $binaryData;
    }

    /**
     * Trim black borders from a GdImage instance. Returns a new cropped GdImage or original if none found.
     */
    public static function trimGdImage(GdImage $im, int $threshold = 30): GdImage
    {
        $w = imagesx($im);
        $h = imagesy($im);

        $left = 0;
        for ($x = 0; $x < $w; $x++) {
            $nonBlack = false;
            for ($y = 0; $y < $h; $y += 2) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r > $threshold || $g > $threshold || $b > $threshold) {
                    $nonBlack = true;
                    break;
                }
            }
            if ($nonBlack) {
                $left = $x;
                break;
            }
        }

        $right = $w - 1;
        for ($x = $w - 1; $x >= 0; $x--) {
            $nonBlack = false;
            for ($y = 0; $y < $h; $y += 2) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r > $threshold || $g > $threshold || $b > $threshold) {
                    $nonBlack = true;
                    break;
                }
            }
            if ($nonBlack) {
                $right = $x;
                break;
            }
        }

        $top = 0;
        for ($y = 0; $y < $h; $y++) {
            $nonBlack = false;
            for ($x = 0; $x < $w; $x += 2) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r > $threshold || $g > $threshold || $b > $threshold) {
                    $nonBlack = true;
                    break;
                }
            }
            if ($nonBlack) {
                $top = $y;
                break;
            }
        }

        $bottom = $h - 1;
        for ($y = $h - 1; $y >= 0; $y--) {
            $nonBlack = false;
            for ($x = 0; $x < $w; $x += 2) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r > $threshold || $g > $threshold || $b > $threshold) {
                    $nonBlack = true;
                    break;
                }
            }
            if ($nonBlack) {
                $bottom = $y;
                break;
            }
        }

        // If no black borders found, return original
        if ($left === 0 && $right === $w - 1 && $top === 0 && $bottom === $h - 1) {
            return $im;
        }

        $newW = $right - $left + 1;
        $newH = $bottom - $top + 1;

        if ($newW <= 20 || $newH <= 20) {
            return $im;
        }

        $cropped = imagecrop($im, ['x' => $left, 'y' => $top, 'width' => $newW, 'height' => $newH]);

        return $cropped !== false ? $cropped : $im;
    }

    /**
     * Tile the watermark diagonally across the whole image so a screenshot or
     * crop cannot recover a clean photo to reuse in a scam. The text repeats
     * on a staggered grid at an angle, in translucent white with a soft dark
     * shadow so it stays legible over both light and dark areas.
     */
    public static function watermark(GdImage $im, string $text = 'CRXFARM'): GdImage
    {
        imagepalettetotruecolor($im);
        imagealphablending($im, true);
        imagesavealpha($im, true);

        $w = imagesx($im);
        $h = imagesy($im);
        $minDim = min($w, $h);
        $angle = 30;

        $fontPath = self::resolveFontPath();

        if ($fontPath !== null && function_exists('imagettfbbox') && function_exists('imagettftext')) {
            $fontSize = max(11, min(42, (int) round($minDim * 0.05)));

            $bbox = @imagettfbbox($fontSize, $angle, $fontPath, $text);
            if ($bbox !== false) {
                $textW = max(abs($bbox[2] - $bbox[0]), abs($bbox[4] - $bbox[6]));
                $textH = max(abs($bbox[7] - $bbox[1]), abs($bbox[5] - $bbox[3]));

                $stepX = $textW + max(24, (int) round($fontSize * 2.2));
                $stepY = $textH + max(24, (int) round($fontSize * 2.6));

                $shadow = imagecolorallocatealpha($im, 0, 0, 0, 105);
                $ink = imagecolorallocatealpha($im, 255, 255, 255, 88);

                $rowIndex = 0;
                for ($y = -$textH; $y < $h + $stepY; $y += $stepY) {
                    // Stagger every other row so the tiles do not line up.
                    $offset = ($rowIndex % 2) * (int) round($stepX / 2);
                    for ($x = -$textW + $offset; $x < $w + $stepX; $x += $stepX) {
                        if ($shadow !== false) {
                            imagettftext($im, $fontSize, $angle, $x + 1, $y + 1, $shadow, $fontPath, $text);
                        }
                        if ($ink !== false) {
                            imagettftext($im, $fontSize, $angle, $x, $y, $ink, $fontPath, $text);
                        }
                    }
                    $rowIndex++;
                }

                return $im;
            }
        }

        // Fallback: built-in GD font cannot rotate, so tile it upright.
        $font = 5;
        $textW = strlen($text) * imagefontwidth($font);
        $textH = imagefontheight($font);
        $stepX = $textW + 40;
        $stepY = $textH + 40;

        $shadow = imagecolorallocatealpha($im, 0, 0, 0, 105);
        $ink = imagecolorallocatealpha($im, 255, 255, 255, 88);

        $rowIndex = 0;
        for ($y = 0; $y < $h; $y += $stepY) {
            $offset = ($rowIndex % 2) * (int) round($stepX / 2);
            for ($x = $offset; $x < $w; $x += $stepX) {
                if ($shadow !== false) {
                    imagestring($im, $font, $x + 1, $y + 1, $text, $shadow);
                }
                if ($ink !== false) {
                    imagestring($im, $font, $x, $y, $text, $ink);
                }
            }
            $rowIndex++;
        }

        return $im;
    }

    /**
     * Convert a GdImage to WebP binary string.
     */
    public static function convertToWebp(GdImage $im, int $quality = 90): string
    {
        ob_start();
        imagewebp($im, null, $quality);
        $result = ob_get_clean();

        return $result !== false ? $result : '';
    }

    /**
     * Store an uploaded file to a disk after trimming black borders, watermarking, and converting to WebP.
     */
    public static function storeUploaded(UploadedFile $file, string $directory = 'listings', string $disk = 'public'): string
    {
        $filename = Str::random(40).'.webp';
        $targetPath = trim($directory, '/').'/'.$filename;

        $content = file_get_contents($file->getRealPath());
        $processed = self::process($content);

        Storage::disk($disk)->put($targetPath, $processed);

        return $targetPath;
    }

    /**
     * Find an available TTF font file on the host system.
     */
    protected static function resolveFontPath(): ?string
    {
        $candidateFonts = [
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
            '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu-sans-fonts/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/liberation-sans/LiberationSans-Bold.ttf',
            '/System/Library/Fonts/Helvetica.ttc',
            '/Library/Fonts/Arial.ttf',
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
        ];

        foreach ($candidateFonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        return null;
    }
}
