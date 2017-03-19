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

defined('_JEXEC') or die;

class plgButtonJUmultithumb_EditorButton extends JPlugin
{
	protected $autoloadLanguage = true;

	public function onDisplay($name, $asset, $author)
	{
		$app = JFactory::getApplication();
		$user = JFactory::getUser();
		$extension = $app->input->get('option');

		if ($asset == '') $asset = $extension;

		if (	$user->authorise('core.edit', $asset)
			||	$user->authorise('core.create', $asset)
			||	(count($user->getAuthorisedCategories($asset, 'core.create')) > 0)
			||	($user->authorise('core.edit.own', $asset) && $author == $user->id)
			||	(count($user->getAuthorisedCategories($extension, 'core.edit')) > 0)
			||	(count($user->getAuthorisedCategories($extension, 'core.edit.own')) > 0 && $author == $user->id)
        ) {
			$link = '../plugins/editors-xtd/jumultithumb_editorbutton/form.php';
			JHtml::_('behavior.modal');

			$button = new JObject;
            
			$button->modal = true;
			$button->class = 'btn';
			$button->link = $link;
			$button->text = JText::_('PLG_JUMULTITHUMB').' - '.JText::_('COM_PLUGINS_GALLERY_FIELDSET_LABEL');
			$button->name = 'image';
			$button->options = "{handler: 'iframe', size: {x: 670, y: 500}}";

			return $button;
		}
        else {
			return false;
		}
	}
}