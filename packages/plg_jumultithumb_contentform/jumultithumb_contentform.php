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

defined('JPATH_BASE') or die;

jimport('joomla.utilities.date');

class plgContentJUMultithumb_ContentForm extends JPlugin
{
	public function __construct(& $subject, $config)
    {
		parent::__construct($subject, $config);
	}

	function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
            
			return false;
		}

		if (in_array($form->getName(), array('com_content.article'))==false)  return true;

		JForm::addFormPath(__DIR__ . '/forms');

		$form->loadFile('article', false);

		return true;
	}
}