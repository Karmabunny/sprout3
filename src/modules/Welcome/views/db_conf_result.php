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
    margin-bottom: 200px;
    padding-top: 2em;
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


<p>Your config files have been generated. Refer to the instructions below for how to install them.</p>


<h3>Database config</h3>

<p>The main database config file is stored in the top-level config directory.</p>

<ol>
    <li>Download the <a href="<?php echo Enc::html($db_config_url); ?>">generated config file</a></li>
    <li>Save the file as <code><?php echo Enc::html(DOCROOT . 'config/database.php'); ?></code></li>
</ol>


<?php if (!empty($pass_config)): ?>
    <h3>Password config</h3>

    <p>
        For security reasons, the best configuration for production sites is to
        store the password in a separate file outside the document root.
    </p>

    <ol>
        <li>Download the <a href="<?php echo Enc::html($pass_config_url); ?>">generated config file</a>
            or copy-and-paste it from below</li>
        <li>Save the file as <code><?php echo Enc::html($pass_filename); ?></code></li>
    </ol>

    <pre><?php echo Enc::html($pass_config); ?></pre>
<?php endif; ?>

<?php if (!empty($host_config)): ?>
    <h3>Server config</h3>

    <p>
        For security reasons, the list of development machines is stored in a config file.
    </p>

    <ol>
        <li>Download the <a href="<?php echo Enc::html($host_config_url); ?>">generated config file</a>
            or copy-and-paste it from below</li>
        <li>Save the file as <code><?php echo Enc::html(DOCROOT . 'config/dev_hosts.php'); ?></code></li>
    </ol>

    <pre><?php echo Enc::html($host_config); ?></pre>
<?php endif; ?>

<p><br><a href="welcome/checklist" class="button">Back to checklist</a></p>
