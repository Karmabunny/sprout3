<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\Lnk;


if (empty($banner) or empty($banner['filename']) or !File::exists($banner['filename'])) {
    return;
}

if (empty($banner['link_label'])) {
    $banner['link_label'] = 'Read more';
}
?>


<div class="section section--hero-banner bg-white">
    <img class="hero-banner__img" src="<?= Enc::html(File::resizeUrl($banner['filename'], 'c1600x400-cc~80')); ?>" alt="">

    <div class="hero-banner__text bg-navyblue">
        <div class="container">
            <?php if (!empty($banner['header'])): ?>
                <h2 class="hero-banner__heading"><?= Enc::html($banner['header']); ?></h2>
            <?php endif; ?>

            <?php if (!empty($banner['description'])): ?>
                <p><?= Enc::html($banner['description']); ?></p>
            <?php endif; ?>

            <?php if (!empty($banner['link'])): ?>
                <p><a class="button button-large" href="<?= Enc::html(Lnk::url($banner['link'])); ?>"><?= Enc::html($banner['link_label']); ?></a></p>
            <?php endif; ?>
        </div>
    </div>
</div>
