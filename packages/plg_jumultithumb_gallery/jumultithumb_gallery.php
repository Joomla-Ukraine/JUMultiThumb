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

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use JUMultiThumb\Helpers\AutoLinks;
use JUMultiThumb\Helpers\Utils;

defined('_JEXEC') or die;

require_once dirname(__DIR__) . '/jumultithumb/libraries/vendor/autoload.php';

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
	 * @param $params
	 * @param $limitstart
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 7.0
	 */
	public function onContentBeforeDisplay($context, $article, $params, $limitstart): void
	{
		if(class_exists($this->modeHelper) === false)
		{
			return;
		}

		if($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
		{
			return;
		}

		if($this->modeHelper::view('Article'))
		{
			return;
		}

		$link          = $this->modeHelper::link($article);
		$article->text = AutoLinks::handleImgLinks($article->text, $article->title, $link, 1);
	}

	/**
	 * @param $context
	 * @param $article
	 *
	 * @return void
	 *
	 * @throws Exception
	 * @since 7.0
	 */
	public function onContentPrepare($context, $article): void
	{
		if(class_exists($this->modeHelper) === false)
		{
			return;
		}

		if($this->app->getName() !== 'site' || !$this->modeHelper::view('Component'))
		{
			return;
		}

		if(!($this->modeHelper::view('Article')) && ($this->params->get('useimgagegallery') == '0'))
		{
			$regex = "/<p>\s*{gallery\s+(.*?)}\s*</p>/i";
			preg_match_all($regex, $article->text, $matches, PREG_SET_ORDER);

			foreach($matches as $val)
			{
				if($val)
				{
					$article->text = preg_replace($regex, '', $article->text, 1);

					return;
				}
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
	public function GalleryReplace($text, $article)
	{
		$regex = "/<p>\s*{gallery\s+(.*?)}\s*<\/p>/i";
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);

		if($matches)
		{
			$plugin = PluginHelper::getPlugin('content', 'jumultithumb');
			$json   = json_decode($plugin->params);

			foreach($matches as $match)
			{
				$matcheslist = explode('|', $match[ 1 ]);

				$galltitle = null;
				$gallstyle = null;

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

						if(!$this->modeHelper::view('Article') && ($this->params->get('useimgagegallery') == '1'))
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

							$thumb_img = $this->juimg->render($file, $_imgparams);

							return $this->_image([
								'image'    => $thumb_img,
								'orig_img' => $file,
								'link_img' => $file,
								'w'        => $this->params->get('width'),
								'h'        => $this->params->get('height'),
								'title'    => $_title,
								'caption'  => $_title
							]);
						}

						$imgsource = $file;

						$imgparams = [
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

						$_gallery[] = $this->_image([
							'image'    => $thumb_img,
							'orig_img' => $imgsource,
							'link_img' => $file,
							'w'        => $gallwidth,
							'h'        => $gallheight,
							'title'    => $_title,
							'caption'  => $_title,
							'lightbox' => $lightbox
						]);
					}

					$gallery = implode($_gallery);

					$html = Utils::tmpl('jumultithumb_gallery', 'gallery', [
						'gallery'   => $gallery,
						'gallstyle' => $gallstyle,
						'galltitle' => $galltitle
					]);
				}

				$text = preg_replace($regex, '' . $html, $text, 1);
			}
		}

		return $text;
	}

	/**
	 * @param      $options
	 *
	 * @return string
	 *
	 * @throws \Exception
	 * @since 7.0
	 */
	public function _image(array $options = []): string
	{
		switch($options[ 'lightbox' ])
		{
			case 'lightgallery':
				$link          = '#';
				$lightbox      = ' ';
				$lightbox_data = ' ' . ($options[ 'link_img' ] ? 'data-src="' . JURI::base() . $options[ 'link_img' ] . '"' : '') . ' ' . ($options[ 'orig_img' ] ? 'data-download-url="' . JURI::base() . $options[ 'orig_img' ] . '"' : '');
				break;

			case 'colorbox':
				$link          = $options[ 'link_img' ];
				$lightbox      = ' class="lightbox" rel="lightbox[gall]"';
				$lightbox_data = '';
				break;

			default:
			case 'jmodal':
				$link          = $options[ 'link_img' ];
				$lightbox      = ' rel="{handler: \'image\', marginImage: {x: 50, y: 50}}"';
				$lightbox_data = '';
				break;
		}

		return Utils::tmpl('jumultithumb_gallery', 'image', [
			'img'            => $options[ 'image' ],
			'w'              => $options[ 'w' ],
			'h'              => $options[ 'h' ],
			'class'          => $options[ 'class' ],
			'alt'            => $options[ 'alt' ],
			'caption'        => $options[ 'caption' ],
			'title'          => $options[ 'title' ],
			'link_img'       => $options[ 'link_img' ],
			'orig_img'       => $options[ 'orig_img' ],
			'link'           => $link,
			'lightbox'       => $lightbox,
			'lightbox_data ' => $lightbox_data
		]);
	}
}