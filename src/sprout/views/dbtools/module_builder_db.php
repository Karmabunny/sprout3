<?php
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;


Fb::setData($data);

if (empty($data['xml'])) {
    $disabled = 'disabled';
} else {
    $disabled = '';
}
?>

<form action="" method="get">

    <div class="mainbar-with-right-sidebar">

        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Table name', true); ?>
                <?php echo Form::text('table', ['-wrapper-class' => 'white']); ?>
            </div>

            <div class="field-group-item col col--one-half">
                <?php Form::nextFieldDetails('Module type', true); ?>
                <?php echo Form::dropdown('type', ['-wrapper-class' => 'white'], [
                    'has_categories' => 'Categories',
                    'list' => 'Sprout List',
                    'simple_list' => 'Simple List',
                    'tree' => 'Tree'
                ]) ?>
            </div>

        </div>

        <?php Form::nextFieldDetails('XML data', false); ?>
        <?php echo Form::multiline('xml', ['-wrapper-class' => 'white', 'rows'=> '6', $disabled => true]); ?>
    </div>

    <div class="right-sidebar">
        <div class="right-sidebar-anchor"></div>
        <div class="right-sidebar-inner">
            <div class="save-changes-box">
                <h2 class="icon-before icon-keyboard_arrow_right">Create Module</h2>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="save-changes-save-button button button-regular button-green icon-before icon-keyboard_arrow_right">Generate db_struct.xml</button>
                </div>
            </div>
        </div>
    </div>

</form>
