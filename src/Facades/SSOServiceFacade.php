<?php

namespace MyController\SSo\Facades;

use Illuminate\Support\Facades\Facade;

class SSOServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sso.service';
    }
}
