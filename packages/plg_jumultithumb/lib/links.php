<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2018 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class AutoLinks
{
	/**
	 * @param $text
	 * @param $title
	 * @param $link
	 * @param $onlyFirstImage
	 *
	 * @return null|string|string[]
	 *
	 * @since 6.0
	 */
	public function handleImgLinks(&$text, $title, $link, $onlyFirstImage)
	{
		if(empty($link))
		{
			return $text;
		}

		$regex = "/<img[^>]+>/i";
		$this->_replaceImg(null, $link, $title);

		if($onlyFirstImage)
		{
			$text = preg_replace_callback($regex, array($this, '_replaceImg'), $text, 1);
		}
		else
		{
			$text = preg_replace_callback($regex, array($this, '_replaceImg'), $text);
		}

		return $text;
	}

	/**
	 * @param      $matches
	 * @param null $link
	 * @param null $title
	 *
	 * @return string
	 *
	 * @since 6.0
	 */
	public function _replaceImg($matches, $link = null, $title = null)
	{
		static $_link;

		static $_title;

		if(isset($link) && isset($title))
		{
			$_link  = $link;
			$title  = str_replace("'", ' ', $title);
			$title  = str_replace('"', ' ', $title);
			$_title = $title;
			$html   = '';
		}
		else
		{
			$img  = $matches[0];
			$img  = str_replace('alt=""', 'alt="' . trim($_title) . '"', $img);
			$html = '<a href="' . $_link . '">' . $img . '</a>';
		}

		return $html;
	}
}