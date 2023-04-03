<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
?>
<ul class="children-gallery-list children-gallery-list--<?php echo Enc::html($thumb_rows); ?>">
<?php foreach ($page_node->children as $page): ?>

    <?php
    if ($hide_blanks) {
        if (empty($page['gallery_thumb'])) continue;
        if (!File::exists($page['gallery_thumb'])) continue;
    }
    $mod = $idx++ % 4;
    ?>

    <li class="children-gallery-list-item children-gallery-item-mod<?php echo Enc::html($mod); ?>">
        <a href="<?php echo Enc::html($page->getFriendlyUrl()); ?>" class="children-gallery-list-item-link">
            <div class="children-gallery-list-item__img-wrap">
                <?php if (!empty($page['gallery_thumb']) and File::exists($page['gallery_thumb'])): ?>
                <img src="<?php echo Enc::html(File::resizeUrl($page['gallery_thumb'], $image_resize)); ?>" class="children-gallery-list-item-image" alt="" role="presentation">
                <?php else: ?>
                <div class="children-gallery-list-item-image-placeholder"></div>
                <?php endif; ?>
            </div>
            <p class="children-gallery-list-item-title"><?php echo Enc::html($page->getNavigationName()); ?></p>
        </a>
    </li>
<?php endforeach; ?>
</ul>
