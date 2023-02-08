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

define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);
define('JPATH_BASE', __DIR__ . '/../../..');
define('MAX_SIZE', '500');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\Session\SessionInterface;

$container = Factory::getContainer();
$container->alias(SessionInterface::class, 'session.web.site');

$app = $container->get(AdministratorApplication::class);

$joomlaUser = Factory::getUser();
$lang       = Factory::getLanguage();
$doc        = Factory::getDocument();

$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);
$language = mb_strtolower($lang->getTag());

if($joomlaUser->get('id') < 1)
{
	echo JText::_('PLG_JUMULTITHUMB_LOGIN');

	return;
}

$plugin     = JPluginHelper::getPlugin('content', 'jumultithumb_gallery');
$json       = json_decode($plugin->params);
$rootfolder = 'images/' . $json->galleryfolder . '/';

?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title><?php echo JText::_('PLG_JUMULTITHUMB_GALLERY_INSERT_TAG'); ?></title>

	<link href="/media/templates/administrator/atum/css/template.css" rel="stylesheet" data-asset-name="template.atum.ltr" data-asset-dependencies="fontawesome" />

	<link href="assets/jqueryFileTree.css" rel="stylesheet" type="text/css" />

	<script src="/media/vendor/jquery/js/jquery.js?3.6.0" data-asset-name="jquery"></script>
	<script src="assets/jqueryFileTree.js?v3"></script>

	<script>
        jQuery.noConflict();
        jQuery(document).ready(function () {
            jQuery('.selects').fileTree({
                root: '<?php echo $rootfolder; ?>',
                script: 'assets/jqueryFileTree.php',
                expandSpeed: 1000,
                collapseSpeed: 1000,
                multiFolder: false
            });
        });

        function insertJUGallery() {
            var folder = document.getElementById("folder").value;
            var title = document.getElementById("title").value;
            var cssclass = document.getElementById("cssclass").value;

            if (folder !== '') {
                folder = "" + folder + "";
            }

            if (title === '' && cssclass !== '') {
                title = "|";
            } else if (title !== '') {
                title = "|" + title;
            } else {
                title = "";
            }

            if (cssclass !== '') {
                cssclass = "|" + cssclass;
            }

            var tag = "{gallery " + folder + title + cssclass + "}";

            window.parent.Joomla.editors.instances['jform_articletext'].replaceSelection(tag);
            window.parent.Joomla.Modal.getCurrent().close();

            return false;
        }

	</script>
	<style>
        body {
            background: transparent;
        }

        fieldset {
            margin: 5px 0 !important;
        }
	</style>
</head>
<body>
<form class="form-horizontal">
	<div class="control-group">
		<label class="control-label"><?php echo JText::_('PLG_JUMULTITHUMB_GALLERY_SELECT_FOLDER'); ?>:</label>
		<div class="controls">
			<div class="selects"></div>
			<br>
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
			<button onclick="insertJUGallery();" class="btn btn-success">
				<?php echo JText::_('PLG_JUMULTITHUMB_GALLERY_INSERT_TAG'); ?>
			</button>
		</div>
	</div>
</form>
</body>
</html>