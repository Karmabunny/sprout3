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

use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Inflector;


?>
<?php if ($recently_updated): ?>
    <!-- Some records found - show the recent ones -->
    <form action="SITE/admin/contents/<?php echo strtolower($controller_name); ?>" method="get">
        <button type="submit" class="button button-small">Show all <?php echo strtolower($friendly_name); ?></button>
    </form>

    <br>

    <h3>Recently updated pages</h3>
    <?php echo $recently_updated; ?>

<?php else: ?>
    <!-- No records found - show an add form -->
    <form action="SITE/admin/add/<?php echo strtolower($controller_name); ?>" method="get">
        <p>No <?php echo strtolower($friendly_name); ?> currently exist</p>
        <button type="submit" class="button button-green button-large icon-after icon-add">Add <?php echo strtolower(Inflector::singular($friendly_name)); ?></button>
    </form>

<?php endif; ?>

<?php if (AdminPerms::canAccess('access_noapproval') and $need_approval): ?>
    <h3>Changes needing approval</h3>
    <?php echo $need_approval; ?>
<?php endif; ?>
