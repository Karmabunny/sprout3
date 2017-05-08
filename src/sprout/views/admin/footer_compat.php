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
?>
<style>
#facebox ul {
    margin-left: 20px;
}

#facebox p {
    margin: 12px 0px;
}
</style>

<div style="margin: 20px;">
    <p><?php echo Kohana::config('branding.product_name'); ?> is officially supported with the following web browsers:</p>

    <ul>
        <li><a href="http://www.mozilla.com/" target="_blank">Mozilla Firefox</a> 3.0 or later</a></li>
        <li><a href="http://www.microsoft.com/windows/internet-explorer/" target="_blank">Internet Explorer</a> 7.0 or later</li>
    </ul>

    <p><?php echo Kohana::config('branding.product_name'); ?> has been tested in Firefox for Windows, Mac and Linux.</p>

    <p><?php echo Kohana::config('branding.product_name'); ?> should work on any modern web browser including Safari, Opera and Chrome,
    but it has not been thoroughly tested on these platforms.</p>

    <p>Internet Explorer 6 will not run <?php echo Kohana::config('branding.product_name'); ?> very well and its use is not recommended.</p>
</div>
