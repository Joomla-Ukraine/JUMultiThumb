<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2023 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;

defined('_JEXEC') or die;

class plgContentJUMultiThumb_com_content
{
	public $plugin;

	/**
	 * plgContentJUMultiThumb_com_content constructor.
	 *
	 * @param $plugin
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	public function __construct(&$plugin)
	{
		$this->plugin = &$plugin;
		$this->app    = Factory::getApplication();
	}

	/**
	 * @param $jlayout
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function jView($jlayout)
	{
		$option = $this->app->input->get('option');
		$view   = $this->app->input->get('view');
		$layout = $this->app->input->get('layout');
		$print  = $this->app->input->get('print');

		switch($jlayout)
		{
			case 'Component':
				return ($option === 'com_content');
				break;

			case 'CatBlog':
				return (($view === 'category' && ($layout === 'blog')) || ($view === 'category' && ($layout === 'card')));
				break;

			case 'Blog':
				return ($layout === 'blog');
				break;

			case 'Category':
				return ($view === 'categories' && !$layout);
				break;

			case 'Categories':
				return ($view === 'categories');
				break;

			case 'Featured':
				return ($view === 'featured');
				break;

			case 'Print':
				return ($print == '1');
				break;

			case 'Article':
				return ($view === 'article');
				break;
		}

		return true;
	}

	/**
	 * @param $article
	 *
	 * @return JURI
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function jViewLink($article)
	{
		if($article->params->get('access-view'))
		{
			$link = Route::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid));
		}
		else
		{
			$active    = $this->app->getMenu()->getActive();
			$itemId    = $active->id;
			$link1     = Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
			$returnURL = Route::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid));
			$link      = new JURI($link1);

			$link->setVar('return', base64_encode($returnURL));
		}

		return $link;
	}
}