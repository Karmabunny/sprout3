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
use Sprout\Helpers\File;
?>

<?php
echo $toolbar;
?>

<div class="info">
    Choose a video from the list below.
</div>

<ul class="link-list">
<?php if (!empty($up_url)): ?>
    <li class="up"><a href="<?= Enc::html($up_url); ?>">Up one level</a></li>
<?php endif; ?>

<?php foreach ($videos as $row): ?>
    <li class="object"><a href="javascript:;" data-src="<?= Enc::html(File::relUrl($row['filename'])); ?>"
        data-title="<?= Enc::html($row['name']); ?>"><?= Enc::html($row['name']); ?></a></li>
<?php endforeach; ?>

</ul>

<script>
$(document).ready(function(){
    $("a[data-src]").click(function(){
        TinyMCE4.setUrl($(this).attr("data-src"));
        TinyMCE4.setField("Title", $(this).attr("data-title"));
        TinyMCE4.closePopup();
    });
})
</script>
