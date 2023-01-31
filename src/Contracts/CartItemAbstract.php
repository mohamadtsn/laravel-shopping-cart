<?php

namespace Mohamadtsn\ShoppingCart\Contracts;

use Illuminate\Support\Collection;

abstract class CartItemAbstract extends Collection
{
    protected $config;

    abstract public function getPriceSum(bool $formatted);

    abstract public function getPriceSumWithConditions(bool $formatted);

    abstract public function getPriceWithConditions(bool $formatted);
}