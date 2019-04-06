<?php

namespace Greenpath\GuzzleKrac;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;

class GuzzleKracServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('guzzlekrac', function()
        {
            return new GuzzleKrac\GuzzleKrac();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
