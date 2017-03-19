<?php
/**
 * JUMultiThumb
 *
 * @version 	7.x
 * @package 	JUMultiThumb
 * @author 		Denys D. Nosov (denys@joomla-ua.org)
 * @copyright 	(C) 2007-2017 by Denys D. Nosov (http://joomla-ua.org)
 * @license 	GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 *
 **/

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
		$option     = JRequest::getVar('option');
		$view       = JRequest::getVar('view');
		$layout     = JRequest::getVar('layout');
		$print      = JRequest::getVar('print');

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
	}

	public function jViewLink($article)
	{

        require_once JPATH_SITE .'/components/com_content/helpers/route.php';

        if ($article->params->get('access-view')) {
            $link       = JRoute::_( ContentHelperRoute::getArticleRoute($article->slug, $article->catid) );
        }
        else {
            $menu       = JFactory::getApplication()->getMenu();
            $active     = $menu->getActive();
            $itemId     = $active->id;
            $link1      = JRoute::_('index.php?option=com_users&view=login&Itemid=' . $itemId);
            $returnURL  = JRoute::_(ContentHelperRoute::getArticleRoute($article->slug, $article->catid));
            $link       = new JURI($link1);

            $link->setVar('return', base64_encode($returnURL));
        }

        return $link;
	}
}