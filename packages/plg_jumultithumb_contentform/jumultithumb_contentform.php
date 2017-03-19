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