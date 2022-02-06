<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Inflector;
?>

<div class="paginate-bar -clearfix">

    <?php if (!empty($next_url)): ?>
    <a class="paginate-bar-button right paginate-bar-next button button-grey button-small icon-after icon-keyboard_arrow_right" href="<?= Enc::html($next_url); ?>">Next</a>
    <?php endif; ?>

    <?php if (!empty($prev_url)): ?>
    <a class="paginate-bar-button left paginate-bar-previous button button-grey button-small icon-before icon-keyboard_arrow_left" href="<?= Enc::html($prev_url); ?>">Prev</a>
    <?php endif; ?>

    <div class="paginate-bar-text">
        <?= Enc::html(sprintf('%u %s', $total_records, Inflector::plural('record', $total_records))); ?> &bull; <?= Enc::html(sprintf('Page %u of %u', $current_page, $total_pages)); ?>
    </div>
</div>
