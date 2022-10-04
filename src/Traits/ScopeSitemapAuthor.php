<?php

namespace Trinityrank\Sitemap\Traits;

trait ScopeSitemapAuthor
{

    public function scopePublishDate($query)
    {
        return $query;
    }

    public function scopeStatus($query)
    {
        return $query->where("show_author", 1);
    }

    public function scopeLanguage($query)
    {
        return $query;
    }

}
