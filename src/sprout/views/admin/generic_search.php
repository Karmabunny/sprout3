<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Sprout\Helpers\Enc;
?>

<form action="SITE/admin/contents/<?php echo Enc::html(strtolower($controller_name)); ?>" method="get" class="-clearfix">

    <div class="mainbar-with-right-sidebar">
        <?php foreach ($refine->getGroups() as $group => $widgets): ?>
            <h3><?php echo Enc::html($group); ?></h3>

            <?php
            foreach ($widgets as $widget):
                echo $widget->render();
            endforeach;
            ?>

        <?php endforeach; ?>
    </div>

    <div class="right-sidebar">
        <div class="right-sidebar-anchor"></div>
        <div class="right-sidebar-inner">
            <div class="save-changes-box">
                <h2 class="icon-before icon-search">Search</h2>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-search">Search <?php echo Enc::html($friendly_name); ?></button>
                </div>
            </div>
        </div>
    </div>

</form>
