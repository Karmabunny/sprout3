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


use Sprout\Helpers\Form;


if (!$temp_writeable) {
    echo "<ul class=\"messages\"><li class=\"error\">Temp dir not writeable :(</li></ul>\n";
    return;
}
?>


<p>This tool will create a module from an existing db_struct.xml file.</p>


<form action="SITE/dbtools/moduleBuilderExistingUploadAction" method="post" enctype="multipart/form-data" class="white-box">
    <?php
    Form::nextFieldDetails('Upload db_struct.xml', true);
    echo Form::upload('file');
    ?>

    <button type="submit" class="button icon-after icon-file_upload">Upload and process XML</button>
</form>


<form action="SITE/dbtools/moduleBuilderExistingUploadAction" method="post" class="white-box">
    <?php
    Form::nextFieldDetails('Use existing db_struct.xml', true);
    echo Form::dropdown('existing', [], $existing_files);
    ?>

    <button type="submit" class="button icon-after icon-file_upload">Process XML</button>
</form>


<form action="SITE/dbtools/moduleBuilderExistingUploadAction" method="post" class="white-box">
    <?php
    Form::nextFieldDetails('Copy-n-paste XML content', true);
    echo Form::multiline('content', ['style' => 'font-family: monospace', 'rows' => 7]);
    ?>

    <button type="submit" class="button icon-after icon-file_upload">Process XML</button>
</form>
