<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Form;
?>

<form action="SITE/dbtools/importXmlAction" method="post" enctype="multipart/form-data">
    <div class="mainbar-with-right-sidebar">

        <div class="white-box">
            <?php echo Csrf::token(); ?>

            <?php
            Form::nextFieldDetails('Sub-site', true, 'Select sub-site to import into');
            echo Form::dropdown('subsite_id', [], $subsites);
            ?>

            <div class="js-page-dropdown">
                <input type="hidden" name="page_id" value="0">
            </div>

            <?php
            Form::nextFieldDetails('XML file', true, 'Sprout 2\'s CMS export XML');
            echo Form::upload('filename');
            ?>
        </div>
    </div>

    <div class="right-sidebar">
        <div class="right-sidebar-anchor"></div>
        <div class="right-sidebar-inner">
            <div class="save-changes-box">
                <h2 class="icon-before icon-file_upload">Import Sprout 2 pages</h2>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-send">Import</button>
                </div>
            </div>
        </div>
    </div>

</form>

<script>
$(document).ready(function()
{
    $('select[name="subsite_id"]').on('change', function()
    {
        var id = parseInt($(this).val());
        if (isNaN(id)) id = 0;
        $.ajax({
            url: 'dbtools/ajaxPageIds/' + id,
            dataType: 'html',
            success: function(html) {
                $('.js-page-dropdown').html(html);
            },
            error: function() {
                $('.js-page-dropdown').html('<input type="hidden" value="0" name="page_id">');
            }
        });
    });
});
</script>
