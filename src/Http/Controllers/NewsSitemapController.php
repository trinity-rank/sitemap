<?php

namespace Trinityrank\Sitemap\Http\Controllers;

use App\Http\Controllers\BaseController;

class NewsSitemapController extends BaseController
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        if (!file_exists('sitemap-news.xml')) {
            abort(404);
        }

        return response(file_get_contents(public_path('/sitemap-news.xml')), 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
}
