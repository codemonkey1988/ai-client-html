<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2022
 */


namespace Aimeos\Client\Html\Basket\Related;


class StandardTest extends \PHPUnit\Framework\TestCase
{
	private $object;
	private $context;


	protected function setUp() : void
	{
		$this->context = \TestHelperHtml::context();

		$this->object = new \Aimeos\Client\Html\Basket\Related\Standard( $this->context );
		$this->object->setView( \TestHelperHtml::view() );
	}


	protected function tearDown() : void
	{
		\Aimeos\Controller\Frontend\Basket\Factory::create( $this->context )->clear();
		unset( $this->object, $this->context );
	}


	public function testHeader()
	{
		$output = $this->object->header();

		$this->assertStringContainsString( '<link rel="stylesheet"', $output );
		$this->assertStringContainsString( '<script defer', $output );
	}


	public function testBody()
	{
		$cntl = \Aimeos\Controller\Frontend\Basket\Factory::create( $this->context );
		$basket = $cntl->get()->addProduct( $this->getOrderProductItem( 'CNE' ) );
		$cntl->save();

		$output = $this->object->body();

		$this->assertStringContainsString( '<section class="basket-related', $output );
		$this->assertStringContainsString( '<section class="basket-related-bought', $output );
		$this->assertStringContainsString( 'Cafe Noire Cappuccino', $output );
	}


	/**
	 * @param string $code
	 * @return \Aimeos\MShop\Order\Item\Base\Product\Iface
	 */
	protected function getOrderProductItem( $code )
	{
		$manager = \Aimeos\MShop::create( $this->context, 'product' );
		$search = $manager->filter();
		$search->setConditions( $search->compare( '==', 'product.code', $code ) );

		if( ( $item = $manager->search( $search )->first() ) === null ) {
			throw new \RuntimeException( sprintf( 'No product item with code "%1$s" found', $code ) );
		}

		$manager = \Aimeos\MShop::create( $this->context, 'order/base/product' );
		$orderItem = $manager->create()->copyFrom( $item )->setStockType( 'default' );

		return $orderItem;
	}
}
