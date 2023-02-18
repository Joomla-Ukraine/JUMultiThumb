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

use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('JPATH_BASE') or die;

class JFormFieldModal_Upload extends FormField
{
	protected $type = 'Modal_Upload';

	/**
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	protected function getInput()
	{
		$link                        = str_replace('administrator/', '', JURI::base()) . 'plugins/content/jumultithumb/load/watermark/watermark.php';
		$modalParams[ 'title' ]      = Text::_('PLG_JUMULTITHUMB_WATERMARK_UPLOAD');
		$modalParams[ 'url' ]        = $link;
		$modalParams[ 'height' ]     = '100%';
		$modalParams[ 'width' ]      = '100%';
		$modalParams[ 'bodyHeight' ] = 70;
		$modalParams[ 'modalWidth' ] = 80;

		$html = HTMLHelper::_('bootstrap.renderModal', 'modal-' . $this->id, $modalParams);
		$html .= '<button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#modal-' . $this->id . '">' . Text::_('PLG_JUMULTITHUMB_WATERMARK_UPLOAD') . '</button>';

		return $html;
	}
}