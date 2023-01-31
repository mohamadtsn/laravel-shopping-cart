<?php namespace Mohamadtsn\ShoppingCart\Facades;

use Illuminate\Support\Facades\Facade;

class CartFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'cart';
    }
}