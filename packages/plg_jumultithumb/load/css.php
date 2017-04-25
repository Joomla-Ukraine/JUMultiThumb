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
define('JPATH_BASE', __DIR__ ."/../../../..");
define ("MAX_SIZE","500");

require_once ( JPATH_BASE .'/includes/defines.php' );
require_once ( JPATH_BASE .'/includes/framework.php' );
require_once ( JPATH_BASE .'/libraries/joomla/factory.php' );

$mainframe  = JFactory::getApplication('administrator');
$joomlaUser = JFactory::getUser();
$lang       = JFactory::getLanguage();
$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

$csslink 	= '<link href="../../../../administrator/templates/isis/css/template.css" rel="stylesheet" type="text/css" />
<link href="../../../../media/jui/css/bootstrap.css" rel="stylesheet" type="text/css" />';

function alert($text, $error)
{
    if($error == 'message') {
        $error = 'alert-info';
    }

    if($error == 'notice') {
        $error = 'alert-error';
    }

    return '<div class="alert '. $error .'">'. $text .'</div>';
}
?>
<?php if ($joomlaUser->get('id') < 1) : ?>
<!DOCTYPE html>
<html>
    <head>
        <meta content="charset=utf-8" />
        <?php echo $csslink; ?>
    </head>
    <body>
        <?php echo alert(JText::_('PLG_JUMULTITHUMB_LOGIN'), 'notice'); ?>
    </body>
</html>
<?php
    return;
endif;

$style = JPATH_BASE .'/media/plg_jumultithumb/style.css';

$newcss = '/*
-----------------------------------------------
File:    style.css
Author:  Denys Nosov at http://www.denysdesign.com
Version: '. date ('d.m.Y') .'

License: Free
----------------------------------------------- */

.juimage {
  border: #ccc 1px solid;
  padding: 1px;
}
.juimg-category {

}
.juimg-featured {

}
.juimg-article {

}
.juleft {
  float: left;
  margin: 0 6px 6px 0;
}
.juright {
  float: right;
  margin: 0 0 6px 6px;
}
';

if(!file_exists($style))
{
    $file = fopen($style, 'w');
    fputs($file, $newcss);
    fclose($file);
    $notice = alert(JText::_('PLG_JUMULTITHUMB_NOTICE1').'<br>'.JText::_('PLG_JUMULTITHUMB_NOTICE2'), 'notice');
}

if(file_exists($style))
{
	if (!empty( $_POST['txt'] ))
	{
    	$file = fopen($style, 'w');
        fputs($file, $_POST['txt']);
        fclose($file);
        $notice = alert(JText::_('PLG_JUMULTITHUMB_NOTICE3'), 'message');
    }
}

if(filesize($style) < 3)
{
	$file = fopen($style, 'w');
    fputs($file, $newcss);
    fclose($file);
    $notice = alert(JText::_('PLG_JUMULTITHUMB_NOTICE4').'<br>'.JText::_('PLG_JUMULTITHUMB_NOTICE5'), 'notice');
}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta content="charset=utf-8" />
		<?php echo $csslink; ?>
	  	<style>
		body{ background: transparent; font-size: 102%; margin: 0 20px 0 20px;}
		.left {
		  float: left;
		}
		.right {
		  float: right;
		}
	  	</style>
	</head>
	<body>
	    <?php if(isset($notice)) echo $notice; ?>
	    <form action="css.php" method="post">

			<legend>
			    <?php echo JText::_('PLG_JUMULTITHUMB_CSS_FRONT'); ?>
				<button type="submit" class="btn btn-primary right"><i class="icon-apply icon-white"></i> <?php echo JText::_('PLG_JUMULTITHUMB_CSS_SAVE'); ?></button>
			</legend>

	        <textarea name="txt" style="width: 100%; height: 585px; clear: both;" id="css_source"><?php readfile($style);?></textarea>
	    </form>
	</body>
</html>