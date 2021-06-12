<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\Lnk;


if (empty($banners[0]) or empty($banners[0]['filename']) or !File::exists($banners[0]['filename'])) return;
if (empty($banners[0]['link_label'])) $banners[0]['link_label'] = 'Read more';
?>

<div class="section section--hero-banner bg-white">
    <img class="hero-banner__img" src="<?= Enc::html(File::resizeUrl($banners[0]['filename'], 'c1600x400-cc~80')); ?>" alt="">

    <div class="hero-banner__text bg-navyblue">
        <div class="container">
            <?php if (!empty($banners[0]['heading'])): ?>
                <h2 class="hero-banner__heading"><?= Enc::html($banners[0]['heading']); ?></h2>
            <?php endif; ?>

            <?php if (!empty($banners[0]['description'])): ?>
                <p><?= Enc::html($banners[0]['description']); ?></p>
            <?php endif; ?>

            <?php if (!empty($banners[0]['link'])): ?>
                <p><a class="button button-large" href="<?= Enc::html(Lnk::url($banners[0]['link'])); ?>"><?= Enc::html($banners[0]['link_label']); ?></a></p>
            <?php endif; ?>
        </div>
    </div>
</div>
