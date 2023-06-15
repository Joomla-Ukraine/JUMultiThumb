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

use Joomla\CMS\Document\Document;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use JUMultiThumb\Helpers\AutoLinks;
use JUMultiThumb\Helpers\Image;
use JUMultiThumb\Helpers\Utils;

defined('_JEXEC') or die;

require_once __DIR__ . '/libraries/vendor/autoload.php';

JLoader::register('JUImage', JPATH_LIBRARIES . '/juimage/JUImage.php');

class plgContentjumultithumb extends CMSPlugin
{
	protected $modeHelper;
	protected JUImage $juimg;
	protected $app;
	protected ?Document $doc;
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
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		$this->loadLanguage();

		$this->juimg      = new JUImage();
		$this->app        = Factory::getApplication();
		$this->doc        = Factory::getDocument();
		$this->option     = $this->app->input->get('option');
		$this->itemid     = $this->app->input->getInt('Itemid');
		$this->modeHelper = '\\JUMultiThumb\\Adapters\\' . $this->option;
	}

	/**
	 * @param $context
	 * @param $article
	 *
	 * @return void
	 *
	 * @since 7.0
	 */
	public function onContentBeforeDisplay($context, $article): void
	{
		if($this->app->getName() !== 'site' || !$this->modeHelper::view('Component') || $this->modeHelper::view('Article'))
		{
			return;
		}

		$onlyFirstImage = $this->params->get('Only_For_First_Image');
		$link           = $this->modeHelper::link($article);

		$article->text      = AutoLinks::handleImgLinks($article->text, $article->title, $link, $onlyFirstImage);
		$article->introtext = AutoLinks::handleImgLinks($article->introtext, $article->title, $link, $onlyFirstImage);
	}

	/**
	 * @param $context
	 * @param $article
	 * @return void
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	public function onContentPrepare($context, $article): void
	{
		if($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
		{
			return;
		}

		if(isset($article->text))
		{
			$article->text = @$this->ImgReplace([
				'text'    => $article->text,
				'article' => $article
			]);
		}

		if(isset($article->fulltext))
		{
			$attribs = json_decode($article->attribs);
			$use_wm  = 1;
			if(isset($attribs->watermark_intro_only) == 1)
			{
				$use_wm = 0;
			}

			$article->fulltext = @$this->ImgReplace([
				'text'    => $article->fulltext,
				'article' => $article,
				'use_wm'  => $use_wm
			]);
		}
	}

	/**
	 * @param array $option
	 *
	 * @return mixed|string|string[]|null
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	private function ImgReplace(array $option = [])
	{
		$only_image_blog     = $this->params->get('only_image_blog');
		$only_image_category = $this->params->get('only_image_category');
		$only_image_featured = $this->params->get('only_image_featured');
		$attribs             = json_decode($option[ 'article' ]->attribs);
		$watermark_o         = $attribs->watermark;
		$watermark_s         = $attribs->watermark_s;

		if($option[ 'use_wm' ] == 0)
		{
			$watermark_o = '0';
			$watermark_s = '0';
		}

		$text = $option[ 'text' ];
		$text = preg_replace('#<img(.*?)mce_src="(.*?)"(.*?)>#s', "<img\\1\\3>", $text);
		$text = preg_replace('#<p>\s*<img(.*?)/>\s*</p>#s', "<img\\1\\3>", $text);
		$text = preg_replace('#<p>\s*<img(.*?)/>\s*#s', "<img\\1\\3><p>", $text);

		preg_match_all('/<img[^>]+>/i', $text, $imageAttr);
		if(count(array_filter($imageAttr[ 0 ])) > 0)
		{
			foreach($imageAttr[ 0 ] as $image)
			{
				$replace = $this->JUMultithumbReplacer([
					'image'       => $image,
					'article'     => $option[ 'article' ],
					'watermark_o' => $watermark_o,
					'watermark_s' => $watermark_s
				]);
				$text    = str_replace($image, $replace, $text);
			}
		}

		if(($only_image_blog == 1 && $this->modeHelper::view('Blog')) || ($only_image_category == 1 && $this->modeHelper::view('Category')) || ($only_image_featured == 1 && $this->modeHelper::view('Featured')))
		{
			preg_match_all('/(<\s*img\s+src\s*="\s*("[^"]*"|\'[^\']*\'|[^"\s]+).*?>)/i', $text, $result);
			$img  = $result[ 1 ][ 0 ];
			$text = $img;
		}

		return $text;
	}

	/**
	 * @param array $option
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	private function JUMultithumbReplacer(array $option = []): string
	{
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
		$items                   = $this->params->get('items');

		$attribs = json_decode($option[ 'article' ]->attribs);
		$use_wm  = 1;
		if($attribs->watermark_off)
		{
			$use_wm = 0;
		}

		switch($thumb_filtercolor)
		{
			case '1':
				$imp_filtercolor = [ 'fltr_1' => 'gray' ];
				break;
			case '2':
				$imp_filtercolor = [ 'fltr_1' => 'sep' ];
				break;
			case '3':
				$imp_filtercolor = [ 'fltr_1' => 'th|' . $thumb_th_seting ];
				break;
			case '4':
				$imp_filtercolor = [ 'fltr_1' => 'clr|' . $colorized . '|' . str_replace('#', '', $colorpicker) ];
				break;
			default:
				$imp_filtercolor = [];
				break;
		}

		$usm_filtercolor = [];
		if($usm == 1 && $thumb_filters == 1)
		{
			$usm_filtercolor = [
				'fltr_2' => 'usm|' . $thumb_unsharp_amount . '|' . $thumb_unsharp_radius . '|' . $thumb_unsharp_threshold
			];
		}

		$blur_filtercolor = [];
		if($thumb_blur == 1 && $thumb_filters == 1)
		{
			$blur_filtercolor = [ 'fltr_3' => 'blur|' . $thumb_blur_seting ];
		}

		$brit_filtercolor = [];
		if($thumb_brit == 1 && $thumb_filters == 1)
		{
			$brit_filtercolor = [ 'fltr_4' => 'brit|' . $thumb_brit_seting ];
		}

		$cont_filtercolor = [];
		if($thumb_cont == 1 && $thumb_filters == 1)
		{
			$cont_filtercolor = [ 'fltr_5' => 'cont|' . $thumb_cont_seting ];
		}

		$lightbox = $this->params->get('selectlightbox');

		preg_match_all('/(width|height|src|alt|title|class|align|style)=("[^"]*")/i', $option[ 'image' ], $imgAttr);
		$countAttr = count($imgAttr[ 0 ]);
		$img       = [];

		for($i = 0; $i < $countAttr; $i++)
		{
			$img[ $imgAttr[ 1 ][ $i ] ] = str_replace('"', '', $imgAttr[ 2 ][ $i ]);
		}

		$imgsource = Utils::img_source($img[ 'src' ]);
		$imgalt    = $img[ 'alt' ];
		$imgtitle  = $img[ 'title' ];
		$imgalign  = $img[ 'align' ];

		if(preg_match('#float:(.*?);#s', $img[ 'style' ], $imgstyle))
		{
			$imgstyle = $imgstyle[ 1 ];
		}

		$img_class = Utils::img_class([
			'imgclass' => $img[ 'class' ],
			'imgalign' => $imgalign,
			'imgstyle' => $imgstyle,
			'view'     => $this->app->input->get('view')
		]);

		
		$img_alt  = Utils::img_alt($img[ 'alt' ]);
		$imgtitle = mb_strtoupper(mb_substr($imgtitle, 0, 1)) . mb_substr($imgtitle, 1);
		$title    = ($imgalt ? : $imgtitle);
		$title    = ($title ? : $option[ 'article' ]->title);
		$title    = ($title ? ' title="' . $title . '"' : '');

		$_image_noresize = 0;
		if($this->params->get('resall') == 0 && $img[ 'class' ] !== 'juimage')
		{
			$size = getimagesize(JPATH_SITE . '/' . $imgsource);

			return Utils::image('jumultithumb', 'default', [
				'image'    => $imgsource,
				'orig_img' => $imgsource,
				'link_img' => $imgsource,
				'w'        => $size[ 0 ],
				'h'        => $size[ 1 ],
				'title'    => $title,
				'caption'  => $title,
				'lightbox' => $lightbox
			]);
		}

		if($this->params->get('resall') == 1 && ($img[ 'class' ] === 'nothumb' || $img[ 'class' ] === 'noimage' || $img[ 'class' ] === 'nothumbnail' || $img[ 'class' ] === 'jugallery' || $img[ 'class' ] == $noimage_class) && $img[ 'class' ] != '')
		{
			if($this->params->get('a_watermark') == 0 || $option[ 'watermark_o' ] != '1')
			{
				$size = getimagesize(JPATH_SITE . '/' . $imgsource);

				return Utils::image('jumultithumb', 'default', [
					'image'    => $imgsource,
					'orig_img' => $imgsource,
					'link_img' => $imgsource,
					'w'        => $size[ 0 ],
					'h'        => $size[ 1 ],
					'title'    => $title,
					'caption'  => $title,
					'lightbox' => $lightbox
				]);
			}

			$_image_noresize = 1;
		}

		if($this->modeHelper::view('Article'))
		{
			$w            = $this->params->get('width');
			$h            = $this->params->get('height');
			$zc           = $this->params->get('zc');
			$zoom_crop_bg = $this->params->get('zoom_crop_bg');
			$cropaspect   = $this->params->get('cropaspect');
			$farcrop      = $this->params->get('farcrop');
			$farcropbg    = $this->params->get('farcropbg');
			$noresize     = $this->params->get('noresize');
			$nofullimg    = $this->params->get('nofullimg');

			foreach($items as $item)
			{
				if(in_array($this->itemid, $item->menu_item))
				{
					$w            = $item->w;
					$h            = $item->h;
					$zc           = $item->zc;
					$zoom_crop_bg = $item->zoom_crop_bg;
					$cropaspect   = $item->cropaspect;
					$farcrop      = $item->farcrop;
					$farcropbg    = $item->farcropbg;
					$noresize     = $item->noresize;
					$nofullimg    = $item->nofullimg;
				}
			}

			echo $w;
		}

		$thumb_img = Image::thumb([
			'image'        => $imgsource,
			'noresize'     => $noresize,
			'zc'           => $zc,
			'cropaspect'   => $cropaspect,
			'zoom_crop_bg' => $zoom_crop_bg,
			'w'            => $w,
			'h'            => $h,
			'farcrop'      => $farcrop,
			'farcropbg'    => $farcropbg,
			'q'            => $quality,
		]);

		$limage = Utils::image('jumultithumb', 'default', [
			'image'    => $thumb_img,
			'orig_img' => $imgsource,
			'link_img' => $imgsource,
			'w'        => $w,
			'h'        => $h,
			'title'    => $title,
			'caption'  => $title,
			'lightbox' => $lightbox
		]);

		/*if($_image_noresize == 1 || $nofullimg == 1 || $this->modeHelper::view('Print'))
		{
			$limage = $this->_image($thumb_img, $w, $h, $img_class, $img_alt, 1, $_image_noresize, $title);
		}
		else
		{
			$limage = $this->_image($thumb_img, $w, $h, 'imgobjct ' . $img_class, $img_alt, 1, 0, $title, $link_img, $imgsource, $lightbox);
		}*/

		return $limage;

		/*
		if($this->modeHelper::view('CatBlog'))
		{
			$b_width           = $this->params->get('b_width');
			$b_height          = $this->params->get('b_height');
			$b_zc              = $this->params->get('b_zc');
			$b_zoomcrop_params = $this->params->get('b_zoomcrop_params');
			$b_zoom_crop_bg    = $this->params->get('b_zoom_crop_bg');
			$b_cropaspect      = $this->params->get('b_cropaspect');
			//$b_zoomcropbg      = $this->params->get('b_zoomcropbg');
			$b_farcrop        = $this->params->get('b_farcrop');
			$b_farcrop_params = $this->params->get('b_farcrop_params');
			$b_farcropbg      = $this->params->get('b_farcropbg');
			$b_aoe            = $this->params->get('b_aoe');
			$b_sx             = $this->params->get('b_sx');
			$b_sy             = $this->params->get('b_sy');

			foreach($items as $item)
			{
				if(in_array($this->itemid, $item->menu_item))
				{
					$b_width           = $item->b_width;
					$b_height          = $item->b_height;
					$b_zc              = $item->b_zc;
					$b_zoomcrop_params = $item->b_zoomcrop_params;
					$b_zoom_crop_bg    = $item->b_zoom_crop_bg;
					$b_cropaspect      = $item->b_cropaspect;
					$b_farcrop         = $item->b_farcrop;
					$b_farcrop_params  = $item->b_farcrop_params;
					$b_farcropbg       = $item->b_farcropbg;
					$b_aoe             = $item->b_aoe;
					$b_sx              = $item->b_sx;
					$b_sy              = $item->b_sy;
				}
			}

			$aspect = 0;
			if($b_zoom_crop_bg == 1)
			{
				$aspect = $this->_aspect($imgsource, $b_cropaspect);
			}

			$new_imgparams = [
				'zc' => $b_zc == 1 ? $b_zoomcrop_params : ''
			];

			if($aspect >= '1' && $b_zoom_crop_bg == 1)
			{
				$new_imgparams = [
					'far' => '1',
					'bg'  => str_replace('#', '', $b_farcropbg)
				];
			}

			if($b_farcrop == 1)
			{
				$new_imgparams = [
					'far' => $b_farcrop_params,
					'bg'  => str_replace('#', '', $b_farcropbg)
				];
			}

			$imgparams = [
				'w'     => $b_width,
				'h'     => $b_height,
				'aoe'   => $b_aoe,
				'sx'    => $b_sx,
				'sy'    => $b_sy,
				'q'     => $quality,
				'cache' => 'img'
			];

			$_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $imgparams, $new_imgparams);
			$thumb_img  = $this->juimg->render($imgsource, $_imgparams);
			$limage     = $this->_image($thumb_img, $b_width, $b_height, $img_class, $img_alt, 0, 0);
		}

		if($this->modeHelper::view('Article') || $this->modeHelper::view('Categories') || $this->modeHelper::view('Category'))
		{

			if($this->modeHelper::view('Article'))
			{
				$w            = $this->params->get('width');
				$h            = $this->params->get('height');
				$zc           = $this->params->get('zc');
				$zoom_crop_bg = $this->params->get('zoom_crop_bg');
				$cropaspect   = $this->params->get('cropaspect');
				$farcrop      = $this->params->get('farcrop');
				$farcropbg    = $this->params->get('farcropbg');
				$noresize     = $this->params->get('noresize');
				$nofullimg    = $this->params->get('nofullimg');

				foreach($items as $item)
				{
					if(in_array($this->itemid, $item->menu_item))
					{
						$w            = $item->w;
						$h            = $item->h;
						$zc           = $item->zc;
						$zoom_crop_bg = $item->zoom_crop_bg;
						$cropaspect   = $item->cropaspect;
						$farcrop      = $item->farcrop;
						$farcropbg    = $item->farcropbg;
						$noresize     = $item->noresize;
						$nofullimg    = $item->nofullimg;
					}
				}

				echo $w;
			}

			$thumb_img = Image::thumb([
				'image'        => $imgsource,
				'noresize'     => $noresize,
				'zc'           => $zc,
				'cropaspect'   => $cropaspect,
				'zoom_crop_bg' => $zoom_crop_bg,
				'w'            => $w,
				'h'            => $h,
				'farcrop'      => $farcrop,
				'farcropbg'    => $farcropbg,
				'q'            => $quality,
			]);


						if($this->modeHelper::view('Categories') || $this->modeHelper::view('Category'))
						{
							$newmaxwidth        = $this->params->get('cat_maxwidth');
							$newmaxheight       = $this->params->get('cat_maxheight');
							$w           = $this->params->get('cat_width');
							$h          = $this->params->get('cat_height');
							$zc        = $this->params->get('cat_zc');
							$newzoomcrop_params = $this->params->get('cat_zoomcrop_params');
							$zoom_crop_bg   = $this->params->get('cat_zoom_crop_bg');
							$cropaspect      = $this->params->get('cat_cropaspect');
							//$newzoomcropbg      = $this->params->get('cat_zoomcropbg');
							$farcrop        = $this->params->get('cat_farcrop');
							$newfarcrop_params = $this->params->get('cat_farcrop_params');
							$farcropbg      = $this->params->get('cat_farcropbg');
							$newaoe            = $this->params->get('cat_aoe');
							$sx             = $this->params->get('cat_sx');
							$sy             = $this->params->get('cat_sy');
							$noresize       = $this->params->get('cat_noresize');
							$nofullimg      = $this->params->get('cat_nofullimg');

							foreach($items as $item)
							{
								if(in_array($this->itemid, $item->cat_menu_item))
								{
									$newmaxwidth        = $item->cat_maxwidth;
									$newmaxheight       = $item->cat_maxheight;
									$w           = $item->cat_width;
									$h          = $item->cat_height;
									$zc        = $item->cat_zc;
									$newzoomcrop_params = $item->cat_zoomcrop_params;
									$zoom_crop_bg   = $item->cat_zoom_crop_bg;
									$cropaspect      = $item->cat_cropaspect;
									//$newzoomcropbg      = $item->cat_zoomcropbg;
									$farcrop        = $item->cat_farcrop;
									$newfarcrop_params = $item->cat_farcrop_params;
									$farcropbg      = $item->cat_farcropbg;
									$newaoe            = $item->cat_aoe;
									$sx             = $item->cat_sx;
									$sy             = $item->cat_sy;
									$noresize       = $item->cat_noresize;
									$nofullimg      = $item->cat_nofullimg;
								}
							}
						}

						if($noresize == 1) // || $cat_newnoresize == 1
						{
							$juimgresmatche = str_replace([
								' /',
								Uri::base()
							], '', $imgsource);

							return $this->_image(Uri::base() . $juimgresmatche, $newmaxwidth, $newmaxheight, $img_class, $img_alt, 1, 1);
						}

						// Watermark
						$wmi = '';
						if($use_wm == 1)
						{
							if($option[ 'watermark_o' ] == 1 || $_image_noresize == 1 || $this->params->get('a_watermark') == 1 || $this->params->get('a_watermarknew1') == 1 || $this->params->get('a_watermarknew2') == 1 || $this->params->get('a_watermarknew3') == 1 || $this->params->get('a_watermarknew4') == 1 || $this->params->get('a_watermarknew5') == 1)
							{
								$wmfile = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/w.png';
								if(!is_file($wmfile))
								{
									$wmfile = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/juw.png';
								}
								$watermark = $wmfile;

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
						if($option[ 'watermark_o' ] == 1 || $_image_noresize == 1 || $this->params->get('a_watermark') == 1 || $this->params->get('a_watermarknew1') == 1 || $this->params->get('a_watermarknew2') == 1 || $this->params->get('a_watermarknew3') == 1 || $this->params->get('a_watermarknew4') == 1 || $this->params->get('a_watermarknew5') == 1 ||

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
							if($option[ 'watermark_s' ] == 1 || $this->params->get('a_watermark_s') == 1 || $this->params->get('a_watermarknew1_s') == 1 || $this->params->get('a_watermarknew2_s') == 1 || $this->params->get('a_watermarknew3_s') == 1 || $this->params->get('a_watermarknew4_s') == 1 || $this->params->get('a_watermarknew5_s') == 1)
							{
								$wmfile = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/ws.png';
								if(!is_file($wmfile))
								{
									$wmfile = JPATH_SITE . '/plugins/content/jumultithumb/load/watermark/juws.png';
								}
								$watermark_s = $wmfile;

								$wmi_s = 'wmi|' . $watermark_s . '|' . $this->params->get('wmposition_s') . '|' . $this->params->get('wmopst_s') . '|' . $this->params->get('wmx_s') . '|' . $this->params->get('wmy_s');
							}
						}

						if($_image_noresize == 1)
						{
							$wmi_s       = $wmi;
							$w    = ($_width && $wmi ? $_width : '');
							$h   = ($_height && $wmi ? $_height : '');
							$newaoe      = '';
							$sx       = '';
							$sy       = '';
							$zc = '0';
						}

						$aspect = 0;
						if($zoom_crop_bg == 1)
						{
							$aspect = $this->_aspect($imgsource, $cropaspect);
						}

						$new_imgparams = [
							'zc' => $zc == 1 ? $newzoomcrop_params : ''
						];
						if($aspect >= '1' && $zoom_crop_bg == 1)
						{
							$new_imgparams = [
								'far' => '1',
								'bg'  => str_replace('#', '', $farcropbg)
							];
						}

						if($farcrop == 1)
						{
							$new_imgparams = [
								'far' => $newfarcrop_params,
								'bg'  => str_replace('#', '', $farcropbg)
							];
						}

						$imgparams = [
							'w'     => $w,
							'h'     => $h,
							'aoe'   => $newaoe,
							'sx'    => $sx,
							'sy'    => $sy,
							'fltr'  => $wmi_s != '' ? $wmi_s : '',
							'q'     => $quality,
							'cache' => 'img'
						];

						$_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $imgparams, $new_imgparams);
						$thumb_img  = $this->juimg->render($imgsource, $_imgparams);


			if($_image_noresize == 1 || $nofullimg == 1 || $this->modeHelper::view('Print'))
			{
				$limage = $this->_image($thumb_img, $w, $h, $img_class, $img_alt, 1, $_image_noresize, $title);
			}
			else
			{
				$limage = $this->_image($thumb_img, $w, $h, 'imgobjct ' . $img_class, $img_alt, 1, 0, $title, $link_img, $imgsource, $lightbox);
			}
		}
		elseif($this->modeHelper::view('Featured'))
		{
			$f_newwidth           = $this->params->get('f_width');
			$f_newheight          = $this->params->get('f_height');
			$f_newzc              = $this->params->get('f_zc');
			$f_newzoomcrop_params = $this->params->get('f_zoomcrop_params');
			$f_newzoom_crop_bg    = $this->params->get('f_zoom_crop_bg');
			$f_newcropaspect      = $this->params->get('f_cropaspect');
			//$f_newzoomcropbg      = $this->params->get('f_zoomcropbg');
			$f_newfarcrop        = $this->params->get('f_farcrop');
			$f_newfarcrop_params = $this->params->get('f_farcrop_params');
			$f_newfarcropbg      = $this->params->get('f_farcropbg');
			$f_aoenew            = $this->params->get('f_aoe');
			$f_sxnew             = $this->params->get('f_sx');
			$f_synew             = $this->params->get('f_sy');

			$aspect = 0;
			if($f_newzoom_crop_bg == 1)
			{
				$aspect = $this->_aspect($imgsource, $f_newcropaspect);
			}

			$new_imgparams = [
				'zc' => $f_newzc == 1 ? $f_newzoomcrop_params : ''
			];
			if($aspect >= '1' && $f_newzoom_crop_bg == 1)
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

			$limage = $this->_image($thumb_img, $this->params->get('f_width'), $this->params->get('f_height'), $img_class, $img_alt, 0, 0);
		}
*/


	}

	/**
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function onBeforeCompileHead(): bool
	{
		if($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
		{
			return true;
		}

		if($this->params->get('uselightbox', '1') == 1 && (($this->modeHelper::view('Article')) || ($this->modeHelper::view('Categories')) || ($this->modeHelper::view('CatBlog'))) && !($this->modeHelper::view('Print')))
		{
			if($this->params->get('jujq') == 0)
			{
				HTMLHelper::_('jquery.framework');
			}

			$juhead = '';

			switch($this->params->get('selectlightbox'))
			{
				case 'customjs':
					$juhead .= "\r";
					if($this->params->get('customjsparam'))
					{
						$juhead .= "\n            " . $this->params->get('customjsparam');
					}
					break;

				default:
				case 'jmodal':
					HTMLHelper::_('bootstrap.renderModal');
					break;
			}

			$this->doc->addScriptDeclaration($juhead);
		}

		return true;
	}
}