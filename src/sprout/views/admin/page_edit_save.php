<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;


?>


<h2 class="icon-before icon-save">Save changes</h2>

<?php
Form::nextFieldDetails('Publish options', true);
echo Form::dropdown('status', ['-dropdown-top' => '', '-wrapper-class' => 'white', 'id' => "publish-options"], [
    'live' => 'Publish changes now',
    'wip' => 'Save as work in progress',
    'need_approval' => 'Request approval for publishing',
    'auto_launch' => 'Auto-publish at future date',
]);
?>

<div class="save-option" id="opts-autolaunch">
    <?php
    Form::nextFieldDetails('Publish date', false);
    echo Form::datepicker('date_launch', ['-wrapper-class' => 'small white']);
    ?>
</div>

<div class="save-option" id="opts-need-check">
    <?php
    Form::nextFieldDetails('Request approval by', true);
    echo Form::dropdown('approval_operator_id', ['-dropdown-top' => 'Select a user', '-wrapper-class' => 'small white'], $approval_admins);
    ?>
</div>

<?php
Form::nextFieldDetails('Describe your changes', false);
echo Form::multiline('changes_made', ['-wrapper-class' => 'small white', 'rows' => '3']);
?>

<?php
Form::nextFieldDetails('Visibility', false);
echo Form::checkboxBoolList(null, ['-wrapper-class' => 'white'], [
    'active' => 'Active',
    'show_in_nav' => 'Show in menu and search results',
]);
?>

<?php if ($allow_delete): ?>
    <p><a class="save-changes-delete icon-link-button icon-before icon-delete" href="admin/delete/page/<?= $id; ?>">Delete page</a></p>
<?php endif; ?>

<?php if ($type != 'standard'): ?>
    <p><a href="admin/edit/page/<?= $id; ?>?type=standard" class="icon-link-button icon-before icon-settings">Change to standard page</a></p>
<?php endif; ?>
<?php if ($type != 'tool'): ?>
    <p><a href="admin/edit/page/<?= $id; ?>?type=tool" class="icon-link-button icon-before icon-settings">Change to tool page</a></p>
<?php endif; ?>
<?php if ($type != 'redirect'): ?>
    <p><a href="admin/edit/page/<?= $id; ?>?type=redirect" class="icon-link-button icon-before icon-settings">Change to a redirect</a></p>
<?php endif; ?>

<div class="save-changes-box-bottom -clearfix">
    <a class="save-changes-preview-button button button-regular button-blue icon-after icon-remove_red_eye" href="<?php echo Enc::html($preview_url); ?>">Preview</a>
    <button class="save-changes-save-button button button-regular button-green icon-after icon-save" type="submit">Save changes</button>
</div>