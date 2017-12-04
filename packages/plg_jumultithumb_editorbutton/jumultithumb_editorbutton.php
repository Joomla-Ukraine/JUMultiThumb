<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2017 (C) Joomla! Ukraine, http://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

class plgButtonJUmultithumb_EditorButton extends JPlugin
{
	protected $autoloadLanguage = true;

	/**
	 * @param $name
	 * @param $asset
	 * @param $author
	 *
	 * @return bool|JObject
	 *
	 * @since 6.0
	 */
	public function onDisplay($name, $asset, $author)
	{
		$app       = JFactory::getApplication();
		$user      = JFactory::getUser();
		$extension = $app->input->get('option');

		if($asset == '') $asset = $extension;

		if($user->authorise('core.edit', $asset)
			|| $user->authorise('core.create', $asset)
			|| (count($user->getAuthorisedCategories($asset, 'core.create')) > 0)
			|| ($user->authorise('core.edit.own', $asset) && $author == $user->id)
			|| (count($user->getAuthorisedCategories($extension, 'core.edit')) > 0)
			|| (count($user->getAuthorisedCategories($extension, 'core.edit.own')) > 0 && $author == $user->id)
		)
		{
			$link = '../plugins/editors-xtd/jumultithumb_editorbutton/form.php';
			JHtml::_('behavior.modal');

			$button = new JObject;

			$button->modal   = true;
			$button->class   = 'btn';
			$button->link    = $link;
			$button->text    = JText::_('PLG_JUMULTITHUMB') . ' - ' . JText::_('COM_PLUGINS_GALLERY_FIELDSET_LABEL');
			$button->name    = 'image';
			$button->options = "{handler: 'iframe', size: {x: 670, y: 500}}";

			return $button;
		}
		else
		{
			return false;
		}
	}
}