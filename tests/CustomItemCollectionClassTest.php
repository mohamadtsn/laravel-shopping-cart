<?php

namespace Tests;

use Illuminate\Contracts\Events\Dispatcher;
use Imanghafoori\EloquentMockery\FakeDB;
use Mockery;
use Mohamadtsn\ShoppingCart\Cart;
use PHPUnit\Framework\TestCase;
use Tests\Helpers\CustomItemCollection;
use Tests\Helpers\Models\Product;
use Tests\Helpers\SessionMock;

class CustomItemCollectionClassTest extends TestCase
{
    protected function setUp(): void
    {
        FakeDB::mockQueryBuilder();
        $events = Mockery::mock(Dispatcher::class);
        $events->shouldReceive('dispatch');

        $this->cart = new Cart(
            new SessionMock(),
            $events,
            'shopping',
            'SAMPLESESSIONKEY',
            require(__DIR__ . '/helpers/configCustomItemCollectionMock.php')
        );
        app()->make('shopping', $this->cart);
    }

    public function tearDown(): void
    {
        FakeDB::dontMockQueryBuilder();
        Mockery::close();
    }

    public function test_isset_custom_item_collection()
    {
        $this->cart->add(455, 'Sample Item', 100.99, 2, []);

        $item = $this->cart->get(455);

        $this->assertInstanceOf(CustomItemCollection::class, $item, 'not set custom item collection class');
    }

    public function test_exist_eager_load_relationship_property()
    {
        $item = [
            'id' => 455,
            'name' => 'Sample Item',
            'price' => 100.99,
            'stock' => 4,
            'quantity' => 2,
        ];
        FakeDB::addRow('products', $item);

        $this->cart->add($item)->associate(Product::class);

        $item = $this->cart->get(455);

        $this->assertClassHasAttribute('eagerLoadRelationModel', get_class($item));
        $this->assertIsArray($item->getEagerLoadRelationModel());
    }

    public function test_set_custom_eager_load_relationship_model()
    {
        $item = [
            'id' => 455,
            'name' => 'Sample Item',
            'price' => 100.99,
            'stock' => 4,
            'quantity' => 2,
        ];
        FakeDB::addRow('products', $item);

        $this->cart->add($item)->associate(Product::class);

        $item = $this->cart->get(455);

        $item->setEagerLoadRelationModel([
            'brands',
        ]);
        $this->assertClassHasAttribute('eagerLoadRelationModel', get_class($item));
        $this->assertEquals(['brands'], $item->getEagerLoadRelationModel());
    }

    public function test_get_custom_eager_load_relationship_from_model()
    {
        $item = [
            'id' => 455,
            'name' => 'Sample Item',
            'price' => 100.99,
            'stock' => 4,
            'quantity' => 2,
        ];
        FakeDB::addRow('products', $item);

        $this->cart->add([$item + ['associatedModel' => Product::class]]);

        $item = $this->cart->get(455);

        // set `photos` relation in constructor class
        $this->assertTrue($item->model->relationLoaded('photos'));

    }
}
