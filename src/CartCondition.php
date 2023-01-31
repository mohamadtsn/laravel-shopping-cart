<?php

namespace Mohamadtsn\ShoppingCart;

use Mohamadtsn\ShoppingCart\Exceptions\InvalidConditionException;
use Mohamadtsn\ShoppingCart\Validators\CartConditionValidator;

class CartCondition
{

    /**
     * the parsed raw value of the condition
     *
     * @var
     */
    public $parsedRawValue;
    /**
     * @var array
     */
    private $args;

    /**
     * @param array $args (name, type, target, value)
     * @throws InvalidConditionException
     */
    public function __construct(array $args)
    {
        $this->args = $args;

        if (isMultiArray($args)) {
            throw new InvalidConditionException('Multi dimensional array is not supported.');
        }

        $this->validate($this->args);
    }

    /**
     * validates condition arguments
     *
     * @param $args
     * @throws InvalidConditionException
     */
    protected function validate($args)
    {
        $rules = [
            'name' => 'required',
            'type' => 'required',
            'value' => 'required',
        ];

        $validator = CartConditionValidator::make($args, $rules);

        if ($validator->fails()) {
            throw new InvalidConditionException($validator->messages()->first());
        }
    }

    /**
     * the target of where the condition is applied.
     * NOTE: On conditions added to per item bases, target is not needed.
     *
     * @return mixed
     */
    public function getTarget()
    {
        return (isset($this->args['target'])) ? $this->args['target'] : '';
    }

    /**
     * the name of the condition
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->args['name'];
    }

    /**
     * the type of the condition
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->args['type'];
    }

    /**
     * get the additional attributes of a condition
     *
     * @return array
     */
    public function getAttributes()
    {
        return (isset($this->args['attributes'])) ? $this->args['attributes'] : [];
    }

    /**
     * Set the order to apply this condition. If no argument order is applied we return 0 as
     * indicator that no assignment has been made
     * @param int $order
     * @return Integer
     */
    public function setOrder($order = 1)
    {
        $this->args['order'] = $order;
    }

    /**
     * the order to apply this condition. If no argument order is applied we return 0 as
     * indicator that no assignment has been made
     *
     * @return Integer
     */
    public function getOrder()
    {
        return isset($this->args['order']) && is_numeric($this->args['order']) ? (int)$this->args['order'] : 0;
    }

    /**
     * apply condition to total or subtotal
     *
     * @param $totalOrSubTotalOrPrice
     * @return float
     */
    public function applyCondition($totalOrSubTotalOrPrice)
    {
        return $this->apply($totalOrSubTotalOrPrice, $this->getValue());
    }

    /**
     * apply condition
     *
     * @param $totalOrSubTotalOrPrice
     * @param $conditionValue
     * @return float
     */
    protected function apply($totalOrSubTotalOrPrice, $conditionValue)
    {
        // if value has a percentage sign on it, we will get first
        // its percentage then we will evaluate again if the value
        // has a minus or plus sign so we can decide what to do with the
        // percentage, whether to add or subtract it to the total/subtotal/price
        // if we can't find any plus/minus sign, we will assume it as plus sign
        if ($this->valueIsPercentage($conditionValue)) {
            if ($this->valueIsToBeSubtracted($conditionValue)) {
                $value = normalizePrice($this->cleanValue($conditionValue));

                $this->parsedRawValue = $totalOrSubTotalOrPrice * ($value / 100);

                $result = (float)($totalOrSubTotalOrPrice - $this->parsedRawValue);
            } else if ($this->valueIsToBeAdded($conditionValue)) {
                $value = normalizePrice($this->cleanValue($conditionValue));

                $this->parsedRawValue = $totalOrSubTotalOrPrice * ($value / 100);

                $result = (float)($totalOrSubTotalOrPrice + $this->parsedRawValue);
            } else {
                $value = normalizePrice($conditionValue);

                $this->parsedRawValue = $totalOrSubTotalOrPrice * ($value / 100);

                $result = floatval($totalOrSubTotalOrPrice + $this->parsedRawValue);
            }
        }

        // if the value has no percent sign on it, the operation will not be a percentage
        // next is we will check if it has a minus/plus sign so then we can just deduct it to total/subtotal/price
        else {
            if ($this->valueIsToBeSubtracted($conditionValue)) {
                $this->parsedRawValue = normalizePrice($this->cleanValue($conditionValue));

                $result = (float)($totalOrSubTotalOrPrice - $this->parsedRawValue);
            } else if ($this->valueIsToBeAdded($conditionValue)) {
                $this->parsedRawValue = normalizePrice($this->cleanValue($conditionValue));

                $result = (float)($totalOrSubTotalOrPrice + $this->parsedRawValue);
            } else {
                $this->parsedRawValue = normalizePrice($conditionValue);

                $result = (float)($totalOrSubTotalOrPrice + $this->parsedRawValue);
            }
        }

        // Do not allow items with negative prices.
        return $result < 0 ? 0.00 : $result;
    }

    /**
     * check if value is a percentage
     *
     * @param $value
     * @return bool
     */
    protected function valueIsPercentage($value)
    {
        return (preg_match('/%/', $value) == 1);
    }

    /**
     * check if value is a subtract
     *
     * @param $value
     * @return bool
     */
    protected function valueIsToBeSubtracted($value)
    {
        return (preg_match('/\-/', $value) == 1);
    }

    /**
     * removes some arithmetic signs (%,+,-) only
     *
     * @param $value
     * @return mixed
     */
    protected function cleanValue($value)
    {
        return str_replace(['%', '-', '+'], '', $value);
    }

    /**
     * check if value is to be added
     *
     * @param $value
     * @return bool
     */
    protected function valueIsToBeAdded($value)
    {
        return (preg_match('/\+/', $value) == 1);
    }

    /**
     * the value of this the condition
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->args['value'];
    }

    /**
     * get the calculated value of this condition supplied by the subtotal|price
     *
     * @param $totalOrSubTotalOrPrice
     * @return mixed
     */
    public function getCalculatedValue($totalOrSubTotalOrPrice)
    {
        $this->apply($totalOrSubTotalOrPrice, $this->getValue());

        return $this->parsedRawValue;
    }
}
