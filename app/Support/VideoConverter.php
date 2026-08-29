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

        try {
            // VP9 + Opus, scaled down, even dimensions, capped bitrate.
            $scale = 'scale=\'min('.self::MAX_DIMENSION.',iw)\':-2';
            $convert = new Process([
                $ffmpeg, '-hide_banner', '-loglevel', 'error', '-y',
                '-i', $tmpIn,
                '-vf', $scale,
                '-c:v', 'libvpx-vp9', '-b:v', '0', '-crf', '34', '-row-mt', '1',
                '-c:a', 'libopus', '-b:a', '96k',
                '-deadline', 'good', '-cpu-used', '4',
                $tmpOut,
            ]);
            $convert->setTimeout(600)->run();

            if (! $convert->isSuccessful() || ! is_file($tmpOut) || filesize($tmpOut) === 0) {
                return false;
            }

            // Poster: first frame, then watermark it with the same tiled mark.
            $posterPath = null;
            $poster = new Process([
                $ffmpeg, '-hide_banner', '-loglevel', 'error', '-y',
                '-i', $tmpOut, '-frames:v', '1', $tmpPoster,
            ]);
            $poster->setTimeout(120)->run();

            if ($poster->isSuccessful() && is_file($tmpPoster) && filesize($tmpPoster) > 0) {
                $posterWebp = ImageTrimmer::process(file_get_contents($tmpPoster));
                $posterPath = $dir.'/'.Str::random(40).'.webp';
                Storage::disk($disk)->put($posterPath, $posterWebp);
            }

            $path = $dir.'/'.Str::random(40).'.webm';
            Storage::disk($disk)->put($path, file_get_contents($tmpOut));

            return ['path' => $path, 'poster_path' => $posterPath];
        } finally {
            @unlink($tmpIn);
            @unlink($tmpOut);
            @unlink($tmpPoster);
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
