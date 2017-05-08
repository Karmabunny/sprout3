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
?>
<style>
.adv-search-form th {
    width: 175px;
}

.adv-search-form select,
.adv-search-form input[type=text] {
    width: 300px;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}
</style>


<form action="SITE/admin/contents/<?php echo strtolower($controller_name); ?>" method="get">

    <?php
    foreach ($refine->getGroups() as $group => $widgets) {
        echo '<h3>', Enc::html($group), '</h3>';
        echo '<table class="form-section adv-search-form">';

        foreach ($widgets as $widget) {
            $html = $widget->render();
            $label = Enc::html($widget->getLabel());

            if (! $html) continue;

            echo '<tr>';
            echo '<th><span class="m">', $label, ':</span></th>';
            echo '<td>', $html, '</td>';
            echo '</td>';
        }

        echo '</table>';
    }
    ?>


    <div class="action-bar">
        <button type="submit" class="save button button-green icon-after icon-search">Search <?php echo Enc::html($friendly_name); ?></button>
    </div>
</form>
