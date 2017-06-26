<?php

namespace Antcern\Paysera\Facades;

use Illuminate\Support\Facades\Facade;

class Paysera extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'paysera';
    }
}
