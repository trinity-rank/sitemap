<?php

use Illuminate\Support\Facades\Route;
use Trinityrank\Sitemap\Http\Controllers\NewsSitemapController;

Route::get('news/sitemap-news.xml', NewsSitemapController::class);
