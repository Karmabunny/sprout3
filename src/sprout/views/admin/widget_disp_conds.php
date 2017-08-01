<?php
use Sprout\Helpers\Form;
?>


<style>
#facebox button.close { display: none !important; }
.widget-conds-wrap { width: 900px; }
.widget-conds-wrap h2 { margin-top: 0; }
</style>


<div class="widget-conds-wrap">
    <form action="javascript:;" method="post" class="js--widget-conds-form">

        <h2>Context engine</h2>

        <?php
        Form::nextFieldDetails('Conditions', true);
        echo Form::conditionsList('conds', [], $cond_list_params);
        ?>


        <div class="text-align-right">
            <button class="js--cancel button button-grey" type="button">Cancel</button>
            &nbsp;
            <button class="js--save button button-green" type="submit">Save</button>
        </div>
    </form>
</div>
