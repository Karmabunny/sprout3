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
?>

<script>
var docImport = {'selector' : '#<?php echo $elemid; ?>'};
</script>


<h2>Import content into the editor</h2>

<form action="SITE/admin_ajax/richtext_import_iframe" method="post" enctype="multipart/form-data" id="doc-import-form" target="doc-import-iframe">
    <input type="file" name="import" id="doc-import-upload">
</form>
<iframe id="doc-import-iframe" name="doc-import-iframe" width="1" height="1" style="border-style: none;"></iframe>


<!-- TODO: externalise -->
<style>
#doc-import-upload {
    padding: 20px;
    width: 400px;
    background: #eee;
    border-radius: 3px;
    margin: 20px auto;
}
</style>
<script>
$(document).ready(function() {
    $('#doc-import-upload').change(function() {
        $('#doc-import-form').submit();
    });

    $('#doc-import-iframe').on("load", function() {
        var nfo = $(this).contents().find('div').text();
        if (nfo == '') return;

        nfo = $.parseJSON(nfo);
        if (nfo.error) {
            alert(nfo.error);
            return;
        }
        if (nfo.html == '') return;

        $(docImport.selector).trigger('richtext_insert', [nfo.html]);
        $(document).trigger('close.facebox');
    });
});
</script>
