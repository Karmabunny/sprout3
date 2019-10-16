<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;

Needs::addCssInclude('https://unpkg.com/leaflet@1.5.1/dist/leaflet.css', ['integrity' => 'sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==', 'crossorigin' => ''], 'leaflet_css');
Needs::addJavascriptInclude('https://unpkg.com/leaflet@1.5.1/dist/leaflet.js', ['integrity' => 'sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==', 'crossorigin' => ''], 'leaflet_js');
Needs::fileGroup('sprout/map_widget');
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

    <div id="map_<?php echo Enc::html($unique); ?>" class="fb-google-map--inner"></div>
</div>

