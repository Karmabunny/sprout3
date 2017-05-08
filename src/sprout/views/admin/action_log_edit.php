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
use Sprout\Helpers\ColModifierHexIP;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Sprout;


$info = json_decode($data['data']);
?>


<div class="white-box">
    <div class="columns">
        <div class="column column-3">
            <b>Type:</b>
            <br>
            <?= Enc::html($data['type']); ?>
        </div>
        <div class="column column-3">
            <b>Date:</b>
            <br>
            <?= Enc::html(Sprout::formatMysqlDatetime($data['date_added'])); ?>
        </div>
        <div class="column column-3">
            <b>Table:</b>
            <br>
            <?= Enc::html($data['record_table']); ?>
        </div>
        <div class="column column-3">
            <b>Record ID:</b>
            <br>
            <?= Enc::html($data['record_id']); ?>
        </div>
    </div>
    <br>
    <div class="columns">
        <div class="column column-3">
            <b>Editor:</b>
            <br>
            <?= Enc::html($data['modified_editor']); ?>
        </div>
        <div class="column column-3">
            <b>IP Address:</b>
            <br>
            <?php
            $mod = new ColModifierHexIP();
            echo Enc::html($mod->modify($data['ip_address'], ''));
            ?>
        </div>
        <div class="column column-6">
            <?php
            if ($data['user_agent']) {
                echo "<p><b>User-agent:</b>\n";
                echo '<br>', Enc::html($data['user_agent']), "</p>\n";
            }
            ?>
        </div>
    </div>
</div>


<?php if (!empty($controller)): ?>
    <?php if ($data['type'] == 'Delete' and $data['restored_date'][0] == '0'): ?>
        <form action="admin/restore/<?php echo $id; ?>" method="post">
            <?php echo Csrf::token(); ?>
            <button type="submit" class="button">Restore record</button>
        </form>

    <?php else: ?>
        <?php if ($data['type'] == 'Delete'): ?>
            <p>This record was restored by <?= Enc::html($data['restored_operator']); ?> on <?= date('D j/n/Y \a\t g:i a', strtotime($data['restored_date'])); ?></p>
        <?php endif; ?>
        <p>
            <a href="SITE/admin/edit/<?php echo Enc::html($controller); ?>/<?php echo $data['record_id']; ?>" class="button">View record</a>
        </p>
    <?php endif; ?>
<?php endif; ?>


<?php
if (@count($info) > 0):
?>
    <h3>Data</h3>
    <table class="form-section">
        <?php foreach ($info as $key => $val): ?>
            <tr>
                <th><?= Enc::html($key); ?></th>
                <td><?= Enc::html($val); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
