<?php
namespace Greenpath\GuzzleKrac;

use Illuminate\Support\Facades\Facade;

class GuzzleKracFacade extends Facade{
    protected static function getFacadeAccessor() { return 'guzzlekrac'; }
}