<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2017 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

class JFormFieldModal_CSS extends JFormField
{
	protected $type = 'Modal_CSS';

	/**
	 *
	 * @return string
	 *
	 * @since 6.0
	 */
	protected function getInput()
	{
		JHtml::_('behavior.modal', 'a.modal');

		$script   = array();
		$script[] = '	function jSelectArticle_' . $this->id . '(id, title, catid, object) {';
		$script[] = '		document.id("' . $this->id . '_id").value = id;';
		$script[] = '		document.id("' . $this->id . '_name").value = title;';
		$script[] = '		SqueezeBox.close();';
		$script[] = '	}';

		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		$html = array();
		$link = str_replace('administrator/', '', JURI::base()) . 'plugins/content/jumultithumb/load/css.php';

		$html[] = '<a class="modal btn btn-primary" title="' . JText::_('PLG_JUMULTITHUMB_CSS_UPLOAD') . '" href="' . $link . '" rel="{handler: \'iframe\', size: {x: 900, y: 550}}"><i class="icon-apply icon-white"></i> ' . JText::_('PLG_JUMULTITHUMB_CSS_UPLOAD') . '</a>';

		return implode("\n", $html);
	}
}