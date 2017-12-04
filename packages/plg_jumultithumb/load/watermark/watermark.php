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

define('_JEXEC', 1);
define('JPATH_BASE', __DIR__ . "/../../../../..");
define("MAX_SIZE", "500");

require_once(JPATH_BASE . '/includes/defines.php');
require_once(JPATH_BASE . '/includes/framework.php');

$mainframe = JFactory::getApplication('administrator');

$joomlaUser = JFactory::getUser();
$lang       = JFactory::getLanguage();
$lang->load('plg_content_jumultithumb', JPATH_ADMINISTRATOR);

/**
 * @param $text
 * @param $error
 *
 * @return string
 *
 * @since 6.0
 */
function alert($text, $error)
{
	if($error == 'message')
	{
		$error = 'alert-info';
	}

	if($error == 'notice')
	{
		$error = 'alert-error';
	}

	return '<div class="alert ' . $error . '">' . $text . '</div>';
}

/**
 * @param $str
 *
 * @return bool|string
 *
 * @since 6.0
 */
function getExtension($str)
{
	$i = strrpos($str, ".");

	if(!$i) return "";

	$l   = strlen($str) - $i;
	$ext = substr($str, $i + 1, $l);

	return $ext;
}

$csslink = '<link href="../../../../../administrator/templates/isis/css/template.css" rel="stylesheet" type="text/css" /><link href="../../../../../media/jui/css/bootstrap.css" rel="stylesheet" type="text/css" />';

if($joomlaUser->get('id') < 1)
{
	?>
    <!DOCTYPE html>
    <html>
    <head><?php echo $csslink; ?></head>
    <body><?php echo alert(JText::_('PLG_JUMULTITHUMB_LOGIN'), 'notice'); ?></body>
    </html>
	<?php
	return;
}

$errors = 0;
if(isset($_POST['Submit']))
{
	if(isset($_FILES['image']['name']))
	{
		$filename  = stripslashes($_FILES['image']['name']);
		$extension = getExtension($filename);
		$extension = strtolower($extension);

		if(($extension != "png"))
		{
			if(isset($_POST['watermark']) == 'big')
			{
				$unknownext = alert(JText::_('PLG_JUMULTITHUMB_NOTICE6'), 'notice');
			}
            elseif(isset($_POST['watermark']) == 'small')
			{
				$unknownext_s = alert(JText::_('PLG_JUMULTITHUMB_NOTICE6'), 'notice');
			}
			$errors = 1;
		}
		else
		{
			$size = $_FILES['image']['size'];
			if($size > MAX_SIZE * 100024)
			{
				if(isset($_POST['watermark']) == 'big')
				{
					$limitimg = alert(JText::_('PLG_JUMULTITHUMB_NOTICE7'), 'notice');
				}
                elseif(isset($_POST['watermark']) == 'small')
				{
					$limitimg_s = alert(JText::_('PLG_JUMULTITHUMB_NOTICE7'), 'notice');
				}
				$errors = 1;
			}

			if($_POST['watermark'] == 'big')
			{
				$image_name = 'w.png';
			}
            elseif($_POST['watermark'] == 'small')
			{
				$image_name = 'ws.png';
			}

			if(!($size > MAX_SIZE * 100024))
			{
				$newname = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/' . $image_name;

				if(!move_uploaded_file($_FILES['image']['tmp_name'], $newname))
				{
					if(isset($_POST['watermark']) == 'big')
					{
						$uploadunsuccessfull = alert(JText::_('PLG_JUMULTITHUMB_NOTICE8'), 'notice');
					}
                    elseif(isset($_POST['watermark']) == 'small')
					{
						$uploadunsuccessfull_s = alert(JText::_('PLG_JUMULTITHUMB_NOTICE8'), 'notice');
					}
					$errors = 1;
				}
			}
		}
	}
}

if(isset($_POST['Submit']) && !$errors)
{
	if(isset($_POST['watermark']) == 'big')
	{
		$uploadsucess = alert(JText::_('PLG_JUMULTITHUMB_NOTICE9'), 'message');
	}
    elseif(isset($_POST['watermark']) == 'small')
	{
		$uploadsucess_s = alert(JText::_('PLG_JUMULTITHUMB_NOTICE9'), 'message');
	}
}

if(JRequest::getString('del') == 'big')
{
	if(is_file(JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/w.png'))
	{
		unlink('w.png');
		$noticewb = alert(JText::_('PLG_JUMULTITHUMB_NOTICE10'), 'message');
	}
	else
	{
		$noticewb = alert(JText::_('PLG_JUMULTITHUMB_NOTICE11'), 'notice');
	}
}
elseif(JRequest::getString('del') == 'small')
{
	if(is_file(JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/ws.png'))
	{
		unlink('ws.png');
		$noticews = alert(JText::_('PLG_JUMULTITHUMB_NOTICE10'), 'message');
	}
	else
	{
		$noticews = alert(JText::_('PLG_JUMULTITHUMB_NOTICE11'), 'notice');
	}
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="content-type">
	<?php echo $csslink; ?>
    <style>
        body {
            background: transparent;
            font-size: 102%;
            margin: 0 20px 0 20px;
            padding: 0
        }

        fieldset {
            margin: 0 0 5px 0 !important;
        }

        .img {
            margin: 0;
            max-width: 300px;
            padding: 50px;
            background-color: #818181;
            background-image: linear-gradient(45deg, #707070 25%, transparent 25%, transparent 74%, #707070 75%, #707070),
            linear-gradient(45deg, #707070 25%, transparent 25%, transparent 74%, #707070 75%, #707070);
            background-size: 21px 21px;
            background-position: 0 0, 11px 11px;
        }
    </style>
</head>
<body>
<?php
if(isset($noticewb))
{
	echo $noticewb;
}

if(isset($noticews))
{
	echo $noticews;
}

if(isset($uploadsucess))
{
	echo $uploadsucess;
}

if(isset($unknownext))
{
	echo $unknownext;
}

if(isset($limitimg))
{
	echo $limitimg;
}

if(isset($uploadunsuccessfull))
{
	echo $uploadunsuccessfull;
}

if(isset($uploadsucess_s))
{
	echo $uploadsucess_s;
}

if(isset($unknownext_s))
{
	echo $unknownext_s;
}

if(isset($limitimg_s))
{
	echo $limitimg_s;
}

if(isset($uploadunsuccessfull_s))
{
	echo $uploadunsuccessfull_s;
}
?>
<fieldset class="adminform" style="clear: both; float: left; width: 48%;">
    <legend>Watermark for original image</legend>

    <form name="wb" method="post" enctype="multipart/form-data" class="well well-small" action="">
        <input type="file" name="image"/>
        <input name="Submit" type="submit" class="btn btn-primary"
               value="<?php echo JText::_('PLG_JUMULTITHUMB_NOTICE15'); ?>"/>
        <input type="hidden" name="watermark" value="big"/>
    </form>

    <form name="wbdel" method="post" action="">
        <input name="Submit" type="submit" class="btn btn-inverse"
               value="<?php echo JText::_('PLG_JUMULTITHUMB_NOTICE16'); ?>"/>
        <input type="hidden" name="del" value="big"/>
    </form>

    <div style="clear: both; padding-top: 10px;">
		<?php
		if(is_file('w.png'))
		{
			echo '<img src="w.png?v=' . time() . '" alt="" class="img img-polaroid" />';
		}
		else
		{
			echo '<a href="http://denysdesign.com" target="_blank" title="Denys Design Studio"><img src="juw.png?v=' . time() . '" alt="" class="img" /></a>';
		}
		?>
    </div>
</fieldset>

<fieldset class="adminform" style="float: right; width: 48%;">
    <legend>Watermark for thumbnail image</legend>
    <form name="ws" method="post" enctype="multipart/form-data" class="well well-small" action="">
        <input type="file" name="image"/>
        <input name="Submit" type="submit" class="btn btn-primary"
               value="<?php echo JText::_('PLG_JUMULTITHUMB_NOTICE15'); ?>"/>
        <input type="hidden" name="watermark" value="small"/>
    </form>

    <form name="wbdel" method="post" action="">
        <input name="Submit" type="submit" class="btn btn-inverse"
               value="<?php echo JText::_('PLG_JUMULTITHUMB_NOTICE16'); ?>"/>
        <input type="hidden" name="del" value="small"/>
    </form>

    <div style="clear: both; padding-top: 10px;">
		<?php
		if(is_file('ws.png'))
		{
			echo '<img src="ws.png?v=' . time() . '" alt="" class="img img-polaroid" />';
		}
		else
		{
			echo '<a href="http://joomla-ua.org" target="_blank" title="Joomla! Ukraine"><img src="juws.png?v=' . time() . '" alt="" class="img" /></a>';
		}
		?>
    </div>
</fieldset>
</body>
</html>