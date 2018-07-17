<?php
use Sprout\Helpers\Enc;
?>

<div class="search-symbol">
    <?php echo $form_field; ?>
</div>

<script>
    var autocomplete;
    var autocomplete_fields = <?php echo json_encode($options); ?>;
    Fb.initAutoCompleteAddress($('.js-autocomplete-address').attr('id'));
</script>
