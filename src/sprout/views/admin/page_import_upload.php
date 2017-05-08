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
use Sprout\Helpers\View;
?>


<style>
#facebox .import-notes { padding: 20px; }
#facebox .import-notes h2 { margin-bottom: 10px; }
#facebox .import-notes ul { margin: 10px 25px 15px 25px; }
</style>


<?php if (count($types) == 0): ?>
    <p><i>No document import backends installed.</i></p>
<?php return; endif; ?>


<form action="admin/call/page/importUploadAction" method="post" enctype="multipart/form-data">
    <?php echo Csrf::token(); ?>

    <h3>Select file</h3>
    <input type="file" class="upload" name="import">


    <h3>Supported file types</h3>
    <table class="pretty-list">
        <thead>
            <tr>
                <th>Type</th>
                <th>File extension</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($types as $row): ?>
                <tr>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['ext']; ?></td>
                <td>
                    <?php
                    try {
                        $view = new View('sprout/doc_import_notes/features_' . $row['ext']);
                        echo '<a href="page/import_notes/features_', $row['ext'], '" rel="facebox">Features and notes</a>';
                    } catch (Exception $ex) {}
                    ?>
                </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>


    <div class="action-bar">
        <button type="submit" class="button">Upload file</button>
    </div>
</form>

