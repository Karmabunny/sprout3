<?php
use Sprout\Helpers\Form;
?>

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
        Form::nextFieldDetails('Thumbnails per row', false);
        echo Form::dropdown('thumb_rows', [], ['4' => '4', '5' => '5']);
        ?>
    </div>
</div>
