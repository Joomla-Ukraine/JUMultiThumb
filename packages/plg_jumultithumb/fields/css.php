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

use Joomla\CMS\Factory;

defined('JPATH_BASE') or die;

class JFormFieldModal_CSS extends JFormField
{
	protected $type = 'Modal_CSS';

	/**
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	protected function getInput()
	{
		JHtml::_('behavior.modal', 'a.modal');

		$script = 'function jSelectArticle_' . $this->id . '(id, title, catid, object) {
		document.id("' . $this->id . '_id").value = id;
		document.id("' . $this->id . '_name").value = title;SqueezeBox.close();
		}';

		Factory::getDocument()->addScriptDeclaration($script);

		$html = [];
		$link = str_replace('administrator/', '', JURI::base()) . 'plugins/content/jumultithumb/load/css.php';

		$html[] = '<a class="modal btn btn-primary" title="' . JText::_('PLG_JUMULTITHUMB_CSS_UPLOAD') . '" href="' . $link . '" rel="{handler: \'iframe\', size: {x: 900, y: 550}}"><i class="icon-apply icon-white"></i> ' . JText::_('PLG_JUMULTITHUMB_CSS_UPLOAD') . '</a>';

		return implode("\n", $html);
	}
}