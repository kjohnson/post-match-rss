<?php

use Illuminate\Support\Facades\Route;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;

Route::get('/', function() {
    $owner      = 'griffisben';
    $repo       = 'Post_Match_App';
    $path       = 'Image_Files/MLS Next Pro 2026';
    $ref        = 'main';

    $response = Http::withHeaders([
        'Accept' => 'application/vnd.github.v3+json',
        'User-Agent' => 'kjohnson/post-match-rss', // GitHub requires this
    ])->get("https://api.github.com/repos/{$owner}/{$repo}/contents/{$path}", [
        'ref' => $ref,
    ]);

    if ($response->failed()) {
        if ($response->status() === 404) {
            return response()->json(['error' => 'Path not found'], 404);
        }
        return response()->json(['error' => 'GitHub API error'], 500);
    }

    $items = $response->json();

    $files = collect($items)
	->where('type', 'file')
        ->filter(fn($item) => strpos($item['name'], 'Chattanooga') !== false )
        ->map(fn($item) => [
            'name'         => $item['name'],
            'path'         => $item['path'],
            'sha'          => $item['sha'],
            'size_kb'      => round($item['size'] / 1024, 1),
            'download_url' => $item['download_url'],
            'html_url'     => $item['html_url'],
        ]);


    $feed = new Feed();

    $channel = (new Channel)
	    ->title('Post Match RSS')
            ->url('https://post-match-rss.laravel.cloud/')
	    ->appendTo($feed);

    $files->each(fn($file) =>
        (new Item)
		->title($file['name'])
	->guid($file['name'])
	    ->url($file['download_url'])
    ->contentEncoded('<img src="' . $file['download_url'] . '"/>')
		->appendTo($channel)

	    );

    return response($feed)->header("Content-type","text/xml");
});
