<?php

namespace Mohamadtsn\ShoppingCart;

use Illuminate\Support\Collection;

class ItemAttributeCollection extends Collection
{
    public function __get($key)
    {
        return $this->get($key);
    }
}