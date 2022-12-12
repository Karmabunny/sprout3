<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Needs;


Needs::fileGroup('sprout/admin_multiselect_tools');
?>

<form action="" method="get" class="selection-action">
    <?= Csrf::token(); ?>

    <?= $itemlist; ?>

    <?php if (!empty($selected_tools) and count($selected_tools)): ?>
    <div class="selected-tools">
        <strong>Selected <?= Enc::html(strtolower($friendly_name)); ?>:</strong>

        <ul class="inline-list">
            <?php foreach ($selected_tools as $tool): ?>
            <li>
                <a href="<?= Enc::html($tool['url']); ?>" class="<?= Enc::html($tool['class']); ?>"><?= Enc::html($tool['label']); ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</form>
