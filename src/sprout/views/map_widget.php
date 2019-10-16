<?php
use Sprout\Helpers\Enc;
?>

<?php if (!empty($align)): ?><div class="<?php echo Enc::html($align); ?>"><?php endif; ?>

<div id="map_<?php echo Enc::html($unique); ?>" style="width: <?php echo Enc::html($width); ?>px; height: <?php echo Enc::html($height); ?>px;"></div>
<script>
var map_<?php Enc::js($unique); ?> = 'map_<?php echo Enc::js($unique); ?>';
$(document).ready(function()
{
    initMapWidget(map_<?php echo Enc::js($unique); ?>,
        <?php echo Enc::js($latlng['lat']); ?>,
        <?php echo Enc::js($latlng['lng']); ?>,
        <?php echo Enc::js($zoom); ?>,
        true
    );
});
</script>
<?php if (!empty($align)): ?></div><?php endif; ?>
