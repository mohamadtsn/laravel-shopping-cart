<?php

namespace Tests\Helpers;

use Mohamadtsn\ShoppingCart\ItemCollection;

class CustomItemCollection extends ItemCollection
{
    public function __construct($items, array $config = [])
    {
        parent::__construct($items, $config);
        $this->setEagerLoadRelationModel([
            'photos',
        ]);
    }
}