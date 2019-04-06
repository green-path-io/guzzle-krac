<?php
namespace Greenpath\GuzzleKrac\Facades;

use Illuminate\Support\Facades\Facade;

class GuzzleKrac extends Facade{
    protected static function getFacadeAccessor() { return 'guzzlekrac'; }
}