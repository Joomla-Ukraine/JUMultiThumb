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

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

require_once( JPATH_SITE .'/plugins/content/jumultithumb/lib/links.php');
require_once( JPATH_SITE .'/libraries/julib/image.php');

class plgContentjumultithumb extends JPlugin
{
    var $modeHelper;

	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

        $option = JRequest::getCmd('option');
		$adapter = JPATH_SITE .'/plugins/content/jumultithumb/adapters/'. $option .'.php';

		if (JFile::exists($adapter))
        {
			require_once($adapter);
			$mode_option = 'plgContentJUMultiThumb_'. $option;
			$this->modeHelper = new $mode_option($this);
		}
	}

    public function onContentBeforeDisplay($context, &$article, &$params, $limitstart)
	{
        $app = JFactory::getApplication();

		if ($app->getName() != 'site') return true;

        if ( !($this->modeHelper && $this->modeHelper->jView('Component')) ) return;

        if ( $this->modeHelper && $this->modeHelper->jView('Article') ) return;

        $autolinks = new AutoLinks();

        $onlyFirstImage = $this->params->get('Only_For_First_Image');
        $link = $this->modeHelper->jViewLink($article);

        $article->text = @$autolinks->handleImgLinks($article->text, $article->title, $link, $onlyFirstImage);
        $article->introtext = @$autolinks->handleImgLinks($article->introtext, $article->title, $link, $onlyFirstImage);
    }

    public function onContentPrepare($context, &$article, &$params, $limitstart)
	{
        $app = JFactory::getApplication();

		if ($app->getName() != 'site') return true;

        if ( !($this->modeHelper && $this->modeHelper->jView('Component')) ) return;

        if(isset($article->text)) $article->text = @$this->ImgReplace($article->text, $article);

        if(isset($article->fulltext))
        {
		    $attribs = json_decode($article->attribs);
		    $watermark_into_only = $attribs->watermark_intro_only;
            $use_wm = 1;

            if($watermark_into_only == 1) $use_wm = 0;

            $article->fulltext = @$this->ImgReplace($article->fulltext, $article, $use_wm);
        }
    }

    public function ImgReplace($text, &$article, $use_wm = 1)
    {
    	$param 					= $this->params;

		$only_image_blog 		= $param->get('only_image_blog');
		$only_image_category 	= $param->get('only_image_category');
		$only_image_featured 	= $param->get('only_image_featured');

		$attribs                = json_decode($article->attribs);
		$watermark_o 			= $attribs->watermark;
		$watermark_s 			= $attribs->watermark_s;

        if($use_wm == 0) {
		    $watermark_o = '0';
		    $watermark_s = '0';
        }

    	$text = preg_replace('#<img(.*?)mce_src="(.*?)"(.*?)>#s', "<img\\1\\3>", $text);
        $text = preg_replace('#<p>\s*<img(.*?)/>\s*</p>#s', "<img\\1\\3>", $text);
        $text = preg_replace('#<p>\s*<img(.*?)/>\s*#s', "<img\\1\\3><p>", $text);

		preg_match_all('/<img[^>]+>/i', $text, $imageAttr);
		if (count(array_filter($imageAttr[0])) > 0)
        {
			foreach ($imageAttr[0] as $image) {
				$replace = $this->JUMultithumbReplacer($image, $article, $watermark_o, $watermark_s);
				$text = str_replace($image, $replace, $text);
			}
		}

        if(
        	($only_image_blog == '1' && $this->modeHelper && $this->modeHelper->jView('Blog')) ||
            ($only_image_category == '1' && $this->modeHelper && $this->modeHelper->jView('Category')) ||
            ($only_image_featured == '1' && $this->modeHelper && $this->modeHelper->jView('Featured'))
        ) {
        	preg_match_all('/(<\s*img\s+src\s*="\s*("[^"]*"|\'[^\']*\'|[^"\s]+).*?>)/i', $text, $result);
            $img = $result[1][0];
            $text = $img;
        }

		return $text;
    }

    public function JUMultithumbReplacer($_img, &$article, $watermark_o, $watermark_s)
    {
    	$JUImg 				= new JUImg();
		$Itemid 			= JRequest::getString('Itemid');
    	$param 				= $this->params;

		// params
		$quality 			= $param->get( 'quality' );
		$noimage_class 		= $param->def('noimage_class');
        $thumb_filtercolor  = intval($param->get( 'thumb_filtercolor', 0 ));
        $colorized          = $param->get( 'colorized', '25' );
        $colorpicker        = $param->get( 'colorpicker', '#0000ff' );
        $thumb_th           = $param->get( 'thumb_th', 0 );
        $thumb_th_seting    = $param->get( 'thumb_th_seting', 0 );
        $thumb_filters      = $param->get( 'thumb_filters', 1 );
        $usm                = $param->get( 'thumb_unsharp', 1 );
        $thumb_unsharp_amount = $param->get( 'thumb_unsharp_amount', 80 );
        $thumb_unsharp_radius = $param->get( 'thumb_unsharp_radius', 1 );
        $thumb_unsharp_threshold = $param->get( 'thumb_unsharp_threshold', 3 );
        $thumb_blur         = $param->get( 'thumb_blur', 0 );
        $thumb_blur_seting  = $param->get( 'thumb_blur_seting', 1 );
        $thumb_brit         = $param->get( 'thumb_brit', 0 );
        $thumb_brit_seting  = $param->get( 'thumb_brit_seting', 50 );
        $thumb_cont         = $param->get( 'thumb_cont', 0 );
        $thumb_cont_seting  = $param->get( 'thumb_cont_seting', 50 );

   		switch ($thumb_filtercolor)
		{
   			case '1':
                $imp_filtercolor = array('fltr_1' => 'gray');
                break;
   			case '2':
                $imp_filtercolor = array('fltr_1' => 'sep');
                break;
   			case '3':
                $imp_filtercolor = array('fltr_1' => 'th|'. $thumb_th_seting);
                break;
   			case '4':
                $imp_filtercolor = array('fltr_1' => 'clr|'. $colorized .'|'. str_replace('#', '', $colorpicker));
                break;
   			default:
                $imp_filtercolor = array();
                break;
   		}

        $usm_filtercolor = array();
       	if($usm == '1' && $thumb_filters == '1') {
       		$usm_filtercolor = array('fltr_2' => 'usm|'. $thumb_unsharp_amount .'|'. $thumb_unsharp_radius .'|'. $thumb_unsharp_threshold );
        }

        $blur_filtercolor = array();
		if($thumb_blur == '1' && $thumb_filters == '1') {
      		$blur_filtercolor = array('fltr_3' => 'blur|'. $thumb_blur_seting );
        }

        $brit_filtercolor = array();
        if($thumb_brit == '1' && $thumb_filters == '1') {
       		$brit_filtercolor = array('fltr_4' => 'brit|'. $thumb_brit_seting );
        }

        $cont_filtercolor = array();
		if($thumb_cont == '1' && $thumb_filters == '1') {
       		$cont_filtercolor = array('fltr_5' => 'cont|'. $thumb_cont_seting  );
        }

		// image replacer
        $lightbox = $param->get('selectlightbox');

		preg_match_all('/(width|height|src|alt|title|class|align|style)=("[^"]*")/i', $_img, $imgAttr);

		$countAttr 	= count($imgAttr[0]);
		$img 		= array();
		for ($i=0; $i < $countAttr; $i++)
        {
			$img[$imgAttr[1][$i]] = str_replace('"', '', $imgAttr[2][$i]);
		}

        $imgsource 	= $img['src'];
        $imgsource 	= str_replace(JURI::base(), '', $imgsource);
        $originalsource = $imgsource;

        $imgalt 	= $img['alt'];
        $imgtitle 	= $img['title'];
        $imgalign 	= $img['align'];
        $imgclass 	= $img['class'] .' ';
        $cssclass 	= $img['class'];

        if(preg_match('#float:(.*?);#s', $img['style'], $imgstyle)) {
            $imgstyle = $imgstyle[1];
        }

		$img_class = '';
        if($imgalign != '') {
            $img_class = 'ju'. trim($imgalign) .' ';
        }
		elseif($imgstyle != ''){
            $img_class = 'ju'. trim($imgstyle) .' ';
        }

        // attributes
        $img_class 	= 'juimage '. $imgclass . $img_class . 'juimg-'. JRequest::getString('view');

		$imgalt 	= mb_strtoupper(mb_substr($imgalt, 0, 1)) . mb_substr($imgalt, 1);
        $img_alt 	= $imgalt;

		$imgtitle 	= mb_strtoupper(mb_substr($imgtitle, 0, 1)) . mb_substr($imgtitle, 1);
        $img_title 	= ($imgalt ? $imgalt : $imgtitle);
		$img_title 	= ($img_title ? $img_title : $article->title);
		$img_title 	= ($img_title ? ' title="'. $img_title .'"' : '');

		$_image_noresize = 0;
		if($param->get('resall') == '0' && $img['class'] != "juimage")
		{
			$size = getimagesize(JPATH_SITE .'/'. $originalsource);
            $limage = $this->_image($originalsource, $size[0], $size[1], $imgclass, $img_alt, 1, 1, $img_title);

            return $limage;
        }

		if(
			$param->get('resall') == '1' &&
			(
				$img['class'] == "noimage" ||
				$img['class'] == "nothumbnail" ||
				$img['class'] == "jugallery" ||
				$img['class'] == $noimage_class
			) &&
			$img['class'] != ''
		) {
			if($param->get('a_watermark') == '0' || $watermark_o != '1')
			{
				$size = getimagesize(JPATH_SITE .'/'. $originalsource);
	            $limage = $this->_image($originalsource, $size[0], $size[1], $img_class, $img_alt, 1, 1, $img_title);

	            return $limage;
			}
            else {
				$_image_noresize = 1;
			}
        }

        if($this->modeHelper && $this->modeHelper->jView('CatBlog'))
        {
            if(in_array($Itemid, ($param->get('menu_item1')) ? $param->get('menu_item1') : array()))
            {
                $b_newwidth       = $param->get('b_widthnew1');
                $b_newheight      = $param->get('b_heightnew1');
                $b_newcropzoom    = $param->get('b_cropzoomnew1');
                $b_newzoomcrop_params = $param->get('b_zoomcrop_paramsnew1');
                $b_newauto_zoomcrop = $param->get('b_auto_zoomcropnew1');
                $b_newcropaspect  = $param->get('b_cropaspectnew1');
                $b_newzoomcropbg  = $param->get('b_zoomcropbgnew1');
                $b_newfarcrop     = $param->get('b_farcropnew1');
                $b_newfarcrop_params = $param->get('b_farcrop_paramsnew1');
                $b_newfarcropbg   = $param->get('b_farcropbgnew1');
                $b_aoenew         = $param->get('b_aoenew1');
                $b_sxnew          = $param->def('b_sxnew1');
                $b_synew          = $param->def('b_synew1');
            }
			elseif(in_array($Itemid, ($param->get('menu_item2')) ? $param->get('menu_item2') : array() ))
            {
                $b_newwidth       = $param->get('b_widthnew2');
                $b_newheight      = $param->get('b_heightnew2');
                $b_newcropzoom    = $param->get('b_cropzoomnew2');
                $b_newzoomcrop_params = $param->get('b_zoomcrop_paramsnew2');
                $b_newauto_zoomcrop = $param->get('b_auto_zoomcropnew2');
                $b_newcropaspect  = $param->get('b_cropaspectnew2');
                $b_newzoomcropbg  = $param->get('b_zoomcropbgnew2');
                $b_newfarcrop     = $param->get('b_farcropnew2');
                $b_newfarcrop_params = $param->get('b_farcrop_paramsnew2');
                $b_newfarcropbg   = $param->get('b_farcropbgnew2');
                $b_aoenew         = $param->get('b_aoenew2');
                $b_sxnew          = $param->def('b_sxnew2');
                $b_synew          = $param->def('b_synew2');
            }
			elseif(in_array($Itemid, ($param->get('menu_item3')) ? $param->get('menu_item3') : array() ))
            {
                $b_newwidth       = $param->get('b_widthnew3');
                $b_newheight      = $param->get('b_heightnew3');
                $b_newcropzoom    = $param->get('b_cropzoomnew3');
                $b_newzoomcrop_params = $param->get('b_zoomcrop_paramsnew3');
                $b_newauto_zoomcrop = $param->get('b_auto_zoomcropnew3');
                $b_newcropaspect  = $param->get('b_cropaspectnew3');
                $b_newzoomcropbg  = $param->get('b_zoomcropbgnew3');
                $b_newfarcrop     = $param->get('b_farcropnew3');
                $b_newfarcrop_params = $param->get('b_farcrop_paramsnew3');
                $b_newfarcropbg   = $param->get('b_farcropbgnew3');
                $b_aoenew         = $param->get('b_aoenew3');
                $b_sxnew          = $param->def('b_sxnew3');
                $b_synew          = $param->def('b_synew3');
            }
			elseif(in_array($Itemid, ($param->get('menu_item4')) ? $param->get('menu_item4') : array() ))
            {
                $b_newwidth       = $param->get('b_widthnew4');
                $b_newheight      = $param->get('b_heightnew4');
                $b_newcropzoom    = $param->get('b_cropzoomnew4');
                $b_newzoomcrop_params = $param->get('b_zoomcrop_paramsnew4');
                $b_newauto_zoomcrop = $param->get('b_auto_zoomcropnew4');
                $b_newcropaspect  = $param->get('b_cropaspectnew4');
                $b_newzoomcropbg  = $param->get('b_zoomcropbgnew4');
                $b_newfarcrop     = $param->get('b_farcropnew4');
                $b_newfarcrop_params = $param->get('b_farcrop_paramsnew4');
                $b_newfarcropbg   = $param->get('b_farcropbgnew4');
                $b_aoenew         = $param->get('b_aoenew4');
                $b_sxnew          = $param->def('b_sxnew4');
                $b_synew          = $param->def('b_synew4');
            }
			elseif(in_array($Itemid, ($param->get('menu_item5')) ? $param->get('menu_item5') : array() ))
            {
                $b_newwidth       = $param->get('b_widthnew5');
                $b_newheight      = $param->get('b_heightnew5');
                $b_newcropzoom    = $param->get('b_cropzoomnew5');
                $b_newzoomcrop_params = $param->get('b_zoomcrop_paramsnew5');
                $b_newauto_zoomcrop = $param->get('b_auto_zoomcropnew5');
                $b_newcropaspect  = $param->get('b_cropaspectnew5');
                $b_newzoomcropbg  = $param->get('b_zoomcropbgnew5');
                $b_newfarcrop     = $param->get('b_farcropnew5');
                $b_newfarcrop_params = $param->get('b_farcrop_paramsnew5');
                $b_newfarcropbg   = $param->get('b_farcropbgnew5');
                $b_aoenew         = $param->get('b_aoenew5');
                $b_sxnew          = $param->def('b_sxnew5');
                $b_synew          = $param->def('b_synew5');
            }
			else {
                $b_newwidth       = $param->get('b_width');
                $b_newheight      = $param->get('b_height');
                $b_newcropzoom    = $param->get('b_cropzoom');
                $b_newzoomcrop_params = $param->get('b_zoomcrop_params');
                $b_newauto_zoomcrop = $param->get('b_auto_zoomcrop');
                $b_newcropaspect  = $param->get('b_cropaspect');
                $b_newzoomcropbg  = $param->get('b_zoomcropbg');
                $b_newfarcrop     = $param->get('b_farcrop');
                $b_newfarcrop_params = $param->get('b_farcrop_params');
                $b_newfarcropbg   = $param->get('b_farcropbg');
                $b_aoenew         = $param->get('b_aoe');
                $b_sxnew          = $param->def('b_sx');
                $b_synew          = $param->def('b_sy');
            }

			$aspect = 0;
			if($b_newauto_zoomcrop == '1') {
			    $aspect = $this->_aspect($imgsource, $b_newcropaspect);
			}

			if ($aspect >= '1' && $b_newauto_zoomcrop == '1')
            {
				$new_imgparams = array(
				    'far' => '1',
					'bg'   => str_replace('#', '', $b_newfarcropbg)
				);
			}
            else {
				$new_imgparams = array(
					'zc' => ($b_newcropzoom == 1 ? $b_newzoomcrop_params : '')
				);
			}

			if ($b_newfarcrop == '1')
            {
				$new_imgparams = array(
			  		'far' => $b_newfarcrop_params,
					'bg'   => str_replace('#', '', $b_newfarcropbg)
				);
			}

            $imgparams = array(
            	'w'     => $b_newwidth,
                'h'     => $b_newheight,
                'aoe'   => $b_aoenew,
                'sx'   	=> $b_sxnew,
                'sy'   	=> $b_synew,
                'q'     => $quality,
                'cache' => 'img'
            );

			$_imgparams = array_merge(
			    $imp_filtercolor,
				$usm_filtercolor,
				$blur_filtercolor,
				$brit_filtercolor,
				$cont_filtercolor,
				$imgparams,
                $new_imgparams
			);

			$thumb_img = $JUImg->Render($imgsource, $_imgparams);

			$limage = $this->_image($thumb_img, $b_newwidth, $b_newheight, $img_class, $img_alt, 0, 0);
        }
		elseif (
            $this->modeHelper && $this->modeHelper->jView('Article') ||
            $this->modeHelper && $this->modeHelper->jView('Categories') ||
            $this->modeHelper && $this->modeHelper->jView('Category')
        ) {
            if( $this->modeHelper && $this->modeHelper->jView('Article') )
            {
                if(in_array($Itemid, ($param->get('menu_item1')) ? $param->get('menu_item1') : array() ))
                {
                    $newmaxwidth    = $param->get('maxwidthnew1');
                    $newmaxheight   = $param->get('maxheightnew1');
                    $newwidth       = $param->get('widthnew1');
                    $newheight      = $param->get('heightnew1');
                    $newcropzoom    = $param->get('cropzoomnew1');
                    $newzoomcrop_params = $param->get('zoomcrop_paramsnew1');
                    $newauto_zoomcrop = $param->get('auto_zoomcropnew1');
                    $newcropaspect  = $param->get('cropaspectnew1');
                    $newzoomcropbg  = $param->get('zoomcropbgnew1');
                    $newfarcrop     = $param->get('farcropnew1');
                    $newfarcrop_params = $param->get('farcrop_paramsnew1');
                    $newfarcropbg   = $param->get('farcropbgnew1');
                    $newaoe         = $param->get('aoenew1');
                    $newsx          = $param->def('sxnew1');
                    $newsy          = $param->def('synew1');
                    $newnoresize    = $param->get('noresizenew1');
                    $newnofullimg   = $param->get('nofullimgnew1');
                }
				elseif(in_array($Itemid, ($param->get('menu_item2')) ? $param->get('menu_item2') : array() ))
                {
                    $newmaxwidth    = $param->get('maxwidthnew2');
                    $newmaxheight   = $param->get('maxheightnew2');
                    $newwidth       = $param->get('widthnew2');
                    $newheight      = $param->get('heightnew2');
                    $newcropzoom    = $param->get('cropzoomnew2');
                    $newzoomcrop_params = $param->get('zoomcrop_paramsnew2');
                    $newauto_zoomcrop = $param->get('auto_zoomcropnew2');
                    $newcropaspect  = $param->get('cropaspectnew2');
                    $newzoomcropbg  = $param->get('zoomcropbgnew2');
                    $newfarcrop     = $param->get('farcropnew2');
                    $newfarcrop_params = $param->get('farcrop_paramsnew2');
                    $newfarcropbg   = $param->get('farcropbgnew2');
                    $newaoe         = $param->get('aoenew2');
                    $newsx          = $param->def('sxnew2');
                    $newsy          = $param->def('synew2');
                    $newnoresize    = $param->get('noresizenew2');
                    $newnofullimg   = $param->get('nofullimgnew2');
                }
				elseif(in_array($Itemid, ($param->get('menu_item3')) ? $param->get('menu_item3') : array() ))
                {
                    $newmaxwidth    = $param->get('maxwidthnew3');
                    $newmaxheight   = $param->get('maxheightnew3');
                    $newwidth       = $param->get('widthnew3');
                    $newheight      = $param->get('heightnew3');
                    $newcropzoom    = $param->get('cropzoomnew3');
                    $newzoomcrop_params = $param->get('zoomcrop_paramsnew3');
                    $newauto_zoomcrop = $param->get('auto_zoomcropnew3');
                    $newcropaspect  = $param->get('cropaspectnew3');
                    $newzoomcropbg  = $param->get('zoomcropbgnew3');
                    $newfarcrop     = $param->get('farcropnew3');
                    $newfarcrop_params = $param->get('farcrop_paramsnew3');
                    $newfarcropbg   = $param->get('farcropbgnew3');
                    $newaoe         = $param->get('aoenew3');
                    $newsx          = $param->def('sxnew3');
                    $newsy          = $param->def('synew3');
                    $newnoresize    = $param->get('noresizenew3');
                    $newnofullimg   = $param->get('nofullimgnew3');
                }
				elseif(in_array($Itemid, ($param->get('menu_item4')) ? $param->get('menu_item4') : array() ))
                {
                    $newmaxwidth    = $param->get('maxwidthnew4');
                    $newmaxheight   = $param->get('maxheightnew4');
                    $newwidth       = $param->get('widthnew4');
                    $newheight      = $param->get('heightnew4');
                    $newcropzoom    = $param->get('cropzoomnew4');
                    $newzoomcrop_params = $param->get('zoomcrop_paramsnew4');
                    $newauto_zoomcrop = $param->get('auto_zoomcropnew4');
                    $newcropaspect  = $param->get('cropaspectnew4');
                    $newzoomcropbg  = $param->get('zoomcropbgnew4');
                    $newfarcrop     = $param->get('farcropnew4');
                    $newfarcrop_params = $param->get('farcrop_paramsnew4');
                    $newfarcropbg   = $param->get('farcropbgnew4');
                    $newaoe         = $param->get('aoenew4');
                    $newsx          = $param->def('sxnew4');
                    $newsy          = $param->def('synew4');
                    $newnoresize    = $param->get('noresizenew4');
                    $newnofullimg   = $param->get('nofullimgnew4');
                }
				elseif(in_array($Itemid, ($param->get('menu_item5')) ? $param->get('menu_item5') : array() ))
                {
                    $newmaxwidth    = $param->get('maxwidthnew5');
                    $newmaxheight   = $param->get('maxheightnew5');
                    $newwidth       = $param->get('widthnew5');
                    $newheight      = $param->get('heightnew5');
                    $newcropzoom    = $param->get('cropzoomnew5');
                    $newzoomcrop_params = $param->get('zoomcrop_paramsnew5');
                    $newauto_zoomcrop = $param->get('auto_zoomcropnew5');
                    $newcropaspect  = $param->get('cropaspectnew5');
                    $newzoomcropbg  = $param->get('zoomcropbgnew5');
                    $newfarcrop     = $param->get('farcropnew5');
                    $newfarcrop_params = $param->get('farcrop_paramsnew5');
                    $newfarcropbg   = $param->get('farcropbgnew5');
                    $newaoe         = $param->get('aoenew5');
                    $newsx          = $param->def('sxnew5');
                    $newsy          = $param->def('synew5');
                    $newnoresize    = $param->get('noresizenew5');
                    $newnofullimg   = $param->get('nofullimgnew5');
                }
				else {
                    $newmaxwidth    = $param->get('maxwidth');
                    $newmaxheight   = $param->get('maxheight');
                    $newwidth       = $param->get('width');
                    $newheight      = $param->get('height');
                    $newcropzoom    = $param->get('cropzoom');
                    $newzoomcrop_params = $param->get('zoomcrop_params');
                    $newauto_zoomcrop = $param->get('auto_zoomcrop');
                    $newcropaspect  = $param->get('cropaspect');
                    $newzoomcropbg  = $param->get('zoomcropbg');
                    $newfarcrop     = $param->get('farcrop');
                    $newfarcrop_params = $param->get('farcrop_params');
                    $newfarcropbg   = $param->get('farcropbg');
                    $newaoe         = $param->get('aoe');
                    $newsx          = $param->def('sx');
                    $newsy          = $param->def('sy');
                    $newnoresize    = $param->get('noresize');
                    $newnofullimg   = $param->get('nofullimg');
                }
            }
			elseif(
				$this->modeHelper && $this->modeHelper->jView('Categories') ||
				$this->modeHelper && $this->modeHelper->jView('Category')
			) {
                if(in_array($Itemid, ($param->get('cat_menu_item1')) ? $param->get('cat_menu_item1') : array() ))
                {
                    $newmaxwidth    = $param->get('cat_maxwidthnew1');
                    $newmaxheight   = $param->get('cat_maxheightnew1');
                    $newwidth       = $param->get('cat_widthnew1');
                    $newheight      = $param->get('cat_heightnew1');
                    $newcropzoom    = $param->get('cat_cropzoomnew1');
                    $newzoomcrop_params = $param->get('cat_zoomcrop_paramsnew1');
                    $newauto_zoomcrop = $param->get('cat_auto_zoomcropnew1');
                    $newcropaspect  = $param->get('cat_cropaspectnew1');
                    $newzoomcropbg  = $param->get('cat_zoomcropbgnew1');
                    $newfarcrop     = $param->get('cat_farcropnew1');
                    $newfarcrop_params = $param->get('cat_farcrop_paramsnew1');
                    $newfarcropbg   = $param->get('cat_farcropbgnew1');
                    $newaoe         = $param->get('cat_aoenew1');
                    $newsx          = $param->def('cat_sxnew1');
                    $newsy          = $param->def('cat_synew1');
                    $newnoresize    = $param->get('cat_noresizenew1');
                    $newnofullimg   = $param->get('cat_nofullimgnew1');
                }
				elseif(in_array($Itemid, ($param->get('cat_menu_item2')) ? $param->get('cat_menu_item2') : array() ))
                {
                    $newmaxwidth    = $param->get('cat_maxwidthnew2');
                    $newmaxheight   = $param->get('cat_maxheightnew2');
                    $newwidth       = $param->get('cat_widthnew2');
                    $newheight      = $param->get('cat_heightnew2');
                    $newcropzoom    = $param->get('cat_cropzoomnew2');
                    $newzoomcrop_params = $param->get('cat_zoomcrop_paramsnew2');
                    $newauto_zoomcrop = $param->get('cat_auto_zoomcropnew2');
                    $newcropaspect  = $param->get('cat_cropaspectnew2');
                    $newzoomcropbg  = $param->get('cat_zoomcropbgnew2');
                    $newfarcrop     = $param->get('cat_farcropnew2');
                    $newfarcrop_params = $param->get('cat_farcrop_paramsnew2');
                    $newfarcropbg   = $param->get('cat_farcropbgnew2');
                    $newaoe         = $param->get('cat_aoenew2');
                    $newsx          = $param->def('cat_sxnew2');
                    $newsy          = $param->def('cat_synew2');
                    $newnoresize    = $param->get('cat_noresizenew2');
                    $newnofullimg   = $param->get('cat_nofullimgnew2');
                }
				elseif(in_array($Itemid, ($param->get('cat_menu_item3')) ? $param->get('cat_menu_item3') : array() ))
                {
                    $newmaxwidth    = $param->get('cat_maxwidthnew3');
                    $newmaxheight   = $param->get('cat_maxheightnew3');
                    $newwidth       = $param->get('cat_widthnew3');
                    $newheight      = $param->get('cat_heightnew3');
                    $newcropzoom    = $param->get('cat_cropzoomnew3');
                    $newzoomcrop_params = $param->get('cat_zoomcrop_paramsnew3');
                    $newauto_zoomcrop = $param->get('cat_auto_zoomcropnew3');
                    $newcropaspect  = $param->get('cat_cropaspectnew3');
                    $newzoomcropbg  = $param->get('cat_zoomcropbgnew3');
                    $newfarcrop     = $param->get('cat_farcropnew3');
                    $newfarcrop_params = $param->get('cat_farcrop_paramsnew3');
                    $newfarcropbg   = $param->get('cat_farcropbgnew3');
                    $newaoe         = $param->get('cat_aoenew3');
                    $newsx          = $param->def('cat_sxnew3');
                    $newsy          = $param->def('cat_synew3');
                    $newnoresize    = $param->get('cat_noresizenew3');
                    $newnofullimg   = $param->get('cat_nofullimgnew3');
                }
				elseif(in_array($Itemid, ($param->get('cat_menu_item4')) ? $param->get('cat_menu_item4') : array() ))
                {
                    $newmaxwidth    = $param->get('cat_maxwidthnew4');
                    $newmaxheight   = $param->get('cat_maxheightnew4');
                    $newwidth       = $param->get('cat_widthnew4');
                    $newheight      = $param->get('cat_heightnew4');
                    $newcropzoom    = $param->get('cat_cropzoomnew4');
                    $newzoomcrop_params = $param->get('cat_zoomcrop_paramsnew4');
                    $newauto_zoomcrop = $param->get('cat_auto_zoomcropnew4');
                    $newcropaspect  = $param->get('cat_cropaspectnew4');
                    $newzoomcropbg  = $param->get('cat_zoomcropbgnew4');
                    $newfarcrop     = $param->get('cat_farcropnew4');
                    $newfarcrop_params = $param->get('cat_farcrop_paramsnew4');
                    $newfarcropbg   = $param->get('cat_farcropbgnew4');
                    $newaoe         = $param->get('cat_aoenew4');
                    $newsx          = $param->def('cat_sxnew4');
                    $newsy          = $param->def('cat_synew4');
                    $newnoresize    = $param->get('cat_noresizenew4');
                    $newnofullimg   = $param->get('cat_nofullimgnew4');
                }
				elseif(in_array($Itemid, ($param->get('cat_menu_item5')) ? $param->get('cat_menu_item5') : array() ))
                {
                    $newmaxwidth    = $param->get('cat_maxwidthnew5');
                    $newmaxheight   = $param->get('cat_maxheightnew5');
                    $newwidth       = $param->get('cat_widthnew5');
                    $newheight      = $param->get('cat_heightnew5');
                    $newcropzoom    = $param->get('cat_cropzoomnew5');
                    $newzoomcrop_params = $param->get('cat_zoomcrop_paramsnew5');
                    $newauto_zoomcrop = $param->get('cat_auto_zoomcropnew5');
                    $newcropaspect  = $param->get('cat_cropaspectnew5');
                    $newzoomcropbg  = $param->get('cat_zoomcropbgnew5');
                    $newfarcrop     = $param->get('cat_farcropnew5');
                    $newfarcrop_params = $param->get('cat_farcrop_paramsnew5');
                    $newfarcropbg   = $param->get('cat_farcropbgnew5');
                    $newaoe         = $param->get('cat_aoenew5');
                    $newsx          = $param->def('cat_sxnew5');
                    $newsy          = $param->def('cat_synew5');
                    $newnoresize    = $param->get('cat_noresizenew5');
                    $newnofullimg   = $param->get('cat_nofullimgnew5');
                }
				else {
                    $newmaxwidth    = $param->get('cat_maxwidth');
                    $newmaxheight   = $param->get('cat_maxheight');
                    $newwidth       = $param->get('cat_width');
                    $newheight      = $param->get('cat_height');
                    $newcropzoom    = $param->get('cat_cropzoom');
                    $newzoomcrop_params = $param->get('cat_zoomcrop_params');
                    $newauto_zoomcrop = $param->get('cat_auto_zoomcrop');
                    $newcropaspect  = $param->get('cat_cropaspect');
                    $newzoomcropbg  = $param->get('cat_zoomcropbg');
                    $newfarcrop     = $param->get('cat_farcrop');
                    $newfarcrop_params = $param->get('cat_farcrop_params');
                    $newfarcropbg   = $param->get('cat_farcropbg');
                    $newaoe         = $param->get('cat_aoe');
                    $newsx          = $param->def('cat_sx');
                    $newsy          = $param->def('cat_sy');
                    $newnoresize    = $param->get('cat_noresize');
                    $newnofullimg   = $param->get('cat_nofullimg');
                }
            }

            if( $newnoresize == '1' || $cat_newnoresize == '1' )
			{
                $juimgresmatche = str_replace(array(' /', JURI::base()), '', $juimgresmatche);
				$limage 		= $this->_image(JURI::base() . $juimgresmatche, $newmaxwidth, $newmaxheight, $img_class, $img_alt, 1, 1);

                return $limage;
            }

            // Watermark
            $wmi = '';
            if(
				$watermark_o == '1' ||
				$_image_noresize == '1' ||
				$param->get('a_watermark') == '1' ||
				$param->get('a_watermarknew1') == '1' ||
				$param->get('a_watermarknew2') == '1' ||
				$param->get('a_watermarknew3') == '1' ||
				$param->get('a_watermarknew4') == '1' ||
				$param->get('a_watermarknew5') == '1'
			) {
                $wmfile = JPATH_SITE.'/plugins/content/jumultithumb/load/watermark/w.png';
                if(is_file($wmfile)){
                    $watermark = $wmfile;
                }
                else {
                    $wmfile = JPATH_SITE.'/plugins/content/jumultithumb/load/watermark/juw.png';
                    $watermark = $wmfile;
                }
				$wmi = 'wmi|'. $watermark .'|'. $param->get('wmposition') . '|'. $param->get('wmopst') . '|'. $param->get('wmx') . '|'. $param->get('wmy');
            }

	        $_width 	= '';
	        $_height 	= '';
			if(
				$param->get('maxsize_orig') == '1' ||
				$param->get('cat_newmaxsize_orig') == '1'
			) {
	        	if( $this->modeHelper && $this->modeHelper->jView('Article') )
				{
	            	$_width 	= $newmaxwidth;
	                $_height 	= $newmaxheight;
                }
                else {
	                $_width 	= $cat_newmaxwidth;
	                $_height 	= $cat_newmaxheight;
	            }
            }

            if(
				$watermark_o == '1' ||
				$_image_noresize == '1' ||
				$param->get('a_watermark') == '1' ||
				$param->get('a_watermarknew1') == '1' ||
				$param->get('a_watermarknew2') == '1' ||
				$param->get('a_watermarknew3') == '1' ||
				$param->get('a_watermarknew4') == '1' ||
				$param->get('a_watermarknew5') == '1' ||

				$param->get('maxsize_orig') == '1' ||
				$param->get('cat_newmaxsize_orig') == '1'
			) {
	            $link_imgparams = array(
	                'w'     => $_width,
	                'h'     => $_height,
	                'aoe'   => $newaoe,
					'fltr'	=> ($wmi != '' ? $wmi : ''),
	                'q'     => $quality,
	                'cache' => 'img'
	            );

				$_link_imgparams = array_merge(
				    $imp_filtercolor,
					$usm_filtercolor,
					$blur_filtercolor,
					$brit_filtercolor,
					$cont_filtercolor,
					$link_imgparams
				);

				$link_img = $JUImg->Render($imgsource, $_link_imgparams);
            }
            else {
            	$link_img = $imgsource;
            }

            // Small watermark
            $wmi_s = '';
            if(
				$watermark_s == '1' ||
				$param->get('a_watermark_s') == '1' ||
				$param->get('a_watermarknew1_s') == '1' ||
				$param->get('a_watermarknew2_s') == '1' ||
				$param->get('a_watermarknew3_s') == '1' ||
				$param->get('a_watermarknew4_s') == '1' ||
				$param->get('a_watermarknew5_s') == '1'
			) {
                $wmfile = JPATH_SITE .'/plugins/content/jumultithumb/load/watermark/ws.png';
                if(is_file($wmfile)){
                    $watermark_s = $wmfile;
                }
                else {
                    $wmfile = JPATH_SITE .'/plugins/content/jumultithumb/load/watermark/juws.png';
                    $watermark_s = $wmfile;
                }
				$wmi_s = 'wmi|'. $watermark_s .'|'. $param->get('wmposition_s') . '|'. $param->get('wmopst_s') . '|'. $param->get('wmx_s') . '|'. $param->get('wmy_s');
            }

			if($_image_noresize == '1') {
				$wmi_s 		= $wmi;
				$newwidth 	= ($_width && $wmi ? $_width : '');
				$newheight 	= ($_height && $wmi ? $_height : '');
				$newaoe 	= '';
				$newsx 		= '';
				$newsy 		= '';
				$newcropzoom = '0';
			}

			$aspect = 0;
			if($newauto_zoomcrop == '1') {
			    $aspect = $this->_aspect($imgsource, $newcropaspect);
			}

			if ($aspect >= '1' && $newauto_zoomcrop == '1')
            {
				$new_imgparams = array(
				    'far' => '1',
					'bg'   => str_replace('#', '', $newfarcropbg)
				);
			}
            else {
				$new_imgparams = array(
					'zc' => ($newcropzoom == 1 ? $newzoomcrop_params : '')
				);
			}

			if ($newfarcrop == '1')
            {
				$new_imgparams = array(
			        'far' => $newfarcrop_params,
					'bg'   => str_replace('#', '', $newfarcropbg)
			    );
			}

            $imgparams = array(
            	'w'     => $newwidth,
                'h'     => $newheight,
	            'aoe'   => $newaoe,
	            'sx'   	=> $newsx,
	            'sy'   	=> $newsy,
				'fltr'	=> ($wmi_s != '' ? $wmi_s : ''),
                'q'     => $quality,
                'cache' => 'img'
            );

			$_imgparams = array_merge(
				$imp_filtercolor,
				$usm_filtercolor,
				$blur_filtercolor,
				$brit_filtercolor,
				$cont_filtercolor,
				$imgparams,
                $new_imgparams
			);

			$thumb_img = $JUImg->Render($imgsource, $_imgparams);

            if($_image_noresize == '1' || $cat_newnofullimg == '1' || $newnofullimg == '1' ||
                $cat_newnofullimg == '1' || ($this->modeHelper && $this->modeHelper->jView('Print'))
            ){
   		        $limage = $this->_image($thumb_img, $newwidth, $newheight, $img_class, $img_alt, 1, $_image_noresize, $img_title);
            }
            else {
				$limage = $this->_image($thumb_img, $newwidth, $newheight, 'imgobjct '. $img_class, $img_alt, 1, 0, $img_title, $link_img, $imgsource, $lightbox);
            }
        }
		elseif($this->modeHelper && $this->modeHelper->jView('Featured'))
        {
            $f_newwidth       = $param->get('f_width');
            $f_newheight      = $param->get('f_height');
            $f_newcropzoom    = $param->get('f_cropzoom');
            $f_newzoomcrop_params = $param->get('f_zoomcrop_params');
            $f_newauto_zoomcrop = $param->get('f_auto_zoomcrop');
            $f_newcropaspect  = $param->get('f_cropaspect');
            $f_newzoomcropbg  = $param->get('f_zoomcropbg');
            $f_newfarcrop     = $param->get('f_farcrop');
            $f_newfarcrop_params = $param->get('f_farcrop_params');
            $f_newfarcropbg   = $param->get('f_farcropbg');
            $f_aoenew         = $param->get('f_aoe');
            $f_sxnew          = $param->def('f_sx');
            $f_synew          = $param->def('f_sy');

			$aspect = 0;
			if($f_newauto_zoomcrop == '1') {
			    $aspect = $this->_aspect($imgsource, $f_newcropaspect);
			}

			if ($aspect >= '1' && $f_newauto_zoomcrop == '1')
            {
				$new_imgparams = array(
				    'far' => '1',
					'bg'   => str_replace('#', '', $f_newfarcropbg)
				);
			}
            else {
				$new_imgparams = array(
					'zc' => ($f_newcropzoom == 1 ? $f_newzoomcrop_params : '')
				);
			}

			if ($b_newfarcrop == '1')
            {
				$new_imgparams = array(
			    	'far' => $f_newfarcrop_params,
				    'bg'   => str_replace('#', '', $f_newfarcropbg)
				);
			}

            $imgparams = array(
                'w'     => $f_newwidth,
                'h'     => $f_newheight,
                'aoe'   => $f_aoenew,
                'sx'   	=> $f_sxnew,
                'sy'   	=> $f_synew,
                'q'     => $quality,
                'cache' => 'img'
            );

			$_imgparams = array_merge(
			    $imp_filtercolor,
				$usm_filtercolor,
				$blur_filtercolor,
				$brit_filtercolor,
				$cont_filtercolor,
				$imgparams,
                $new_imgparams
			);

			$thumb_img = $JUImg->Render($imgsource, $_imgparams);

			$limage = $this->_image($thumb_img, $param->get('f_width'), $param->get('f_height'), $img_class, $img_alt, 0, 0);
        }

    	return $limage;
    }

    public static function _aspect($html, $_cropaspect)
    {
		$size 	= getimagesize( rawurldecode(JPATH_SITE .'/'. $html) );
		$width 	= $size[0];
		$height = $size[1] * ($_cropaspect != '' ? $_cropaspect : '0');
		$aspect = $height / $width;

    	return $aspect;
    }

	public function getTmpl($template, $name) {

		$search = JPATH_SITE . '/templates/'. $template .'/html/plg_jumultithumb/'. $name .'.php';

		if (is_file($search)) {
			$tmpl = $search;
		}
        else {
			$tmpl = JPATH_SITE . '/plugins/content/jumultithumb/tmpl/'. $name .'.php';
		}

		return $tmpl;
	}

    public function _image($_img, $_w, $_h, $_class = null, $_alt = null, $_caption = null, $_noresize = null, $_title = null, $_link_img = null, $_orig_img = null, $_lightbox = null)
    {
		$app = JFactory::getApplication();
		$template = $app->getTemplate();

        switch ($_lightbox)
		{
            case 'lightgallery':
                $lightbox = ' '. ($_link_img ? 'data-src="'. JURI::base() . $_link_img .'"' : '') .' '. ($_orig_img ? 'data-download-url="'. JURI::base() . $_orig_img .'"' : '');
                break;
            case 'colorbox':
                $lightbox = ' class="lightbox" rel="lightbox[gall]"';
                break;
            default:
            case 'jmodal':
                $lightbox = ' class="modal" rel="{handler: \'image\', marginImage: {x: 50, y: 50}}"';
                break;
        }

		$tmpl = $this->getTmpl($template, 'default');

		ob_start();
		require $tmpl;
		$img = ob_get_contents();
		ob_end_clean();

		return $img;
    }

    public function onBeforeCompileHead()
    {
        $app = JFactory::getApplication();

		if ($app->getName() != 'site') return true;
        if (!($this->modeHelper && $this->modeHelper->jView('Component'))) return;

        $doc = JFactory::getDocument();
    	$param = $this->params;		

		$selectlightbox = $param->get('selectlightbox');

        if(
			$param->get('uselightbox','1') == '1' &&
			(
				$this->modeHelper && $this->modeHelper->jView('Article') ||
              	$this->modeHelper && $this->modeHelper->jView('Categories') ||
              	$this->modeHelper && $this->modeHelper->jView('CatBlog')
            ) &&
			!($this->modeHelper && $this->modeHelper->jView('Print'))
        ) {
            if($param->get("jujq") == '0') $doc->addScript(JURI::root(true).'/media/jui/js/jquery.min.js');

			$juhead = "";

            switch ( $selectlightbox )
			{
                case 'customjs':
                    if($param->get('customjsparam')){
                        $juhead .= "\n            ". $param->get('customjsparam');
                    }
                    else {
                        $juhead .= "\r";
                    }
                    break;

                case 'colorbox':
                    if($param->get('colorboxparam')){
                        $jsparams .= "{\n		". str_replace("<br />", "\n		", $param->get('colorboxparam')) ."\n	}";
                    }
                    else {
                        $jsparams .= "\r";
                    }
                    $doc->addStyleSheet(JURI::base() .'media/plg_jumultithumb/colorbox/'. $param->get('colorboxstyle') .'/colorbox.css');
					$doc->addScript(JURI::base() .'media/plg_jumultithumb/colorbox/jquery.colorbox-min.js');

                    $juhead .= "jQuery(window).on('load', function() {\n";
					$juhead .= "	jQuery(\"a[rel='lightbox[gall]']\").colorbox(";
            		$juhead .= $jsparams;
        			$juhead .= ");\n";
    				$juhead .= "});\n";
                    break;

                default:
                case 'jmodal':
                    JHTML::_('behavior.modal');
                    break;
            }
            $doc->addScriptDeclaration( $juhead );
        }
        if ($param->get('use_css') == 1 ) $doc->addStyleSheet(JURI::base() .'media/plg_jumultithumb/style.css');
    }
}