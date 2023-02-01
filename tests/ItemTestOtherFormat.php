<?php use Illuminate\Contracts\Events\Dispatcher;

/**
 * Created by PhpStorm.
 * User: darryl
 * Date: 3/18/2015
 * Time: 6:17 PM
 */

use Mohamadtsn\ShoppingCart\Cart;
use Mockery as m;
use Tests\Helpers\SessionMock;


class ItemTestOtherFormat extends PHPUnit\Framework\TestCase
{

    /**
     * @var Mohamadtsn\ShoppingCart\Cart
     */
    protected $cart;

    public function setUp(): void
    {
        $events = m::mock(Dispatcher::class);
        $events->shouldReceive('dispatch');

        $this->cart = new Cart(
            new SessionMock(),
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configMockOtherFormat.php')
        );
    }

    public function tearDown(): void
    {
        m::close();
    }

    public function test_item_get_sum_price_using_property()
    {
        $this->cart->add(455, 'Sample Item', 100.99, 2, []);

        $item = $this->cart->get(455);

        $this->assertEquals('201,980', $item->getPriceSum(), 'Item summed price should be 201.98');
    }

    public function test_item_get_sum_price_using_array_style()
    {
        $this->cart->add(455, 'Sample Item', 100.99, 2, []);

        $item = $this->cart->get(455);

        $this->assertEquals('201,980', $item->getPriceSum(), 'Item summed price should be 201.98');
    }
}