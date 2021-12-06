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
use Sprout\Helpers\Inflector;

// Inflector only works with single words, so only apply to last word
$words = explode(' ', $friendly_name);
$words[count($words)-1] = Inflector::singular($words[count($words)-1]);
?>

<script type="text/javascript">
function nav_actions(elem) {
    var item_id = $(elem).attr('rel').replace(/\/$/, '').replace(/^.*[\/\\]/g, '');

    var html = '<p><a href="admin/delete/<?php echo Enc::html($controller_name); ?>/' + item_id + '">Delete</a></p>';

}

$(document).ready(function() {
    var t = $('.sidebar-box-content ul');

    $(t).find('LI A').mouseup(function(event) {
        if (event.button == 2) {
            nav_actions(this);
        }

        event.stopPropagation();
        return false;
    });

    $(t).find('LI A').each(function(i) {
        this.oncontextmenu = function() {return false;};
    });
});
</script>

<?php if ($allow_add): ?>
<div class="inline-buttons sidebar-action-buttons -clearfix">
    <a class="icon-after icon-add button button-small" href="admin/add/<?php echo Enc::html($controller_name); ?>">Add <?php echo Enc::html(implode(' ', $words)); ?></a>
</div>
<?php endif; ?>

<ul class="list-style-1">
    <li class="file ext_txt"><a href="SITE/admin/contents/<?= Enc::html($controller_name); ?>">All <?= Enc::html(strtolower($friendly_name)); ?></a></li>

    <?php
    foreach($items as $item) {
        $name = Enc::html($item['name']);
        if ($item['id'] == $record_id) {
            $class = "file ext_txt active-node";
        } else {
            $class = "file ext_txt";
        }
        echo "<li class=\"{$class}\"><a href=\"SITE/admin/edit/{$controller_name}/{$item['id']}\" rel=\"{$item['id']}\">{$name}</a></li>";
    }
    ?>
</ul>
