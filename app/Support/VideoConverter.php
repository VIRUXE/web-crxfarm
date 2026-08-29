<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Converts an uploaded/downloaded video to a web-friendly WebM (VP9 + Opus)
 * and grabs a watermarked poster frame. All heavy lifting is ffmpeg; if it is
 * not available the caller gets false and can fall back or skip.
 */
class VideoConverter
{
    /** Longest edge the video is scaled down to. */
    public const MAX_DIMENSION = 1280;

    public static function ffmpegAvailable(): bool
    {
        return self::binary('ffmpeg') !== null;
    }

    /**
     * Convert raw video bytes to WebM and a watermarked JPEG-ish poster, storing
     * both on the given disk under $dir. Returns their storage paths, or false
     * if conversion was not possible.
     *
     * @return array{path: string, poster_path: ?string}|false
     */
    public static function store(string $videoBytes, string $dir = 'listings/videos', string $disk = 'public'): array|false
    {
        $ffmpeg = self::binary('ffmpeg');
        if ($ffmpeg === null || $videoBytes === '') {
            return false;
        }

        $tmpIn = tempnam(sys_get_temp_dir(), 'crxvid_').'.src';
        file_put_contents($tmpIn, $videoBytes);
        $tmpOut = tempnam(sys_get_temp_dir(), 'crxvid_').'.webm';
        $tmpPoster = tempnam(sys_get_temp_dir(), 'crxpos_').'.png';
        $tmpMark = tempnam(sys_get_temp_dir(), 'crxmark_').'.png';

        // A square tiled watermark; scale2ref stretches it to cover any frame.
        file_put_contents($tmpMark, ImageTrimmer::watermarkOverlay(self::MAX_DIMENSION, self::MAX_DIMENSION));

        try {
            // Scale the video down, stretch the watermark to match, and burn it
            // over every frame. VP9 + Opus, capped quality.
            $filter = '[0:v]scale=\'min('.self::MAX_DIMENSION.',iw)\':-2,setsar=1[v];'
                .'[1:v][v]scale2ref=w=iw:h=ih[mark][vid];'
                .'[vid][mark]overlay=0:0';

            $convert = new Process([
                $ffmpeg, '-hide_banner', '-loglevel', 'error', '-y',
                '-i', $tmpIn, '-i', $tmpMark,
                '-filter_complex', $filter,
                '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', '34', '-row-mt', '1',
                '-c:a', 'libopus', '-b:a', '96k',
                '-deadline', 'good', '-cpu-used', '4',
                $tmpOut,
            ]);
            $convert->setTimeout(600)->run();

            if (! $convert->isSuccessful() || ! is_file($tmpOut) || filesize($tmpOut) === 0) {
                return false;
            }

            // Poster: a frame of the already-watermarked video, encoded to WebP
            // (no second watermark — it is baked into the frame).
            $posterPath = null;
            $poster = new Process([
                $ffmpeg, '-hide_banner', '-loglevel', 'error', '-y',
                '-i', $tmpOut, '-frames:v', '1', $tmpPoster,
            ]);
            $poster->setTimeout(120)->run();

            if ($poster->isSuccessful() && is_file($tmpPoster) && filesize($tmpPoster) > 0) {
                $frame = @imagecreatefromstring((string) file_get_contents($tmpPoster));
                if ($frame !== false) {
                    ob_start();
                    imagewebp($frame, null, 82);
                    $posterWebp = (string) ob_get_clean();
                    $posterPath = $dir.'/'.Str::random(40).'.webp';
                    Storage::disk($disk)->put($posterPath, $posterWebp);
                }
            }

            $path = $dir.'/'.Str::random(40).'.webm';
            Storage::disk($disk)->put($path, file_get_contents($tmpOut));

            return ['path' => $path, 'poster_path' => $posterPath];
        } finally {
            @unlink($tmpIn);
            @unlink($tmpOut);
            @unlink($tmpPoster);
            @unlink($tmpMark);
        }
    }

    /**
     * Whether a set of bytes looks like a video container ffmpeg can read.
     */
    public static function looksLikeVideo(string $bytes): bool
    {
        // MP4/MOV ("ftyp"), WebM/Matroska (EBML), AVI ("RIFF..AVI ").
        return str_contains(substr($bytes, 0, 32), 'ftyp')
            || str_starts_with($bytes, "\x1A\x45\xDF\xA3")
            || (str_starts_with($bytes, 'RIFF') && str_contains(substr($bytes, 0, 16), 'AVI'));
    }

    private static function binary(string $name): ?string
    {
        foreach (['/usr/bin/'.$name, '/usr/local/bin/'.$name, '/opt/homebrew/bin/'.$name] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        $which = new Process(['which', $name]);
        $which->run();

        $path = trim($which->getOutput());

        return $which->isSuccessful() && $path !== '' ? $path : null;
    }
}
