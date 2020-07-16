<?php
use Sprout\Helpers\Form;
?>

<?php
Form::nextFieldDetails('Play-list URL', true);
echo Form::text('playlist_id');
?>

<div class="field-group-wrap -clearfix">
    <div class="field-group-item col col--one-half">
        <?php
        Form::nextFieldDetails('Captions', true);
        echo Form::dropdown('captions', [], ['0' => 'No', '1' => 'Yes']);
        ?>
    </div>
    <div class="field-group-item col col--one-half">
        <?php
        Form::nextFieldDetails('Thumbnails per row', false);
        echo Form::dropdown('thumb_rows', [], $thumbs);
        ?>
    </div>
</div>
