<?php

namespace Trinityrank\Sitemap;

use Illuminate\Support\Str;

class MakeSitemap
{
    protected static $index;
    protected static $files = [];
    protected static $latestNews = [];

    public static function generate($that, $config)
    {
        $lang_default = config('app.locale') ?? "us";
        $languages = config('app.locales') ?? [$lang_default];

        // For each Language create separate sitemap
        foreach ($languages as $language) {
            // Generate lang sufix in path name
            $lang = ($lang_default == $language) ? '' : '/' . $language;

            // For each Model, make separate sitemap
            foreach ($config as $item) {
                // If multiple models need to be in same sitemap
                $merge = $item['merge'] ?? false;

                // Init sitemap creation
                if ($merge === false || $merge === "--first") {
                    //$sitemap = Sitemap::create();

                    $sitemap = "<?xml version='1.0' encoding='UTF-8'?> \n" .
                    "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xhtml='http://www.w3.org/1999/xhtml' xmlns:image='http://www.google.com/schemas/sitemap-image/1.1'> \n";

                    self::$index = 0;
                }

                // List all posts and pages from given model class
                $sitemapItems= $item['model']::status()->language($language)->publishDate()->get()->map(function ($post, $key) use ($sitemap, $lang, $item) {
                    // Get category (if exists)
                    $category = $post->categories ? $post->categories->first()->slug : '';
                    // here we correct home page link
                    $slug = ($post->slug == "/") ? "" : $post->slug;
                    // Define complete slug
                    $item_slug = str_replace(['$lang', '$category', '$slug'], [$lang, $category, $slug], $item['slug']);

                    $postSitemap = "\t <url> \n";
                    $postSitemap  .= "\t \t <loc>". Str::beforeLast(route('home'), '/') . $item_slug . "</loc> \n";
                    $postSitemap  .= "\t \t <lastmod>" . $post->updated_at->toW3cString() . "</lastmod> \n";
                    $postSitemap  .= "\t \t <priority>0.8</priority> \n";
                    $postSitemap  .= "\t </url> \n";

                    // Increment index
                    self::$index++;

                    if ($post->type === "App\Articles\Types\News"
                        &&
                        $post->updated_at->diffInDays(now()) < 2) {
                        array_push(self::$latestNews, $post);
                    }

                    return $postSitemap;
                });

                $sitemapItems = implode('', $sitemapItems->toArray());

                if (self::$index > 0) {
                    // Continue loop if multiple Models need to be inside one sitemap
                    if ($merge === "--first" || $merge === "--next") {
                        continue;
                    }

                    $sitemap = $sitemap . $sitemapItems . '</urlset>' ;

                    // Control line - write sitemaps in terminal
                    $that->info($lang . "/sitemap/". $item['sitemap-name'] ."_sitemap.xml");

                    if (!file_exists(public_path($lang . '/sitemap/'))) {
                        mkdir(public_path($lang . '/sitemap/'), 0777, true);
                    }
                    file_put_contents(public_path($lang . '/sitemap/'. $item['sitemap-name'] .'_sitemap.xml'), $sitemap);

                    array_push(self::$files, $lang . '/sitemap/'. $item['sitemap-name'] .'_sitemap.xml');
                }
            }
        }

        self::createNewsSitemap(self::$latestNews);
        self::createIndexSitemap(self::$files);
    }

    public static function test($config)
    {
        $lang_default = config('app.locale') ?? "us";
        $languages = config('app.locales') ?? [$lang_default];

        $langsUrlCollection = collect($languages)->map(function ($language) use ($config, $lang_default) {
            $lang = ($lang_default == $language) ? '' : '/' . $language;

            $urls = collect();

            foreach ($config as $item) {
                $urlResolver = $item['model']::status()->language($language)->publishDate()->get()->map(function ($post) use ($lang, $item) {
                    $category = $post->categories ? $post->categories->first()->slug : '';
                    $slug = ($post->slug == "/") ? "" : $post->slug;
                    $item_slug = str_replace(['$lang', '$category', '$slug'], [$lang, $category, $slug], $item['slug']);

                    return Str::beforeLast(route('home'), '/') . ($item_slug);
                });

                $urls = $urls->concat($urlResolver);
            }

            return $urls;
        });

        return $langsUrlCollection;
    }

    private static function createNewsSitemap($newsList)
    {
        $sitemapNews = "<?xml version='1.0' encoding='UTF-8'?> \n" .
        "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' 
                 xmlns:news='http://www.google.com/schemas/sitemap-news/0.9'> \n";

        foreach ($newsList as $news) {
            $sitemapNews .= "\t <url>\n" .
                            "\t \t <loc>". Str::beforeLast(route('home'), '/') . $news->slug . "</loc>\n" .
                            "\t \t <news:news> \n" .
                            "\t \t <news:publication> \n" .
                            "\t \t \t <news:name>" . $news->title . "</news:name> \n" .
                            "\t \t \t <news:language>" . $news->multilang_language . "</news:language> \n" .
                            "\t \t </news:publication> \n" .
                            "\t \t <news:publication_date>" . $news->created_at->toW3cString() . "</news:publication_date> \n" .
                            "\t \t \t <news:title>" . $news->title . "</news:title>\n" .
                            "\t \t </news:news> \n".
                            "\t </url> \n";
        }

        $sitemapNews .= "</urlset>";

        file_put_contents(public_path('sitemap-news.xml'), $sitemapNews);
    }

    private static function createIndexSitemap($files)
    {
        $sitemapIndex = "<?xml version='1.0' encoding='UTF-8'?> \n" .
        "<?xml-stylesheet type='text/xsl' href='/sitemap-style.xsl'?> \n" .
        "<sitemapindex xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'> \n";

        foreach ($files as $file) {
            $sitemapIndex .= "\t <sitemap> \n";
            $sitemapIndex  .= "\t \t <loc>". Str::beforeLast(route('home'), '/') . $file . "</loc> \n";
            $sitemapIndex  .= "\t \t <lastmod>" . now()->toW3cString() . "</lastmod> \n";
            $sitemapIndex  .= "\t </sitemap> \n";
        }

        $sitemapIndex  .= '</sitemapindex>';

        file_put_contents(public_path('sitemap.xml'), $sitemapIndex);
    }
}
