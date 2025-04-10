
<?php

use Sprout\Helpers\Form;
use Sprout\Helpers\Phones;

?>

<div class="field-element field-element--text field-element--required">
    <div class="field-label">
        <label for="field0"><?php echo $label ?? 'Phone (mobile)'; ?>
            <?php if ($required) { ?>
                <span class="field-label__required">required</span>
            <?php } ?>
        </label>
    </div>
    <div class="field-input">
        <div class="row">
            <div class="col-xs-12 col-sm-4 col-md-3">
                <?php echo Form::dropdown($field_names[1], ['-dropdown-top' => 'Country'], Phones::countryPhoneCodeOptGroups($common)); ?>
            </div>
            <div class="col-xs-12 col-sm-8 col-md-9">
                <?php echo Form::number($field_names[0], ['placeholder' => 'Phone number']); ?>
            </div>
        </div>
    </div>
</div>