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
*       $_img       — thumb image
*       $_link_img  — link for original
*       $_w         — image width
*       $_h         — image height
*       $_caption   — use caption from alt or title
*       $_title     — title for attribute title
*       $_alt       — title for attribute alt
*       $_class     — css class
*       $lightbox_data  — litebox group data (lightgallery)
*       $lightbox   — litebox (lightgallery, colorbox, jmodal)
*/

?>
<div class="col-xs-4 col-sm-3"<?php echo $lightbox_data; ?>>
    <div class="<?php echo $_class; ?> thumbnail">
        <figure
            class="galleryobjcts"
            itemprop="associatedMedia"
            itemscope
            itemtype="http://schema.org/ImageObject"
        >
            <?php if($_link_img) : ?>
            <a
                itemprop="contentUrl"
                href="<?php echo $link; ?>"
                <?php echo ($_title ? ' title="'. $_title .'"' : '') . $lightbox; ?>
            >
            <?php endif; ?>

                <img
                    itemprop="thumbnail"
                    src="<?php echo $_img; ?>"
                    alt="<?php echo $_alt; ?>"
                    width="<?php echo $_w; ?>"
                    height="<?php echo $_h; ?>"
                />

                <?php if($_w): ?>
                <meta itemprop="width" content="<?php echo $_w; ?>" />
                <?php endif; ?>

                <?php if($_h): ?>
                <meta itemprop="height" content="<?php echo $_h; ?>" />
                <?php endif; ?>

                <?php if($_alt != ''): ?>
                    <?php if($_caption == 1): ?>
                    <figcaption
                        itemprop="caption"
                        class="text-muted"
                    >
                        <?php echo $_alt; ?>
                    </figcaption>
                    <?php else: ?>
                    <meta itemprop="caption" content="<?php echo $_alt; ?>" />
                    <?php endif; ?>
                <?php endif; ?>

            <?php if($_link_img): ?>
            </a>
            <?php endif; ?>

        </figure>
    </div>
</div>