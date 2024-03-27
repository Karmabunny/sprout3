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

use Sprout\Helpers\Form;
?>


<style>
.login-box {
    max-width: 900px;
    margin: 2em auto;
    padding-top: 0;
}
</style>

<script>
$(document).ready(function() {
    $('.js--test-connection').on('click', testConnection);

    function testConnection() {
        var opts = {};

        opts.host = $('input[name="host"]').val();
        opts.user = $('input[name="user"]').val();
        opts.pass = $('input[name="pass"]').val();
        opts.database = $('input[name="database"]').val();

        if (opts.host == '') { alert('You must specify a host'); return; }
        if (opts.user == '') { alert('You must specify a user'); return; }
        if (opts.pass == '') { alert('You must specify a pass'); return; }
        if (opts.database == '') { alert('Please specify a database'); return; }

        $.post('welcome/db_conf_test', opts, function(data) {
            alert(data.result);
        }, 'json');
    }
});
</script>


<form action="welcome/db_conf_result" method="post">
    <?php
    Form::nextFieldDetails('Environment', true);
    echo Form::dropdown('env', [], [
        'dev' => 'Development',
        'test' => 'Testing',
        'qa' => 'Staging/QA',
        'prod' => 'Live/production',
    ]);
    ?>

    <?php
    Form::nextFieldDetails('Database type', true);
    echo Form::dropdown('type', [], [
        'mysql' => 'MySQL Server/MariaDB',
        'pgsql' => 'PostgreSQL',
        'sqlite' => 'SQLite',
        'mssql' => 'SQL Server',
    ]);
    ?>

    <?php
    Form::nextFieldDetails('Hostname', true, 'This can be a hostname or an ip address (e.g. localhost or db-master.example.com)');
    echo Form::text('host');
    ?>

    <?php
    Form::nextFieldDetails('Username', true, 'The database username (e.g. example_web)');
    echo Form::text('user');
    ?>

    <?php
    Form::nextFieldDetails('Password', true, 'Note: This field is shown in plaintext');
    echo Form::text('pass');
    ?>

    <?php
    Form::nextFieldDetails('Database name', true, 'The name of the actual database (e.g. example_v1)');
    echo Form::text('database');
    ?>

    <div>
        <a href="welcome/checklist" class="button button-grey">Back</a>
        <button type="submit" class="right button button-green icon-before icon-save">Generate config</button>
        <button type="button" class="right button button-grey icon-before icon-build js--test-connection">Test connection</button>
    </div>
</form>
