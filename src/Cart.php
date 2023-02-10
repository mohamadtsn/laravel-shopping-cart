<?php

namespace Mohamadtsn\ShoppingCart;

use Illuminate\Database\Eloquent\Model;
use Mohamadtsn\ShoppingCart\CartCondition;
use Mohamadtsn\ShoppingCart\Contracts\CartItemAbstract;
use Mohamadtsn\ShoppingCart\Exceptions\InvalidConditionException;
use Mohamadtsn\ShoppingCart\Exceptions\InvalidItemException;
use Mohamadtsn\ShoppingCart\Exceptions\UnknownModelException;
use Mohamadtsn\ShoppingCart\ItemCollection;
use Mohamadtsn\ShoppingCart\Validators\CartItemValidator;
use Mohamadtsn\ShoppingCart\CartCollection;
use Mohamadtsn\ShoppingCart\CartConditionCollection;
use Mohamadtsn\ShoppingCart\ItemAttributeCollection;
use Tests\Helpers\CustomItemCollection;

/**
 * Class Cart
 * @package Mohamadtsn\ShoppingCart
 */
class Cart
{

    /**
     * the item storage
     *
     * @var
     */
    protected $session;

    /**
     * the event dispatcher
     *
     * @var
     */
    protected $events;

    /**
     * the cart session key
     *
     * @var
     */
    protected $instanceName;

    /**
     * the session key use for the cart
     *
     * @var
     */
    protected $sessionKey;

    /**
     * the session key use to persist cart items
     *
     * @var
     */
    protected $sessionKeyCartItems;

    /**
     * the session key use to persist cart conditions
     *
     * @var
     */
    protected $sessionKeyCartConditions;

    /**
     * Configuration to pass to ItemCollection
     *
     * @var
     */
    protected $config;

    /**
     * This holds the currently added item id in cart for association
     *
     * @var CartItemAbstract|null
     */
    protected $currentItemId;

    /**
     * This holds the cart items item in cart for association
     *
     * @var CartCollection|CartItemAbstract[]|null
     */
    public $cartItems;

    /**
     * This holds the cart conditions item in cart for association
     *
     * @var CartConditionCollection|CartCondition[]|null
     */
    protected $cartConditions;

    /**
     * This holds the cart conditions item in cart for association
     *
     * @var string
     */
    protected $itemClass;

    /**
     * This holds the cart conditions item in cart for association
     *
     * @var array
     */
    public array $cacheModels;

    /**
     * This holds the cart conditions item in cart for association
     *
     * @var bool
     */
    public bool $endSaveAllItems = true;

    /**
     * our object constructor
     *
     * @param $session
     * @param $events
     * @param $instanceName
     * @param $session_key
     * @param $config
     */
    public function __construct($session, $events, $instanceName, $session_key, $config)
    {
        $this->events = $events;
        $this->session = $session;
        $this->instanceName = $instanceName;
        $this->sessionKey = $session_key;
        $this->sessionKeyCartItems = $this->sessionKey . '_cart_items';
        $this->sessionKeyCartConditions = $this->sessionKey . '_cart_conditions';
        $this->config = $config;
        $this->currentItemId = null;
        $this->itemClass = $config['item_class'] ?? ItemCollection::class;
        $this->fireEvent('created');
    }

    /**
     * @param $name
     * @param mixed $value
     * @return mixed
     */
    protected function fireEvent($name, $value = [])
    {
        return $this->events->dispatch($this->getInstanceName() . '.' . $name, array_values([$value, $this]), true);
    }

    /**
     * get instance name of the cart
     *
     * @return string
     */
    public function getInstanceName(): string
    {
        return $this->instanceName;
    }

    /**
     * sets the session key
     *
     * @param string $sessionKey the session key or identifier
     * @return $this
     * @throws \Exception
     */
    public function session(string $sessionKey): self
    {
        if (!$sessionKey) {
            throw new \RuntimeException('Session key is required.');
        }

        $this->sessionKey = $sessionKey;
        $this->sessionKeyCartItems = $this->sessionKey . '_cart_items';
        $this->sessionKeyCartConditions = $this->sessionKey . '_cart_conditions';

        $this->setCartItems();
        $this->setCartConditions();

        $this->initializeCacheItemModel();

        return $this;
    }

    /**
     * add item to the cart, it can be an array or multidimensional array
     *
     * @param string|array $id
     * @param string $name
     * @param float $price
     * @param int $quantity
     * @param array $attributes
     * @param CartCondition|array $conditions
     * @param string $associatedModel
     * @return $this
     * @throws InvalidItemException
     */
    public function add($id, $name = null, $price = null, $quantity = null, $attributes = [], $conditions = [], $associatedModel = null)
    {
        // if the first argument is an array,
        // we will need to call add again
        if (is_array($id)) {
            // the first argument is an array, now we will need to check if it is a multidimensional
            // array, if so, we will iterate through each item and call add again
            if (isMultiArray($id)) {
                $this->endSaveAllItems = false;
                foreach ($id as $item) {
                    $this->add(
                        $item['id'],
                        $item['name'],
                        $item['price'],
                        $item['quantity'],
                        issetAndHasValueOrAssignDefault($item['attributes'], []),
                        issetAndHasValueOrAssignDefault($item['conditions'], []),
                        issetAndHasValueOrAssignDefault($item['associatedModel'], null)
                    );
                }
                $this->endSaveAllItems = true;
                $this->resetCacheItemModel();
            } else {
                $this->add(
                    $id['id'],
                    $id['name'],
                    $id['price'],
                    $id['quantity'],
                    issetAndHasValueOrAssignDefault($id['attributes'], []),
                    issetAndHasValueOrAssignDefault($id['conditions'], []),
                    issetAndHasValueOrAssignDefault($id['associatedModel'], null)
                );
            }

            return $this;
        }

        $data = [
            'id' => $id,
            'name' => $name,
            'price' => normalizePrice($price),
            'quantity' => $quantity,
            'attributes' => new ItemAttributeCollection($attributes),
            'conditions' => $conditions,
            'instance_name' => $this->getInstanceName(),
        ];

        if (!empty($associatedModel)) {
            $data['associatedModel'] = $associatedModel;
        }

        // validate data
        $item = $this->validate($data);

        // get the cart
        $cart = $this->getContent();

        // if the item is already in the cart we will just update it
        if ($cart->has($id)) {

            $this->update($id, $item);
        } else {

            $this->addRow($id, $item);
        }

        $this->currentItemId = $id;

        return $this;
    }

    /**
     * validate Item data
     *
     * @param $item
     * @return array $item;
     * @throws InvalidItemException
     */
    protected function validate($item)
    {
        $rules = [
            'id' => 'required',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric|min:0.1',
            'name' => 'required',
        ];

        $validator = CartItemValidator::make($item, $rules);

        if ($validator->fails()) {
            throw new InvalidItemException($validator->messages()->first());
        }

        return $item;
    }

    /**
     * check if an item exists by item ID
     *
     * @param $itemId
     * @return bool
     */
    public function has($itemId)
    {
        return $this->getContent()->has($itemId);
    }

    /**
     * update a cart
     *
     * @param $id
     * @param array $data
     *
     * the $data will be an associative array, you don't need to pass all the data, only the key value
     * of the item you want to update on it
     * @return bool
     */
    public function update($id, array $data)
    {
        if ($this->fireEvent('updating', $data) === false) {
            return false;
        }

        $cart = $this->getContent();

        $item = $cart->pull($id);

        foreach ($data as $key => $value) {
            // if the key is currently "quantity" we will need to check if an arithmetic
            // symbol is present, so we can decide if the update of quantity is being added
            // or being reduced.
            if ($key === 'quantity') {
                // we will check if quantity value provided is array,
                // if it is, we will need to check if a key "relative" is set,
                // and we will evaluate its value if true or false,
                // this tells us how to treat the quantity value if it should be updated
                // relatively to its current quantity value or just totally replace the value
                if (is_array($value)) {
                    if (isset($value['relative'])) {
                        if ((bool)$value['relative']) {
                            $item = $this->updateQuantityRelative($item, $key, $value['value']);
                        } else {
                            $item = $this->updateQuantityNotRelative($item, $key, $value['value']);
                        }
                    }
                } else {
                    $item = $this->updateQuantityRelative($item, $key, $value);
                }
            } else if ($key === 'attributes') {
                if (($attributes = $item[$key]) instanceof ItemAttributeCollection) {
                    $item[$key] = $attributes->merge($value);
                } else {
                    $item[$key] = new ItemAttributeCollection($value);
                }
            } else {
                $item[$key] = $value;
            }
        }

        $cart->put($id, $item);

        $this->save($cart);

        $this->fireEvent('updated', $item);
        return true;
    }

    /**
     * update a cart item quantity relative to its current quantity
     *
     * @param $item
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function updateQuantityRelative($item, $key, $value)
    {
        if (preg_match('/\-/', $value) === 1) {
            $value = (int)str_replace('-', '', $value);

            // we will not allow to reduced quantity to 0, so if the given value
            // would result to item quantity of 0, we will not do it.
            if (($item[$key] - $value) > 0) {
                $item[$key] -= $value;
            }
        } else if (false !== strpos($value, '+')) {
            $item[$key] += (int)str_replace('+', '', $value);
        } else {
            $item[$key] += (int)$value;
        }

        return $item;
    }

    /**
     * update cart item quantity not relative to its current quantity value
     *
     * @param $item
     * @param $key
     * @param $value
     * @return mixed
     */
    protected function updateQuantityNotRelative($item, $key, $value)
    {
        $item[$key] = (int)$value;

        return $item;
    }

    /**
     * save the cart
     *
     * @param $cart CartCollection
     */
    protected function save(CartCollection $cart)
    {
        $this->session->put($this->sessionKeyCartItems, $cart);
        $this->setCartItems($cart);
    }

    /**
     * add row to cart collection
     *
     * @param $id
     * @param $item
     * @return bool
     */
    protected function addRow($id, $item)
    {
        if ($this->fireEvent('adding', $item) === false) {
            return false;
        }

        $cart = $this->getContent();

        $cart->put($id, new $this->itemClass($item, $this->config));

        $this->save($cart);

        $this->fireEvent('added', $item);

        return true;
    }

    /**
     * add condition on an existing item on the cart
     *
     * @param int|string $itemId
     * @param CartCondition $itemCondition
     * @return $this
     */
    public function addItemCondition($itemId, $itemCondition)
    {
        if ($itemCondition instanceof (CartCondition::class) && ($item = $this->get($itemId))) {
            // we need to copy first to a temporary variable to hold the conditions
            // to avoid hitting this error "Indirect modification of overloaded element of Mohamadtsn\ShoppingCart\ItemCollection has no effect"
            // this is due to laravel Collection instance that implements Array Access
            // // see link for more info: http://stackoverflow.com/questions/20053269/indirect-modification-of-overloaded-element-of-splfixedarray-has-no-effect
            $itemConditionTempHolder = $item['conditions'];

            if (is_array($itemConditionTempHolder)) {
                $itemConditionTempHolder[] = $itemCondition;
            } else {
                $itemConditionTempHolder = $itemCondition;
            }

            $this->update($itemId, [
                'conditions' => $itemConditionTempHolder, // the newly updated conditions
            ]);
            $item['conditions'] = $itemConditionTempHolder;

            $this->updateCartItems($item);
        }

        return $this;
    }

    /**
     * removes an item on cart by item ID
     *
     * @param $id
     * @return bool
     */
    public function remove($id)
    {
        $cart = $this->getContent();

        if ($this->fireEvent('removing', $id) === false) {
            return false;
        }

        $cart->forget($id);

        $this->save($cart);

        $this->fireEvent('removed', $id);
        return true;
    }

    /**
     * clear cart
     * @return bool
     */
    public function clear()
    {
        if ($this->fireEvent('clearing') === false) {
            return false;
        }

        $this->session->put(
            $this->sessionKeyCartItems,
            []
        );

        $this->setCartItems([]);
        $this->fireEvent('cleared');
        return true;
    }

    /**
     * add a condition on the cart
     *
     * @param CartCondition|array $condition
     * @return $this
     * @throws InvalidConditionException
     */
    public function condition($condition)
    {
        if (is_array($condition)) {
            foreach ($condition as $c) {
                $this->condition($c);
            }

            return $this;
        }

        if (!$condition instanceof CartCondition) {
            throw new InvalidConditionException('Argument 1 must be an instance of \'Mohamadtsn\ShoppingCart\CartCondition\'');
        }

        $conditions = $this->getConditions();

        // Check if order has been applied
        if ($condition->getOrder() === 0) {
            $last = $conditions->last();
            $condition->setOrder(!is_null($last) ? ($last->getOrder() + 1) : 1);
        }

        $conditions->put($condition->getName(), $condition);

        $conditions = $conditions->sortBy(function ($condition) {
            return $condition->getOrder();
        });

        $this->saveConditions($conditions);

        return $this;
    }

    /**
     * get conditions applied on the cart
     *
     * @return CartConditionCollection
     */
    public function getConditions()
    {
        return new CartConditionCollection($this->cartConditions);
    }

    /**
     * save the cart conditions
     *
     * @param $conditions
     */
    protected function saveConditions($conditions)
    {
        $this->session->put($this->sessionKeyCartConditions, $conditions);
        $this->setCartConditions($conditions);
    }

    /**
     * get condition applied on the cart by its name
     *
     * @param $conditionName
     * @return CartCondition
     */
    public function getCondition($conditionName)
    {
        return $this->getConditions()->get($conditionName);
    }

    /**
     * Remove all the condition with the $type specified
     * Please Note that this will only remove condition added on cart bases, not those conditions added
     * specifically on an per item bases
     *
     * @param $type
     * @return $this
     */
    public function removeConditionsByType($type)
    {
        $this->getConditionsByType($type)->each(function ($condition) {
            $this->removeCartCondition($condition->getName());
        });
    }

    /**
     * Get all the condition filtered by Type
     * Please Note that this will only return condition added on cart bases, not those conditions added
     * specifically on an per item bases
     *
     * @param $type
     * @return CartConditionCollection
     */
    public function getConditionsByType($type)
    {
        return $this->getConditions()->filter(function (CartCondition $condition) use ($type) {
            return (string)$condition->getType() === (string)$type;
        });
    }

    /**
     * removes a condition on a cart by condition name,
     * this can only remove conditions that are added on cart bases not conditions that are added on an item/product.
     * If you wish to remove a condition that has been added for a specific item/product, you may
     * use the removeItemCondition(itemId, conditionName) method instead.
     *
     * @param $conditionName
     * @return void
     */
    public function removeCartCondition($conditionName)
    {
        $conditions = $this->getConditions();

        $conditions->pull($conditionName);

        $this->saveConditions($conditions);
    }

    /**
     * remove a condition that has been applied on an item that is already on the cart
     *
     * @param $itemId
     * @param $conditionName
     * @return bool
     */
    public function removeItemCondition($itemId, $conditionName)
    {
        if (!$item = $this->getContent()->get($itemId)) {
            return false;
        }

        if ($this->itemHasConditions($item)) {
            // NOTE:
            // we do it this way, we get first conditions and store
            // it in a temp variable $originalConditions, then we will modify the array there
            // and after modification we will store it again on $item['conditions']
            // This is because of ArrayAccess implementation
            // see link for more info: http://stackoverflow.com/questions/20053269/indirect-modification-of-overloaded-element-of-splfixedarray-has-no-effect

            $tempConditionsHolder = $item['conditions'];
            // if the item's conditions is in array format
            // we will iterate through all of it and check if the name matches
            // to the given name the user wants to remove, if so, remove it
            if (is_array($tempConditionsHolder)) {
                foreach ($tempConditionsHolder as $k => $condition) {
                    if ((string)$condition->getName() === (string)$conditionName) {
                        unset($tempConditionsHolder[$k]);
                    }
                }

                $item['conditions'] = $tempConditionsHolder;
            }

            // if the item condition is not an array, we will check if it is
            // an instance of a Condition, if so, we will check if the name matches
            // on the given condition name the user wants to remove, if so,
            // lets just make $item['conditions'] an empty array as there's just 1 condition on it anyway
            else if (($item['conditions'] instanceof (CartCondition::class)) && (string)$tempConditionsHolder->getName() === (string)$conditionName) {
                $item['conditions'] = [];
            }
        }

        $this->update($itemId, [
            'conditions' => $item['conditions'],
        ]);

        $this->updateCartItems($item);

        return true;
    }

    /**
     * check if an item has condition
     *
     * @param CartItemAbstract $item
     * @return bool
     */
    protected function itemHasConditions($item)
    {
        if (!isset($item['conditions'])) {
            return false;
        }

        if (is_array($item->conditions)) {
            return count($item->conditions) > 0;
        }

        return $item->conditions instanceof (CartCondition::class);
    }

    /**
     * remove all conditions that has been applied on an item that is already on the cart
     *
     * @param $itemId
     * @return bool
     */
    public function clearItemConditions($itemId)
    {
        if (!($item = $this->getContent()->get($itemId))) {
            return false;
        }

        $this->update($itemId, [
            'conditions' => [],
        ]);
        $item['conditions'] = [];

        $this->updateCartItems($item);

        return true;
    }

    /**
     * clears all conditions on a cart,
     * this does not remove conditions that has been added specifically to an item/product.
     * If you wish to remove a specific condition to a product, you may use the method: removeItemCondition($itemId, $conditionName)
     *
     * @return void
     */
    public function clearCartConditions()
    {
        $this->session->put(
            $this->sessionKeyCartConditions,
            []
        );
        $this->setCartConditions([]);
    }

    /**
     * get cart sub-total without conditions
     * @param bool $formatted
     * @return float
     */
    public function getSubTotalWithoutConditions($formatted = true)
    {
        $cart = $this->getContent();

        $sum = $cart->sum(function ($item) {
            return $item->getPriceSum(false);
        });

        return formatValue((float)$sum, $formatted, $this->config);
    }

    /**
     * the new total in which conditions are already applied
     *
     * @return float
     */
    public function getTotal()
    {
        $subTotal = $this->getSubTotal(false);

        $newTotal = 0.00;

        $process = 0;

        $conditions = $this
            ->getConditions()
            ->filter(function (CartCondition $cond) {
                return $cond->getTarget() === 'total';
            });

        // if no conditions were added, just return the sub total
        if (!$conditions->count()) {
            return formatValue($subTotal, $this->config['format_numbers'], $this->config);
        }

        $conditions
            ->each(function (CartCondition $cond) use ($subTotal, &$newTotal, &$process) {
                $toBeCalculated = ($process > 0) ? $newTotal : $subTotal;

                $newTotal = $cond->applyCondition($toBeCalculated);

                $process++;
            });

        return formatValue($newTotal, $this->config['format_numbers'], $this->config);
    }

    /**
     * get cart sub total
     * @param bool $formatted
     * @return float
     */
    public function getSubTotal(bool $formatted = true)
    {
        $cart = $this->getContent();

        $sum = $cart->sum(function (CartItemAbstract $item) {
            return $item->getPriceSumWithConditions(false);
        });

        // get the conditions that are meant to be applied
        // on the subtotal and apply it here before returning the subtotal
        $conditions = $this
            ->getConditions()
            ->filter(function (CartCondition $cond) {
                return $cond->getTarget() === 'subtotal';
            });

        // if there is no conditions, lets just return the sum
        if (!$conditions->count()) {
            return formatValue((float)$sum, $formatted, $this->config);
        }

        // there are conditions, lets apply it
        $newTotal = 0.00;
        $process = 0;

        $conditions->each(function (CartCondition $cond) use ($sum, &$newTotal, &$process) {

            // if this is the first iteration, the toBeCalculated
            // should be the sum as initial point of value.
            $toBeCalculated = ($process > 0) ? $newTotal : $sum;

            $newTotal = $cond->applyCondition($toBeCalculated);

            $process++;
        });

        return formatValue($newTotal, $formatted, $this->config);
    }

    /**
     * get total quantity of items in the cart
     *
     * @return int
     */
    public function getTotalQuantity()
    {
        $items = $this->getContent();

        if ($items->isEmpty()) {
            return 0;
        }

        return $items->sum(function ($item) {
            return $item['quantity'];
        });
    }

    /**
     * check if cart is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->getContent()->isEmpty();
    }

    /**
     * Setter for decimals. Change value on demand.
     * @param $decimals
     */
    public function setDecimals($decimals)
    {
        $this->decimals = $decimals;
    }

    /**
     * Setter for decimals point. Change value on demand.
     * @param $dec_point
     */
    public function setDecPoint($dec_point)
    {
        $this->dec_point = $dec_point;
    }

    public function setThousandsSep($thousands_sep)
    {
        $this->thousands_sep = $thousands_sep;
    }

    /**
     * Associate the cart item with the given id with the given model.
     *
     * @param mixed $model
     *
     * @return Cart
     * @throws UnknownModelException
     */
    public function associate($model): self
    {
        if (is_string($model) && !class_exists($model)) {
            throw new UnknownModelException("The supplied model {$model} does not exist.");
        }

        $cart = $this->getContent();

        $item = $cart->pull($this->currentItemId);

        $item['associatedModel'] = $model;

        $cart->put($this->currentItemId, new $this->itemClass($item, $this->config));

        $this->save($cart);

        return $this;
    }

    protected function setCartItems($cart_items = null)
    {
        if (!is_bool($cart_items) && !is_null($cart_items)) {
            $this->cartItems = $cart_items;
        } else if (empty($this->cartItems) || $cart_items === true) {
            $this->cartItems = $this->session->get($this->sessionKeyCartItems);
        }

        $this->endSaveAllItems && $this->initializeCacheItemModel();

        return $this;
    }

    protected function updateCartItems(CartItemAbstract $item)
    {
        $this->cartItems->pull($item->id);
        $this->cartItems->put($item->id, $item);
        return $this;
    }

    /**
     * get an item on a cart by item ID
     *
     * @param $itemId
     * @return CartItemAbstract|ItemCollection|null
     */
    public function get($itemId)
    {
        return $this->getContent()->get($itemId);
    }

    /**
     * get the cart
     *
     * @return CartCollection
     */
    public function getContent(): CartCollection
    {
        $this->initializeCacheItemModel();
        return (new CartCollection($this->cartItems))->reject(function ($item) {
            return !($item instanceof CartItemAbstract);
        });
    }

    public function resetCacheItemModel(): void
    {
        $this->cacheModels = [];
    }

    public function initializeCacheItemModel()
    {
        if (empty($this->cartItems) || !empty($this->cacheModels)) {
            return false;
        }

        $prepare_for_cache = $this->cartItems
            ->reject(fn($item) => !$item->has('associatedModel'))
            ->groupBy('associatedModel');

        $this->storeItemModelToCache($prepare_for_cache);
    }

    public function storeItemModelToCache(CartCollection $items)
    {
        if ($items->isEmpty()) {
            return false;
        }
        $items->each(function ($item, $associated_model) {
            $relations = $item->map->getEagerLoadRelationModel()->collapse()->toArray();
            $models = with(new $associated_model())->with($relations)->whereIn('id', $item->pluck('id')->toarray())->get();
            if ($models->isNotEmpty()) {
                $models->each(fn($model) => $this->cacheModels[$associated_model][$model->id] = $model);
            }
        });
    }

    public function getModelFromCache($associatedModel, $item_id)
    {
        return $this->cacheModels[$associatedModel][$item_id] ?? null;
    }

    protected function setCartConditions($cart_conditions = null)
    {
        if (!is_bool($cart_conditions) && !is_null($cart_conditions)) {
            $this->cartConditions = $cart_conditions;
        } else if (empty($this->cartConditions) || $cart_conditions === true) {
            $this->cartConditions = $this->session->get($this->sessionKeyCartConditions);
        }
    }
}
