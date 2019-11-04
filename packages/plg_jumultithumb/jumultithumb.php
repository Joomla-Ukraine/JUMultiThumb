<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2019 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;

JLoader::register('AutoLinks', JPATH_SITE . '/plugins/content/jumultithumb/lib/links.php');
JLoader::register('JUImage', JPATH_LIBRARIES . '/juimage/JUImage.php');

class plgContentjumultithumb extends CMSPlugin
{
	protected $modeHelper;
	protected $juimg;
	protected $app;
	protected $doc;
	protected $option;
	protected $itemid;

	/**
	 * plgContentjumultithumb constructor.
	 *
	 * @param $subject
	 * @param $config
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		$this->loadLanguage();

		$this->juimg  = new JUImage();
		$this->app    = Factory::getApplication();
		$this->doc    = Factory::getDocument();
		$this->option = $this->app->input->get('option');
		$this->itemid = $this->app->input->getInt('Itemid');

		$adapter = JPATH_SITE . '/plugins/content/jumultithumb/adapters/' . $this->option . '.php';
		if(File::exists($adapter))
		{
			require_once $adapter;

			$mode_option      = 'plgContentJUMultiThumb_' . $this->option;
			$this->modeHelper = new $mode_option($this);
		}
	}

	/**
	 * @param $context
	 * @param $article
	 * @param $params
	 * @param $limitstart
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function onContentBeforeDisplay($context, &$article, &$params, $limitstart)
	{
		if($this->app->getName() !== 'site')
		{
			return;
		}

		if(!($this->modeHelper && $this->modeHelper->jView('Component')))
		{
			return;
		}

		if($this->modeHelper && $this->modeHelper->jView('Article'))
		{
			return;
		}

		$autolinks      = new AutoLinks();
		$onlyFirstImage = $this->params->get('Only_For_First_Image');
		$link           = $this->modeHelper->jViewLink($article);

		$article->text      = @$autolinks->handleImgLinks($article->text, $article->title, $link, $onlyFirstImage);
		$article->introtext = @$autolinks->handleImgLinks($article->introtext, $article->title, $link, $onlyFirstImage);
	}

	/**
	 * @param $context
	 * @param $article
	 * @param $params
	 * @param $limitstart
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function onContentPrepare($context, &$article, &$params, $limitstart)
	{
		if($this->app->getName() !== 'site')
		{
			return true;
		}

		if(!($this->modeHelper && $this->modeHelper->jView('Component')))
		{
			return true;
		}

		if(isset($article->text))
		{
			$article->text = @$this->ImgReplace($article->text, $article);
		}

		if(isset($article->fulltext))
		{
			$attribs = json_decode($article->attribs);

			$use_wm = 1;
			if(isset($attribs->watermark_intro_only) == 1)
			{
				$watermark_into_only = $attribs->watermark_intro_only;
				$use_wm              = 0;
			}

			$article->fulltext = @$this->ImgReplace($article->fulltext, $article, $use_wm);
		}

		return true;
	}

	/**
	 * @param     $text
	 * @param     $article
	 * @param int $use_wm
	 *
	 * @return mixed|null|string|string[]
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function ImgReplace($text, &$article, $use_wm = 1)
	{
		$only_image_blog     = $this->params->get('only_image_blog');
		$only_image_category = $this->params->get('only_image_category');
		$only_image_featured = $this->params->get('only_image_featured');

		$attribs     = json_decode($article->attribs);
		$watermark_o = $attribs->watermark;
		$watermark_s = $attribs->watermark_s;

		if($use_wm == 0)
		{
			$watermark_o = '0';
			$watermark_s = '0';
		}

		$text = preg_replace('#<img(.*?)mce_src="(.*?)"(.*?)>#s', "<img\\1\\3>", $text);
		$text = preg_replace('#<p>\s*<img(.*?)/>\s*</p>#s', "<img\\1\\3>", $text);
		$text = preg_replace('#<p>\s*<img(.*?)/>\s*#s', "<img\\1\\3><p>", $text);

		preg_match_all('/<img[^>]+>/i', $text, $imageAttr);
		if(count(array_filter($imageAttr[0])) > 0)
		{
			foreach ($imageAttr[0] as $image)
			{
				$replace = $this->JUMultithumbReplacer($image, $article, $watermark_o, $watermark_s);
				$text    = str_replace($image, $replace, $text);
			}
		}

		if(($only_image_blog == 1 && $this->modeHelper && $this->modeHelper->jView('Blog')) || ($only_image_category == 1 && $this->modeHelper && $this->modeHelper->jView('Category')) || ($only_image_featured == 1 && $this->modeHelper && $this->modeHelper->jView('Featured')))
		{
			preg_match_all('/(<\s*img\s+src\s*="\s*("[^"]*"|\'[^\']*\'|[^"\s]+).*?>)/i', $text, $result);
			$img  = $result[1][0];
			$text = $img;
		}

		return $text;
	}

	/**
	 * @param $_img
	 * @param $article
	 * @param $watermark_o
	 * @param $watermark_s
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function JUMultithumbReplacer($_img, &$article, $watermark_o, $watermark_s)
	{
		// params
		$quality                 = $this->params->get('quality');
		$noimage_class           = $this->params->get('noimage_class');
		$thumb_filtercolor       = $this->params->get('thumb_filtercolor', 0);
		$colorized               = $this->params->get('colorized', '25');
		$colorpicker             = $this->params->get('colorpicker', '#0000ff');
		$thumb_th_seting         = $this->params->get('thumb_th_seting', 0);
		$thumb_filters           = $this->params->get('thumb_filters', 1);
		$usm                     = $this->params->get('thumb_unsharp', 1);
		$thumb_unsharp_amount    = $this->params->get('thumb_unsharp_amount', 80);
		$thumb_unsharp_radius    = $this->params->get('thumb_unsharp_radius', 1);
		$thumb_unsharp_threshold = $this->params->get('thumb_unsharp_threshold', 3);
		$thumb_blur              = $this->params->get('thumb_blur', 0);
		$thumb_blur_seting       = $this->params->get('thumb_blur_seting', 1);
		$thumb_brit              = $this->params->get('thumb_brit', 0);
		$thumb_brit_seting       = $this->params->get('thumb_brit_seting', 50);
		$thumb_cont              = $this->params->get('thumb_cont', 0);
		$thumb_cont_seting       = $this->params->get('thumb_cont_seting', 50);

		$attribs = json_decode($article->attribs);
		$use_wm  = 1;
		if($attribs->watermark_off)
		{
			$use_wm = 0;
		}

		switch ($thumb_filtercolor)
		{
			case '1':
				$imp_filtercolor = ['fltr_1' => 'gray'];
				break;
			case '2':
				$imp_filtercolor = ['fltr_1' => 'sep'];
				break;
			case '3':
				$imp_filtercolor = ['fltr_1' => 'th|' . $thumb_th_seting];
				break;
			case '4':
				$imp_filtercolor = ['fltr_1' => 'clr|' . $colorized . '|' . str_replace('#', '', $colorpicker)];
				break;
			default:
				$imp_filtercolor = [];
				break;
		}

		$usm_filtercolor = [];
		if($usm == 1 && $thumb_filters == 1)
		{
			$usm_filtercolor = ['fltr_2' => 'usm|' . $thumb_unsharp_amount . '|' . $thumb_unsharp_radius . '|' . $thumb_unsharp_threshold];
		}

		$blur_filtercolor = [];
		if($thumb_blur == 1 && $thumb_filters == 1)
		{
			$blur_filtercolor = ['fltr_3' => 'blur|' . $thumb_blur_seting];
		}

		$brit_filtercolor = [];
		if($thumb_brit == 1 && $thumb_filters == 1)
		{
			$brit_filtercolor = ['fltr_4' => 'brit|' . $thumb_brit_seting];
		}

		$cont_filtercolor = [];
		if($thumb_cont == 1 && $thumb_filters == 1)
		{
			$cont_filtercolor = ['fltr_5' => 'cont|' . $thumb_cont_seting];
		}

		// image replacer
		$lightbox = $this->params->get('selectlightbox');

		preg_match_all('/(width|height|src|alt|title|class|align|style)=("[^"]*")/i', $_img, $imgAttr);
		$countAttr = count($imgAttr[0]);
		$img       = [];

		for ($i = 0; $i < $countAttr; $i++)
		{
			$img[$imgAttr[1][$i]] = str_replace('"', '', $imgAttr[2][$i]);
		}

		$imgsource      = $img['src'];
		$imgsource      = str_replace(Uri::base(), '', $imgsource);
		$originalsource = $imgsource;
		$imgalt         = $img['alt'];
		$imgtitle       = $img['title'];
		$imgalign       = $img['align'];
		$imgclass       = $img['class'] . ' ';

		if(preg_match('#float:(.*?);#s', $img['style'], $imgstyle))
		{
			$imgstyle = $imgstyle[1];
		}

		$img_class = '';
		if($imgalign !== '')
		{
			$img_class = 'ju' . trim($imgalign) . ' ';
		}
		elseif($imgstyle !== '')
		{
			$img_class = 'ju' . trim($imgstyle) . ' ';
		}

		// attributes
		$img_class = 'juimage ' . $imgclass . $img_class . 'juimg-' . $this->app->input->get('view');
		$imgalt    = mb_strtoupper(mb_substr($imgalt, 0, 1)) . mb_substr($imgalt, 1);
		$img_alt   = $imgalt;
		$imgtitle  = mb_strtoupper(mb_substr($imgtitle, 0, 1)) . mb_substr($imgtitle, 1);
		$img_title = ($imgalt ?: $imgtitle);
		$img_title = ($img_title ?: $article->title);
		$img_title = ($img_title ? ' title="' . $img_title . '"' : '');

		$_image_noresize = 0;
		if($this->params->get('resall') == 0 && $img['class'] !== 'juimage')
		{
			$size = getimagesize(JPATH_SITE . '/' . $originalsource);

			return $this->_image($originalsource, $size[0], $size[1], $imgclass, $img_alt, 1, 1, $img_title);
		}

		if($this->params->get('resall') == 1 && ($img['class'] === 'nothumb' || $img['class'] === 'noimage' || $img['class'] === 'nothumbnail' || $img['class'] === 'jugallery' || $img['class'] == $noimage_class) && $img['class'] != '')
		{
			if($this->params->get('a_watermark') == 0 || $watermark_o != '1')
			{
				$size = getimagesize(JPATH_SITE . '/' . $originalsource);

				return $this->_image($originalsource, $size[0], $size[1], $img_class, $img_alt, 1, 1, $img_title);
			}

			$_image_noresize = 1;
		}

		if($this->modeHelper && $this->modeHelper->jView('CatBlog'))
		{
			if(in_array($this->itemid, $this->params->get('menu_item1') ?: [], true))
			{
				$b_newwidth           = $this->params->get('b_widthnew1');
				$b_newheight          = $this->params->get('b_heightnew1');
				$b_newcropzoom        = $this->params->get('b_cropzoomnew1');
				$b_newzoomcrop_params = $this->params->get('b_zoomcrop_paramsnew1');
				$b_newauto_zoomcrop   = $this->params->get('b_auto_zoomcropnew1');
				$b_newcropaspect      = $this->params->get('b_cropaspectnew1');
				$b_newfarcrop         = $this->params->get('b_farcropnew1');
				$b_newfarcrop_params  = $this->params->get('b_farcrop_paramsnew1');
				$b_newfarcropbg       = $this->params->get('b_farcropbgnew1');
				$b_aoenew             = $this->params->get('b_aoenew1');
				$b_sxnew              = $this->params->get('b_sxnew1');
				$b_synew              = $this->params->get('b_synew1');
			}
			elseif(in_array($this->itemid, $this->params->get('menu_item2') ?: [], true))
			{
				$b_newwidth           = $this->params->get('b_widthnew2');
				$b_newheight          = $this->params->get('b_heightnew2');
				$b_newcropzoom        = $this->params->get('b_cropzoomnew2');
				$b_newzoomcrop_params = $this->params->get('b_zoomcrop_paramsnew2');
				$b_newauto_zoomcrop   = $this->params->get('b_auto_zoomcropnew2');
				$b_newcropaspect      = $this->params->get('b_cropaspectnew2');
				$b_newfarcrop         = $this->params->get('b_farcropnew2');
				$b_newfarcrop_params  = $this->params->get('b_farcrop_paramsnew2');
				$b_newfarcropbg       = $this->params->get('b_farcropbgnew2');
				$b_aoenew             = $this->params->get('b_aoenew2');
				$b_sxnew              = $this->params->get('b_sxnew2');
				$b_synew              = $this->params->get('b_synew2');
			}
			elseif(in_array($this->itemid, $this->params->get('menu_item3') ?: [], true))
			{
				$b_newwidth           = $this->params->get('b_widthnew3');
				$b_newheight          = $this->params->get('b_heightnew3');
				$b_newcropzoom        = $this->params->get('b_cropzoomnew3');
				$b_newzoomcrop_params = $this->params->get('b_zoomcrop_paramsnew3');
				$b_newauto_zoomcrop   = $this->params->get('b_auto_zoomcropnew3');
				$b_newcropaspect      = $this->params->get('b_cropaspectnew3');
				$b_newfarcrop         = $this->params->get('b_farcropnew3');
				$b_newfarcrop_params  = $this->params->get('b_farcrop_paramsnew3');
				$b_newfarcropbg       = $this->params->get('b_farcropbgnew3');
				$b_aoenew             = $this->params->get('b_aoenew3');
				$b_sxnew              = $this->params->get('b_sxnew3');
				$b_synew              = $this->params->get('b_synew3');
			}
			elseif(in_array($this->itemid, $this->params->get('menu_item4') ?: [], true))
			{
				$b_newwidth           = $this->params->get('b_widthnew4');
				$b_newheight          = $this->params->get('b_heightnew4');
				$b_newcropzoom        = $this->params->get('b_cropzoomnew4');
				$b_newzoomcrop_params = $this->params->get('b_zoomcrop_paramsnew4');
				$b_newauto_zoomcrop   = $this->params->get('b_auto_zoomcropnew4');
				$b_newcropaspect      = $this->params->get('b_cropaspectnew4');
				$b_newfarcrop         = $this->params->get('b_farcropnew4');
				$b_newfarcrop_params  = $this->params->get('b_farcrop_paramsnew4');
				$b_newfarcropbg       = $this->params->get('b_farcropbgnew4');
				$b_aoenew             = $this->params->get('b_aoenew4');
				$b_sxnew              = $this->params->get('b_sxnew4');
				$b_synew              = $this->params->get('b_synew4');
			}
			elseif(in_array($this->itemid, $this->params->get('menu_item5') ?: [], true))
			{
				$b_newwidth           = $this->params->get('b_widthnew5');
				$b_newheight          = $this->params->get('b_heightnew5');
				$b_newcropzoom        = $this->params->get('b_cropzoomnew5');
				$b_newzoomcrop_params = $this->params->get('b_zoomcrop_paramsnew5');
				$b_newauto_zoomcrop   = $this->params->get('b_auto_zoomcropnew5');
				$b_newcropaspect      = $this->params->get('b_cropaspectnew5');
				$b_newfarcrop         = $this->params->get('b_farcropnew5');
				$b_newfarcrop_params  = $this->params->get('b_farcrop_paramsnew5');
				$b_newfarcropbg       = $this->params->get('b_farcropbgnew5');
				$b_aoenew             = $this->params->get('b_aoenew5');
				$b_sxnew              = $this->params->get('b_sxnew5');
				$b_synew              = $this->params->get('b_synew5');
			}
			else
			{
				$b_newwidth           = $this->params->get('b_width');
				$b_newheight          = $this->params->get('b_height');
				$b_newcropzoom        = $this->params->get('b_cropzoom');
				$b_newzoomcrop_params = $this->params->get('b_zoomcrop_params');
				$b_newauto_zoomcrop   = $this->params->get('b_auto_zoomcrop');
				$b_newcropaspect      = $this->params->get('b_cropaspect');
				//$b_newzoomcropbg      = $this->params->get('b_zoomcropbg');
				$b_newfarcrop        = $this->params->get('b_farcrop');
				$b_newfarcrop_params = $this->params->get('b_farcrop_params');
				$b_newfarcropbg      = $this->params->get('b_farcropbg');
				$b_aoenew            = $this->params->get('b_aoe');
				$b_sxnew             = $this->params->get('b_sx');
				$b_synew             = $this->params->get('b_sy');
			}

			$aspect = 0;
			if($b_newauto_zoomcrop == 1)
			{
				$aspect = $this->_aspect($imgsource, $b_newcropaspect);
			}

			$new_imgparams = [
				'zc' => $b_newcropzoom == 1 ? $b_newzoomcrop_params : ''
			];

			if($aspect >= '1' && $b_newauto_zoomcrop == 1)
			{
				$new_imgparams = [
					'far' => '1',
					'bg'  => str_replace('#', '', $b_newfarcropbg)
				];
			}

			if($b_newfarcrop == 1)
			{
				$new_imgparams = [
					'far' => $b_newfarcrop_params,
					'bg'  => str_replace('#', '', $b_newfarcropbg)
				];
			}

			$imgparams = [
				'w'     => $b_newwidth,
				'h'     => $b_newheight,
				'aoe'   => $b_aoenew,
				'sx'    => $b_sxnew,
				'sy'    => $b_synew,
				'q'     => $quality,
				'cache' => 'img'
			];

			$_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $imgparams, $new_imgparams);
			$thumb_img  = $this->juimg->render($imgsource, $_imgparams);
			$limage     = $this->_image($thumb_img, $b_newwidth, $b_newheight, $img_class, $img_alt, 0, 0);
		}
		elseif(($this->modeHelper && $this->modeHelper->jView('Article')) || ($this->modeHelper && $this->modeHelper->jView('Categories')) || ($this->modeHelper && $this->modeHelper->jView('Category')))
		{
			if($this->modeHelper && $this->modeHelper->jView('Article'))
			{
				if(in_array($this->itemid, $this->params->get('menu_item1') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('maxwidthnew1');
					$newmaxheight       = $this->params->get('maxheightnew1');
					$newwidth           = $this->params->get('widthnew1');
					$newheight          = $this->params->get('heightnew1');
					$newcropzoom        = $this->params->get('cropzoomnew1');
					$newzoomcrop_params = $this->params->get('zoomcrop_paramsnew1');
					$newauto_zoomcrop   = $this->params->get('auto_zoomcropnew1');
					$newcropaspect      = $this->params->get('cropaspectnew1');
					$newfarcrop         = $this->params->get('farcropnew1');
					$newfarcrop_params  = $this->params->get('farcrop_paramsnew1');
					$newfarcropbg       = $this->params->get('farcropbgnew1');
					$newaoe             = $this->params->get('aoenew1');
					$newsx              = $this->params->get('sxnew1');
					$newsy              = $this->params->get('synew1');
					$newnoresize        = $this->params->get('noresizenew1');
					$newnofullimg       = $this->params->get('nofullimgnew1');
				}
				elseif(in_array($this->itemid, $this->params->get('menu_item2') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('maxwidthnew2');
					$newmaxheight       = $this->params->get('maxheightnew2');
					$newwidth           = $this->params->get('widthnew2');
					$newheight          = $this->params->get('heightnew2');
					$newcropzoom        = $this->params->get('cropzoomnew2');
					$newzoomcrop_params = $this->params->get('zoomcrop_paramsnew2');
					$newauto_zoomcrop   = $this->params->get('auto_zoomcropnew2');
					$newcropaspect      = $this->params->get('cropaspectnew2');
					$newfarcrop         = $this->params->get('farcropnew2');
					$newfarcrop_params  = $this->params->get('farcrop_paramsnew2');
					$newfarcropbg       = $this->params->get('farcropbgnew2');
					$newaoe             = $this->params->get('aoenew2');
					$newsx              = $this->params->get('sxnew2');
					$newsy              = $this->params->get('synew2');
					$newnoresize        = $this->params->get('noresizenew2');
					$newnofullimg       = $this->params->get('nofullimgnew2');
				}
				elseif(in_array($this->itemid, $this->params->get('menu_item3') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('maxwidthnew3');
					$newmaxheight       = $this->params->get('maxheightnew3');
					$newwidth           = $this->params->get('widthnew3');
					$newheight          = $this->params->get('heightnew3');
					$newcropzoom        = $this->params->get('cropzoomnew3');
					$newzoomcrop_params = $this->params->get('zoomcrop_paramsnew3');
					$newauto_zoomcrop   = $this->params->get('auto_zoomcropnew3');
					$newcropaspect      = $this->params->get('cropaspectnew3');
					$newfarcrop         = $this->params->get('farcropnew3');
					$newfarcrop_params  = $this->params->get('farcrop_paramsnew3');
					$newfarcropbg       = $this->params->get('farcropbgnew3');
					$newaoe             = $this->params->get('aoenew3');
					$newsx              = $this->params->get('sxnew3');
					$newsy              = $this->params->get('synew3');
					$newnoresize        = $this->params->get('noresizenew3');
					$newnofullimg       = $this->params->get('nofullimgnew3');
				}
				elseif(in_array($this->itemid, $this->params->get('menu_item4') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('maxwidthnew4');
					$newmaxheight       = $this->params->get('maxheightnew4');
					$newwidth           = $this->params->get('widthnew4');
					$newheight          = $this->params->get('heightnew4');
					$newcropzoom        = $this->params->get('cropzoomnew4');
					$newzoomcrop_params = $this->params->get('zoomcrop_paramsnew4');
					$newauto_zoomcrop   = $this->params->get('auto_zoomcropnew4');
					$newcropaspect      = $this->params->get('cropaspectnew4');
					$newfarcrop         = $this->params->get('farcropnew4');
					$newfarcrop_params  = $this->params->get('farcrop_paramsnew4');
					$newfarcropbg       = $this->params->get('farcropbgnew4');
					$newaoe             = $this->params->get('aoenew4');
					$newsx              = $this->params->get('sxnew4');
					$newsy              = $this->params->get('synew4');
					$newnoresize        = $this->params->get('noresizenew4');
					$newnofullimg       = $this->params->get('nofullimgnew4');
				}
				elseif(in_array($this->itemid, $this->params->get('menu_item5') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('maxwidthnew5');
					$newmaxheight       = $this->params->get('maxheightnew5');
					$newwidth           = $this->params->get('widthnew5');
					$newheight          = $this->params->get('heightnew5');
					$newcropzoom        = $this->params->get('cropzoomnew5');
					$newzoomcrop_params = $this->params->get('zoomcrop_paramsnew5');
					$newauto_zoomcrop   = $this->params->get('auto_zoomcropnew5');
					$newcropaspect      = $this->params->get('cropaspectnew5');
					$newfarcrop         = $this->params->get('farcropnew5');
					$newfarcrop_params  = $this->params->get('farcrop_paramsnew5');
					$newfarcropbg       = $this->params->get('farcropbgnew5');
					$newaoe             = $this->params->get('aoenew5');
					$newsx              = $this->params->get('sxnew5');
					$newsy              = $this->params->get('synew5');
					$newnoresize        = $this->params->get('noresizenew5');
					$newnofullimg       = $this->params->get('nofullimgnew5');
				}
				else
				{
					$newmaxwidth        = $this->params->get('maxwidth');
					$newmaxheight       = $this->params->get('maxheight');
					$newwidth           = $this->params->get('width');
					$newheight          = $this->params->get('height');
					$newcropzoom        = $this->params->get('cropzoom');
					$newzoomcrop_params = $this->params->get('zoomcrop_params');
					$newauto_zoomcrop   = $this->params->get('auto_zoomcrop');
					$newcropaspect      = $this->params->get('cropaspect');
					//$newzoomcropbg      = $this->params->get('zoomcropbg');
					$newfarcrop        = $this->params->get('farcrop');
					$newfarcrop_params = $this->params->get('farcrop_params');
					$newfarcropbg      = $this->params->get('farcropbg');
					$newaoe            = $this->params->get('aoe');
					$newsx             = $this->params->get('sx');
					$newsy             = $this->params->get('sy');
					$newnoresize       = $this->params->get('noresize');
					$newnofullimg      = $this->params->get('nofullimg');
				}
			}
			elseif(($this->modeHelper && $this->modeHelper->jView('Categories')) || ($this->modeHelper && $this->modeHelper->jView('Category')))
			{
				if(in_array($this->itemid, $this->params->get('cat_menu_item1') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('cat_maxwidthnew1');
					$newmaxheight       = $this->params->get('cat_maxheightnew1');
					$newwidth           = $this->params->get('cat_widthnew1');
					$newheight          = $this->params->get('cat_heightnew1');
					$newcropzoom        = $this->params->get('cat_cropzoomnew1');
					$newzoomcrop_params = $this->params->get('cat_zoomcrop_paramsnew1');
					$newauto_zoomcrop   = $this->params->get('cat_auto_zoomcropnew1');
					$newcropaspect      = $this->params->get('cat_cropaspectnew1');
					$newfarcrop         = $this->params->get('cat_farcropnew1');
					$newfarcrop_params  = $this->params->get('cat_farcrop_paramsnew1');
					$newfarcropbg       = $this->params->get('cat_farcropbgnew1');
					$newaoe             = $this->params->get('cat_aoenew1');
					$newsx              = $this->params->get('cat_sxnew1');
					$newsy              = $this->params->get('cat_synew1');
					$newnoresize        = $this->params->get('cat_noresizenew1');
					$newnofullimg       = $this->params->get('cat_nofullimgnew1');
				}
				elseif(in_array($this->itemid, $this->params->get('cat_menu_item2') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('cat_maxwidthnew2');
					$newmaxheight       = $this->params->get('cat_maxheightnew2');
					$newwidth           = $this->params->get('cat_widthnew2');
					$newheight          = $this->params->get('cat_heightnew2');
					$newcropzoom        = $this->params->get('cat_cropzoomnew2');
					$newzoomcrop_params = $this->params->get('cat_zoomcrop_paramsnew2');
					$newauto_zoomcrop   = $this->params->get('cat_auto_zoomcropnew2');
					$newcropaspect      = $this->params->get('cat_cropaspectnew2');
					$newfarcrop         = $this->params->get('cat_farcropnew2');
					$newfarcrop_params  = $this->params->get('cat_farcrop_paramsnew2');
					$newfarcropbg       = $this->params->get('cat_farcropbgnew2');
					$newaoe             = $this->params->get('cat_aoenew2');
					$newsx              = $this->params->get('cat_sxnew2');
					$newsy              = $this->params->get('cat_synew2');
					$newnoresize        = $this->params->get('cat_noresizenew2');
					$newnofullimg       = $this->params->get('cat_nofullimgnew2');
				}
				elseif(in_array($this->itemid, $this->params->get('cat_menu_item3') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('cat_maxwidthnew3');
					$newmaxheight       = $this->params->get('cat_maxheightnew3');
					$newwidth           = $this->params->get('cat_widthnew3');
					$newheight          = $this->params->get('cat_heightnew3');
					$newcropzoom        = $this->params->get('cat_cropzoomnew3');
					$newzoomcrop_params = $this->params->get('cat_zoomcrop_paramsnew3');
					$newauto_zoomcrop   = $this->params->get('cat_auto_zoomcropnew3');
					$newcropaspect      = $this->params->get('cat_cropaspectnew3');
					$newfarcrop         = $this->params->get('cat_farcropnew3');
					$newfarcrop_params  = $this->params->get('cat_farcrop_paramsnew3');
					$newfarcropbg       = $this->params->get('cat_farcropbgnew3');
					$newaoe             = $this->params->get('cat_aoenew3');
					$newsx              = $this->params->get('cat_sxnew3');
					$newsy              = $this->params->get('cat_synew3');
					$newnoresize        = $this->params->get('cat_noresizenew3');
					$newnofullimg       = $this->params->get('cat_nofullimgnew3');
				}
				elseif(in_array($this->itemid, $this->params->get('cat_menu_item4') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('cat_maxwidthnew4');
					$newmaxheight       = $this->params->get('cat_maxheightnew4');
					$newwidth           = $this->params->get('cat_widthnew4');
					$newheight          = $this->params->get('cat_heightnew4');
					$newcropzoom        = $this->params->get('cat_cropzoomnew4');
					$newzoomcrop_params = $this->params->get('cat_zoomcrop_paramsnew4');
					$newauto_zoomcrop   = $this->params->get('cat_auto_zoomcropnew4');
					$newcropaspect      = $this->params->get('cat_cropaspectnew4');
					$newfarcrop         = $this->params->get('cat_farcropnew4');
					$newfarcrop_params  = $this->params->get('cat_farcrop_paramsnew4');
					$newfarcropbg       = $this->params->get('cat_farcropbgnew4');
					$newaoe             = $this->params->get('cat_aoenew4');
					$newsx              = $this->params->get('cat_sxnew4');
					$newsy              = $this->params->get('cat_synew4');
					$newnoresize        = $this->params->get('cat_noresizenew4');
					$newnofullimg       = $this->params->get('cat_nofullimgnew4');
				}
				elseif(in_array($this->itemid, $this->params->get('cat_menu_item5') ?: [], true))
				{
					$newmaxwidth        = $this->params->get('cat_maxwidthnew5');
					$newmaxheight       = $this->params->get('cat_maxheightnew5');
					$newwidth           = $this->params->get('cat_widthnew5');
					$newheight          = $this->params->get('cat_heightnew5');
					$newcropzoom        = $this->params->get('cat_cropzoomnew5');
					$newzoomcrop_params = $this->params->get('cat_zoomcrop_paramsnew5');
					$newauto_zoomcrop   = $this->params->get('cat_auto_zoomcropnew5');
					$newcropaspect      = $this->params->get('cat_cropaspectnew5');
					$newfarcrop         = $this->params->get('cat_farcropnew5');
					$newfarcrop_params  = $this->params->get('cat_farcrop_paramsnew5');
					$newfarcropbg       = $this->params->get('cat_farcropbgnew5');
					$newaoe             = $this->params->get('cat_aoenew5');
					$newsx              = $this->params->get('cat_sxnew5');
					$newsy              = $this->params->get('cat_synew5');
					$newnoresize        = $this->params->get('cat_noresizenew5');
					$newnofullimg       = $this->params->get('cat_nofullimgnew5');
				}
				else
				{
					$newmaxwidth        = $this->params->get('cat_maxwidth');
					$newmaxheight       = $this->params->get('cat_maxheight');
					$newwidth           = $this->params->get('cat_width');
					$newheight          = $this->params->get('cat_height');
					$newcropzoom        = $this->params->get('cat_cropzoom');
					$newzoomcrop_params = $this->params->get('cat_zoomcrop_params');
					$newauto_zoomcrop   = $this->params->get('cat_auto_zoomcrop');
					$newcropaspect      = $this->params->get('cat_cropaspect');
					//$newzoomcropbg      = $this->params->get('cat_zoomcropbg');
					$newfarcrop        = $this->params->get('cat_farcrop');
					$newfarcrop_params = $this->params->get('cat_farcrop_params');
					$newfarcropbg      = $this->params->get('cat_farcropbg');
					$newaoe            = $this->params->get('cat_aoe');
					$newsx             = $this->params->get('cat_sx');
					$newsy             = $this->params->get('cat_sy');
					$newnoresize       = $this->params->get('cat_noresize');
					$newnofullimg      = $this->params->get('cat_nofullimg');
				}
			}

			if($newnoresize == 1) // || $cat_newnoresize == 1
			{
				$juimgresmatche = str_replace([
					' /',
					Uri::base()
				], '', $originalsource);

				return $this->_image(Uri::base() . $juimgresmatche, $newmaxwidth, $newmaxheight, $img_class, $img_alt, 1, 1);
			}

			// Watermark
			$wmi = '';
			if($use_wm == 1)
			{
				if($watermark_o == 1 || $_image_noresize == 1 || $this->params->get('a_watermark') == 1 || $this->params->get('a_watermarknew1') == 1 || $this->params->get('a_watermarknew2') == 1 || $this->params->get('a_watermarknew3') == 1 || $this->params->get('a_watermarknew4') == 1 || $this->params->get('a_watermarknew5') == 1)
				{
					$wmfile = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/w.png';
					if(is_file($wmfile))
					{
						$watermark = $wmfile;
					}
					else
					{
						$wmfile    = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/juw.png';
						$watermark = $wmfile;
					}

					$wmi = 'wmi|' . $watermark . '|' . $this->params->get('wmposition') . '|' . $this->params->get('wmopst') . '|' . $this->params->get('wmx') . '|' . $this->params->get('wmy');
				}
			}

			$_width  = '';
			$_height = '';
			if($this->params->get('maxsize_orig') == 1 || $this->params->get('cat_newmaxsize_orig') == 1)
			{
				$_width  = $newmaxwidth;
				$_height = $newmaxheight;
			}

			$link_img = $imgsource;
			if($watermark_o == 1 || $_image_noresize == 1 || $this->params->get('a_watermark') == 1 || $this->params->get('a_watermarknew1') == 1 || $this->params->get('a_watermarknew2') == 1 || $this->params->get('a_watermarknew3') == 1 || $this->params->get('a_watermarknew4') == 1 || $this->params->get('a_watermarknew5') == 1 ||

				$this->params->get('maxsize_orig') == 1 || $this->params->get('cat_newmaxsize_orig') == 1)
			{
				$link_imgparams = [
					'w'     => $_width,
					'h'     => $_height,
					'aoe'   => $newaoe,
					'fltr'  => $wmi != '' ? $wmi : '',
					'q'     => $quality,
					'cache' => 'img'
				];

				$_link_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $link_imgparams);
				$link_img        = $this->juimg->render($imgsource, $_link_imgparams);
			}

			// Small watermark
			$wmi_s = '';
			if($use_wm == 1)
			{
				if($watermark_s == 1 || $this->params->get('a_watermark_s') == 1 || $this->params->get('a_watermarknew1_s') == 1 || $this->params->get('a_watermarknew2_s') == 1 || $this->params->get('a_watermarknew3_s') == 1 || $this->params->get('a_watermarknew4_s') == 1 || $this->params->get('a_watermarknew5_s') == 1)
				{
					$wmfile = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/ws.png';
					if(is_file($wmfile))
					{
						$watermark_s = $wmfile;
					}
					else
					{
						$wmfile      = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/juws.png';
						$watermark_s = $wmfile;
					}

					$wmi_s = 'wmi|' . $watermark_s . '|' . $this->params->get('wmposition_s') . '|' . $this->params->get('wmopst_s') . '|' . $this->params->get('wmx_s') . '|' . $this->params->get('wmy_s');
				}
			}

			if($_image_noresize == 1)
			{
				$wmi_s       = $wmi;
				$newwidth    = ($_width && $wmi ? $_width : '');
				$newheight   = ($_height && $wmi ? $_height : '');
				$newaoe      = '';
				$newsx       = '';
				$newsy       = '';
				$newcropzoom = '0';
			}

			$aspect = 0;
			if($newauto_zoomcrop == 1)
			{
				$aspect = $this->_aspect($imgsource, $newcropaspect);
			}

			$new_imgparams = [
				'zc' => $newcropzoom == 1 ? $newzoomcrop_params : ''
			];
			if($aspect >= '1' && $newauto_zoomcrop == 1)
			{
				$new_imgparams = [
					'far' => '1',
					'bg'  => str_replace('#', '', $newfarcropbg)
				];
			}

			if($newfarcrop == 1)
			{
				$new_imgparams = [
					'far' => $newfarcrop_params,
					'bg'  => str_replace('#', '', $newfarcropbg)
				];
			}

			$imgparams = [
				'w'     => $newwidth,
				'h'     => $newheight,
				'aoe'   => $newaoe,
				'sx'    => $newsx,
				'sy'    => $newsy,
				'fltr'  => $wmi_s != '' ? $wmi_s : '',
				'q'     => $quality,
				'cache' => 'img'
			];

			$_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $imgparams, $new_imgparams);
			$thumb_img  = $this->juimg->render($imgsource, $_imgparams);

			if($_image_noresize == 1 || $newnofullimg == 1 || ($this->modeHelper && $this->modeHelper->jView('Print')))
			{
				$limage = $this->_image($thumb_img, $newwidth, $newheight, $img_class, $img_alt, 1, $_image_noresize, $img_title);
			}
			else
			{
				$limage = $this->_image($thumb_img, $newwidth, $newheight, 'imgobjct ' . $img_class, $img_alt, 1, 0, $img_title, $link_img, $imgsource, $lightbox);
			}
		}
		elseif($this->modeHelper && $this->modeHelper->jView('Featured'))
		{
			$f_newwidth           = $this->params->get('f_width');
			$f_newheight          = $this->params->get('f_height');
			$f_newcropzoom        = $this->params->get('f_cropzoom');
			$f_newzoomcrop_params = $this->params->get('f_zoomcrop_params');
			$f_newauto_zoomcrop   = $this->params->get('f_auto_zoomcrop');
			$f_newcropaspect      = $this->params->get('f_cropaspect');
			//$f_newzoomcropbg      = $this->params->get('f_zoomcropbg');
			$f_newfarcrop        = $this->params->get('f_farcrop');
			$f_newfarcrop_params = $this->params->get('f_farcrop_params');
			$f_newfarcropbg      = $this->params->get('f_farcropbg');
			$f_aoenew            = $this->params->get('f_aoe');
			$f_sxnew             = $this->params->get('f_sx');
			$f_synew             = $this->params->get('f_sy');

			$aspect = 0;
			if($f_newauto_zoomcrop == 1)
			{
				$aspect = $this->_aspect($imgsource, $f_newcropaspect);
			}

			$new_imgparams = [
				'zc' => $f_newcropzoom == 1 ? $f_newzoomcrop_params : ''
			];
			if($aspect >= '1' && $f_newauto_zoomcrop == 1)
			{
				$new_imgparams = [
					'far' => '1',
					'bg'  => str_replace('#', '', $f_newfarcropbg)
				];
			}

			if($f_newfarcrop == 1)
			{
				$new_imgparams = [
					'far' => $f_newfarcrop_params,
					'bg'  => str_replace('#', '', $f_newfarcropbg)
				];
			}

			$imgparams = [
				'w'     => $f_newwidth,
				'h'     => $f_newheight,
				'aoe'   => $f_aoenew,
				'sx'    => $f_sxnew,
				'sy'    => $f_synew,
				'q'     => $quality,
				'cache' => 'img'
			];

			$_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $imgparams, $new_imgparams);

			$thumb_img = $this->juimg->render($imgsource, $_imgparams);
			$limage    = $this->_image($thumb_img, $this->params->get('f_width'), $this->params->get('f_height'), $img_class, $img_alt, 0, 0);
		}

		return $limage;
	}

	/**
	 * @param      $_img
	 * @param      $_w
	 * @param      $_h
	 * @param null $_class
	 * @param null $_alt
	 * @param null $_caption
	 * @param null $_noresize
	 * @param null $_title
	 * @param null $_link_img
	 * @param null $_orig_img
	 * @param null $_lightbox
	 *
	 * @return string
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function _image($_img, $_w, $_h, $_class = null, $_alt = null, $_caption = null, $_noresize = null, $_title = null, $_link_img = null, $_orig_img = null, $_lightbox = null)
	{
		$template = $this->app->getTemplate();

		switch ($_lightbox)
		{
			case 'lightgallery':
				$lightbox = ' ' . ($_link_img ? 'data-src="' . Uri::base() . $_link_img . '"' : '') . ' ' . ($_orig_img ? 'data-download-url="' . Uri::base() . $_orig_img . '"' : '');
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

		return ob_get_clean();
	}

	/**
	 * @param $template
	 * @param $name
	 *
	 * @return string
	 *
	 * @since 7.0
	 */
	public function getTmpl($template, $name)
	{
		$search = JPATH_SITE . '/templates/' . $template . '/html/plg_jumultithumb/' . $name . '.php';
		$tmpl   = JPATH_SITE . '/plugins/content/jumultithumb/tmpl/' . $name . '.php';

		if(is_file($search))
		{
			$tmpl = $search;
		}

		return $tmpl;
	}

	/**
	 * @param $html
	 * @param $_cropaspect
	 *
	 * @return float|int
	 *
	 * @since 7.0
	 */
	public function _aspect($html, $_cropaspect)
	{
		$size   = getimagesize(rawurldecode(JPATH_SITE . '/' . $html));
		$width  = $size[0];
		$height = $size[1] * ($_cropaspect != '' ? $_cropaspect : '0');

		return $height / $width;
	}

	/**
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function onBeforeCompileHead()
	{
		if($this->app->getName() !== 'site')
		{
			return true;
		}

		if(!($this->modeHelper && $this->modeHelper->jView('Component')))
		{
			return true;
		}

		if($this->params->get('uselightbox', '1') == 1 && (($this->modeHelper && $this->modeHelper->jView('Article')) || ($this->modeHelper && $this->modeHelper->jView('Categories')) || ($this->modeHelper && $this->modeHelper->jView('CatBlog'))) && !($this->modeHelper && $this->modeHelper->jView('Print')))
		{
			if($this->params->get('jujq') == 0)
			{
				HTMLHelper::_('jquery.framework');
			}

			$juhead = '';

			switch ($this->params->get('selectlightbox'))
			{
				case 'customjs':
					$juhead .= "\r";
					if($this->params->get('customjsparam'))
					{
						$juhead .= "\n            " . $this->params->get('customjsparam');
					}
					break;

				case 'colorbox':
					$jsparams = "\r";
					if($this->params->get('colorboxparam'))
					{
						$jsparams = "{\n		" . str_replace('<br />', "\n		", $this->params->get('colorboxparam')) . "\n	}";
					}

					$this->doc->addStyleSheet(Uri::base() . 'media/plg_jumultithumb/colorbox/' . $this->params->get('colorboxstyle') . '/colorbox.css');
					$this->doc->addScript(Uri::base() . 'media/plg_jumultithumb/colorbox/jquery.colorbox-min.js');

					$juhead .= "jQuery(window).on('load', function() {\n";
					$juhead .= "	jQuery(\"a[rel='lightbox[gall]']\").colorbox(";
					$juhead .= $jsparams;
					$juhead .= ");\n";
					$juhead .= "});\n";
					break;

				default:
				case 'jmodal':
					HTMLHelper::_('behavior.modal');
					break;
			}

			$this->doc->addScriptDeclaration($juhead);
		}

		if($this->params->get('use_css') == 1)
		{
			$this->doc->addStyleSheet(Uri::base() . 'media/plg_jumultithumb/style.css');
		}

		return true;
	}
}