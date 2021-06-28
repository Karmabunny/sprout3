<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Url;


Needs::fileGroup('magnific_popup');
Needs::fileGroup('video_gallery');
?>

<?php if (!empty($videos) and count($videos) > 0): ?>
<ul class="video-gallery -clearfix video-gallery--cols-<?php echo Enc::html($thumb_rows); ?>">
<?php foreach ($videos as $video): ?>
    <li class="video-gallery__item">
        <a href="<?php echo Enc::html(Url::addUrlScheme('www.youtube.com/watch?v=' . $video['id'])); ?>">
            <img src="<?php echo Enc::html($video['thumb_url']); ?>" alt="<?php echo Enc::html($video['title']); ?>">
        </a>
        <?php if ($captions): ?>
        <p class="video-gallery__item__caption"><?php echo Enc::html($video['title']); ?></p>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
