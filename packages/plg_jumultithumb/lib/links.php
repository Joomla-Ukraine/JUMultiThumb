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

class AutoLinks
{
	public function handleImgLinks(&$text, $title, $link, $onlyFirstImage)
	{
        if(empty($link)) return $text;

		$regex = "/<img[^>]+>/i";
		$this->_replaceImg(NULL, $link, $title);

		if($onlyFirstImage) {
			$text = preg_replace_callback($regex, array(&$this, '_replaceImg'), $text, 1);
		}
        else{
			$text = preg_replace_callback($regex, array(&$this, '_replaceImg'), $text);
		}

		return $text;
	}

	public function _replaceImg($matches, $link = NULL, $title = NULL)
	{
		static $_link;
		static $_title;

        if(isset($link) && isset($title))
		{
			$_link  = $link;
			$title  = str_replace("'", ' ', $title);
			$title  = str_replace('"', ' ', $title);
			$_title = $title;
			$html 	= '';
		}
        else {
			$img    = $matches[0];
			$img    = str_replace('alt=""', 'alt="'. trim($_title) .'"', $img);
			$html   = '<a href="'. $_link .'">'. $img .'</a>';
		}

		return $html;
	}
}