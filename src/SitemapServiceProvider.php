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

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
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
