<?php

namespace Tests\Unit;

use App\Support\ImageTrimmer;
use PHPUnit\Framework\TestCase;

class ImageTrimmerTest extends TestCase
{
    public function test_trims_black_pillarbox_borders_from_image(): void
    {
        // Create an image: 100x50 with 25px black on left and right, red in the middle
        $im = imagecreatetruecolor(100, 50);
        $black = imagecolorallocate($im, 0, 0, 0);
        $red = imagecolorallocate($im, 255, 0, 0);

        imagefilledrectangle($im, 0, 0, 99, 49, $black);
        imagefilledrectangle($im, 25, 0, 74, 49, $red);

        ob_start();
        imagejpeg($im, null, 100);
        $raw = ob_get_clean();

        $trimmedRaw = ImageTrimmer::trim($raw);

        $trimmedIm = imagecreatefromstring($trimmedRaw);
        $this->assertNotFalse($trimmedIm);

        $this->assertEquals(50, imagesx($trimmedIm));
        $this->assertEquals(50, imagesy($trimmedIm));
    }

    public function test_returns_original_if_no_black_borders(): void
    {
        $im = imagecreatetruecolor(50, 50);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, 49, 49, $white);

        ob_start();
        imagejpeg($im, null, 100);
        $raw = ob_get_clean();

        $result = ImageTrimmer::trim($raw);
        $this->assertSame($raw, $result);
    }

    public function test_process_converts_jpeg_to_webp_and_adds_watermark(): void
    {
        $im = imagecreatetruecolor(400, 300);
        $blue = imagecolorallocate($im, 20, 50, 200);
        imagefilledrectangle($im, 0, 0, 399, 299, $blue);

        ob_start();
        imagejpeg($im, null, 95);
        $jpegRaw = ob_get_clean();

        $processed = ImageTrimmer::process($jpegRaw);

        $info = getimagesizefromstring($processed);
        $this->assertIsArray($info);
        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(400, $info[0]);
        $this->assertSame(300, $info[1]);

        $loaded = imagecreatefromstring($processed);
        $this->assertNotFalse($loaded);

        // The watermark tiles translucent white text across the whole image,
        // so on the solid blue fill (r=20) it shows as pixels with a lifted red
        // channel. Sample the whole frame, not just a corner.
        $watermarkPixels = 0;
        for ($x = 0; $x < 400; $x += 2) {
            for ($y = 0; $y < 300; $y += 2) {
                $rgb = imagecolorat($loaded, $x, $y);
                // Base fill has red ~20; translucent white ink lifts it well above.
                if ((($rgb >> 16) & 0xFF) > 60) {
                    $watermarkPixels++;
                }
            }
        }
        // Tiled across the image, the mark touches many pixels (a single corner
        // stamp would touch far fewer).
        $this->assertGreaterThan(200, $watermarkPixels, 'Expected the tiled watermark to be present across the image.');
    }

    public function test_process_converts_png_to_webp(): void
    {
        $im = imagecreatetruecolor(200, 200);
        $green = imagecolorallocate($im, 30, 180, 50);
        imagefilledrectangle($im, 0, 0, 199, 199, $green);

        ob_start();
        imagepng($im);
        $pngRaw = ob_get_clean();

        $processed = ImageTrimmer::process($pngRaw);

        $info = getimagesizefromstring($processed);
        $this->assertIsArray($info);
        $this->assertSame('image/webp', $info['mime']);
    }

    public function test_process_processes_already_webp_image(): void
    {
        $im = imagecreatetruecolor(250, 250);
        $purple = imagecolorallocate($im, 140, 20, 180);
        imagefilledrectangle($im, 0, 0, 249, 249, $purple);

        ob_start();
        imagewebp($im, null, 90);
        $webpRaw = ob_get_clean();

        $processed = ImageTrimmer::process($webpRaw);

        $info = getimagesizefromstring($processed);
        $this->assertIsArray($info);
        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(250, $info[0]);
        $this->assertSame(250, $info[1]);
    }

    public function test_process_trims_borders_and_applies_watermark_and_converts_to_webp(): void
    {
        $im = imagecreatetruecolor(100, 50);
        $black = imagecolorallocate($im, 0, 0, 0);
        $red = imagecolorallocate($im, 255, 0, 0);

        imagefilledrectangle($im, 0, 0, 99, 49, $black);
        imagefilledrectangle($im, 25, 0, 74, 49, $red);

        ob_start();
        imagejpeg($im, null, 100);
        $raw = ob_get_clean();

        $processed = ImageTrimmer::process($raw);

        $info = getimagesizefromstring($processed);
        $this->assertIsArray($info);
        $this->assertSame('image/webp', $info['mime']);
        $this->assertSame(50, $info[0]);
        $this->assertSame(50, $info[1]);
    }

    public function test_process_returns_raw_data_if_invalid_image(): void
    {
        $invalid = 'not-a-valid-image-data';
        $this->assertSame($invalid, ImageTrimmer::process($invalid));
    }
}
