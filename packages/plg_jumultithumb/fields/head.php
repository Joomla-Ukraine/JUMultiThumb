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

defined('JPATH_BASE') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

$doc     = Factory::getDocument();
$adm_url = str_replace('/administrator', '', Uri::base());

$doc->addStyleSheet($adm_url . 'plugins/content/jumultithumb/assets/css/jumultithumb.css?v=2');

$snipets = '
    jQuery.noConflict();
    (function($)
	{
        $(function()
		{
			$("p.readmore a").addClass("btn");
        });
    })(jQuery);
';

JHtml::_('jquery.framework');

$doc->addScriptDeclaration($snipets);