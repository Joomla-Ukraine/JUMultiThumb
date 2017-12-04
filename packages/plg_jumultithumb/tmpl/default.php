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

?>
<?php if($_noresize == 1): ?>
<div class="row row-fluid">
    <div class="col-xs-12 span12">
<?php endif; ?>

        <figure
        class="<?php echo $_class . ($_noresize == 1 ? ' thumbnail' : ''); ?>"
        itemprop="image"
        itemscope itemtype="https://schema.org/ImageObject"
        >
            <?php if($_link_img && $_noresize == 1) : ?>
            <a href="<?php echo $_link_img; ?>"<?php echo $_title . $lightbox; ?>>
            <?php endif; ?>

            <img
            src="<?php echo $_img; ?>"
            alt="<?php echo $_alt; ?>"
            itemprop="url"
            <?php echo ($_w ? 'width="'. $_w .'"' : '') . ($_h ? 'height="'. $_h .'"' : ''); ?>
            >

            <?php if($_w): ?>
            <meta itemprop="width" content="<?php echo $_w; ?>" />
            <?php endif; ?>

            <?php if($_h): ?>
            <meta itemprop="height" content="<?php echo $_h; ?>" />
            <?php endif; ?>

            <?php if($_alt != ''): ?>
                <?php if($_caption == 1): ?>
                <figcaption itemprop="caption" class="text-muted"><?php echo $_alt; ?></figcaption>
                <?php else: ?>
                <meta itemprop="caption" content="<?php echo $_alt; ?>" />
                <?php endif; ?>
            <?php endif; ?>

            <?php if($_link_img && $_noresize == 1): ?>
            </a>
            <?php endif; ?>

        </figure>

<?php if($_noresize == 1): ?>
    </div>
</div>
<?php endif; ?>