<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Text;
?>

<style>
.dbtools-box { color: #333; text-decoration: none; min-height: 150px; }
.dbtools-box:hover { color: #333; text-decoration: none; box-shadow: 0 0 2px 2px #c3c7d4; }
.dbtools-box h4 { margin: 0 0 0.5em 0; font-size: 20px; }
.dbtools-box span { font-size: small; color: #666; }
</style>

<div class="info">These tools allow you to manage various aspects of this SproutCMS install.</div>

<?php foreach ($sections as $section => $tools): ?>
<h3><?= Enc::html($section); ?></h3>
<div class="dbtools-wrap columns">
    <?php $index = 0; ?>

    <?php foreach ($tools as $tool): ?>
    <a href="<?= Enc::html($tool['url']); ?>" class="<?= Enc::html($base_class . (++$index % 4 === 0 ? ' column-last' : '')); ?>">
        <h4><?= Enc::html($tool['name']); ?></h4>
        <p><?= Text::limitedSubsetHtml($tool['desc']); ?></p>
    </a>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>
