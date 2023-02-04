<?php

use Illuminate\Contracts\Events\Dispatcher;
use Mohamadtsn\ShoppingCart\Cart;
use Tests\Helpers\SessionMock;


class CartTestEvents extends PHPUnit\Framework\TestCase
{

    public const CART_INSTANCE_NAME = 'shopping';

    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function test_event_cart_created()
    {
        $events = Mockery::mock(Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.created', Mockery::type('array'), true);

        $cart = new Cart(
            new SessionMock(),
            $events,
            self::CART_INSTANCE_NAME,
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configMock.php')
        );

        $this->assertTrue(true);
    }

    public function test_event_cart_adding()
    {
        $events = Mockery::mock(\Illuminate\Events\Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.created', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.adding', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.added', Mockery::type('array'), true);

        $cart = new Cart(
            new SessionMock(),
            $events,
            self::CART_INSTANCE_NAME,
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item', 100.99, 2, []);

        $this->assertTrue(true);
    }

    public function test_event_cart_adding_multiple_times()
    {
        $events = Mockery::mock(\Illuminate\Events\Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.created', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(2)->with(self::CART_INSTANCE_NAME . '.adding', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(2)->with(self::CART_INSTANCE_NAME . '.added', Mockery::type('array'), true);

        $cart = new Cart(
            new SessionMock(),
            $events,
            self::CART_INSTANCE_NAME,
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configMock.php')
        );

        $cart->add(455, 'Sample Item 1', 100.99, 2, []);
        $cart->add(562, 'Sample Item 2', 100.99, 2, []);

        $this->assertTrue(true);
    }

    public function test_event_cart_adding_multiple_times_scenario_two()
    {
        $events = Mockery::mock(\Illuminate\Events\Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.created', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(3)->with(self::CART_INSTANCE_NAME . '.adding', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(3)->with(self::CART_INSTANCE_NAME . '.added', Mockery::type('array'), true);

        $items = [
            [
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 4,
                'attributes' => [],
            ],
            [
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 4,
                'attributes' => [],
            ],
            [
                'id' => 856,
                'name' => 'Sample Item 3',
                'price' => 50.25,
                'quantity' => 4,
                'attributes' => [],
            ],
        ];

        $cart = new Cart(
            new SessionMock(),
            $events,
            self::CART_INSTANCE_NAME,
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configMock.php')
        );

        $cart->add($items);

        $this->assertTrue(true);
    }

    public function test_event_cart_remove_item()
    {
        $events = Mockery::mock(\Illuminate\Events\Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.created', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(3)->with(self::CART_INSTANCE_NAME . '.adding', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(3)->with(self::CART_INSTANCE_NAME . '.added', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(1)->with(self::CART_INSTANCE_NAME . '.removing', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(1)->with(self::CART_INSTANCE_NAME . '.removed', Mockery::type('array'), true);

        $items = [
            [
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 4,
                'attributes' => [],
            ],
            [
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 4,
                'attributes' => [],
            ],
            [
                'id' => 856,
                'name' => 'Sample Item 3',
                'price' => 50.25,
                'quantity' => 4,
                'attributes' => [],
            ],
        ];

        $cart = new Cart(
            new SessionMock(),
            $events,
            self::CART_INSTANCE_NAME,
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configMock.php')
        );

        $cart->add($items);

        $cart->remove(456);

        $this->assertTrue(true);
    }

    public function test_event_cart_clear()
    {
        $events = Mockery::mock(\Illuminate\Events\Dispatcher::class);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.created', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(3)->with(self::CART_INSTANCE_NAME . '.adding', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->times(3)->with(self::CART_INSTANCE_NAME . '.added', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.clearing', Mockery::type('array'), true);
        $events->shouldReceive('dispatch')->once()->with(self::CART_INSTANCE_NAME . '.cleared', Mockery::type('array'), true);

        $items = [
            [
                'id' => 456,
                'name' => 'Sample Item 1',
                'price' => 67.99,
                'quantity' => 4,
                'attributes' => [],
            ],
            [
                'id' => 568,
                'name' => 'Sample Item 2',
                'price' => 69.25,
                'quantity' => 4,
                'attributes' => [],
            ],
            [
                'id' => 856,
                'name' => 'Sample Item 3',
                'price' => 50.25,
                'quantity' => 4,
                'attributes' => [],
            ],
        ];

        $cart = new Cart(
            new SessionMock(),
            $events,
            self::CART_INSTANCE_NAME,
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configMock.php')
        );

        $cart->add($items);

        $cart->clear();

        $this->assertTrue(true);
    }
}