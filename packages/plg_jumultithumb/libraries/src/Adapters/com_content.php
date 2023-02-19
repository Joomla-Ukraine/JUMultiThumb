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

namespace JUMultiThumb\Adapters;

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use JURI;

defined('_JEXEC') or die;

class com_content
{
	/**
	 * @param $layout
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	public static function view($type): string
	{
		$app    = Factory::getApplication();
		$option = $app->input->get('option');
		$view   = $app->input->get('view');
		$layout = $app->input->get('layout');
		$print  = $app->input->get('print');

		switch($type)
		{
			case 'Component':
				return ($option === 'com_content');

			case 'CatBlog':
				return (($view === 'category' && ($layout === 'blog')) || ($view === 'category' && ($layout === 'card')));

			case 'Blog':
				return ($layout === 'blog');

			case 'Category':
				return ($view === 'categories' && !$layout);

			case 'Categories':
				return ($view === 'categories');

			case 'Featured':
				return ($view === 'featured');

			case 'Print':
				return ($print == '1');

			case 'Article':
				return ($view === 'article');
		}

		return '';
	}

	/**
	 * @param $article
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	public static function link($article): string
	{
		$app = Factory::getApplication();

		if($article->params->get('access-view'))
		{
			$link = Route::_(RouteHelper::getArticleRoute($article->slug, $article->catid));
		}
		else
		{
			$active    = $app->getMenu()->getActive();
			$itemId    = $active->id;
			$link1     = Route::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
			$returnURL = Route::_(RouteHelper::getArticleRoute($article->slug, $article->catid));
			$link      = new JURI($link1);

			$link->setVar('return', base64_encode($returnURL));
		}

		return $link;
	}
}