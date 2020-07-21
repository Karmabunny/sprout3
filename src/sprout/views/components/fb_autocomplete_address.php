<?php
use Sprout\Helpers\Enc;
?>

<div class="search-symbol">
    <?php echo $form_field; ?>
</div>

<script>
    var autocomplete;
    Fb.initAutoCompleteAddress($('.js-autocomplete-address').attr('id'),
    <?php echo json_encode($options); ?>);
</script>
