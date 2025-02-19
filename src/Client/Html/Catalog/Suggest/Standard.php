<?php

/**
 * @license LGPLv3, http://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2015-2023
 * @package Client
 * @subpackage Html
 */


namespace Aimeos\Client\Html\Catalog\Suggest;


/**
 * Default implementation of catalog suggest section HTML clients.
 *
 * @package Client
 * @subpackage Html
 */
class Standard
	extends \Aimeos\Client\Html\Common\Client\Factory\Base
	implements \Aimeos\Client\Html\Common\Client\Factory\Iface
{
	/** client/html/catalog/suggest/name
	 * Class name of the used catalog suggest client implementation
	 *
	 * Each default HTML client can be replace by an alternative imlementation.
	 * To use this implementation, you have to set the last part of the class
	 * name as configuration value so the client factory knows which class it
	 * has to instantiate.
	 *
	 * For example, if the name of the default class is
	 *
	 *  \Aimeos\Client\Html\Catalog\Suggest\Standard
	 *
	 * and you want to replace it with your own version named
	 *
	 *  \Aimeos\Client\Html\Catalog\Suggest\Mysuggest
	 *
	 * then you have to set the this configuration option:
	 *
	 *  client/html/catalog/suggest/name = Mysuggest
	 *
	 * The value is the last part of your own class name and it's case sensitive,
	 * so take care that the configuration value is exactly named like the last
	 * part of the class name.
	 *
	 * The allowed characters of the class name are A-Z, a-z and 0-9. No other
	 * characters are possible! You should always start the last part of the class
	 * name with an upper case character and continue only with lower case characters
	 * or numbers. Avoid chamel case names like "MySuggest"!
	 *
	 * @param string Last part of the class name
	 * @since 2015.02
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
		$context = $this->context();
		$config = $context->config();

		$cntl = \Aimeos\Controller\Frontend::create( $context, 'product' )
			->text( $view->param( 'f_search' ) ); // sort by relevance first


		/** client/html/catalog/suggest/domains
		 * List of domain items that should be fetched along with the products
		 *
		 * The suggsted entries for the full text search in the catalog filter component
		 * usually consist of the names of the matched products. By default, only the
		 * product item including the localized name is available. You can add more domains
		 * like e.g. "media" to get the images of the product as well.
		 *
		 * **Note:** The more domains you will add, the slower the autocomplete requests
		 * will be! Keep it to an absolute minium for user friendly response times.
		 *
		 * @param array List of domain names
		 * @since 2016.08
		 * @see client/html/catalog/suggest/template-body
		 * @see client/html/catalog/suggest/restrict
		 * @see client/html/catalog/suggest/size
		 */
		$domains = $config->get( 'client/html/catalog/suggest/domains', ['text'] );

		/** client/html/catalog/suggest/size
		 * The number of products shown in the list of suggestions
		 *
		 * Limits the number of products that are shown in the list of suggested
		 * products.
		 *
		 * @param integer Number of products
		 * @since 2018.10
		 * @see client/html/catalog/suggest/domains
		 * @see client/html/catalog/suggest/restrict
		 */
		$size = $config->get( 'client/html/catalog/suggest/size', 24 );

		/** client/html/catalog/suggest/restrict
		 * Restricts suggestions to category and attribute facets
		 *
		 * Limits the shown suggestions to the current category and selected
		 * attribute facets. If disabled, suggestions are limited by the
		 * entered text only.
		 *
		 * @param boolean True to use category and facets, false for all results
		 * @since 2019.07
		 * @see client/html/catalog/suggest/domains
		 * @see client/html/catalog/suggest/size
		 */
		if( $config->get( 'client/html/catalog/suggest/restrict', true ) == true )
		{
			$level = $config->get( 'client/html/catalog/lists/levels', \Aimeos\MW\Tree\Manager\Base::LEVEL_ONE );
			$catids = $view->param( 'f_catid', $config->get( 'client/html/catalog/lists/catid-default' ) );

			$cntl->category( $catids, 'default', $level )
				->allOf( $view->param( 'f_attrid', [] ) )
				->oneOf( $view->param( 'f_optid', [] ) )
				->oneOf( $view->param( 'f_oneid', [] ) );

			$this->call( 'conditions', $cntl, $view );
		}

		$view->suggestItems = $cntl->uses( $domains )->slice( 0, $size )->search();

		return parent::data( $view, $tags, $expire );
	}


	/**
	 * Adds additional conditions for filtering
	 *
	 * @param \Aimeos\Controller\Frontend\Product\Iface $cntl Product controller
	 * @param \Aimeos\Base\View\Iface $view View object
	 */
	protected function conditions( \Aimeos\Controller\Frontend\Product\Iface $cntl, \Aimeos\Base\View\Iface $view )
	{
		if( $view->config( 'client/html/catalog/instock', false ) ) {
			$cntl->compare( '>', 'product.instock', 0 );
		}
	}


	/** client/html/catalog/suggest/template-body
	 * Relative path to the HTML body template of the catalog suggest client.
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
	 * @since 2015.02
	 * @see client/html/catalog/suggest/template-header
	 * @see client/html/catalog/suggest/domains
	 */

	/** client/html/catalog/suggest/template-header
	 * Relative path to the HTML header template of the catalog suggest client.
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
	 * @since 2015.02
	 * @see client/html/catalog/suggest/template-body
	 * @see client/html/catalog/suggest/domains
	 */
}
