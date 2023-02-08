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

use Joomla\CMS\Plugin\CMSPlugin;

defined('JPATH_BASE') or die;

class plgContentJUMultithumb_ContentForm extends CMSPlugin
{
	/**
	 * @since version
	 * @var
	 */
	private $_subject;

	/**
	 * plgContentJUMultithumb_ContentForm constructor.
	 *
	 * @param $subject
	 * @param $config
	 *
	 * @since 7.0
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
	}

	/**
	 * @param $form
	 * @param $data
	 *
	 * @return bool
	 *
	 * @since 7.0
	 */
	public function onContentPrepareForm($form, $data): bool
	{
		if(!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');

			return false;
		}

		if($form->getName() !== 'com_content.article')
		{
			return true;
		}

		JForm::addFormPath(__DIR__ . '/forms');

		$form->loadFile('article', false);

		return true;
	}
}