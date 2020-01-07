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
    max-width: 1200px;
    margin: 2em auto;
    padding-top: 0;
}
code {
    border: none;
    padding: 2px;
    background: #eee;
}
</style>


<p>The authentication details for your super-operator account have been generated.</p>

<h3>A: First super-operator</h3>
<ol>
    <li>Download the <a href="<?php echo Enc::html($superop_config_url); ?>">generated config file</a></li>
    <li>Save the file as <code><?php echo Enc::html(DOCROOT . 'config/super_ops.php'); ?></code></li>
</ol>

<h3>B: Or if adding an extra super-operator</h3>
<ol>
    <li>Update the file <code><?php echo Enc::html(DOCROOT . 'config/super_ops.php'); ?></code> adding the following:</li>
</ol>

<?php
echo "<pre>";
foreach ($users as $username => $user) {
    echo "    '", Enc::html(Enc::js($username));
    echo "' =&gt; ['uid' => {$user['uid']}, 'hash' =&gt; '", Enc::html(Enc::js($user['hash']));
    echo "', 'salt' =&gt; '", Enc::html(Enc::js($user['salt'])), "'],\n";
}
echo "</pre>";
?>

<p><a href="welcome/checklist" class="button">Back to checklist</a></p>
