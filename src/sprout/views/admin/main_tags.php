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

use Sprout\Helpers\Enc;
use Sprout\Helpers\Needs;

Needs::fileGroup('tags_ui');
?>

<script type="text/javascript">
var table = '<?php echo Enc::js($table); ?>';
</script>


<div id="tags-wrapper" class="page-edit-tab">
    <div class="heading-with-buttons">
        <button class="button button-small button-grey icon-close icon-after page-edit-tab-close" type="button" data-target="tags-wrapper">Close</button>
        <h3 class="h2 icon-before icon-local_offer">Tags</h3>
    </div>

    <div class="white-box">
        <div class="field-element field-element--white field-element--text">
        <div class="field-label">
            <label for="tags-text">Tags</label>
            <div class="field-helper">
            Lowercase comma-separated words, can contain letters, numbers and dashes
            </div>
        </div>
        <div class="field-input">
            <input type="text" name="tags" class="textbox" value="<?php echo Enc::html($current_tags); ?>" id="tags-text" autocomplete="off">
        </div>
        </div>



        <p class="tags-suggest">
            <?php
            foreach ($suggestions as $tag) {
                if (strpos($current_tags, $tag) === false) {
                    echo " <a href=\"#\">{$tag}</a>";
                } else {
                    echo " <a href=\"#\" class=\"selected\">{$tag}</a>";
                }
            }
            ?>
        </p>
    </div>
</div>
