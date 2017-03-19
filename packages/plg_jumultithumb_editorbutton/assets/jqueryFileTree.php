<?php
//
// jQuery File Tree PHP Connector
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// Output a list of files for jQuery File Tree
//

//error_reporting(0);

if(isset($_SERVER['HTTP_ORIGIN']))
	header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
else
	header('Access-Control-Allow-Origin: *');
if(strtolower($_SERVER['REQUEST_METHOD']) == 'options')
{
	header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
	header('Access-Control-Allow-Headers: X-Requested-With, Accept');
	header('Access-Control-Max-Age: 1728000');
	die();
}

$_POST['dir'] = urldecode($_SERVER['DOCUMENT_ROOT'].'/'.$_POST['dir']);

if( file_exists($_POST['dir']) )
{
	$files = scandir($_POST['dir']);
	natcasesort($files);

	if( count($files) > 2 ) { /* The 2 accounts for . and .. */
		echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
		// All dirs
		foreach( $files as $file ) {
			if( file_exists($_POST['dir'] . $file) && $file != '.' && $file != '..' && is_dir($_POST['dir'] . $file) ) {
				echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities( str_replace($_SERVER['DOCUMENT_ROOT'].'/', '', $_POST['dir']) . $file) . "/\">" . htmlentities($file) . "</a></li>";
			}
		}
		echo "</ul>";
	}

}  