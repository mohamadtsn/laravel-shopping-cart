<?php

namespace Mohamadtsn\ShoppingCart;

use Illuminate\Database\Eloquent\Model;
use Mohamadtsn\ShoppingCart\Contracts\CartItemAbstract;

/**
 * @property Model|null $model
 * @property array $config
 * @property CartCondition|array $conditions
 * @property-read string id
 * @property string name
 * @property int quantity
 * @property float price
 * @property ItemAttributeCollection attributes
 */
class ItemCollection extends CartItemAbstract
{
    protected $config;

    /**
     * ItemCollection constructor.
     * @param array|mixed $items
     * @param array $config
     */
    public function __construct($items, array $config = [])
    {
        parent::__construct($items);

        $this->config = $config;
    }

    public function getPriceSum(bool $formatted = true)
    {
        return formatValue($this->price * $this->quantity, $formatted, $this->config);
    }

    public function __get($key)
    {
        if ($key === 'model' || $this->has($key)) {
            return !is_null($this->get($key)) ? $this->get($key) : $this->getAssociatedModel();
        }
        return parent::__get($key);
    }

    protected function getAssociatedModel()
    {
        if (!$this->has('associatedModel')) {
            return null;
        }

        $associatedModel = $this->get('associatedModel');

        return \app($this->get('instance_name'))->getModelFromCache($associatedModel, $this->get('id'));
    }

    /**
     * check if item has conditions
     *
     * @return array|CartCondition
     */
    public function getConditions()
    {
        return !$this->hasConditions() ? [] : $this->conditions;
    }

    /**
     * check if item has conditions
     *
     * @return bool
     */
    public function hasConditions(): bool
    {
        if (!isset($this['conditions'])) {
            return false;
        }
        if (is_array($this['conditions'])) {
            return count($this['conditions']) > 0;
        }

        return $this['conditions'] instanceof (CartCondition::class);
    }

    /**
     * get the sum of price in which conditions are already applied
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceSumWithConditions(bool $formatted = true)
    {
        return formatValue($this->getPriceWithConditions(false) * $this->quantity, $formatted, $this->config);
    }

    /**
     * get the single price in which conditions are already applied
     * @param bool $formatted
     * @return mixed|null
     */
    public function getPriceWithConditions(bool $formatted = true)
    {
        $originalPrice = $this->price;
        $newPrice = 0.00;
        $processed = 0;

        if ($this->hasConditions()) {
            if (is_array($this->conditions)) {
                foreach ($this->conditions as $condition) {
                    ($processed > 0) ? $toBeCalculated = $newPrice : $toBeCalculated = $originalPrice;
                    $newPrice = $condition->applyCondition($toBeCalculated);
                    $processed++;
                }
            } else {
                $newPrice = $this->conditions->applyCondition($originalPrice);
            }

            return formatValue($newPrice, $formatted, $this->config);
        }
        return formatValue($originalPrice, $formatted, $this->config);
    }
}
