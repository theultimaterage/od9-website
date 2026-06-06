<?php
/**
 * YouTube API Helper for NCZ
 *
 * Checks if channel is LIVE, falls back to latest uploaded video.
 * Uses caching to avoid hitting API limits.
 *
 * Built with Orion (meetorion.app)
 */

// YouTube Data API v3 key (restricted, read-only). Lives in the gitignored
// sibling youtube.config.php (which defines YOUTUBE_API_KEY) — see
// youtube.config.example.php. Rotate in Google Cloud Console -> Credentials.
$_ytCfg = __DIR__ . '/youtube.config.php';
if (is_file($_ytCfg)) { require $_ytCfg; }
if (!defined('YOUTUBE_API_KEY')) { define('YOUTUBE_API_KEY', ''); }

// Channel ID for TheUltimateRage
define('NCZ_CHANNEL_ID', 'UC8-GRxvRYhOHAG3tnlGnwcQ');

// Cache settings
define('NCZ_CACHE_DIR', __DIR__ . '/../cache');
define('NCZ_CACHE_DURATION', 60); // 60 seconds cache

/**
 * Get the best video to embed: live stream if active, otherwise latest upload
 *
 * @return array ['type' => 'live'|'video', 'videoId' => string, 'title' => string]
 */
function ncz_get_embed_video(): array {
    $cacheFile = NCZ_CACHE_DIR . '/ncz_youtube_embed.json';

    if (!is_dir(NCZ_CACHE_DIR)) {
        @mkdir(NCZ_CACHE_DIR, 0755, true);
    }

    if (file_exists($cacheFile)) {
        $cacheAge = time() - filemtime($cacheFile);
        if ($cacheAge < NCZ_CACHE_DURATION) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['videoId'])) {
                return $cached;
            }
        }
    }

    // Check if currently live
    $liveVideo = ncz_check_live_status();
    if ($liveVideo) {
        $result = [
            'type' => 'live',
            'videoId' => $liveVideo['videoId'],
            'title' => $liveVideo['title'],
            'isLive' => true
        ];
        file_put_contents($cacheFile, json_encode($result));
        return $result;
    }

    // Get latest uploaded video
    $latestVideo = ncz_get_latest_video();
    if ($latestVideo) {
        $result = [
            'type' => 'video',
            'videoId' => $latestVideo['videoId'],
            'title' => $latestVideo['title'],
            'isLive' => false
        ];
        file_put_contents($cacheFile, json_encode($result));
        return $result;
    }

    // Fallback - return channel embed
    return [
        'type' => 'channel',
        'videoId' => null,
        'title' => 'The No Cap Zone',
        'isLive' => false
    ];
}

/**
 * Check if channel is currently streaming live
 *
 * @return array|null ['videoId' => string, 'title' => string] or null if not live
 */
function ncz_check_live_status(): ?array {
    $url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query([
        'part' => 'snippet',
        'channelId' => NCZ_CHANNEL_ID,
        'eventType' => 'live',
        'type' => 'video',
        'key' => YOUTUBE_API_KEY
    ]);

    $response = @file_get_contents($url);
    if (!$response) {
        return null;
    }

    $data = json_decode($response, true);
    if (empty($data['items'])) {
        return null;
    }

    $item = $data['items'][0];
    return [
        'videoId' => $item['id']['videoId'],
        'title' => $item['snippet']['title']
    ];
}

/**
 * Get the latest uploaded video from the channel
 *
 * @return array|null ['videoId' => string, 'title' => string] or null on error
 */
function ncz_get_latest_video(): ?array {
    $channelUrl = 'https://www.googleapis.com/youtube/v3/channels?' . http_build_query([
        'part' => 'contentDetails',
        'id' => NCZ_CHANNEL_ID,
        'key' => YOUTUBE_API_KEY
    ]);

    $response = @file_get_contents($channelUrl);
    if (!$response) {
        return null;
    }

    $data = json_decode($response, true);
    if (empty($data['items'])) {
        return null;
    }

    $uploadsPlaylistId = $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
    if (!$uploadsPlaylistId) {
        return null;
    }

    $playlistUrl = 'https://www.googleapis.com/youtube/v3/playlistItems?' . http_build_query([
        'part' => 'snippet',
        'playlistId' => $uploadsPlaylistId,
        'maxResults' => 1,
        'key' => YOUTUBE_API_KEY
    ]);

    $response = @file_get_contents($playlistUrl);
    if (!$response) {
        return null;
    }

    $data = json_decode($response, true);
    if (empty($data['items'])) {
        return null;
    }

    $item = $data['items'][0];
    return [
        'videoId' => $item['snippet']['resourceId']['videoId'],
        'title' => $item['snippet']['title']
    ];
}

/**
 * Generate the embed HTML for NCZ
 *
 * @return string HTML for the embed
 */
function ncz_render_embed(): string {
    $embed = ncz_get_embed_video();

    $liveIndicator = '';
    if ($embed['isLive']) {
        $liveIndicator = '<div class="ncz-live-indicator"><span class="live-dot"></span> LIVE NOW</div>';
    }

    if ($embed['videoId']) {
        $embedUrl = 'https://www.youtube.com/embed/' . htmlspecialchars($embed['videoId']);
        if ($embed['isLive']) {
            $embedUrl .= '?autoplay=1';
        }
    } else {
        $embedUrl = 'https://www.youtube.com/embed?listType=user_uploads&list=theultimaterage';
    }

    $title = htmlspecialchars($embed['title']);

    return <<<HTML
{$liveIndicator}
<div class="embed-wrap">
<iframe src="{$embedUrl}" title="{$title}" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
</div>
HTML;
}