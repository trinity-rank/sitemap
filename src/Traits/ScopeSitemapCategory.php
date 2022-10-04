<?php

namespace Trinityrank\Sitemap\Traits;

trait ScopeSitemapCategory
{

    public function scopePublishDate($query)
    {
        return $query;
    }

    public function scopeStatus($query)
    {
        return $query;
    }


}
