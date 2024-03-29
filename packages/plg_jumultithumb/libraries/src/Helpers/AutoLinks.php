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

namespace JUMultiThumb\Helpers;

defined('_JEXEC') or die;

class AutoLinks
{
	/**
	 * @param $text
	 * @param $title
	 * @param $link
	 * @param $onlyFirstImage
	 *
	 * @return string
	 *
	 * @since 8.0
	 */
	public static function handleImgLinks($text, $title, $link, $onlyFirstImage)
	{
		if(empty($link))
		{
			return $text;
		}

		$regex = '/<img[^>]+>/i';
		self::_replaceImg(null, $link, $title);

		if($onlyFirstImage)
		{
			return preg_replace_callback($regex, [
				__CLASS__,
				'_replaceImg'
			], $text, 1);
		}

		return preg_replace_callback($regex, [
			__CLASS__,
			'_replaceImg'
		], $text);
	}

	/**
	 * @param      $matches
	 * @param null $link
	 * @param null $title
	 *
	 * @return string
	 *
	 * @since 8.0
	 */
	private static function _replaceImg($matches, $link = null, $title = null)
	{
		static $_link;
		static $_title;

		if($title !== null)
		{
			$_link  = $link;
			$title  = str_replace([ "'", '"' ], ' ', $title);
			$_title = $title;

			return '';
		}

		$img = $matches[ 0 ];
		$img = str_replace('alt=""', 'alt="' . trim($_title) . '"', $img);

		return '<a href="' . $_link . '">' . $img . '</a>';
	}
}