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

$ns = 'SproutModules\\Karmabunny\\Welcome\\Controllers\\';

// Redirect traffic to the home page into the welcome system
$config[''] = $ns . 'WelcomeController/redirect';

// Useful tools
$config['welcome/info'] = $ns . 'WelcomeController/phpInfo';

// The welcome system UI
$config['welcome/checklist'] = $ns . 'WelcomeController/checklist';
$config['welcome/run_test/([_a-z]+)'] = $ns . 'WelcomeController/runTest/$1';
$config['welcome/db_conf_form'] = $ns . 'WelcomeController/dbConfForm';
$config['welcome/db_conf_test'] = $ns . 'WelcomeController/dbConfTest';
$config['welcome/db_conf_result'] = $ns . 'WelcomeController/dbConfResult';
$config['welcome/db_conf_database'] = $ns . 'WelcomeController/dbConfDatabase';
$config['welcome/db_conf_hosts'] = $ns . 'WelcomeController/dbConfHosts';
$config['welcome/db_conf_password'] = $ns . 'WelcomeController/dbConfPassword';
$config['welcome/sync'] = $ns . 'WelcomeController/sync';
$config['welcome/super_op_form'] = $ns . 'WelcomeController/superOperatorForm';
$config['welcome/super_op_action'] = $ns . 'WelcomeController/superOperatorAction';
$config['welcome/super_op_result'] = $ns . 'WelcomeController/superOperatorResult';
