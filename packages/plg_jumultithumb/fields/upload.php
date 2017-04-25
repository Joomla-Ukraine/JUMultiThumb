<?php
/**
 * JUMultiThumb
 *
 * @version          7.x
 * @package          JUMultiThumb
 * @author           Denys D. Nosov (denys@joomla-ua.org)
 * @copyright    (C) 2007-2017 by Denys D. Nosov (http://joomla-ua.org)
 * @license          GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 *
 **/

defined('JPATH_BASE') or die;

jimport('joomla.form.formfield');

class JFormFieldModal_Upload extends JFormField
{
	protected $type = 'Modal_Upload';

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
		$link = str_replace('administrator/', '', JURI::base()) . 'plugins/content/jumultithumb/load/watermark/watermark.php';

		$html[] = '	<a class="modal btn btn-primary" title="' . JText::_('PLG_JUMULTITHUMB_WATERMARK_UPLOAD') . '" href="' . $link . '" rel="{handler: \'iframe\', size: {x: 900, y: 550}}"><i class="icon-upload"></i> ' . JText::_('PLG_JUMULTITHUMB_WATERMARK_UPLOAD') . '</a>';

		return implode("\n", $html);
	}
}