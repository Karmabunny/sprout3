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
use Sprout\Helpers\Constants;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Tags;


Form::setData($_GET);
Needs::module('tags_ui');
?>

<script type="text/javascript">
var table = '';
</script>


<form action="SITE/advanced_search" method="get">

    <?php Form::nextFieldDetails('Find', true); ?>
    <?= Form::checkboxSet('type', [], $avail_types); ?>


    <?php Form::nextFieldDetails('containing the terms', false); ?>
    <?= Form::text('q'); ?>

    <?php Admin::toggleStrip('q_type', Constants::$search_modifiers, $_GET['q_type']); ?>


    <?php Form::nextFieldDetails('and the tags', false); ?>
    <?= Form::text('tag', ['id' => 'tags-text', 'autocomplete' => 'off']); ?>
    <p class="tags-suggest">
        <?php
        $suggestions = Tags::suggestTags();
        foreach ($suggestions as $tag) {
            if (strpos($_GET['tag'], $tag) === false) {
                echo " <a href=\"#\">", Enc::html($tag), "</a>";
            } else {
                echo " <a href=\"#\" class=\"selected\">", Enc::html($tag), "</a>";
            }
        }
        ?>
    </p>

    <?php Admin::toggleStrip('tag_type', Constants::$search_modifiers, $_GET['tag_type']); ?>


    <?php Form::nextFieldDetails('and last modified', false); ?>
    <?= Form::dropdown('date', [], Constants::$relative_dates); ?>


    <tr>
        <td>&nbsp;</td>
        <td class="buttons"><input type="submit" value="Search" class="button submit"></td>
        <td class="field-info">&nbsp;</td>
    </tr>

</form>
