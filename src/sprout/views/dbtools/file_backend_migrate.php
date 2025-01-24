<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Text;
use Sprout\Helpers\Url;

?>

<style>
.dbtools-box { color: #333; text-decoration: none; min-height: 150px; }
.dbtools-box:hover { color: #333; text-decoration: none; box-shadow: 0 0 2px 2px #c3c7d4; }
.dbtools-box h4 { margin: 0 0 0.5em 0; font-size: 20px; }
.dbtools-box span { font-size: small; color: #666; }
</style>

<form action="<?php echo Url::current(); ?>">

<input type="hidden" name="go" value="1" />

<h3>There are approximately <?php echo (int) $files_count; ?> files to be migrated.</h3>

<div class="white-box">
    <h4>Please choose your action carefully:</h4>

    <h5>Prepare:</h5>
    <p>This will create copies on the chosen new backend,
        and adds the migration type &amp; date to te database.
        <br>This will not change the current backend for the file.
        <br>Use this to prepare large file systems before making the final config change.
        <br>You will be required to run an 'Update database' action when the active backend config is changed.
    </p>

    <h5>Update:</h5>
    <p>This will update the active backend in the record for any prepared migrations.
        <br>This update the 'backend_type' field in the database, copied from 'backend_migrated', and will not create any new files.
        <br>Updates will only be performed where backend_migrated matches the chosen new backend, and the date_migrated field is not empty.
        <br>Use this to finalise a prepared migration.
    </p>

    <h5>Full:</h5>
    <p>This will perform both of the above actions.
        <br>Updates will only be performed where backend_migrated matches the chosen new backend, and the date_migrated field is not empty.
        <br>Use this for fast migration of small volume file systems.
    </p>

    <br>
    <?php Form::nextFieldDetails('Migration action', false); ?>
    <?php echo Form::dropdown('action', [], $migration_opts); ?>
</div>

<hr>

<div class="white-box">
    <h4>Configuration options</h4>

    <?php Form::nextFieldDetails('Migrate files from', true); ?>
    <?php echo Form::dropdown('backend_source', [], $backend_opts); ?>

    <?php Form::nextFieldDetails('Migrate files to', true); ?>
    <?php echo Form::dropdown('backend_target', [], $backend_opts); ?>


    <?php Form::nextFieldDetails('Migration options', false); ?>
    <?php echo Form::checkboxBoolList(null, [], [
        'create_missing' => "Create missing file records (use with 'prepare' or 'full')",
    ]); ?>

    <div class="js--file-cat">
        <?php Form::nextFieldDetails('Category to add existing file records to', false); ?>
        <?php echo Form::dropdown('category_id_files', [], $categories); ?>

        <?php Form::nextFieldDetails('Category to add orphan files to', false); ?>
        <?php echo Form::dropdown('category_id_orphans', [], $categories); ?>
    </div>
</div>

<hr>

<p><button type="submit" class="button">Do it!</button>
&nbsp;&nbsp;&nbsp;<a href="dbtools">cancel</a></p>

</form>

<script>
    // On 'create_missing' checkbox change, toggle the category dropdown
    $('input[name="create_missing"]').on('change', function() {
        var $cat = $('.js--file-cat');
        if ($(this).is(':checked')) {
            $cat.show();
        } else {
            $cat.hide();
        }
    }).change();
</script>
