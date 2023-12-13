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
use Sprout\Helpers\Sprout;

header('Content-type: text/html; charset=UTF-8');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<style type="text/css">
<?php readfile(__DIR__ . '/../../../media/css/error_development.css'); ?>
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title><?php echo Enc::html($error) ?></title>
<base href="http://php.net/" />
</head>
<body>
<div id="framework_error">
<h3><?php echo Enc::html($error) ?></h3>
<p><?php echo Enc::html($description) ?></p>
<?php if ( ! empty($line) AND ! empty($file)): ?>
<p><?php echo Kohana::lang('core.error_file_line', $file, $line) ?></p>
<?php endif ?>
<p><code class="block"><?php echo Enc::html($message) ?></code></p>
<?php if (preg_match('/^SQLSTATE\[(?:42S22|42S02)\]/', $message)): ?>
    <p class="helpful-msg"><a href="<?php echo Enc::html(Sprout::absRoot()); ?>dbtools/sync">You can probably <span>run a DB sync</span> to fix this problem</a></p>
<?php endif; ?>
<?php if ($error === 'karmabunny\pdb\Exceptions\RowMissingException'): ?>
    <p class="helpful-msg">On the live site this message will show as a regular 404 error</p>
<?php endif; ?>
<p class="stats"><?php echo 'Log ID ', $log_id; ?></p>
<?php if ( ! empty($trace)): ?>
<h3><?php echo Kohana::lang('core.stack_trace') ?></h3>
<?php echo $trace ?>
<?php endif ?>
<?php if (isset($exception) AND ($previous = $exception->getPrevious())): ?>
<?php
$trace = $previous->getTrace();
$trace = Sprout::simpleBacktrace($trace);
array_shift($trace);
?>
<h3>Caused by: <?php echo Enc::html(get_class($previous)) ?></h3>
<p><?php echo Kohana::lang('core.error_file_line', $previous->getFile(), $previous->getLine()) ?></p>
<p><code class="block"><?php echo Enc::html($previous->getMessage()) ?></code></p>
<?php echo Kohana::backtrace($trace); ?>
<?php endif ?>
<p class="stats"><?php echo 'Sprout version: ', Sprout::getVersion(); ?></p>
</div>
</body>
</html>
