<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Controller;
use App\Models\FootballNews;
use Illuminate\Support\Facades\File;

class PostsCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:posts-cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('PostsCron command started!');
        $buildId = $this->getBuildId();
        if ($buildId == null) {
            $this->info('Build ID not found!');
            return;
        }
        $this->createNewPosts($buildId);
        $needUpdateBodyPosts = $this->getNeedUpdateBodyPosts();
        foreach ($needUpdateBodyPosts as $post) {
            $this->info('CRAW BODY - ' . $post["id"]);
            $this->updatePostBody($post, $buildId);
        }
        $this->info('PostsCron command executed successfully!');
    }

    private function getBuildId()
    {
        // get environment variable

        $data = Controller::getData(env('SOURCE_DOMAIN') . "en/");
        if ($data != null) {
            $strs = explode("/", explode("/_buildManifest.js", $data)[0]);
            $id = $strs[count($strs) - 1];
            return $id;
        }
        return null;
    }

    private function createNewPosts($buildId)
    {
        $data = json_decode(Controller::getData(env('SOURCE_DOMAIN') . "_next/data/$buildId/en/news/football.json?category=football"));
        if ($data != null) {
            if (isset($data->pageProps) && isset($data->pageProps->articles)) {
                $oldIds = $this->getOldNewsId();
                foreach ($data->pageProps->articles as $article) {
                    if (!in_array($article->id, $oldIds) && self::checkValidContent($article->title)) {
                        $article->publishedAt = strtotime($article->publishedAt);
                        $this->info('NEW POST - ' . $article->id);
                        FootballNews::firstOrCreate([
                            "id" => $article->id
                        ], [
                            "title" => $article->title,
                            "slug" => str_ireplace("-" . $article->id . "/", "", str_replace("/en/news/", "", $article->url)),
                            "publishedAt" => $article->publishedAt,
                            "tags" => $article->related->tags,
                            "imageUrl" => $article->mainMedia[0]->gallery->url,
                            "alt" => $article->mainMedia[0]->gallery->alt,
                            "updatedTime" => 0,
                            "imageExt" => self::getImageExt($article->mainMedia[0]->original->url),
                            "status" => "draft"
                        ]);
                    }
                }
            }
        }
    }

    private function getOldNewsId()
    {
        return array_map(function ($item) {
            return $item["id"];
        }, FootballNews::select('id')
            ->orderByDesc('id')
            ->take(50)
            ->get()
            ->toArray());
    }

    private function updatePostBody($post, $buildId)
    {
        $result = "";
        $meta = null;
        $body = null;
        $data = json_decode(Controller::getData(env('SOURCE_DOMAIN') . "_next/data/$buildId/en/news/article/" . $post["slug"] . "-" . $post["id"] . ".json?article=" . $post["slug"] . "-" . $post["id"]));
        if (isset($data->notFound) && $data->notFound) {
            $result = "NOT FOUND - " . $post["id"];
            FootballNews::where("id", $post["id"])
                ->update([
                    "updatedTime" => $post["updatedTime"] + 1
                ]);
        } else if (isset($data->pageProps) && isset($data->pageProps->article) && isset($data->pageProps->article->body)) {
            $relatedPosts = array();
            if (isset($data->pageProps->article->related) && isset($data->pageProps->article->related->relatedArticles)) {
                $relatedPosts = array_map(function ($item) {
                    return $item->id;
                }, $data->pageProps->article->related->relatedArticles);
            }
            $body = $data->pageProps->article->body;
            $status = $this->checkBody($body) ? "publish" : "review-request";
            $newsAssetPath = "../assets/news/" . $post["id"];
            self::crawImage($post["imageUrl"], $newsAssetPath, "image");
            $meta = $data->pageProps->layoutContext->meta;
            FootballNews::where("id", $post["id"])
                ->update([
                    "meta" => json_encode($meta),
                    "body" => json_encode($body),
                    "status" => $status,
                    "related_posts" => json_encode($relatedPosts)
                ]);
            $result = "SUCCESS - " . $post["id"];
            $bodyArray = array();
            foreach ($body as $b) {
                if (isset($b->type) && $b->type == "image") {
                    $b->data->type = "image";
                    $b->data->image = self::crawImage($b->image->article->url, $newsAssetPath, md5($b->image->article->url));
                }
                array_push($bodyArray, $b);
            }
            FootballNews::where("id", $post["id"])
                ->update([
                    "body" => json_encode($body)
                ]);
        } else {
            FootballNews::where("id", $post["id"])
                ->update([
                    "updatedTime" => $post["updatedTime"] + 1
                ]);
            $result = "FAIL - " . $post["id"];
        }
        return $result;
    }

    private function checkBody($body)
    {
        if ($body == null) {
            return false;
        }
        foreach ($body as $tag) {
            $data = $tag->data;
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
            ->where("updatedTime", "<", "5")
            ->orderBy("updatedTime", "ASC")
            ->take(30)
            ->get()
            ->toArray();
    }

    private static function getImageExt($url)
    {
        $result = ".jpg";
        $strs = explode(".", explode("?", $url)[0]);
        if (count($strs) > 1) {
            $result = "." . end($strs);
        }
        return $result;
    }

    public static function crawImage($url, $folder, $fileName)
    {
        $name = $fileName;
        if ($fileName == null) {
            return "";
        }
        // Kiểm tra và tạo thư mục nếu chưa tồn tại
        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0777, true, true);
        }
        $name = $name . self::getImageExt($url);
        $contents = Controller::getData($url);
        if (strlen($contents) > 10000) {
            file_put_contents($folder . "/" . $name, $contents);
            if ($fileName != null) {
                return $contents;
            }
            return $name;
        }
        return "";
    }
}
