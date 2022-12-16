<?php

namespace Trinityrank\Sitemap;

use Illuminate\Support\Str;

class MakeSitemap
{
    protected static $index;
    protected static $files = [];
    protected static $latestNews = [];
    protected static $sumCategories = [];

    public static function generate($that, $config)
    {
        $lang_default = config('app.locale') ?? 'us';
        $languages = config('app.locales') ?? [$lang_default];

        // For each Language create separate sitemap
        foreach ($languages as $language) {
            self::$files = [];
            self::$latestNews = [];

            // Generate lang sufix in path name
            $lang = ($lang_default == $language) ? '' : '/' . $language;

            // For each Model, make separate sitemap
            foreach ($config as $item) {
                // If multiple models need to be in same sitemap
                $merge = $item['merge'] ?? false;

                // Init sitemap creation
                if ($merge === false || $merge === '--first') {
                    //$sitemap = Sitemap::create();

                    $sitemap = "<?xml version='1.0' encoding='UTF-8'?> \n" .
                    "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xhtml='http://www.w3.org/1999/xhtml' xmlns:image='http://www.google.com/schemas/sitemap-image/1.1'> \n";

                    self::$index = 0;
                }

                // List all posts and pages from given model class
                $sitemapItems = $item['model']::status()->language($language)->publishDate()->get()->map(function ($post, $key) use ($language, $lang, $item) {
                    // Get category (if exists)
                    $category = $post->categories ? $post->categories->first()->slug : '';

                    // creation of parent category
                    if (method_exists($post, 'parent')) {
                        $parentCategory = $post->parent()->exists() ? $post->parent->slug : null;
                    } elseif (method_exists($post, 'categories')) {
                        if (method_exists($post->categories->first(), 'parent')) {
                            $parentCategory = $post->categories->first()->parent ? $post->categories->first()->parent->slug : null;
                        } else {
                            $parentCategory = null;
                        }
                    } else {
                        $parentCategory = null;
                    }

                    // here we correct home page link
                    $slug = ($post->slug == '/') ? '' : $post->slug;
                    // Define complete slug
                    $item_slug = str_replace(['$lang', '$parentCategory', '$category', '$slug'], [$lang, $parentCategory, $category, $slug], $item['slug']);

                    // is Parent Category visible for sitemap
                    $parentShow = isset($item['parent-show']) && $item['parent-show'] === true ? true : false;

                    // sitemap init
                    $postSitemap = '';

                    // add category index sitemap from manual
                    if ($key === 0 && isset($item['manual'])) {
                        foreach ($item['manual'] as $manual) {
                            if (Str::between($manual, '/', '/') == Str::after($manual, '/') && $lang == '') {
                                $postSitemap .= "\t <url> \n";
                                $postSitemap .= "\t \t <loc>" . Str::beforeLast(route('home'), '/') . $manual . '/' . "</loc> \n";
                                $postSitemap .= "\t \t <lastmod>" . now()->toW3cString() . "</lastmod> \n";
                                $postSitemap .= "\t \t <priority>0.8</priority> \n";
                                $postSitemap .= "\t </url> \n";
                            } elseif (Str::between($manual, '/', '/') === Str::after($lang, '/')) {
                                $postSitemap .= "\t <url> \n";
                                $postSitemap .= "\t \t <loc>" . Str::beforeLast(route('home'), '/') . $manual . '/' . "</loc> \n";
                                $postSitemap .= "\t \t <lastmod>" . now()->toW3cString() . "</lastmod> \n";
                                $postSitemap .= "\t \t <priority>0.8</priority> \n";
                                $postSitemap .= "\t </url> \n";
                            }
                            continue;
                        }
                    }

                    // ignore parent categories to not show
                    if ($post->getTable() == 'categories') {
                        if (method_exists($post, 'children')) {
                            if (\App\Categories\Category::find($post->id)->children()->exists() && !$parentShow) {
                                return 'ignore';
                            }
                        }
                    };

                    // ignore users which doesn't have posts
                    if ($post->getTable() == 'users') {
                        if (method_exists($post, 'pages') && method_exists($post, 'articles')) {
                            if ($post->pages()->language($language)->count() == 0 && $post->articles()->language($language)->count() == 0) {
                                return 'ignore';
                            }
                        }
                    };

                    // clear slug
                    if (Str::contains($item_slug, '//')) {
                        $item_slug = Str::before($item_slug, '//') . '/' . Str::after($item_slug, '//');
                    }
                    if ($item_slug === '/') {
                        $item_slug = '';
                    } elseif ($item_slug === $lang . '/') {
                        $item_slug = $lang;
                    }

                    // adding sitemap items
                    $postSitemap = $postSitemap;
                    $postSitemap .= "\t <url> \n";
                    $postSitemap .= "\t \t <loc>" . Str::beforeLast(route('home'), '/') . $item_slug . '/' . "</loc> \n";
                    $postSitemap .= "\t \t <lastmod>" . $post->updated_at->toW3cString() . "</lastmod> \n";
                    $postSitemap .= "\t \t <priority>0.8</priority> \n";
                    $postSitemap .= "\t </url> \n";

                    // increase the index
                    self::$index++;

                    // adding items to news sitemap
                    if ($post->type === "App\Articles\Types\News"
                        &&
                        $post->created_at->diffInDays(now()) < 2
                    ) {
                        $post->sluggish = $item_slug;
                        array_push(self::$latestNews, $post);
                    }

                    return $postSitemap;
                })->filter(function ($value) {
                    if ($value !== 'ignore') {
                        return $value;
                    }
                });

                if (self::$index > 0) {
                    // concat category items in one file
                    if ($merge === '--first' || $merge === '--next') {
                        self::$sumCategories = collect(self::$sumCategories)->concat($sitemapItems);

                        continue;
                    }

                    // close category items in one file
                    if ($merge === '--last') {
                        self::$sumCategories = collect(self::$sumCategories)->concat($sitemapItems);

                        $sitemapItems = self::$sumCategories;
                        self::$sumCategories = [];
                    }

                    // join to array
                    $sitemapItems = implode('', $sitemapItems->toArray());

                    // finish sitemap file
                    $sitemap = $sitemap . $sitemapItems . '</urlset>' ;

                    // Control line - write sitemaps in terminal
                    $that->info($lang . '/sitemap/' . $item['sitemap-name'] . '_sitemap.xml');

                    // create dir for sitemap
                    if (!file_exists(public_path($lang . '/sitemap/'))) {
                        mkdir(public_path($lang . '/sitemap/'), 0777, true);
                    }

                    // put files
                    file_put_contents(public_path($lang . '/sitemap/' . $item['sitemap-name'] . '_sitemap.xml'), $sitemap);

                    // create index sitemap
                    array_push(self::$files, $lang . '/sitemap/' . $item['sitemap-name'] . '_sitemap.xml');
                }
            }

            $filterManual = collect($config)->filter(function ($value, $key) {
                return array_key_exists('manual', $value);
            })->flatten()->toArray();

            if (in_array($lang . '/' . 'news', $filterManual)) {
                self::createNewsSitemap(self::$latestNews, $lang);
            }

            self::createIndexSitemap(self::$files, $lang);
        }
    }

    public static function test($config)
    {
        $lang_default = config('app.locale') ?? 'us';
        $languages = config('app.locales') ?? [$lang_default];

        $langsUrlCollection = collect($languages)->map(function ($language) use ($config, $lang_default) {
            $lang = ($lang_default == $language) ? '' : '/' . $language;

            $urls = collect();

            foreach ($config as $item) {
                $urlResolver = $item['model']::status()->language($language)->publishDate()->get()->map(function ($post) use ($lang, $item) {
                    $category = $post->categories ? $post->categories->first()->slug : '';
                    $slug = ($post->slug == '/') ? '' : $post->slug;
                    $item_slug = str_replace(['$lang', '$category', '$slug'], [$lang, $category, $slug], $item['slug']);

                    return Str::beforeLast(route('home'), '/') . ($item_slug);
                });

                $urls = $urls->concat($urlResolver);
            }

            return $urls;
        });

        return $langsUrlCollection;
    }

    private static function createNewsSitemap($newsList, $lang)
    {
        $sitemapNews = "<?xml version='1.0' encoding='UTF-8'?> \n" .
        "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9' 
                 xmlns:news='http://www.google.com/schemas/sitemap-news/0.9'> \n";

        foreach ($newsList as $news) {
            $sitemapNews .= "\t <url>\n" .
                            "\t \t <loc>" . Str::beforeLast(route('home'), '/') . $news->sluggish . '/' . "</loc>\n" .
                            "\t \t <news:news> \n" .
                            "\t \t <news:publication> \n" .
                            "\t \t \t <news:name>" . $news->title . "</news:name> \n" .
                            "\t \t \t <news:language>" . $news->multilang_language . "</news:language> \n" .
                            "\t \t </news:publication> \n" .
                            "\t \t <news:publication_date>" . $news->created_at->toW3cString() . "</news:publication_date> \n" .
                            "\t \t \t <news:title>" . $news->title . "</news:title>\n" .
                            "\t \t </news:news> \n" .
                            "\t </url> \n";
        }

        $sitemapNews .= '</urlset>';

        // create dir for news sitemap
        if (!file_exists(public_path($lang . '/news/'))) {
            mkdir(public_path($lang . '/news/'), 0777, true);
        }

        file_put_contents(public_path($lang . '/news/sitemap.xml'), $sitemapNews);
    }

    private static function createIndexSitemap($files, $lang)
    {
        $sitemapIndex = "<?xml version='1.0' encoding='UTF-8'?> \n" .
        "<?xml-stylesheet type='text/xsl' href='/sitemap-style.xsl'?> \n" .
        "<sitemapindex xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'> \n";

        foreach ($files as $file) {
            $sitemapIndex .= "\t <sitemap> \n";
            $sitemapIndex .= "\t \t <loc>" . Str::beforeLast(route('home'), '/') . $file . "</loc> \n";
            $sitemapIndex .= "\t \t <lastmod>" . now()->toW3cString() . "</lastmod> \n";
            $sitemapIndex .= "\t </sitemap> \n";
        }

        $sitemapIndex .= '</sitemapindex>';

        file_put_contents(public_path($lang . '/sitemap.xml'), $sitemapIndex);
    }
}
