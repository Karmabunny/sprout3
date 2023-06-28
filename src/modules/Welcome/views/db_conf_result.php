<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2016 Karmabunny Pty Ltd.
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


<style>
.login-box {
    max-width: 900px;
    margin: 2em auto;
    padding-top: 0;
}
code {
    border: none;
    padding: 2px;
    background: #eee;
}
.expando-opener-para {
    font-size: 12px;
    text-align: right;
}
li {
    margin: 0.8em 0;
}
</style>


<h3>Complete!</h3>

<p>Your environment file has been generated. It has been placed into your application root directory.</p>

<ul>
    <li>Do not check this into your revisioning software.</li>
    <li>Do not expose this file to untrusted parties.</li>
    <li>Ensure this file has <code>0600</code> permissions.</li>
</ul>

<p>Your document root <em>must</em> be the <code>web/</code> directory. This is critical for the security and stability of your site.</p>

<p><br><a href="welcome/checklist" class="button">Back to checklist</a></p>
