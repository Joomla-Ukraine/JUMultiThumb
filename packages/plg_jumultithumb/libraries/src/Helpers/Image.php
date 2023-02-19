<?php
/**
 * @package     JUMultiThumb\Helpers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace JUMultiThumb\Helpers;

use JUImage;

\JLoader::register('JUImage', JPATH_LIBRARIES . '/juimage/JUImage.php');

class Image
{
	public static function thumb(array $options = []): string
	{
		$juImg = new JUImage();

		$aspect = '0';
		if($options[ 'zc' ] == 1 && isset($options[ 'cropaspect' ]))
		{
			$aspect = self::aspect($options[ 'image' ], $options[ 'cropaspect' ]);
		}

		if($aspect >= 1 && $options[ 'zc' ] == 4)
		{
			$options[ 'zc' ] = 'T';
		}

		if($aspect >= 1 && $options[ 'zc' ] == 2)
		{
			$new_imgparams = [
				'far' => '1',
				'bg'  => str_replace('#', '', $options[ 'zoom_crop_bg' ])
			];
		}
		else
		{
			$new_imgparams = [
				'zc' => $options[ 'zc' ]
			];
		}

		if($options[ 'zc' ] == 3)
		{
			$new_imgparams = [
				'far' => $options[ 'far_crop' ],
				'bg'  => str_replace('#', '', $options[ 'zoom_crop_bg' ])
			];
		}

		/*$usm_filtercolor = [];
		if($options[ 'typo' ]->get('filters') == 1 && $options[ 'typo' ]->get('unsharp') == 1)
		{
			$usm_filtercolor = [
				'fltr_2' => 'usm|' . $options[ 'typo' ]->get('amount') . '|' . $options[ 'typo' ]->get('radius') . '|' . $options[ 'typo' ]->get('threshold')
			];
		}*/

		/*$blur_filtercolor = [];
		if($options[ 'typo' ]->get('filters') == 1 && $options[ 'typo' ]->get('blur'))
		{
			$blur_filtercolor = [
				'fltr_3' => 'blur|' . $options[ 'typo' ]->get('blur')
			];
		}

		$brit_filtercolor = [];
		if($options[ 'typo' ]->get('filters') == 1 && $options[ 'typo' ]->get('brit'))
		{
			$brit_filtercolor = [
				'fltr_4' => 'brit|' . $options[ 'typo' ]->get('brit')
			];
		}

		$cont_filtercolor = [];
		if($options[ 'typo' ]->get('filters') == 1 && $options[ 'typo' ]->get('cont'))
		{
			$cont_filtercolor = [
				'fltr_5' => 'cont|' . $options[ 'typo' ]->get('cont')
			];
		}
*/
		/*$img_format = [];
		if($options[ 'typo' ]->get('img_format'))
		{
			$img_format = [
				'f' => $options[ 'typo' ]->get('img_format')
			];
		}*/

		$imgparams = [
			'w'     => $options[ 'w' ],
			'h'     => $options[ 'h' ],
			'q'     => $options[ 'q' ],
			'cache' => 'img'
		];

		$blur_bg = [];
		if($aspect >= 0.98 && $options[ 'zc' ] == 5)
		{
			$_imgsource = $juImg->render($options[ 'image' ], [
				'h'     => $options[ 'h' ],
				'cache' => 'img'
			]);
			$_imgsource = JPATH_BASE . '/' . $_imgsource;

			$blur_bg = [
				'blur_bg'  => 100,
				'fltr_100' => 'wmi|' . $_imgsource . '|C|100',
			];
		}

		$_imgparams = array_merge($imgparams, $new_imgparams, $blur_bg); // $usm_filtercolor, $blur_filtercolor, $brit_filtercolor, $cont_filtercolor, $img_format,
		$thumb      = $juImg->render($options[ 'image' ], $_imgparams);

		/*if($aspect >= 0.98 && $options[ 'zc' ] == 5 && is_file($_imgsource))
		{
			unlink($_imgsource);
		}*/

		return $thumb;
	}

	/**
	 * @param $html
	 * @param $_cropaspect
	 *
	 * @return float|int
	 *
	 * @since 7.0
	 */
	private static function aspect(string $html, float $_cropaspect)
	{
		$juImg = new JUImage();

		$size   = $juImg->size(rawurldecode(JPATH_SITE . '/' . $html));
		$width  = $size->width;
		$height = ($size->height * ($_cropaspect !== '' ? $_cropaspect : '0'));

		return $height / $width;
	}
}