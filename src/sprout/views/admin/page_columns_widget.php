<?php
use Sprout\Helpers\Form;
?>

<div class="field-group-wrap -clearfix">
    <div class="field-group-item col col--one-half">
        <?php
        Form::nextFieldDetails('Column layout', true);
        echo Form::dropdown('column', [], $columns);
        ?>
    </div>

    <div class="field-group-item col col--one-half">
        <?php
        Form::nextFieldDetails('1st column style', false);
        echo Form::dropdown('style_col1', [], $styles);
        ?>
    </div>

    <div class="field-group-item col col--one-half">
        <?php
        Form::nextFieldDetails('2nd column style', false);
        echo Form::dropdown('style_col2', [], $styles);
        ?>
    </div>

    <div class="field-group-item col col--one-half">
        <?php
        Form::nextFieldDetails('3rd column style', false);
        echo Form::dropdown('style_col3', [], $styles);
        ?>
    </div>

</div>
