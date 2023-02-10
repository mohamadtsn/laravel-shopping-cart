<?php

namespace Mohamadtsn\ShoppingCart\Contracts;

use Illuminate\Support\Collection;

abstract class CartItemAbstract extends Collection
{
    protected $config;

    protected array $eagerLoadRelationModel = [];

    /**
     * @param array $eagerLoadRelationModel
     */
    public function setEagerLoadRelationModel(array $eagerLoadRelationModel): void
    {
        $this->eagerLoadRelationModel = $eagerLoadRelationModel;
    }

    /**
     * @return array
     */
    public function getEagerLoadRelationModel(): array
    {
        return $this->eagerLoadRelationModel;
    }

    abstract public function getPriceSum(bool $formatted);

    abstract public function getPriceSumWithConditions(bool $formatted);

    abstract public function getPriceWithConditions(bool $formatted);
}