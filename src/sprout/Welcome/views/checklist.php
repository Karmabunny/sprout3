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
.test {
    background: #EEF0F3;
    border: 1px solid #CED2DC;
    padding: 10px 30px;
    margin: 30px 0;
}
.test[data-result="0"] {
    border-left: #F77450 solid 10px;
}
.test[data-result="1"] {
    border-left: #28943E solid 10px;
}
.test h3 {
    margin-top: 10px;
}
.test h3 small {
    vertical-align: middle;
    margin-left: 10px;
    font-size: 1.5rem;
}
.test .message {
    font-size: 12px;
}
code {
    border: none;
    padding: 2px;
    background: rgba(255, 255, 255, 0.5);
}
</style>


<p>Before you can use SproutCMS, you'll need to complete the following tasks:</p>


<div class="test" data-test="dbconf" data-result="<?php echo (int)$results['dbconf'][0]; ?>">
    <h3>1. Create an environment file</h3>

    <p>
        Use our <a href="welcome/db_conf_form">environment config generator</a> to create
        a <code>.env</code> (dot-env) file. This lives in your application root directory.
    </p>

    <?php if (!empty($results['dbconf'][1])): ?>
        <p class="message">
            <b>Tried to connect to database, but got an error:</b>
            <br>
            <?php echo Enc::html($results['dbconf'][1]); ?>
        </p>
    <?php endif; ?>
</div>


<div class="test" data-test="dbsync" data-result="<?php echo (int)$results['dbsync'][0]; ?>">
    <h3>2. Generate tables</h3>

    <p>
        Run the <a href="welcome/sync">database sync</a> tool, which will generate your tables.
    </p>
</div>


<div class="test" data-test="superop" data-result="<?php echo (int)$results['superop'][0]; ?>">
    <h3>3. Create a super operator</h3>

    <p>
        Super-operators are special accounts which are stored in a config file, so they can be
        used even when a database is unavailable. They also have access to the full set of developer
        tools.
    </p>

    <p>
        You'll need to <a href="welcome/super_op_form">create a super operator</a>.
    </p>
</div>


<div class="test" data-test="sample" data-result="<?php echo (int)$results['sample'][0]; ?>">
    <h3>4. Add sample content <small>(optional)</small></h3>

    <p>
        There is some sample content available to get you started with the CMS.
    </p>
    <p>
        <a href="welcome/add_sample_action">Add sample content</a>
    </p>
</div>


<div class="test" data-test="welcome" data-result="<?php echo (int)$results['welcome'][0]; ?>">
    <h3>5. Remove welcome module</h3>

    <p>
        Open the file <code>config/config.php</code>, and remove the registration of
        the <code>Welcome</code> module from line <?php echo $welcome_line_num; ?>.
    </p>
</div>

<?php if ($overall_success): ?>
    <div class="test" data-result="1" style="text-align: center">
        <h3>All done!</h3>
        <p>
            <a href="admin" class="button">Log in to SproutCMS now!</a>
        </p>
    </div>
<?php else: ?>
    <div style="text-align: center">
        <a href="javascript:window.location.reload();" class="button icon-before icon-rotate_right">Reload</a>
    </div>
<?php endif; ?>


<p>&nbsp;</p>

<p>
    <b>Tools:</b>
    &nbsp;
    <a href="welcome/info">PHP information</a>
</p>
