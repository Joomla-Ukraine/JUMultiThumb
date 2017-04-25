<?php
/**
 * JUMultiThumb
 *
 * @version          7.x
 * @package          JUMultiThumb
 * @author           Denys D. Nosov (denys@joomla-ua.org)
 * @copyright    (C) 2007-2017 by Denys D. Nosov (http://joomla-ua.org)
 * @license          GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('_JEXEC') or die;

class plgContentJUMultiThumb_com_content
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
				return ($option == 'com_content');
				break;
			case 'CatBlog':
				return (($view == 'category' && ($layout == 'blog')) || ($view == 'category' && ($layout == 'card')));
				break;
			case 'Blog':
				return ($layout == 'blog');
				break;
			case 'Category':
				return ($view == 'categories' && !($layout));
				break;
			case 'Categories':
				return ($view == 'categories');
				break;
			case 'Featured':
				return ($view == 'featured');
				break;
			case 'Print':
				return ($print == '1');
				break;
			case 'Article':
				return ($view == 'article');
				break;
		}

		return true;
	}

	public function jViewLink($article)
	{
		require_once (JPATH_SITE . '/components/com_content/helpers/route.php');

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