<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2015 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
?>

<link href="ROOT/sprout/media/css/admin_layout.css" rel="stylesheet">

<?php echo $toolbar; ?>

<div class="info">
    Upload files using the form below.
</div>

<div class="emu-mce-wrapper">
    <form action="SITE/admin/call/file/quickUpload" method="post" target="quick-upload" enctype="multipart/form-data" id="mce-upload">
        <?= Csrf::token(); ?>

        <?php Form::nextFieldDetails('File', true); ?>
        <?= Form::chunkedUpload('file', [], ['sess_key' => 'admin_quick_upload']); ?>


        <?php Form::nextFieldDetails('Name', true); ?>
        <?= Form::text('name'); ?>


        <?php Form::nextFieldDetails('Category', true); ?>
        <?= Form::dropdown('category_id', [], $cats); ?>

        <input class="button" type="submit" value="Upload">
        <input type="hidden" name="type" value="<?= Enc::html(@$_GET['type']); ?>">
    </form>
</div>

<iframe name="quick-upload" id="quick-upload" style="display: none;"></iframe>



<script type="text/javascript">
$(document).ready(function() {
    $('#quick-upload').on("load", function() {
        var nfo = $('#quick-upload').contents().find('div').text();
        if (nfo == '') return;

        nfo = $.parseJSON(nfo);

        if (typeof(nfo.error) != 'undefined') {
            alert(nfo.error);
            return;
        }

        if (nfo.type != <?php echo $f_type; ?>) {
            alert('Your file has been uploaded successfully, but is the wrong type for this field.');
            return;
        }

        <?php if ($_GET['type'] == 'image'): ?>
        window.location = ROOT + 'tinymce4/image_size/' + nfo.id;
        <?php else: ?>
        TinyMCE4.setUrl(nfo.rel_url);
        TinyMCE4.closePopup();
        <?php endif; ?>
    });

    $('#mce-upload .emu-mce-upload').change(function() {
        if ($('input[name="name"]').val() == '') {
            var newname = $(this).val();

            newname = newname.replace(/[_-]/g, ' ');            // underscore to space
            newname = newname.replace(/\.[a-z]{3,4}$/i, '');    // remove ext
            newname = newname.replace('C:\\fakepath\\', '');    // chrome fake paths
            newname = newname.charAt(0).toUpperCase() + newname.slice(1);    // Uppercase first

            $('input[name="name"]').val(newname);
        }
    });

    $('#mce-upload select[name="category_id"]').change(function() {
        if ($(this).val() == '_new') {
            $(this).parent().html('<input name="category_new" class="emu-mce-input">');
            $('#mce-upload input[name="category_new"]').select();
        }
    }).change();
});
</script>
