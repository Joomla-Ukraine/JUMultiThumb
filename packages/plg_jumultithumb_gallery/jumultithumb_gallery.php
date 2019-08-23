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

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filesystem\File;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;

JLoader::register('AutoLinks', JPATH_SITE . '/plugins/content/jumultithumb/lib/links.php');
JLoader::register('JUImage', JPATH_LIBRARIES . '/juimage/JUImage.php');

class plgContentJUMULTITHUMB_Gallery extends CMSPlugin
{
	protected $modeHelper;
	protected $juimg;
	protected $app;
	protected $doc;
	protected $option;
	protected $itemid;

	/**
	 * plgContentJUMULTITHUMB_Gallery constructor.
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
	 * @return bool|void
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

		$autolinks     = new AutoLinks();
		$link          = $this->modeHelper->jViewLink($article);
		$article->text = $autolinks->handleImgLinks($article->text, $article->title, $link, 1);
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

		if(!($this->modeHelper && $this->modeHelper->jView('Article')) && ($this->params->get('useimgagegallery') == '0'))
		{
			$regex = "/<p>\s*{gallery\s+(.*?)}\s*</p>/i";
			preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);

			foreach($matches as $match)
			{
				$article->text = preg_replace($regex, '', $article->text, 1);

				return true;
			}
		}

		if(isset($article->text))
		{
			$article->text = @$this->GalleryReplace($article->text, $article);
		}

		if(isset($article->fulltext))
		{
			$article->fulltext = @$this->GalleryReplace($article->fulltext, $article);
		}

		return true;
	}

	/**
	 * @param $text
	 * @param $article
	 *
	 * @return null|string|string[]
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function GalleryReplace($text, &$article)
	{
		$regex = "/<p>\s*{gallery\s+(.*?)}\s*<\/p>/i";
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);

		if($matches)
		{
			$plugin = PluginHelper::getPlugin('content', 'jumultithumb');
			$json   = json_decode($plugin->params);

			$a_watermarkgall_s = $this->params->get('watermarkgall_s');
			$a_watermarkgall   = $this->params->get('watermarkgall');

			$attribs             = json_decode($article->attribs);
			$watermark_gallery   = $attribs->watermark_gallery;
			$watermark_gallery_s = $attribs->watermark_gallery_s;

			$attribs = json_decode($article->attribs);
			$use_wm  = 1;
			if($attribs->watermark_off)
			{
				$use_wm = 0;
			}

			foreach($matches as $match)
			{
				$matcheslist = explode('|', $match[ 1 ]);

				$galltitle = null;
				$gallstyle = null;
				$img_alt   = null;
				$img_style = null;
				$attr      = null;

				if(!array_key_exists(1, $matcheslist))
				{
					$matcheslist[ 1 ] = null;
				}

				if(!array_key_exists(2, $matcheslist))
				{
					$matcheslist[ 2 ] = $galltitle;
				}

				if(!array_key_exists(3, $matcheslist))
				{
					$matcheslist[ 3 ] = $gallstyle;
				}

				if(in_array($this->itemid, $this->params->get('menu_item1') ? : []))
				{
					$maxsize_orignew = $this->params->get('maxsize_orignew1');
					$newmaxwidth     = $this->params->get('maxwidthnew1');
					$newmaxheight    = $this->params->get('maxheightnew1');

					$gallwidth    = $this->params->get('gallwidth1');
					$gallheight   = $this->params->get('gallheight1');
					$gallcropzoom = $this->params->get('gallcropzoom1');

					$galltitle         = ($this->params->get('usegallery_title1') == 1 ? $this->params->get('gallery_title1') : '');
					$gallstyle         = $this->params->get('cssclass1');
					$a_watermarkgall_s = $this->params->get('watermark_gall_s1');
					$a_watermarkgall   = $this->params->get('watermark_gall1');
				}
				elseif(in_array($this->itemid, $this->params->get('menu_item2') ? : []))
				{
					$maxsize_orignew = $this->params->get('maxsize_orignew2');
					$newmaxwidth     = $this->params->get('maxwidthnew2');
					$newmaxheight    = $this->params->get('maxheightnew2');

					$gallwidth    = $this->params->get('gallwidth2');
					$gallheight   = $this->params->get('gallheight2');
					$gallcropzoom = $this->params->get('gallcropzoom2');

					$galltitle         = ($this->params->get('usegallery_title2') == 1 ? $this->params->get('gallery_title2') : '');
					$gallstyle         = $this->params->get('cssclass2');
					$a_watermarkgall_s = $this->params->get('watermark_gall_s2');
					$a_watermarkgall   = $this->params->get('watermark_gall2');
				}
				elseif(in_array($this->itemid, $this->params->get('menu_item3') ? : []))
				{
					$maxsize_orignew = $this->params->get('maxsize_orignew3');
					$newmaxwidth     = $this->params->get('maxwidthnew3');
					$newmaxheight    = $this->params->get('maxheightnew3');

					$gallwidth    = $this->params->get('gallwidth3');
					$gallheight   = $this->params->get('gallheight3');
					$gallcropzoom = $this->params->get('gallcropzoom3');

					$galltitle         = ($this->params->get('usegallery_title3') == 1 ? $this->params->get('gallery_title3') : '');
					$gallstyle         = $this->params->get('cssclass3');
					$a_watermarkgall_s = $this->params->get('watermark_gall_s3');
					$a_watermarkgall   = $this->params->get('watermark_gall3');
				}
				elseif(in_array($this->itemid, $this->params->get('menu_item4') ? : []))
				{
					$maxsize_orignew = $this->params->get('maxsize_orignew4');
					$newmaxwidth     = $this->params->get('maxwidthnew4');
					$newmaxheight    = $this->params->get('maxheightnew4');

					$gallwidth    = $this->params->get('gallwidth4');
					$gallheight   = $this->params->get('gallheight4');
					$gallcropzoom = $this->params->get('gallcropzoom4');

					$galltitle         = ($this->params->get('usegallery_title4') == 1 ? $this->params->get('gallery_title4') : '');
					$gallstyle         = $this->params->get('cssclass4');
					$a_watermarkgall_s = $this->params->get('watermark_gall_s4');
					$a_watermarkgall   = $this->params->get('watermark_gall4');
				}
				elseif(in_array($this->itemid, $this->params->get('menu_item5') ? : []))
				{
					$maxsize_orignew = $this->params->get('maxsize_orignew5');
					$newmaxwidth     = $this->params->get('maxwidthnew5');
					$newmaxheight    = $this->params->get('maxheightnew5');

					$gallwidth    = $this->params->get('gallwidth5');
					$gallheight   = $this->params->get('gallheight5');
					$gallcropzoom = $this->params->get('gallcropzoom5');

					$galltitle         = ($this->params->get('usegallery_title5') == 1 ? $this->params->get('gallery_title5') : '');
					$gallstyle         = $this->params->get('cssclass5');
					$a_watermarkgall_s = $this->params->get('watermark_gall_s5');
					$a_watermarkgall   = $this->params->get('watermark_gall5');
				}
				else
				{
					$maxsize_orignew = $this->params->get('maxsize_orignew');
					$newmaxwidth     = $this->params->get('maxwidthnew');
					$newmaxheight    = $this->params->get('maxheightnew');

					$gallwidth    = $this->params->get('gallwidth');
					$gallheight   = $this->params->get('gallheight');
					$gallcropzoom = $this->params->get('gallcropzoom');

					$galltitle = str_replace('title=', '', trim($matcheslist[ 1 ]));
					if(!$galltitle)
					{
						$galltitle = $this->params->get('gallery_title');
					}

					$gallstyle = str_replace('class=', '', trim($matcheslist[ 2 ]));
					if(!$gallstyle)
					{
						$gallstyle = $this->params->get('cssclass');
					}
				}

				$img_cache  = $this->params->get('img_cache');
				$img_title  = preg_replace('/"/', "'", $article->title);
				$lightbox   = $this->params->get('selectlightbox');
				$folder     = trim($matcheslist[ 0 ]);
				$imgpath    = 'images/' . $folder;
				$root       = JPATH_ROOT . '/';
				$img_folder = $root . $imgpath;

				$html = '';
				if(is_dir($img_folder))
				{
					$images = glob($img_folder . '/{*.[jJ][pP][gG],*.[jJ][pP][eE][gG],*.[gG][iI][fF],*.[pP][nN][gG],*.[bB][mM][pP],*.[tT][iI][fF],*.[tT][iI][fF][fF]}', GLOB_BRACE);
					$images = str_replace($root, '', $images);

					$_gallery = [];
					foreach($images as $file)
					{
						switch($json->thumb_filtercolor)
						{
							case '1':
								$imp_filtercolor = [ 'fltr_1' => 'gray' ];
								break;
							case '2':
								$imp_filtercolor = [ 'fltr_1' => 'sep' ];
								break;
							case '3':
								$imp_filtercolor = [ 'fltr_1' => 'th|' . $json->thumb_th_seting ];
								break;
							case '4':
								$imp_filtercolor = [ 'fltr_1' => 'clr|' . $json->colorized . '|' . str_replace('#', '', $json->colorpicker) ];
								break;
							default:
								$imp_filtercolor = [];
								break;
						}

						$usm_filtercolor = [];
						if($json->thumb_unsharp == 1)
						{
							$usm_filtercolor = [ 'fltr_2' => 'usm|' . $json->thumb_unsharp_amount . '|' . $json->thumb_unsharp_radius . '|' . $json->thumb_unsharp_threshold ];
						}

						$blur_filtercolor = [];
						if($json->thumb_blur == 1)
						{
							$blur_filtercolor = [ 'fltr_3' => 'blur|' . $json->thumb_blur_seting ];
						}

						$brit_filtercolor = [];
						if($json->thumb_brit == 1)
						{
							$brit_filtercolor = [ 'fltr_4' => 'brit|' . $json->thumb_brit_seting ];
						}

						$cont_filtercolor = [];
						if($json->thumb_cont == 1)
						{
							$cont_filtercolor = [ 'fltr_5' => 'cont|' . $json->thumb_cont_seting ];
						}

						if(!($this->modeHelper && $this->modeHelper->jView('Article')) && ($this->params->get('useimgagegallery') == '1'))
						{
							$_title = mb_strtoupper(mb_substr($img_title, 0, 1)) . mb_substr($img_title, 1);

							$imgparams = [
								'w'     => $this->params->get('width'),
								'h'     => $this->params->get('height'),
								'aoe'   => '1',
								'zc'    => $this->params->get('cropzoom'),
								'cache' => $img_cache
							];

							$_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $imgparams);

							$thumb_img   = $this->juimg->render($file, $_imgparams);
							$bloggallery = $this->_image($thumb_img, $this->params->get('width'), $this->params->get('height'), null, $_title, 0, $_title, null);

							return $bloggallery;
						}

						// Watermark
						$wmi = '';
						if($use_wm == 1)
						{
							if($watermark_gallery == '1' || $a_watermarkgall == '1')
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

						$imgsource = $file;
						if($watermark_gallery == '1' || $a_watermarkgall == '1')
						{
							$link_imgparams = [
								'w'     => $this->params->get('maxsize_orignew') == '1' ? $newmaxwidth : '',
								'h'     => $this->params->get('maxsize_orignew') == '1' ? $newmaxheight : '',
								'fltr'  => $wmi != '' ? $wmi : '',
								'cache' => $img_cache
							];

							$_link_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $link_imgparams);

							$imgsource = $this->juimg->render($file, $_link_imgparams);
						}

						// Small watermark
						$wmi_s = '';
						if($use_wm == 1)
						{
							if($watermark_gallery_s == '1' || $a_watermarkgall_s == '1')
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

						$imgparams = [
							'fltr'  => $wmi_s != '' ? $wmi_s : '',
							'w'     => $gallwidth,
							'h'     => $gallheight,
							'aoe'   => '1',
							'zc'    => $gallcropzoom,
							'cache' => $img_cache
						];

						$_imgparams = array_merge($imp_filtercolor, $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $imgparams);
						$thumb_img  = $this->juimg->render($file, $_imgparams);
						$_title     = ($galltitle == '' ? $img_title : $galltitle . '. ' . $img_title);
						$_title     = mb_strtoupper(mb_substr($_title, 0, 1)) . mb_substr($_title, 1);

						$_gallery[] = $this->_image($thumb_img, $gallwidth, $gallheight, null, $_title, 0, $_title, $imgsource, $file, $lightbox);
					}

					$gallery  = implode($_gallery);
					$template = $this->app->getTemplate();
					$tmpl     = $this->getTmpl($template, 'gallery');

					ob_start();
					require $tmpl;
					$html = ob_get_clean();
				}

				$text = preg_replace($regex, $html, $text, 1);
			}
		}

		return $text;
	}

	/**
	 * @param      $_img
	 * @param      $_w
	 * @param      $_h
	 * @param null $_class
	 * @param null $_alt
	 * @param null $_caption
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
	public function _image($_img, $_w, $_h, $_class = null, $_alt = null, $_caption = null, $_title = null, $_link_img = null, $_orig_img = null, $_lightbox = null)
	{
		$template = $this->app->getTemplate();

		switch($_lightbox)
		{
			case 'lightgallery':
				$link          = '#';
				$lightbox      = ' ';
				$lightbox_data = ' ' . ($_link_img ? 'data-src="' . JURI::base() . $_link_img . '"' : '') . ' ' . ($_orig_img ? 'data-download-url="' . JURI::base() . $_orig_img . '"' : '');
				break;

			case 'colorbox':
				$link          = $_link_img;
				$lightbox      = ' class="lightbox" rel="lightbox[gall]"';
				$lightbox_data = '';
				break;

			default:
			case 'jmodal':
				$link          = $_link_img;
				$lightbox      = ' class="modal" rel="{handler: \'image\', marginImage: {x: 50, y: 50}}"';
				$lightbox_data = '';
				break;
		}

		$tmpl = $this->getTmpl($template, 'image');

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
		$search = JPATH_SITE . '/templates/' . $template . '/html/plg_jumultithumb_gallery/' . $name . '.php';
		if(is_file($search))
		{
			return $search;
		}

		return JPATH_SITE . '/plugins/content/jumultithumb_gallery/tmpl/' . $name . '.php';
	}
}