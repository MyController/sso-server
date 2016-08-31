<?php

namespace MyController\SSOServer\Facades;

use Illuminate\Support\Facades\Facade;

class SSOServerFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'MyController\SSOServer\Providers\SSOServer';
    }
}
