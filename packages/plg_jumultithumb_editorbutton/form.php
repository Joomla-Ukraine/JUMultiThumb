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

define( '_JEXEC', 1 );
define('DS', DIRECTORY_SEPARATOR);
define('JPATH_BASE', __DIR__ ."/../../..");
define ("MAX_SIZE","500");

require_once ( JPATH_BASE .'/includes/defines.php' );
require_once ( JPATH_BASE .'/includes/framework.php' );
require_once ( JPATH_BASE .'/libraries/joomla/factory.php' );

$mainframe = JFactory::getApplication('administrator');
$mainframe->initialise();

$joomlaUser = JFactory::getUser();

$lang = JFactory::getLanguage();
$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

$language = mb_strtolower($lang->getTag());

$doc = JFactory::getDocument();

$doc->addStyleSheet('/media/jui/css/bootstrap.min.css');

if ($joomlaUser->get('id') < 1) {
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
<meta content="charset=utf-8" />
<link href="../../../../media/jui/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
</head>
<body>
<dl id="system-message">
    <dt class="error"><?php echo JText::_('PLG_JUMULTITHUMB_NOTICE'); ?></dt>
    <dd class="message error fade">
        <ul>
            <li><?php echo JText::_('PLG_JUMULTITHUMB_LOGIN'); ?></li>
        </ul>
    </dd>
</dl>
</body>
</html>
<?php
    return;
}

$plugin = JPluginHelper::getPlugin('content', 'jumultithumb_gallery');
$json = json_decode($plugin->params);

$rootfolder = 'images/'.$json->galleryfolder.'/';
?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo JText::_('PLG_JUMULTITHUMB_GALLERY_INSERT_TAG'); ?></title>

	<link href="../../../../media/jui/css/bootstrap.min.css"  rel="stylesheet" type="text/css" />
	<link href="assets/jqueryFileTree.css"  rel="stylesheet" type="text/css" />

	<script src="../../../../media/jui/js/jquery.min.js"></script>
	<script src="assets/jqueryFileTree.js?v3"></script>

	<script>
	jQuery.noConflict();
	jQuery(document).ready( function()
	{
	    jQuery('.selects').fileTree({
	        root: '<?php echo $rootfolder; ?>',
	        script: 'assets/jqueryFileTree.php',
	        expandSpeed: 1000,
	        collapseSpeed: 1000,
	        multiFolder: false
	    });
	});

	function insertJUGallery()
    {
		var folder = document.getElementById("folder").value;
	    var title = document.getElementById("title").value;
	    var cssclass = document.getElementById("cssclass").value;

	    if (folder != '') {
			folder = ""+folder+"";
		}

		if (title == '' && cssclass != '') {
			title = "|";
		}
        else if (title != '') {
		    title = "|"+title;
		}
        else {
		    title == "";
		}

		if (cssclass != '') {
			cssclass = "|"+cssclass;
		}

		var tag = "{gallery "+ folder + title+ cssclass +"}";
		window.parent.jInsertEditorText(tag, 'jform_articletext');
		window.parent.SqueezeBox.close();
		return false;
	}
	</script>
	<style>
	body{ background: transparent; }
	fieldset{
	  margin: 5px 0!important;
	}
	</style>
	</head>
	<body>
	    <form class="form-horizontal">
			<div class="control-group">
		    	<label class="control-label"><?php echo JText::_('PLG_JUMULTITHUMB_GALLERY_SELECT_FOLDER'); ?>:</label>
		    	<div class="controls">
	                <div class="selects"></div><br>
	                <input id="folder" class="folderurl uneditable-input" name="selectfolder" disabled="disabled" style="width:30%">
		    	</div>
		  	</div>
			<div class="control-group">
		    	<label class="control-label"><?php echo JText::_('PLG_JUMULTITHUMB_GALLERY_TITLE'); ?>:</label>
		    	<div class="controls">
	                <input type="text" id="title" name="title" size="60" value="" />
		    	</div>
		  	</div>
			<div class="control-group">
		    	<label class="control-label"><?php echo JText::_('PLG_JUMULTITHUMB_GALLERY_CSS_CLASS'); ?>:</label>
		    	<div class="controls">
	                <input type="text" id="cssclass" name="cssclass" size="60" />
		    	</div>
		  	</div>
		  	<div class="control-group">
		    	<div class="controls">
					<button onclick="insertJUGallery();" class="btn btn-success"><?php echo JText::_('PLG_JUMULTITHUMB_GALLERY_INSERT_TAG'); ?></button>
		    	</div>
		  	</div>
		</form>
	</body>
</html>