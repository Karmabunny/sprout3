<?php
use Sprout\Helpers\Enc;
?>


<?php foreach ($items as $item): ?>

    <h2><a href="<?php echo Enc::html($item['url']); ?>"><?php echo Enc::html($item['name']); ?></a></h2>

    <?php if (isset($item['image'])): ?>
        <img src="<?php echo Enc::html($item['image']); ?>" width="200" class="right">
    <?php endif; ?>

    <p><b><?php echo Enc::html($item['date']->format('d M Y \a\t g:ia')); ?></b></p>

    <p><?php echo Enc::html($item['text']); ?></p>

    <div class="clear"></div>

<?php endforeach; ?>
