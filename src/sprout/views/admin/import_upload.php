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
use Sprout\Helpers\Form;


$helptext = ($xls
    ? 'The selected file must be in the CSV or Microsoft Excel (XLS) file formats'
    : 'The selected file must be in the CSV file format'
);
?>


<form action="SITE/admin/import_upload_action/<?php echo $type; ?>" method="post" enctype="multipart/form-data">
    <?php echo Csrf::token(); ?>

    <?php
    Form::nextFieldDetails('File', true, $helptext);
    echo Form::upload('import');
    ?>


    <div class="action-bar">
        <button type="submit" class="button">Upload file</button>
    </div>
</form>
