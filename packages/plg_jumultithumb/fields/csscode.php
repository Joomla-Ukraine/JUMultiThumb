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

jimport('joomla.form.formfield');

class JFormFieldCSSCode extends JFormField
{
	protected $type = 'CSSCode';

	protected function getInput()
	{
		return '<textarea style="width: 350px; height: 250px;" id="css_source" readonly>img[style $=\'width\'] {
	width: 150px;
	height: auto!important;
}
img{
	width: 150px!important;
	height: auto!important;
	padding: 2px;
	border: red 1px dashed;
	margin: 3px 18px;
}
img.noimage{
	width: inherit!important;
	height: auto!important;
	border: #0000cd 1px dashed!important;
}</textarea>
	';
	}
}