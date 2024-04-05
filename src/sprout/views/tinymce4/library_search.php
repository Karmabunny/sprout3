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

<?php echo $toolbar; ?>

<div class="info">
    Browse the <b><?php echo Enc::html($library_name); ?></b> search results using the list below.
</div>

<?php
if (count($objects) == 0) {
    echo '<p><i>No results found.</i></p>';
    return;
}
?>


<?php
echo '<ul class="link-list">';

foreach ($objects as $obj) {
    $attrs = $obj->getAttrs();

    echo '<li class="', $obj->getIconClass(), '"><a href="javascript:;" data-src="', Enc::html($attrs['href']), '" data-title="', Enc::html($attrs['title'] ?? ''), '">';
    echo Enc::html($obj->getLabel());
    echo '</a></li>';
}

echo '</ul>';
?>

<script>
$(document).ready(function(){
    $("a[data-src]").click(function(){
        TinyMCE4.setUrl($(this).attr("data-src"));
        TinyMCE4.setField("Title", $(this).attr("data-title"));
        TinyMCE4.closePopup();
    });
})
</script>
