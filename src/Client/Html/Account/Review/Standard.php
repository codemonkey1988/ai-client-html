<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2016-2023
 * @package Client
 * @subpackage Html
 */


namespace Aimeos\Client\Html\Account\Review;


/**
 * Default implementation of account review HTML client.
 *
 * @package Client
 * @subpackage Html
 */
class Standard
	extends \Aimeos\Client\Html\Common\Client\Factory\Base
	implements \Aimeos\Client\Html\Iface
{
	/** client/html/account/review/name
	 * Class name of the used account review client implementation
	 *
	 * Each default HTML client can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the client factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Client\Html\Account\Review\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Client\Html\Account\Review\Myreview
	 *
	 * then you have to set the this configuration option:
	 *
	 *  client/html/account/review/name = Myreview
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MyReview"!
	 *
	 * @param string Last part of the class name
	 * @since 2016.10
	 */


	/**
	 * Sets the necessary parameter values in the view.
	 *
	 * @param \Aimeos\Base\View\Iface $view The view object which generates the HTML output
	 * @param array &$tags Result array for the list of tags that are associated to the output
	 * @param string|null &$expire Result variable for the expiration date of the output (null for no expiry)
	 * @return \Aimeos\Base\View\Iface Modified view object
	 */
	public function data( \Aimeos\Base\View\Iface $view, array &$tags = [], string &$expire = null ) : \Aimeos\Base\View\Iface
	{
		$products = [];
		$context = $this->context();
		$config = $context->config();

		/** client/html/account/review/size
		 * Maximum number of products shown for review
		 *
		 * After customers bought products, they can write a review for those items.
		 * The products bought last will be displayed first for review and this
		 * setting limits the number of products shown in the account page.
		 *
		 * @param int Number of products
		 * @since 2020.10
		 * @see client/html/account/review/days-after
		 */
		$size = $config->get( 'client/html/account/review/size', 10 );

		/** client/html/account/review/days-after
		 * Number of days after the product can be reviewed
		 *
		 * After customers bought products, they can write a review for those items.
		 * To avoid fake or revenge reviews, the option for reviewing the products is
		 * shown after the configured number of days to customers.
		 *
		 * @param int Number of days
		 * @since 2020.10
		 * @see client/html/account/review/size
		 */
		$days = $config->get( 'client/html/account/review/days-after', 0 );

		$orders = \Aimeos\Controller\Frontend::create( $context, 'order' )
			->compare( '>', 'order.statuspayment', \Aimeos\MShop\Order\Item\Base::PAY_PENDING )
			->compare( '<=', 'order.ctime', date( 'Y-m-d H:i:s', time() - $days * 86400 ) )
			->uses( ['order', 'order/product'] )
			->sort( '-order.ctime' )
			->slice( 0, $size )
			->search();

		$prodMap = $orders->getProducts()->flat()
			->col( 'order.product.id', 'order.product.productid' );

		$exclude = \Aimeos\Controller\Frontend::create( $context, 'review' )
			->for( 'product', $prodMap->keys()->toArray() )
			->slice( 0, $prodMap->count() )
			->list()->getRefId();

		if( ( $prodIds = $prodMap->keys()->diff( $exclude )->toArray() ) !== [] )
		{
			$productItems = \Aimeos\Controller\Frontend::create( $context, 'product' )
				->uses( ['text' => ['name'], 'media' => ['default']] )
				->product( $prodIds )
				->search();

			foreach( $prodMap as $prodId => $ordProdId )
			{
				if( $item = $productItems->get( $prodId ) ) {
					$products[$prodId] = $item->set( 'orderProductId', $ordProdId );
				}
			}
		}

		$view->reviewProductItems = map( $products )->filter()->take( $size );

		return parent::data( $view, $tags, $expire );
	}


	/**
	 * Processes the input, e.g. store given values.
	 *
	 * A view must be available and this method doesn't generate any output
	 * besides setting view variables if necessary.
	 */
	public function init()
	{
		$view = $this->view();

		if( ( $reviews = $view->param( 'review', [] ) ) !== [] )
		{
			$ratings = 0;
			$context = $this->context();

			$cntl = \Aimeos\Controller\Frontend::create( $context, 'review' );
			$addr = \Aimeos\Controller\Frontend::create( $context, 'customer' )->get()->getPaymentAddress();

			foreach( $reviews as $values )
			{
				$item = $cntl->create( $values )->setDomain( 'product' )->setName( $addr->getFirstName() );

				if( $item->getRating() )
				{
					$cntl->save( $item ); // only if value is greater than 0
					$ratings++;
				}
			}

			if( $ratings ) {
				$view->infos = array_merge( $view->get( 'infos', [] ), [$view->translate( 'client', 'Thank you for your review!' )] );
			}
		}

		parent::init();
	}


	/** client/html/account/review/template-body
	 * Relative path to the HTML body template of the account review client.
	 *
	 * The template file contains the HTML code and processing instructions
	 * to generate the result shown in the body of the frontend. The
	 * configuration string is the path to the template file relative
	 * to the templates directory (usually in templates/client/html).
	 *
	 * You can overwrite the template file configuration in extensions and
	 * provide alternative templates. These alternative templates should be
	 * named like the default one but suffixed by
	 * an unique name. You may use the name of your project for this. If
	 * you've implemented an alternative client class as well, it
	 * should be suffixed by the name of the new class.
	 *
	 * @param string Relative path to the template creating code for the HTML page body
	 * @since 2020.10
	 * @see client/html/account/review/template-header
	 */

	/** client/html/account/review/template-header
	 * Relative path to the HTML header template of the account review client.
	 *
	 * The template file contains the HTML code and processing instructions
	 * to generate the HTML code that is inserted into the HTML page header
	 * of the rendered page in the frontend. The configuration string is the
	 * path to the template file relative to the templates directory (usually
	 * in templates/client/html).
	 *
	 * You can overwrite the template file configuration in extensions and
	 * provide alternative templates. These alternative templates should be
	 * named like the default one but suffixed by
	 * an unique name. You may use the name of your project for this. If
	 * you've implemented an alternative client class as well, it
	 * should be suffixed by the name of the new class.
	 *
	 * @param string Relative path to the template creating code for the HTML page head
	 * @since 2020.10
	 * @see client/html/account/review/template-body
	 */
}
