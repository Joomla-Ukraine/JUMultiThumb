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

class Utils
{
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

		$override = JPATH_SITE . '/templates/' . $template . '/html/plg_cck_field_typo_jumultithumbs/';
		$tmpl     = JPATH_SITE . '/plugins/content/' . $plugin . '/tmpl/';
		$filename = $override . '/' . $name . '.php';

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