<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2017 (C) Joomla! Ukraine, http://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class plgContentJUMultiThumb_com_fjrelated
{
	var $plugin;

	public function __construct(&$plugin)
	{
		$this->plugin = &$plugin;
	}

	public function jView($jlayout)
	{
		$app = JFactory::getApplication();

		$option = $app->input->get('option');
		$view   = $app->input->get('view');
		$layout = $app->input->get('layout');
		$print  = $app->input->get('print');

		switch ($jlayout)
		{
			case 'Component':
				return ($option == 'com_fjrelated');
				break;
			case 'CatBlog':
				return ($view == 'fjrelated' && ($layout == 'blog'));
				break;
			case 'Blog':
				return ($layout == 'blog');
				break;
			case 'Print':
				return ($print == '1');
				break;
		}

		return true;
	}

	public function jViewLink($article)
	{

		require_once JPATH_SITE . '/components/com_content/helpers/route.php';

		if($article->params->get('access-view'))
		{
			$link = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid));
		}
		else
		{
			$menu      = JFactory::getApplication()->getMenu();
			$active    = $menu->getActive();
			$itemId    = $active->id;
			$link1     = JRoute::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
			$returnURL = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid));
			$link      = new JURI($link1);

			$link->setVar('return', base64_encode($returnURL));
		}

		return $link;
	}
}