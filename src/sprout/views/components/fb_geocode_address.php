<?php
use Sprout\Helpers\Enc;
?>

<div class="search-symbol fb-geocode">
    <?php echo $form_field; ?>
    <div class="js-geocode-results fb-geocode__results"></div>
</div>

<script>
    var geocode;
    Fb.initGeocodeAddress($('.js-geocode-address').attr('id'),
        <?php echo json_encode($options); ?>);
</script>
