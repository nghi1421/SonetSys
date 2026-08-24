<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Downloads a small, reusable pool of freely-licensed photos (Picsum, backed
 * by Unsplash) and short sample video clips (Google's public GTV sample
 * bucket — Blender Foundation open-movie trailers) into a local cache
 * directory, once. Every seeded post/story/reel that wants media reuses one
 * of these source files through the real upload pipeline (StorageService),
 * so nothing is fetched over the network at app runtime — only here, once,
 * during `php artisan db:seed`.
 */
final class DemoMediaLibrary
{
    private const FEED_IMAGE_SEEDS = [
        'sonetsys-01', 'sonetsys-02', 'sonetsys-03', 'sonetsys-04', 'sonetsys-05',
        'sonetsys-06', 'sonetsys-07', 'sonetsys-08', 'sonetsys-09', 'sonetsys-10',
        'sonetsys-11', 'sonetsys-12', 'sonetsys-13', 'sonetsys-14', 'sonetsys-15',
        'sonetsys-16', 'sonetsys-17', 'sonetsys-18', 'sonetsys-19', 'sonetsys-20',
    ];

    private const STORY_IMAGE_SEEDS = [
        'sonetsys-story-01', 'sonetsys-story-02', 'sonetsys-story-03', 'sonetsys-story-04',
        'sonetsys-story-05', 'sonetsys-story-06', 'sonetsys-story-07', 'sonetsys-story-08',
    ];

    // Google's old gtv-videos-bucket sample set now 403s — these are the
    // small (~1-12MB) CC-licensed open-movie clips + generic samples that
    // are still actually reachable.
    private const VIDEO_URLS = [
        'https://test-videos.co.uk/vids/bigbuckbunny/mp4/h264/360/Big_Buck_Bunny_360_10s_1MB.mp4',
        'https://test-videos.co.uk/vids/sintel/mp4/h264/360/Sintel_360_10s_1MB.mp4',
        'https://test-videos.co.uk/vids/jellyfish/mp4/h264/360/Jellyfish_360_10s_1MB.mp4',
        'https://download.samplelib.com/mp4/sample-10s.mp4',
        'https://download.samplelib.com/mp4/sample-15s.mp4',
    ];

    private const SONG_URLS = [
        'https://download.samplelib.com/mp3/sample-3s.mp3',
        'https://download.samplelib.com/mp3/sample-6s.mp3',
        'https://download.samplelib.com/mp3/sample-9s.mp3',
        'https://download.samplelib.com/mp3/sample-12s.mp3',
        'https://download.samplelib.com/mp3/sample-15s.mp3',
        'https://download.samplelib.com/mp3/sample-30s.mp3',
        'https://download.samplelib.com/mp3/sample-45s.mp3',
    ];

    /**
     * @return list<string> absolute local file paths
     */
    public function feedImages(): array
    {
        return $this->downloadPool(
            self::FEED_IMAGE_SEEDS,
            fn (string $seed) => "https://picsum.photos/seed/{$seed}/1200/800.jpg",
            $this->cacheDir('images'),
            fn (string $seed) => "{$seed}.jpg",
        );
    }

    /**
     * @return list<string> absolute local file paths
     */
    public function storyImages(): array
    {
        return $this->downloadPool(
            self::STORY_IMAGE_SEEDS,
            fn (string $seed) => "https://picsum.photos/seed/{$seed}/720/1280.jpg",
            $this->cacheDir('images'),
            fn (string $seed) => "{$seed}.jpg",
        );
    }

    /**
     * @return list<string> absolute local file paths
     */
    public function videos(): array
    {
        return $this->downloadPool(
            self::VIDEO_URLS,
            fn (string $url) => $url,
            $this->cacheDir('videos'),
            fn (string $url) => basename(parse_url($url, PHP_URL_PATH) ?: $url),
        );
    }

    /**
     * @return list<string> absolute local file paths
     */
    public function songs(): array
    {
        return $this->downloadPool(
            self::SONG_URLS,
            fn (string $url) => $url,
            $this->cacheDir('songs'),
            fn (string $url) => basename(parse_url($url, PHP_URL_PATH) ?: $url),
        );
    }

    /**
     * Wraps an already-downloaded pool file as an UploadedFile so it can go
     * through the real StorageService/MediaService upload pipeline — the
     * same one a real HTTP upload would use. `test: true` is Laravel's own
     * documented way to construct one outside of an actual request; nothing
     * here deletes the source file, so the same pool path can be reused
     * across many posts/stories.
     */
    public function asUploadedFile(string $path): UploadedFile
    {
        return new UploadedFile($path, basename($path), File::mimeType($path) ?: 'application/octet-stream', null, true);
    }

    /**
     * @param  list<string>  $keys
     * @param  callable(string): string  $urlFor
     * @param  callable(string): string  $filenameFor
     * @return list<string>
     */
    private function downloadPool(array $keys, callable $urlFor, string $dir, callable $filenameFor): array
    {
        File::ensureDirectoryExists($dir);

        $paths = [];

        foreach ($keys as $key) {
            $path = $dir.DIRECTORY_SEPARATOR.$filenameFor($key);

            if (! File::exists($path) || File::size($path) === 0) {
                if (! $this->download($urlFor($key), $path)) {
                    continue;
                }
            }

            $paths[] = $path;
        }

        return $paths;
    }

    private function download(string $url, string $destination): bool
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning("DemoMediaLibrary: failed to download {$url} ({$response->status()})");

                return false;
            }

            File::put($destination, $response->body());

            return true;
        } catch (\Throwable $e) {
            Log::warning("DemoMediaLibrary: failed to download {$url}: {$e->getMessage()}");

            return false;
        }
    }

    private function cacheDir(string $subdir): string
    {
        return storage_path('app/seed-source/'.$subdir);
    }
}
