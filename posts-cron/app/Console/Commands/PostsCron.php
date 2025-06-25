<?php

namespace App\Console\Commands;

use App\Http\Controllers\Controller;
use App\Models\FootballNews;
use App\Models\FootballNewsTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PostsCron extends Command
{
    protected $signature = 'app:posts-cron';
    protected $description = 'Crawl and save football news from remote source';

    public function handle()
    {
        $this->info('PostsCron started');

        $buildId = $this->getBuildId();
        if (!$buildId) {
            $this->error('Build ID not found!');
            return;
        }

        $this->createNewPosts($buildId);

        foreach ($this->getNeedUpdateBodyPosts() as $post) {
            $this->info('Updating body for post ID: ' . $post["id"]);
            $this->updatePostBody($post, $buildId);
        }

        $this->info('PostsCron finished');
    }

    private function getBuildId(): ?string
    {
        $data = Controller::getData(env('SOURCE_DOMAIN') . "en/");
        if ($data) {
            $strs = explode("/", explode("/_buildManifest.js", $data)[0]);
            return end($strs);
        }
        return null;
    }

    private function createNewPosts(string $buildId): void
    {
        $data = json_decode(Controller::getData(env('SOURCE_DOMAIN') . "_next/data/$buildId/en/news/football.json?category=football"));

        if ($data?->pageProps?->articles) {
            $oldIds = $this->getOldNewsId();
            foreach ($data->pageProps->articles as $article) {
                if (in_array($article->id, $oldIds)) continue;
                if (!self::checkValidContent($article->title)) continue;

                $publishedAt = strtotime($article->publishedAt);
                $slug = str_ireplace("-{$article->id}/", "", str_replace("/en/news/", "", $article->url));

                $news = FootballNews::create([
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $slug,
                    'publishedAt' => $publishedAt,
                    'imageUrl' => $article->mainMedia[0]->gallery->url,
                    'alt' => $article->mainMedia[0]->gallery->alt ?? '',
                    'updatedTime' => 0,
                    'imageExt' => self::getImageExt($article->mainMedia[0]->original->url),
                    'status' => 'draft'
                ]);

                $tags = collect($article->related->tags ?? [])->map(function ($tag) {
                    return FootballNewsTag::firstOrCreate([
                        'title' => $tag->title
                    ], [
                        'type' => $tag->type ?? null
                    ])->id;
                })->toArray();

                $news->tags()->sync($tags);
            }
        }
    }

    private function updatePostBody(array $post, string $buildId): string
    {
        $url = env('SOURCE_DOMAIN') . "_next/data/$buildId/en/news/article/{$post['slug']}-{$post['id']}.json?article={$post['slug']}-{$post['id']}";
        $data = json_decode(Controller::getData($url));

        if ($data?->notFound) {
            FootballNews::where('id', $post['id'])->increment('updatedTime');
            return "NOT FOUND - {$post['id']}";
        }

        if (!isset($data->pageProps->article->body)) {
            FootballNews::where('id', $post['id'])->increment('updatedTime');
            return "NO BODY - {$post['id']}";
        }

        $body = $data->pageProps->article->body;
        $relatedIds = collect($data->pageProps->article->related->relatedArticles ?? [])->pluck('id')->toArray();
        $meta = $data->pageProps->layoutContext->meta ?? null;

        $assetPath = public_path("assets/news/{$post['id']}");
        self::crawImage($post['imageUrl'], $assetPath, 'image');

        $bodyArray = [];
        foreach ($body as $b) {
            if (isset($b->type) && $b->type === 'image' && isset($b->image->article->url)) {
                $b->data->type = 'image';
                $b->data->image = self::crawImage($b->image->article->url, $assetPath, md5($b->image->article->url));
            }
            $bodyArray[] = $b;
        }

        $status = $this->checkBody($body) ? 'publish' : 'review-request';

        FootballNews::where('id', $post['id'])->update([
            'body' => json_encode($bodyArray),
            'status' => $status,
            'related_posts' => json_encode($relatedIds)
        ]);

        // Save meta tags
        $news = FootballNews::find($post['id']);
        if ($news && $meta) {
            $news->metaTags()->delete();
            foreach ($meta as $key => $value) {
                $news->metaTags()->create([
                    'tag_type' => str_starts_with($key, 'og:') || str_starts_with($key, 'twitter:') ? 'property' : 'name',
                    'tag_key' => $key,
                    'tag_value' => $value,
                ]);
            }
        }

        return "UPDATED - {$post['id']}";
    }

    private function getOldNewsId(): array
    {
        return FootballNews::orderByDesc('id')->take(50)->pluck('id')->toArray();
    }

    private function getNeedUpdateBodyPosts(): array
    {
        return FootballNews::whereNull('body')
            ->where('status', 'draft')
            ->where('updatedTime', '<', 5)
            ->orderBy('updatedTime')
            ->take(30)
            ->get()
            ->toArray();
    }

    private function checkBody($body): bool
    {
        if (!$body) return false;

        foreach ($body as $tag) {
            $data = $tag->data ?? null;
            if ($data?->type && in_array($data->type, ['paragraph', 'heading']) && $this->checkValidContent($data->content)) {
                return true;
            }
        }
        return false;
    }

    private static function checkValidContent($content): bool
    {
        $rejectKeywords = explode(',', env('REJECT_KEYWORDS', ''));
        foreach ($rejectKeywords as $keyword) {
            if (stripos(strtolower($content), strtolower(trim($keyword))) !== false) {
                return false;
            }
        }
        return true;
    }

    private static function getImageExt(string $url): string
    {
        $parts = explode('.', explode('?', $url)[0]);
        return '.' . end($parts);
    }

    public static function crawImage(string $url, string $folder, string $fileName): string
    {
        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0777, true);
        }

        $ext = self::getImageExt($url);
        $path = "$folder/{$fileName}{$ext}";

        $content = Controller::getData($url);
        if (strlen($content) > 10000) {
            file_put_contents($path, $content);
            return basename($path);
        }

        return '';
    }
}
