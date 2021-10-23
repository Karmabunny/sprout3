<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;

Form::setData($_GET);
?>

<form action="" method="get" class="field-group-wrap -clearfix">

    <div class="field-group-item col col--one-half">
        <?php
        Form::nextFieldDetails('String', true);
        echo Form::text('payload');
        ?>
    </div>

    <div class="field-group-item col col--one-half" style="margin-top: 2em;">
        <button class="button icon-after icon-send" type="submit">Update</button>
    </div>

</form>

<?php if (!empty($img)): ?>
<img src="<?= Enc::html($img); ?>" alt="">
<?php endif; ?>
