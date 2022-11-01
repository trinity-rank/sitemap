<?php

namespace App\Providers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class MacrosServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Builder::macro('language', function ($lang) {
            $this->where(function (Builder $query) use ($lang) {
                $lang = $lang ?? config('app.locale');

                if (!Schema::hasColumn($this->from, 'multilang_language')) {
                    return $query;
                }

                return $query
                        ->where('multilang_language', $lang)
                        ->orWhere('multilang_language', null);
            });
        });

        Builder::macro('status', function () {
            $this->where(function (Builder $query) {
                if ($this->from === 'users') {
                    return $query->where('show_author', 1);
                }

                if (!Schema::hasColumn($this->from, 'status')) {
                    return $query;
                }

                return $query->where('status', 1);
            });
        });

        Builder::macro('publishDate', function () {
            $this->where(function (Builder $query) {
                if (!Schema::hasColumn($this->from, 'publish_at')) {
                    return $query;
                }

                return $query->where('publish_at', '<', Carbon::now()->toDateTimeString());
            });
        });
    }
}
