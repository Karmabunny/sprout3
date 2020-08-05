<?php
use Sprout\Helpers\Enc;
?>

<div class="columns js-random-code">
    <?php echo $form_field; ?>
    <button type="button" class="button column column-3">
        Generate Code
    </button>
</div>

<script>
    Fb.initRandomCode(
        "<?= Enc::id($form_id); ?>",
        <?= json_encode($options); ?>);
</script>
