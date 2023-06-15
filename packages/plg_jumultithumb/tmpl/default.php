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

defined('_JEXEC') or die;

/*
*       Params :
*       $_noresize  — use rule for original image
*       $_img       — thumb image
*       $_link_img  — link for original
*       $_w         — image width
*       $_h         — image height
*       $_caption   — use caption from alt or title
*       $_title     — title for attribute title
*       $_alt       — title for attribute alt
*       $_class     — css class
*       $lightbox   — litebox (lightgallery, colorbox, jmodal)
*/

$data = (object) $displayData;

?>
<?php if($data->noresize == 1): ?>
	<div class="row row-fluid">
	<div class="col-xs-12 span12">
<?php endif; ?>

	<figure class="<?php echo $data->class . ($data->noresize == 1 ? ' thumbnail' : ''); ?>">
		<?php if($data->link_img) : ?>
		<a href="<?php echo $data->link_img; ?>"<?php echo $data->title . $data->lightbox; ?>>
			<?php endif; ?>

			<img
					src="<?php echo $data->img; ?>"
					alt="<?php echo $data->alt; ?>"
				<?php echo ($data->w ? 'width="' . $data->w . '"' : '') . ($data->h ? 'height="' . $data->h . '"' : ''); ?>
			>

			<?php if($data->alt != ''): ?>
				<?php if($data->caption == 1): ?>
					<figcaption itemprop="caption" class="text-muted"><?php echo $data->alt; ?></figcaption>
				<?php endif; ?>
			<?php endif; ?>

			<?php if($data->link_img): ?>
		</a>
	<?php endif; ?>

	</figure>

<?php if($data->noresize == 1): ?>
	</div>
	</div>
<?php endif; ?>