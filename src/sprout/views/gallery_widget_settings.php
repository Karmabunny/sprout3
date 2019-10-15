<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;

$unique = md5(microtime(true));
?>

<div class="js--<?php echo Enc::html($unique); ?>">
    <div class="field-group-wrap -clearfix">
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Category', true);
            echo Form::dropdown('category', [], $cats);
            ?>
        </div>
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Display order', true);
            echo Form::dropdown('order', [], $ordering);
            ?>
        </div>
    </div>

    <div class="field-group-wrap -clearfix">
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Max number of images to show', true);
            echo Form::text('limit');
            ?>
        </div>
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Captions', true);
            echo Form::dropdown('captions', [], ['0' => 'No', '1' => 'Yes']);
            ?>
        </div>
    </div>

    <div class="field-group-wrap -clearfix">
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Cropping anchor', false);
            echo Form::dropdown('cropping', [], $cropping);
            ?>
        </div>
        <div class="field-group-item col col--one-half">
            <?php
            Form::nextFieldDetails('Gallery type', false);
            echo Form::dropdown('display_opts', ['class' => 'js--display-opts'], $display);
            ?>
        </div>
    </div>

    <div class="js--settings-grid">
        <div class="field-group-wrap -clearfix">
            <?php
            Form::nextFieldDetails('Thumbnails per row', false);
            echo Form::dropdown('thumb_rows', [], ['4' => '4', '5' => '5']);
            ?>
        </div>
    </div>

    <div class="js--settings-slider">
        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-half">
                <?php
                Form::nextFieldDetails('Slider options', false);
                echo Form::checkboxList(['slider_dots' => 'Dots', 'slider_arrows' => 'Arrows', 'slider_autoplay' => 'Auto-scroll'], []);
                ?>
            </div>
            <div class="field-group-item col col--one-half">
                <?php
                Form::nextFieldDetails('Auto-scroll timer', false, 'Seconds');
                echo Form::number('slider_speed', []);
                ?>

                <?php
                Form::nextFieldDetails('Images per slide', false);
                echo Form::dropdown('num_images', [], ['1' => '1', '2' => '2', '3' => '3', '4' => '4']);
                ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var unique = '<?php echo Enc::js($unique); ?>';

    $('.js--' + unique + ' .js--display-opts').on('change', function() {
        if ($(this).val() == 'grid') {
            $('.js--' + unique + ' .js--settings-grid').show();
            $('.js--' + unique + ' .js--settings-slider').hide();
        } else if ($(this).val() == 'slider') {
            $('.js--' + unique + ' .js--settings-grid').hide();
            $('.js--' + unique + ' .js--settings-slider').show();
        } else {
            $('.js--' + unique + ' .js--settings-grid').hide();
            $('.js--' + unique + ' .js--settings-slider').hide();
        }
    });

    $('.js--' + unique + ' .js--display-opts').change();
})
</script>
