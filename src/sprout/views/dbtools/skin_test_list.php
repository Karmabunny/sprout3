<?php
use Sprout\Helpers\Enc;
?>

<div class="info highlight-neutral">Note: Not all templates will work if they rely on variables being set which are not set by this tool.</div>

<?php if (!empty($skins) and count($skins)): ?>
    <?php foreach($skins as $name => $list): ?>
    <h3><?= Enc::html($name); ?></h3>
    <?= $list; ?>
    <?php endforeach; ?>
<?php endif; ?>
