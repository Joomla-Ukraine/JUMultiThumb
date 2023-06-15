<?php
/**
 * @package     JUMultiThumb\Helpers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace JUMultiThumb\Helpers;

use Joomla\CMS\Factory;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Uri\Uri;

class Utils
{
	public static function img_source(string $src): string
	{
		return str_replace(Uri::base(), '', $src);
	}

	public static function img_alt(string $alt): string
	{
		return mb_strtoupper(mb_substr($alt, 0, 1)) . mb_substr($alt, 1);
	}

	public static function img_class(array $options = []): string
	{
		$img_class = '';
		if($options[ 'imgalign' ] !== '')
		{
			$img_class = 'ju' . trim($options[ 'imgalign' ]) . ' ';
		}
		elseif($options[ 'imgstyle' ] !== '')
		{
			$img_class = 'ju' . trim($options[ 'imgstyle' ]) . ' ';
		}

		return 'juimage ' . $options[ 'imgclass' ] . ' ' . $img_class . 'juimg-' . $options[ 'view' ];
	}

	public static function image(string $plugin, string $name, array $options = []): string
	{
		switch($options[ 'lightbox' ])
		{
			case 'lightgallery':
				$link          = '#';
				$lightbox      = ' ';
				$lightbox_data = ' ' . ($options[ 'link_img' ] ? 'data-src="' . Uri::base() . $options[ 'link_img' ] . '"' : '') . ' ' . ($options[ 'orig_img' ] ? 'data-download-url="' . Uri::base() . $options[ 'orig_img' ] . '"' : '');
				break;

			case 'colorbox':
				$link          = $options[ 'link_img' ];
				$lightbox      = ' class="lightbox" rel="lightbox[gall]"';
				$lightbox_data = '';
				break;

			default:
			case 'jmodal':
				$link          = $options[ 'link_img' ];
				$lightbox      = ' rel="{handler: \'image\', marginImage: {x: 50, y: 50}}"';
				$lightbox_data = '';
				break;
		}

		return Utils::tmpl($plugin, $name, [
			'img'            => $options[ 'image' ],
			'w'              => $options[ 'w' ],
			'h'              => $options[ 'h' ],
			'class'          => $options[ 'class' ],
			'alt'            => $options[ 'alt' ],
			'caption'        => $options[ 'caption' ],
			'title'          => $options[ 'title' ],
			'link_img'       => $options[ 'link_img' ],
			'orig_img'       => $options[ 'orig_img' ],
			'link'           => $link,
			'lightbox'       => $lightbox,
			'lightbox_data ' => $lightbox_data
		]);
	}

	/**
	 * @param string $plugin
	 * @param string $name
	 * @param array  $variables
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	public static function tmpl(string $plugin, string $name, array $variables = []): string
	{
		$template = Factory::getApplication()->getTemplate();

		$override = JPATH_SITE . '/templates/' . $template . '/html/' . $plugin . '/';
		$tmpl     = JPATH_SITE . '/plugins/content/' . $plugin . '/tmpl/';
		$filename = $override . $name . '.php';

		if(is_file($filename))
		{
			//print_r((new FileLayout($name, $override))->setDebug(true));

			return (new FileLayout($name, $override))->render($variables);
		}

		//$t = (new FileLayout($name, $tmpl))->debug();
		//print_r($t);

		return (new FileLayout($name, $tmpl))->render($variables);
	}
}