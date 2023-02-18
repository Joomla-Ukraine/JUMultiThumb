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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Plugin\CMSPlugin;

defined('_JEXEC') or die;

class plgButtonJUmultithumb_EditorButton extends CMSPlugin
{
	protected $autoloadLanguage = true;

	/**
	 * @param $name
	 * @param $asset
	 * @param $author
	 *
	 * @return bool|JObject
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function onDisplay($name, $asset, $author)
	{
		$user             = Factory::getUser();
		$canCreateRecords = $user->authorise('core.create', 'com_content') || count($user->getAuthorisedCategories('com_content', 'core.create')) > 0;
		$values           = (array) Factory::getApplication()->getUserState('com_content.edit.article.id');
		$isEditingRecords = count($values);
		$hasAccess        = $canCreateRecords || $isEditingRecords;

		if(!$hasAccess)
		{
			return;
		}

		$link            = '../plugins/editors-xtd/jumultithumb_editorbutton/form.php';
		$button          = new CMSObject();
		$button->modal   = true;
		$button->link    = $link;
		$button->text    = Text::_('PLG_JUMULTITHUMB') . ' - ' . Text::_('COM_PLUGINS_GALLERY_FIELDSET_LABEL');
		$button->name    = 'image';
		$button->icon    = 'gallery';
		$button->iconSVG = '<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px"><path d="M0 0h24v24H0z" fill="none"/><path d="M22 16V4c0-1.1-.9-2-2-2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2zm-11-4l2.03 2.71L16 11l4 5H8l3-4zM2 6v14c0 1.1.9 2 2 2h14v-2H4V6H2z"/></svg>';
		$button->options = [
			'height'     => '300px',
			'width'      => '800px',
			'bodyHeight' => '70',
			'modalWidth' => '80',
		];

		return $button;
	}
}