<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;

Needs::googleMaps();
?>

<div class="fb-google-map">
    <input type="hidden" data-field="lat" name="<?php echo Enc::html($names[0]); ?>" value="<?php echo Enc::html($values[0]); ?>">
    <input type="hidden" data-field="lng" name="<?php echo Enc::html($names[1]); ?>" value="<?php echo Enc::html($values[1]); ?>">

    <?php if (isset($names[2])): ?>
        <?php if (!empty($values[2])): ?>
            <input type="hidden" data-field="zoom" name="<?php echo Enc::html($names[2]); ?>" value="<?php echo Enc::html($values[2]); ?>">
        <?php else: ?>
            <input type="hidden" data-field="zoom" name="<?php echo Enc::html($names[2]); ?>" value="9">
        <?php endif; ?>
    <?php endif; ?>

    <div class="fb-google-map--search">

        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--two-third">
                <?php
                Form::nextFieldDetails('', false);
                echo Form::text('fb-google-map--search-name', ['-wrapper-class' => 'white', 'placeholder' => 'Search...', 'id' => 'fb-google-map--search-name', 'class' => 'fb-google-map--search-name']);
                ?>
            </div>
            <div class="field-group-item col col--one-third">
                <button type="button" class="button button-blue icon-after icon-search fb-google-map--search-go">Search</button>
            </div>
        </div>

    </div>

    <div class="fb-google-map--inner"></div>
</div>

