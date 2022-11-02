<?php

namespace App\Console\Commands;

use App\Articles\Types\Blog;
use App\Articles\Types\News;
use App\Categories\Types\BlogCategory;
use App\Categories\Types\NewsCategory;
use App\Categories\Types\MoneyPageCategory;
use App\Categories\Types\ReviewPageCategory;
use App\Models\User;
use App\Pages\Types\ReviewPage;
use App\Pages\Types\MoneyPage;
use App\Pages\Types\StaticPage;
use Illuminate\Console\Command;
use Trinityrank\Sitemap\MakeSitemap;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate the website sitemap.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        /*
            |--------------------------------------------------------------------------
            | Url Properties
            |--------------------------------------------------------------------------
            | $lang --- default value
            | $category --- use only for single pages
            | $parentCategory
            | $slug
            */

        /*
           |--------------------------------------------------------------------------
           | Keys Configuration
           |--------------------------------------------------------------------------
           |
           * All Items:
           | 'model',
           | 'slug',
           | 'sitemap-name',
           * Only for categories:
           | 'merge' ,
           | 'manual',
           | 'parent-show'
           */

        MakeSitemap::generate($this, [
            [
                'model' => Blog::class,
                'slug' => '$lang/blog/$slug',
                'sitemap-name' => 'blog'
            ],
            [
                'model' => News::class,
                'slug' => '$lang/news/$category/$slug',
                'sitemap-name' => 'news'
            ],
            [
                'model' => ReviewPage::class,
                'slug' => '$lang/reviews/$slug',
                'sitemap-name' => 'review'
            ],
            [
                'model' => MoneyPage::class,
                'slug' => '$lang/best/$slug',
                'sitemap-name' => 'product'
            ],
            [
                'model' => StaticPage::class,
                'slug' => '$lang/$slug',
                'sitemap-name' => 'page'
            ],
            [
                'model' => User::class,
                'slug' => '$lang/author/$slug',
                'sitemap-name' => 'author'
            ],
            [
                'model' => BlogCategory::class,
                'slug' => '$lang/$slug',
                'sitemap-name' => 'category',
                'merge' => '--first',
                'manual' => ['blog', 'news', 'reviews']
            ],
            [
                'model' => NewsCategory::class,
                'slug' => '$lang/$slug',
                'sitemap-name' => 'category',
                'merge' => '--next',
            ],
            [
                'model' => ReviewPageCategory::class,
                'slug' => '$lang/$slug',
                'sitemap-name' => 'category',
                'merge' => '--next',
            ],
            [
                'model' => MoneyPageCategory::class,
                'slug' => '$lang/$slug',
                'sitemap-name' => 'category',
                'merge' => '--last',
                //'parent-show' => true
            ],
        ]);
    }
}
