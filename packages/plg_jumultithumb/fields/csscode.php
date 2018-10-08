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

jimport('joomla.form.formfield');

class JFormFieldCSSCode extends JFormField
{
	protected $type = 'CSSCode';

	/**
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
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