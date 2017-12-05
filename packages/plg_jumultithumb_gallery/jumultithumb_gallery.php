<?php
/**
 * JUMultiThumb
 *
 * @package          Joomla.Site
 * @subpackage       pkg_jumultithumb
 *
 * @author           Denys Nosov, denys@joomla-ua.org
 * @copyright        2007-2017 (C) Joomla! Ukraine, https://joomla-ua.org. All rights reserved.
 * @license          GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');

require_once(JPATH_SITE . '/plugins/content/jumultithumb/lib/links.php');
require_once(JPATH_SITE . '/libraries/julib/image.php');

class plgContentJUMULTITHUMB_Gallery extends JPlugin
{
	var $modeHelper;

	/**
	 * plgContentJUMULTITHUMB_Gallery constructor.
	 *
	 * @param $subject
	 * @param $config
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();

		$option = JRequest::getCmd('option');

		$adapter = JPATH_SITE . '/plugins/content/jumultithumb/adapters/' . $option . '.php';
		if(JFile::exists($adapter))
		{
			require_once($adapter);

			$mode_option      = 'plgContentJUMultiThumb_' . $option;
			$this->modeHelper = new $mode_option($this);
		}
	}

	/**
	 * @param $context
	 * @param $article
	 * @param $params
	 * @param $limitstart
	 *
	 * @return bool
	 *
	 * @since 6.0
	 */
	public function onContentBeforeDisplay($context, &$article, &$params, $limitstart)
	{
		$app = JFactory::getApplication();

		if($app->getName() != 'site')
		{
			return true;
		}

		if(!($this->modeHelper && $this->modeHelper->jView('Component')))
		{
			return true;
		}

		if($this->modeHelper && $this->modeHelper->jView('Article'))
		{
			return;
		}

		$autolinks = new AutoLinks();
		$link      = $this->modeHelper->jViewLink($article);

		$article->text = @$autolinks->handleImgLinks($article->text, $article->title, $link);

		return;
	}

	/**
	 * @param $context
	 * @param $article
	 * @param $params
	 * @param $limitstart
	 *
	 * @return bool
	 *
	 * @since 6.0
	 */
	public function onContentPrepare($context, &$article, &$params, $limitstart)
	{
		$app = JFactory::getApplication();

		if($app->getName() != 'site')
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

			foreach ($matches as $match)
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
	 * @since 6.0
	 */
	public function GalleryReplace($text, &$article)
	{
		$app = JFactory::getApplication();

		$JUImg = new JUImg();

		$regex = "/<p>\s*{gallery\s+(.*?)}\s*<\/p>/i";
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);

		if($matches)
		{
			$Itemid = $app->input->getInt('Itemid');
			$param  = $this->params;

			$plugin = JPluginHelper::getPlugin('content', 'jumultithumb');
			$json   = json_decode($plugin->params);

			$a_watermarkgall_s = $param->get('watermarkgall_s');
			$a_watermarkgall   = $param->get('watermarkgall');

			$attribs             = json_decode($article->attribs);
			$watermark_gallery   = $attribs->watermark_gallery;
			$watermark_gallery_s = $attribs->watermark_gallery_s;

			foreach ($matches as $match)
			{
				$matcheslist = explode('|', $match[1]);

				$galltitle = null;
				$gallstyle = null;
				$img_alt   = null;
				$img_style = null;
				$attr      = null;

				if(!array_key_exists(1, $matcheslist))
				{
					$matcheslist[1] = null;
				}

				if(!array_key_exists(2, $matcheslist))
				{
					$matcheslist[2] = $galltitle;
				}

				if(!array_key_exists(3, $matcheslist))
				{
					$matcheslist[3] = $gallstyle;
				}

				if(in_array($Itemid, ($param->get('menu_item1')) ? $param->get('menu_item1') : array()))
				{
					$maxsize_orignew = $param->get('maxsize_orignew1');
					$newmaxwidth     = $param->get('maxwidthnew1');
					$newmaxheight    = $param->get('maxheightnew1');

					$gallwidth    = $param->get('gallwidth1');
					$gallheight   = $param->get('gallheight1');
					$gallcropzoom = $param->get('gallcropzoom1');

					$galltitle         = ($param->get('usegallery_title1') == 1 ? $param->get('gallery_title1') : '');
					$gallstyle         = $param->get('cssclass1');
					$a_watermarkgall_s = $param->get('watermark_gall_s1');
					$a_watermarkgall   = $param->get('watermark_gall1');
				}
				elseif(in_array($Itemid, ($param->get('menu_item2')) ? $param->get('menu_item2') : array()))
				{
					$maxsize_orignew = $param->get('maxsize_orignew2');
					$newmaxwidth     = $param->get('maxwidthnew2');
					$newmaxheight    = $param->get('maxheightnew2');

					$gallwidth    = $param->get('gallwidth2');
					$gallheight   = $param->get('gallheight2');
					$gallcropzoom = $param->get('gallcropzoom2');

					$galltitle         = ($param->get('usegallery_title2') == 1 ? $param->get('gallery_title2') : '');
					$gallstyle         = $param->get('cssclass2');
					$a_watermarkgall_s = $param->get('watermark_gall_s2');
					$a_watermarkgall   = $param->get('watermark_gall2');
				}
				elseif(in_array($Itemid, ($param->get('menu_item3')) ? $param->get('menu_item3') : array()))
				{
					$maxsize_orignew = $param->get('maxsize_orignew3');
					$newmaxwidth     = $param->get('maxwidthnew3');
					$newmaxheight    = $param->get('maxheightnew3');

					$gallwidth    = $param->get('gallwidth3');
					$gallheight   = $param->get('gallheight3');
					$gallcropzoom = $param->get('gallcropzoom3');

					$galltitle         = ($param->get('usegallery_title3') == 1 ? $param->get('gallery_title3') : '');
					$gallstyle         = $param->get('cssclass3');
					$a_watermarkgall_s = $param->get('watermark_gall_s3');
					$a_watermarkgall   = $param->get('watermark_gall3');
				}
				elseif(in_array($Itemid, ($param->get('menu_item4')) ? $param->get('menu_item4') : array()))
				{
					$maxsize_orignew = $param->get('maxsize_orignew4');
					$newmaxwidth     = $param->get('maxwidthnew4');
					$newmaxheight    = $param->get('maxheightnew4');

					$gallwidth    = $param->get('gallwidth4');
					$gallheight   = $param->get('gallheight4');
					$gallcropzoom = $param->get('gallcropzoom4');

					$galltitle         = ($param->get('usegallery_title4') == 1 ? $param->get('gallery_title4') : '');
					$gallstyle         = $param->get('cssclass4');
					$a_watermarkgall_s = $param->get('watermark_gall_s4');
					$a_watermarkgall   = $param->get('watermark_gall4');
				}
				elseif(in_array($Itemid, ($param->get('menu_item5')) ? $param->get('menu_item5') : array()))
				{
					$maxsize_orignew = $param->get('maxsize_orignew5');
					$newmaxwidth     = $param->get('maxwidthnew5');
					$newmaxheight    = $param->get('maxheightnew5');

					$gallwidth    = $param->get('gallwidth5');
					$gallheight   = $param->get('gallheight5');
					$gallcropzoom = $param->get('gallcropzoom5');

					$galltitle         = ($param->get('usegallery_title5') == 1 ? $param->get('gallery_title5') : '');
					$gallstyle         = $param->get('cssclass5');
					$a_watermarkgall_s = $param->get('watermark_gall_s5');
					$a_watermarkgall   = $param->get('watermark_gall5');
				}
				else
				{
					$maxsize_orignew = $param->get('maxsize_orignew');
					$newmaxwidth     = $param->get('maxwidthnew');
					$newmaxheight    = $param->get('maxheightnew');

					$gallwidth    = $param->get('gallwidth');
					$gallheight   = $param->get('gallheight');
					$gallcropzoom = $param->get('gallcropzoom');

					$galltitle = str_replace('title=', '', trim($matcheslist[1]));
					if(!$galltitle)
					{
						$galltitle = $param->get('gallery_title');
					}

					$gallstyle = str_replace('class=', '', trim($matcheslist[2]));
					if(!$gallstyle)
					{
						$gallstyle = $param->get('cssclass');
					}
				}

				$img_cache = $param->get('img_cache');

				$img_title = preg_replace("/\"/", "'", $article->title);

				$lightbox = $param->get('selectlightbox');

				$folder     = trim($matcheslist[0]);
				$imgpath    = 'images/' . $folder;
				$root       = JPATH_ROOT . '/';
				$img_folder = $root . $imgpath;

				$html = '';
				if(is_dir($img_folder))
				{
					$images = glob($img_folder . "/{*.[jJ][pP][gG],*.[jJ][pP][eE][gG],*.[gG][iI][fF],*.[pP][nN][gG],*.[bB][mM][pP],*.[tT][iI][fF],*.[tT][iI][fF][fF]}", GLOB_BRACE);
					$images = str_replace($root, '', $images);

					$_gallery = array();
					foreach ($images as $file)
					{
						switch ($json->thumb_filtercolor)
						{
							case '1':
								$imp_filtercolor = array('fltr_1' => 'gray');
								break;

							case '2':
								$imp_filtercolor = array('fltr_1' => 'sep');
								break;

							case '3':
								$imp_filtercolor = array('fltr_1' => 'th|' . $json->thumb_th_seting);
								break;

							case '4':
								$imp_filtercolor = array('fltr_1' => 'clr|' . $json->colorized . '|' . str_replace('#', '', $json->colorpicker));
								break;

							default:
								$imp_filtercolor = array();
								break;
						}

						$usm_filtercolor = array();
						if($json->thumb_unsharp == 1)
						{
							$usm_filtercolor = array('fltr_2' => 'usm|' . $json->thumb_unsharp_amount . '|' . $json->thumb_unsharp_radius . '|' . $json->thumb_unsharp_threshold);
						}

						$blur_filtercolor = array();
						if($json->thumb_blur == 1)
						{
							$blur_filtercolor = array('fltr_3' => 'blur|' . $json->thumb_blur_seting);
						}

						$brit_filtercolor = array();
						if($json->thumb_brit == 1)
						{
							$brit_filtercolor = array('fltr_4' => 'brit|' . $json->thumb_brit_seting);
						}

						$cont_filtercolor = array();
						if($json->thumb_cont == 1)
						{
							$cont_filtercolor = array('fltr_5' => 'cont|' . $json->thumb_cont_seting);
						}

						if(!($this->modeHelper && $this->modeHelper->jView('Article')) && ($param->get('useimgagegallery') == '1'))
						{
							$_title = mb_strtoupper(mb_substr($img_title, 0, 1)) . mb_substr($img_title, 1);

							$imgparams = array(
								'w'     => $param->get('width'),
								'h'     => $param->get('height'),
								'aoe'   => '1',
								'zc'    => $param->get('cropzoom'),
								'cache' => $img_cache
							);

							$_imgparams = array_merge(
								$imp_filtercolor,
								$usm_filtercolor,
								$blur_filtercolor,
								$brit_filtercolor,
								$cont_filtercolor,
								$imgparams
							);

							$thumb_img   = $JUImg->Render($file, $_imgparams);
							$bloggallery = $this->_image($thumb_img, $param->get('width'), $param->get('height'), null, $_title, 0, $_title, null);

							return $bloggallery;
						}

						// Watermark
						$wmi = '';
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

							$wmi = 'wmi|' . $watermark . '|' . $param->get('wmposition') . '|' . $param->get('wmopst') . '|' . $param->get('wmx') . '|' . $param->get('wmy');
						}

						$imgsource = $file;
						if($watermark_gallery == '1' || $a_watermarkgall == '1')
						{
							$link_imgparams = array(
								'w'     => ($param->get('maxsize_orignew') == '1' ? $newmaxwidth : ''),
								'h'     => ($param->get('maxsize_orignew') == '1' ? $newmaxheight : ''),
								'fltr'  => ($wmi != '' ? $wmi : ''),
								'cache' => $img_cache
							);

							$_link_imgparams = array_merge(
								$imp_filtercolor,
								$usm_filtercolor,
								$blur_filtercolor,
								$brit_filtercolor,
								$cont_filtercolor,
								$link_imgparams
							);

							$imgsource = $JUImg->Render($file, $_link_imgparams);
						}

						// Small watermark
						$wmi_s = '';
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
							$wmi_s = 'wmi|' . $watermark_s . '|' . $param->get('wmposition_s') . '|' . $param->get('wmopst_s') . '|' . $param->get('wmx_s') . '|' . $param->get('wmy_s');
						}

						$imgparams = array(
							'fltr'  => ($wmi_s != '' ? $wmi_s : ''),
							'w'     => $gallwidth,
							'h'     => $gallheight,
							'aoe'   => '1',
							'zc'    => $gallcropzoom,
							'cache' => $img_cache
						);

						$_imgparams = array_merge(
							$imp_filtercolor,
							$usm_filtercolor,
							$blur_filtercolor,
							$brit_filtercolor,
							$cont_filtercolor,
							$imgparams
						);

						$thumb_img = $JUImg->Render($file, $_imgparams);

						$_title = ($galltitle == '' ? $img_title : $galltitle . '. ' . $img_title);
						$_title = mb_strtoupper(mb_substr($_title, 0, 1)) . mb_substr($_title, 1);

						$_gallery[] = $this->_image($thumb_img, $gallwidth, $gallheight, null, $_title, 0, $_title, $imgsource, $file, $lightbox);
					}

					$gallery = implode($_gallery);

					$app      = JFactory::getApplication();
					$template = $app->getTemplate();
					$tmpl     = $this->getTmpl($template, 'gallery');

					ob_start();
					require $tmpl;
					$html = ob_get_contents();
					ob_end_clean();
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
	 * @since 6.0
	 */
	public function _image($_img, $_w, $_h, $_class = null, $_alt = null, $_caption = null, $_title = null, $_link_img = null, $_orig_img = null, $_lightbox = null)
	{
		$app      = JFactory::getApplication();
		$template = $app->getTemplate();

		switch ($_lightbox)
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
		$img = ob_get_contents();
		ob_end_clean();

		return $img;
	}

	/**
	 * @param $template
	 * @param $name
	 *
	 * @return string
	 *
	 * @since 6.0
	 */
	public function getTmpl($template, $name)
	{
		$search = JPATH_SITE . '/templates/' . $template . '/html/plg_jumultithumb_gallery/' . $name . '.php';

		if(is_file($search))
		{
			$tmpl = $search;
		}
		else
		{
			$tmpl = JPATH_SITE . '/plugins/content/jumultithumb_gallery/tmpl/' . $name . '.php';
		}

		return $tmpl;
	}
}