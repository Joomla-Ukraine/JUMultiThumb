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

defined('JPATH_BASE') or die;

$doc = JFactory::getDocument();
$adm_url = str_replace('/administrator', '', JURI::base());

$doc->addStyleSheet( $adm_url . 'plugins/content/jumultithumb/assets/css/jumultithumb.css?v=2' );

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

$doc->addScriptDeclaration( $snipets );