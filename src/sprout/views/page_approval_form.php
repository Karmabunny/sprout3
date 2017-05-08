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


// This form is displayed at the top of a page which is being reviewed as part of the
// page approval process.
?>


<style type="text/css">
#page-rev-approval-form {
    padding: 40px;
    margin: 20px 0 30px;
    border: 1px solid #CED2DC;
    border-radius: 5px;
    background-color: #EEF0F3;
}
</style>


<div id="page-rev-approval-form">
    <h1 class="page-rev-approval-form-title">Please approve or reject this revision</h1>

    <form method="post" action="page/review/<?= (int)$rev_id; ?>">
        <?php echo Csrf::token(); ?>

        <input type="hidden" name="code" value="<?= Enc::html($code); ?>">

        <div class="field-elements-inline">
            <?php
            Form::nextFieldDetails('Message', false);
            echo Form::text('message', ['-wrapper-class' => 'white new-category large', 'placeholder' => 'Enter a message', 'id' => 'approval-form-message']);
            ?>

            <div class="field-element field-element--button">
                <button name="do" value="approve" type="submit" class="button button-large button-green">Approve</button>
            </div>

            <div class="field-element field-element--button">
                <button name="do" value="reject" type="submit" class="button button-large button-red">Reject</button>
            </div>
        </div>
    </form>
</div>
