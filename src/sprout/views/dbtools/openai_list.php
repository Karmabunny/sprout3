<?php

use Sprout\Helpers\Csrf;
use Sprout\Helpers\Form;
use Sprout\Helpers\Enc;

?>

<style>
    .json-data {
        word-break: break-all;
    }
    .json-data .fieldset-input {
        margin-bottom: 10px;
    }
</style>

<p>
    <a href="dbtools/openAiTools">&laquo; Return to tools list</a>
    | <a class="preview" id="select-all-none" href="javascript:;">Select all/none</a>
</p>

<script type="text/javascript">
$('#select-all-none').click(function(){
    var all_checked = true;
    $("input[name*='items[']").each(function() {
        if (!$(this).prop('checked')) all_checked = false;
    });
    if (all_checked) {
        $("input[name*='items[']").each(function() {
            $(this).prop('checked', false);
        });
    } else {
        $("input[name*='items[']").each(function() {
            $(this).prop('checked', true);
        });
    }
    return false;
});
</script>

<div>
    <form method="POST" action="dbtools/openAiListAction/<?php echo Enc::html($type); ?>">
        <?php echo Csrf::token(); ?>

        <?php Form::nextFieldDetails('Items', false); ?>
        <div class="json-data">
            <?php echo Form::checkboxSet('items', [], $item_opts); ?>
        </div>

        <?php Form::nextFieldDetails('Action', true); ?>
        <?php echo Form::dropdown('action',['-dropdown-top' => ''],  [
            '' => 'None',
            'delete' => 'Delete',
        ]); ?>

        <button type="submit" class="button">Process Action</button>
    </form>
</div>

