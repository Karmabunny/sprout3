<?php
use Sprout\Helpers\Enc;
?>


<div class="fb-conditions-list" data-params="<?php echo Enc::html(json_encode($params)); ?>">
    <input type="hidden" class="fb-conditions--data" name="<?php echo Enc::html($name); ?>" value="<?php echo Enc::html($data); ?>">
    <div class="fb-conditions--list"></div>
    <button class="fb-conditions--add button button-grey button-icon icon-before icon-add" type="button"><span class="-vis-hidden">Add condition</span></button>
</div>
