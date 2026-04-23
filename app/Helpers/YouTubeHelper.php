<?php

namespace App\Helpers;

class YouTubeHelper
{
    public static function extractVideoId(string $url): ?string
    {
        $url = trim($url);
        $parts = parse_url($url);

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? '';

        // 合法網域白名單
        $isYoutubeHost = fn(string $h) => (
            $h === 'youtube.com' ||
            $h === 'www.youtube.com' ||
            $h === 'm.youtube.com' ||
            $h === 'music.youtube.com' ||
            $h === 'youtu.be' ||
            $h === 'www.youtu.be' ||
            $h === 'youtube-nocookie.com' ||
            $h === 'www.youtube-nocookie.com'
        );

        if (! $isYoutubeHost($host)) {
            return null;
        }

        // 1) youtu.be/ID
        if (str_ends_with($host, 'youtu.be')) {
            $id = ltrim($path, '/');
            return self::isValidVideoId($id) ? $id : null;
        }

        // 2) /watch?v=ID
        if (str_starts_with($path, '/watch')) {
            parse_str($query, $qs);
            $id = $qs['v'] ?? null;
            return self::isValidVideoId($id) ? $id : null;
        }

        // 3) /embed/ID
        if (preg_match('#/embed/([A-Za-z0-9_-]{11})#', $path, $m)) {
            return $m[1];
        }

        // 4) /shorts/ID
        if (preg_match('#/shorts/([A-Za-z0-9_-]{11})#', $path, $m)) {
            return $m[1];
        }

        // 5) /live/ID （直播頁面）
        if (preg_match('#/live/([A-Za-z0-9_-]{11})#', $path, $m)) {
            return $m[1];
        }

        return null;
    }

    public static function convertToStandardUrl(string $url): ?string
    {
        $id = self::extractVideoId($url);
        return $id ? "https://www.youtube.com/watch?v={$id}" : null;
    }

    public static function convertToEmbedUrl(string $url): ?string
    {
        $id = self::extractVideoId($url);
        return $id ? "https://www.youtube.com/embed/{$id}" : null;
    }

    public static function isValidYouTubeUrl(string $url): bool
    {
        return (bool) self::extractVideoId($url);
    }

    public static function getThumbnailUrl(string $url, string $quality = 'high'): ?string
    {
        $id = self::extractVideoId($url);
        if (! $id) return null;

        $qualityMap = [
            'default'  => 'default',     // 120x90
            'medium'   => 'mqdefault',   // 320x180
            'high'     => 'hqdefault',   // 480x360
            'standard' => 'sddefault',   // 640x480
            'maxres'   => 'maxresdefault'// 1280x720
        ];

        $q = $qualityMap[$quality] ?? 'hqdefault';
        return "https://img.youtube.com/vi/{$id}/{$q}.jpg";
    }

    private static function isValidVideoId(?string $id): bool
    {
        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1;
    }
}
