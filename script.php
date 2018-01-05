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

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.archive');
jimport('joomla.filesystem.path');
jimport('joomla.error.error');

class Pkg_JUMultiThumbInstallerScript
{
	protected $dbSupport = array('mysql', 'mysqli', 'postgresql', 'sqlsrv', 'sqlazure');
	protected $message;
	protected $status;
	protected $sourcePath;

	/**
	 * @param $type
	 * @param $parent
	 *
	 * @return bool
	 *
	 * @since 6.0
	 */
	public function preflight($type, $parent)
	{
		if(version_compare(JVERSION, '3.4.0', 'lt'))
		{
			JFactory::getApplication()->enqueueMessage('Update for Joomla! 3.4+', 'error');

			return false;
		}

		$lang = JFactory::getLanguage();
		$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

		if(!in_array(JFactory::getDbo()->name, $this->dbSupport))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('PLG_JUMULTITHUMB_ERROR_DB_SUPPORT'), 'error');

			return false;
		}

		$this->MakeDirectory($dir = JPATH_SITE . '/img', $mode = 0777);

		if(!is_dir(JPATH_SITE . '/img/'))
		{
			JFactory::getApplication()->enqueueMessage("Error creating folder 'img'. Please manually create the folder 'img' in the root of the site where you installed Joomla!", 'error');
		}

		$cache = JFactory::getCache('plg_jumultithumb');
		$cache->clean();

		return true;
	}

	/**
	 * @param $dir
	 * @param $mode
	 *
	 * @return bool
	 *
	 * @since 6.0
	 */
	public function MakeDirectory($dir, $mode)
	{
		if(is_dir($dir) || @mkdir($dir, $mode))
		{
			$indexfile = $dir . '/index.html';
			if(!file_exists($indexfile))
			{
				$file = fopen($indexfile, 'w');
				fputs($file, '<!DOCTYPE html><title></title>');
				fclose($file);
			}

			return true;
		}

		if(!$this->MakeDirectory(dirname($dir), $mode))
		{
			return false;
		}

		return @mkdir($dir, $mode);
	}

	public function uninstall($parent)
	{
		return true;
	}

	public function update($parent)
	{
		return true;
	}

	public function postflight($type, $parent, $results)
	{
		$enabled  = array();
		$newalert = '';

		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$app   = JFactory::getApplication();

		$version = new JVersion;
		$joomla  = substr($version->getShortVersion(), 0, 3);

		$qv = $db->getQuery(true);
		$qv = 'UPDATE `#__extensions` SET `enabled` = 1, `ordering` = -100 WHERE `element` = ' . $db->Quote('jumultithumb') . ' AND `type` = ' . $db->Quote('plugin') . ' AND `client_id` = 0';
		$db->setQuery($qv);
		$db->query();

		$qv = $db->getQuery(true);
		$qv = 'UPDATE `#__extensions` SET `enabled` = 1, `ordering` = -99 WHERE `element` = ' . $db->Quote('jumultithumb_gallery') . ' AND `type` = ' . $db->Quote('plugin') . ' AND `client_id` = 0';
		$db->setQuery($qv);
		$db->query();

		$qv = $db->getQuery(true);
		$qv = 'UPDATE `#__extensions` SET `enabled` = 1 WHERE `element` = ' . $db->Quote('jumultithumb_editorbutton') . ' AND `type` = ' . $db->Quote('plugin') . ' AND `client_id` = 0';
		$db->setQuery($qv);
		$db->query();

		$qv = $db->getQuery(true);
		$qv = 'UPDATE `#__extensions` SET `enabled` = 1 WHERE `element` = ' . $db->Quote('jumultithumb_contentform') . ' AND `type` = ' . $db->Quote('plugin') . ' AND `client_id` = 0';
		$db->setQuery($qv);
		$db->query();

		foreach ($results as $result)
		{
			$extension = (string) $result['name'];
			$query->clear();
			$query->select($db->quoteName('enabled'));
			$query->from($db->quoteName('#__extensions'));
			$query->where($db->quoteName('name') . ' = ' . $db->quote($extension));
			$db->setQuery($query);
			$enabled[$extension] = $db->loadResult();
		}

		$html = '';
		$html .= '<style type="text/css">
		.juinstall {
			clear: both;
			color: #333!important;
			font-weight: normal;
		    margin: 0!important;
		    padding: 0;
		    background: #fff!important;
			position: absolute!important;
			top: 0!important;
			left: 0!important;
			width: 100%;
			height: 100%;
			z-index: 100!important;
		}
			.juinstall-content {
			    margin: 8% auto!important;
			    padding: 35px 0 18px 0;
				width: 50%;
			}
			.juinstall .newalert {
				clear: both;
				margin: 5px 10%!important;
			}
		            .juinstall p {
		              	margin-left: 0;
		              	text-align: left;
		            }
		            .juinstall table td .label {margin: 0 auto;}
		            .juinstall .jursslogo {
		              	float: right;
		              	margin: 0 0 3px 6px;
		            }
		            .juinstall hr {
		              	margin-top:6px;
		              	margin-bottom:6px;
		              	border:0;
		              	border-top:1px solid #eee
		            }
		        .clear {clear: both;}
        </style>';

		$html .= '<div class="juinstall">
        	<div class="juinstall-content">
                <h2>' . JText::_('PKG_JUMULTITHUMB') . '</h2>
                <p>' . JText::_('PKG_JUMULTITHUMB_DESCRIPTION') . '</p>
                <hr>
        		<table class="table table-striped" width="100%">
        			<thead>
        				<tr>
        					<th>' . JText::_('PKG_JUMULTITHUMB_EXTENSION') . '</th>
        					<th>' . JText::_('JSTATUS') . '</th>
        					<th>' . JText::_('JENABLED') . '</th>
        				</tr>
        			</thead>
        			<tbody>';

		foreach ($results as $result)
		{
			$extension = (string) $result['name'];

			$html .= '<tr><td>';
			$html .= JText::_($extension);
			$html .= '</td><td><strong>';

			if($result['result'] == true)
			{
				$html .= '<span class="label label-success">' . JText::_('PKG_JUMULTITHUMB_INSTALLED') . '</span>';
			}
			else
			{
				$html .= '<span class="label label-important">' . JText::_('PKG_JUMULTITHUMB_NOT_INSTALLED') . '</span>';
			}
			$html .= '</strong></td><td>';

			if($enabled[$extension] == 1)
			{
				$html .= '<span class="label label-success">' . JText::_('JYES') . '</span>';
			}
			else
			{
				$html .= '<span class="label label-important">' . JText::_('JNO') . '</span>';
			}

			$html .= '</td></tr>';
		}

		$html .= '</tbody></table>';

		$path = JPATH_SITE . '/plugins/content/jumultithumb/';

		$files = array(
			$path . 'assets/jumultithumb.jpg',
			$path . 'assets/close.png',
			$path . 'assets/toggler.js',
			$path . 'assets/script.js',
			$path . 'assets/style.css'
		);

		$folders = array(
			$path . 'img'
		);

		$i = 0;
		foreach ($files AS $file)
		{
			if(file_exists($file)) $i++;
		}

		$j = 0;
		foreach ($folders AS $folder)
		{
			if(is_dir($folder)) $j++;
		}

		if(($i + $j) > 0)
		{
			$html .= '<h2>' . JText::_('PLG_JUMULTITHUMB_REMOVE_OLD_FILES') . '</h2>
        		<table class="table table-striped">
        			<thead>
        				<tr>
        					<th>' . JText::_('PLG_JUMULTITHUMB_EXTENSION') . '</th>
        					<th>' . JText::_('JSTATUS') . '</th>
        				</tr>
        			</thead>
        			<tbody>';

			foreach ($files AS $file)
			{
				if(file_exists($file))
				{
					$filepath = str_replace($path, '', $file);
					unlink($file);

					$html .= '<tr>
            					<td><span class="label">File:</span> <code>' . $filepath . '</code></td>
            					<td><span class="label label-inverse">Delete</span></td>
            				</tr>';
				}
			}

			foreach ($folders AS $folder)
			{
				if(is_dir($folder))
				{
					$folderpath = str_replace($path, '', $folder);
					$this->unlinkRecursive($folder, 1);

					$html .= '<tr>
            					<td><span class="label">Folder:</span> <code>' . $folderpath . '</code></td>
            					<td><span class="label label-inverse">Delete</span></td>
            				</tr>';
				}
			}

			$html .= '</tbody></table>';
		}

		$html .= '</div></div>';

		if($joomla < '3.4')
		{
			echo $html;
		}
		else
		{
			$app->enqueueMessage($html, 'message');
		}

		return true;
	}

	/**
	 * @param $dir
	 * @param $deleteRootToo
	 *
	 *
	 * @since 6.0
	 */
	public function unlinkRecursive($dir, $deleteRootToo)
	{
		if(!$dh = @opendir($dir))
		{
			return;
		}

		while (false !== ($obj = readdir($dh)))
		{
			if($obj == '.' || $obj == '..')
			{
				continue;
			}

			if(!@unlink($dir . '/' . $obj))
			{
				$this->unlinkRecursive($dir . '/' . $obj, true);
			}
		}

		closedir($dh);

		if($deleteRootToo) @rmdir($dir);

		return;
	}
}