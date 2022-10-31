<?php

namespace Trinityrank\Sitemap;

use Illuminate\Support\ServiceProvider;

class SitemapServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ ."/Commands/GenerateSitemap.php" =>
                'app/Console/Commands/GenerateSitemap.php',
                __DIR__ ."/Providers/MacrosServiceProvider.php" =>
                'app/Providers/MacrosServiceProvider.php',
                __DIR__ ."/Assets/sitemap-style.xsl" =>
                'public/sitemap-style.xsl',
            ], "generate-sitemap-files");
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
