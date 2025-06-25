<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Controller;
use App\Models\FootballNews;
use App\Models\FootballNewsTag;
use App\Models\FootballNewsMetaTag;
use Illuminate\Support\Facades\File;

class PostsCron extends Command
{
    protected $signature = 'app:posts-cron';
    protected $description = 'Crawl and save football posts';

    public function handle()
    {
        $this->info('PostsCron command started!');
        $buildId = $this->getBuildId();

        if (!$buildId) {
            $this->warn('Build ID not found!');
            return;
        }

        $this->createNewPosts($buildId);

        $needUpdateBodyPosts = $this->getNeedUpdateBodyPosts();
        foreach ($needUpdateBodyPosts as $post) {
            $this->info('CRAW BODY - ' . $post["id"]);
            $this->updatePostBody($post, $buildId);
        }

        $this->info('PostsCron command completed!');
    }

    private function getBuildId()
    {
        $data = Controller::getData(env('SOURCE_DOMAIN') . "en/");
        if ($data) {
            $strs = explode("/", explode("/_buildManifest.js", $data)[0]);
            return end($strs);
        }
        return null;
    }

    private function createNewPosts($buildId)
    {
        $data = json_decode(Controller::getData(env('SOURCE_DOMAIN') . "_next/data/$buildId/en/news/football.json?category=football"));
        if ($data?->pageProps?->articles) {
            $oldIds = $this->getOldNewsId();
            foreach ($data->pageProps->articles as $article) {
                if (!in_array($article->id, $oldIds) && self::checkValidContent($article->title)) {
                    $article->publishedAt = strtotime($article->publishedAt);

                    $this->info('NEW POST - ' . $article->id);
                    $news = FootballNews::create([
                        "id" => $article->id,
                        "title" => $article->title,
                        "slug" => str_ireplace("-" . $article->id . "/", "", str_replace("/en/news/", "", $article->url)),
                        "publishedAt" => $article->publishedAt,
                        "imageUrl" => $article->mainMedia[0]->gallery->url,
                        "alt" => $article->mainMedia[0]->gallery->alt,
                        "updatedTime" => 0,
                        "imageExt" => self::getImageExt($article->mainMedia[0]->original->url),
                        "status" => "draft"
                    ]);

                    // Lưu tags
                    if (isset($article->related->tags)) {
                        $tags = array_map(function ($tag) {
                            return [
                                'title' => $tag->title,
                                'type' => $tag->type ?? null,
                                'href' => $tag->href ?? null,
                                'provider' => $tag->provider ?? null
                            ];
                        }, $article->related->tags);

                        $news->tags()->createMany($tags);
                    }
                }
            }
        }
    }

    private function getOldNewsId()
    {
        return FootballNews::orderByDesc('id')->take(50)->pluck('id')->toArray();
    }

    private function updatePostBody($post, $buildId)
    {
        $data = json_decode(Controller::getData(env('SOURCE_DOMAIN') . "_next/data/$buildId/en/news/article/{$post['slug']}-{$post['id']}.json?article={$post['slug']}-{$post['id']}"));

        $news = FootballNews::find($post["id"]);
        if (!$news) return;

        if (isset($data->notFound) && $data->notFound) {
            $news->increment('updatedTime');
            return "NOT FOUND - {$post['id']}";
        }

        if (!isset($data->pageProps?->article?->body)) {
            $news->increment('updatedTime');
            return "FAIL - {$post['id']}";
        }

        $body = $data->pageProps->article->body;
        $relatedPosts = collect($data->pageProps->article->related->relatedArticles ?? [])
            ->pluck('id')->toArray();

        $status = $this->checkBody($body) ? "publish" : "review-request";

        // Crawl main image
        $newsAssetPath = "../assets/news/" . $post["id"];
        self::crawImage($post["imageUrl"], $newsAssetPath, "image");

        // Crawl all image inside body
        foreach ($body as $b) {
            if (isset($b->type) && $b->type === "image") {
                $b->data->type = "image";
                $b->data->image = self::crawImage($b->image->article->url, $newsAssetPath, md5($b->image->article->url));
            }
        }

        // Update body + status + related_posts
        $news->update([
            "body" => json_encode($body),
            "status" => $status,
            "related_posts" => json_encode($relatedPosts)
        ]);

        // Lưu lại meta tag dưới dạng từng dòng
        $news->metaTags()->delete();
        $meta = $data->pageProps->layoutContext->meta ?? null;
        if ($meta && is_object($meta)) {
            $metaTags = [];
            foreach ($meta as $key => $value) {
                $value = is_array($value) || is_object($value) ? json_encode($value) : $value;
                $type = str_starts_with($key, 'og:') || str_starts_with($key, 'twitter:') ? 'property' : 'name';

                $metaTags[] = [
                    'tag_type' => $type,
                    'tag_key' => $key,
                    'tag_value' => $value
                ];
            }
            $news->metaTags()->createMany($metaTags);
        }

        return "SUCCESS - {$post['id']}";
    }

    private function checkBody($body)
    {
        if (!$body) return false;

        foreach ($body as $tag) {
            $data = $tag->data ?? null;
            if (isset($data->type) && in_array($data->type, ["paragraph", "heading"]) && $this->checkValidContent($data->content)) {
                return false;
            }
        }
        return true;
    }

    private static function checkValidContent($content)
    {
        $rejectKeywords = explode(',', env('REJECT_KEYWORDS', ''));
        foreach ($rejectKeywords as $keyword) {
            if (stripos(strtolower($content), strtolower(trim($keyword))) !== false) {
                return false;
            }
        }
        return true;
    }

    private function getNeedUpdateBodyPosts()
    {
        return FootballNews::whereNull("body")
            ->where("status", "draft")
            ->where("updatedTime", "<", 5)
            ->orderBy("updatedTime")
            ->take(30)
            ->get()
            ->toArray();
    }

    private static function getImageExt($url)
    {
        $clean = explode("?", $url)[0];
        $ext = pathinfo($clean, PATHINFO_EXTENSION);
        return '.' . ($ext ?: 'jpg');
    }

    public static function crawImage($url, $folder, $fileName)
    {
        if (!$fileName) return "";

        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0777, true, true);
        }

        $ext = self::getImageExt($url);
        $name = $fileName . $ext;

        $contents = Controller::getData($url);
        if (strlen($contents) > 10000) {
            file_put_contents($folder . "/" . $name, $contents);
            return $name;
        }

        return "";
    }
}
