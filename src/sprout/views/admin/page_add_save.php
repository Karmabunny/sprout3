<?php
use Sprout\Helpers\Form;
?>


<h2 class="icon-before icon-add">Add page</h2>

<?php
Form::nextFieldDetails('Visibility', false);
echo Form::checkboxBoolList(null, [], [
    'active' => 'Active',
    'show_in_nav' => 'Show in menu and search results',
]);
?>

<div class="save-changes-box-bottom -clearfix">
    <button class="save-changes-save-button button button-regular button-green icon-after icon-add" type="submit">Add page</button>
</div>