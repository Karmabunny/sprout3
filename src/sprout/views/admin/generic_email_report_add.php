<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
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
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;
use Sprout\Helpers\MultiEdit;

Form::setData(
    $_SESSION['admin']['field_values'] ??
    [
        'format' => 'csv',
    ]
);
Form::setErrors(
    $_SESSION['admin']['field_errors'] ?? []
);

echo $refine;
?>

<h3>Preview of records</h3>
<div class="scrollable-table"><?php echo $itemlist; ?></div>

<form action="SITE/admin/email_report_action/<?php echo $controller_name; ?>" id="report-form" method="post">
    <?php echo Csrf::token(); ?>

    <?= Fb::hiddenFields($refine_fields); ?>

    <h3>Options</h3>
    <?php
    Form::nextFieldDetails('Report name', false);
    echo Form::text('email_report_name', []);
    ?>

    <?php
    Form::nextFieldDetails('Format', true);
    echo Form::multiradio('email_report_format', [], ['csv' => 'CSV spreadsheet', 'xml' => 'XML']);
    ?>

    <h3>Recipients</h3>
    <div id="multiedit-recipients">
        <input type="hidden" name="m_id">

        <div class="clear-group">
            <div class="col col--one-half">
                <?php
                Form::nextFieldDetails('Recipient name', false);
                echo Form::text('m_name', []);
                ?>
            </div>
            <div class="col col--one-half">
                <?php
                Form::nextFieldDetails('Recipient email', false);
                echo Form::text('m_email', []);
                ?>
            </div>
        </div>
    </div>

    <?php
    MultiEdit::itemName('recipient');
    MultiEdit::display('recipients', $data['multiedit_recipients'] ?? []);
    ?>

    <div class="action-bar">
        <button type="submit" class="button button-green button-regular icon-after icon-keyboard_arrow_right no-disable">Create report</button>
    </div>
</form>


<script>
    $(document).ready(function() {
        var isChanged = false;
        $('.refine-bar').on('change', 'select,  input', function() {
            console.log('change');
            isChanged = true;
        });

        $('form#report-form').submit(function(e) {
            if (isChanged) {
                return confirm('You have unsaved filter changes. Are you sure you want to continue?');
            }
        });
    })
</script>
