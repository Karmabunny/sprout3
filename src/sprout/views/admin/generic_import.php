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
use Sprout\Helpers\Admin;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;


Form::setData($data);
?>


<form action="SITE/admin/import_action/<?php echo $controller_name; ?>" method="post">
    <?php echo Csrf::token(); ?>
    <input type="hidden" name="timestamp" value="<?php echo (int)$_GET['timestamp']; ?>">

    <h3>Field mappings</h3>
    <table class="main-list">
    <thead>
        <tr><th>CSV field</th><th>Data sample</th><th>Database field</th></tr>
    </thead>
    <tbody>
        <?php foreach ($headings as $name): ?>
            <?php $post_name = Enc::httpfield($name); ?>
            <tr>
            <td><?php echo Enc::html($name); ?></td>
            <td><?php echo Enc::html(@implode(', ', $sample[$name])); ?></td>
            <td>
                <?php
                $params = ['style' => 'width: 225px;'];
                echo Fb::dropdown("columns[{$post_name}]", $params, $import_columns);
                ?>
            </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    </table>


    <?php if ($duplicate_options): ?>
        <h3>Duplicate records</h3>
        <?php
        Form::nextFieldDetails('Duplicates', true);
        echo Form::multiradio('duplicates', [], [
            'new' => 'Add as new database record',
            'merge' => 'Merge CSV record into existing database record',
            'merge_blank' => 'Merge records, excluding blank fields',
            'skip' => 'Skip CSV record',
        ]);
        ?>

        <?php
        Form::nextFieldDetails('Match duplicates on', true);
        echo Form::dropdown('match_field', [], $import_columns);
        ?>
    <?php endif; ?>


    <?php echo $extra_options; ?>


    <?php echo $ai_options; ?>


    <div class="action-bar">
        <button type="submit" class="button">Import data</button>
    </div>
</form>
<?php Admin::clearFieldErrors(); ?>
