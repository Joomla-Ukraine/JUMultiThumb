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

/*
*       Params :
*       $gallstyle  — css class fom short code
*       $galltitle  — title form short code or plugin setting
*       $gallery    — display gallery
*/

?>
<div class="juphotogallery<?php echo (isset($gallstyle) ? ' '. $gallstyle : ''); ?>">
    <?php if($galltitle != '') : ?>
    <h3 class="jutitlegallery">
        <?php echo $galltitle; ?>
    </h3>
    <?php endif; ?>

    <div class="jugallerybody row row-photo" itemscope itemtype="http://schema.org/ImageGallery">
        <?php echo $gallery; ?>
    </div>
</div>