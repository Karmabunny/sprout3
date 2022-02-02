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
use Sprout\Helpers\Jquery;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Sprout;


$merged_js = 'media/merged/admin.' . Sprout::getVersion() . '.js';
$merged_css = 'media/merged/admin.' . Sprout::getVersion() . '.css';

?>
<!DOCTYPE html>
<html lang="en" class="login-page">
<head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title><?= Enc::html($browser_title); ?> | SproutCMS</title>

    <base href="<?= Enc::html(Sprout::absRoot()); ?>">

    <script type="text/javascript">
    var ROOT = 'ROOT/';
    var SITE = 'SITE/';
    </script>

    <link rel="icon" href="ROOT/media/images/favicon.ico" type="image/x-icon" sizes="16x16 32x32 48x48 256x256">
    <link rel="icon" type="image/png" href="ROOT/media/images/favicon-16x16.png" sizes="16x16">
    <link rel="icon" type="image/png" href="ROOT/media/images/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="ROOT/media/images/favicon-96x96.png" sizes="96x96">
    <link rel="apple-touch-icon" sizes="152x152" href="ROOT/media/images/apple-touch-icon-152x152.png">

    <!-- Styles -->
    <?php if (file_exists(DOCROOT . $merged_css)): ?>
    <link href="ROOT/<?= Enc::html($merged_css); ?>" rel="stylesheet" type="text/css">
    <?php else: ?>
    <link href="ROOT/media/css/normalize.css" rel="stylesheet" type="text/css">
    <link href="ROOT/media/css/common.css" rel="stylesheet" type="text/css">
    <link href="ROOT/media/css/ui.core.css" rel="stylesheet" type="text/css">
    <link href="ROOT/sprout/media/css/admin_layout.css" rel="stylesheet">
    <link href="ROOT/sprout/media/css/admin_editing_area.css" rel="stylesheet">
    <link href="ROOT/media/css/facebox.css" rel="stylesheet">
    <?php endif; ?>

    <!-- jQuery + jQuery UI -->
    <?= Jquery::script('jquery', 'admin'); ?>

    <!-- Libraries -->
    <?php if (file_exists(DOCROOT . $merged_js)): ?>
    <script src="ROOT/<?= Enc::html($merged_js); ?>"></script>
    <?php else: ?>
    <script src="ROOT/media/js/jquery.cookie.js"></script>
    <script src="ROOT/media/js/common.js"></script>
    <script src="/media/js/jquery.matchHeight-min.js"></script>
    <script src="ROOT/sprout/media/js/admin_layout.js"></script>
    <script src="ROOT/sprout/media/js/admin_editing_area.js"></script>
    <script src="ROOT/media/js/facebox.js"></script>
    <script src="ROOT/media/js/login.js"></script>
    <?php endif; ?>

    <needs />

</head>
<body>

    <div id="wrapper">

        <div class="login-loading-box">
             <div class="processing processing-large processing-animate">
                <div class="processing-dots-wrapper">
                    <span class="processing-dot processing-dot-1"></span>
                    <span class="processing-dot processing-dot-2"></span>
                    <span class="processing-dot processing-dot-3"></span>
                    <span class="processing-dot processing-dot-4"></span>
                    <span class="processing-dot processing-dot-5"></span>
                    <span class="processing-dot processing-dot-6"></span>
                    <span class="processing-dot processing-dot-7"></span>
                    <span class="processing-dot processing-dot-8"></span>
                </div>
            </div>
        </div>

        <div class="login-box">
            <div class="login-box-header">
                <div class="logo -clearfix"><span class="logo-sprout">Sprout</span> <span class="logo-cms">CMS</span> <span class="logo-version"><?= Enc::html(Sprout::getVersion()); ?></span></div>
            </div>
            <h1 class="h2 -vis-hidden"><?= Enc::html($main_title); ?></h1>
            <div class="login-box-content">
                <?= Notification::checkMessages(); ?>
                <?= $main_content; ?>
                <?php if (!empty($info_message)) echo $info_message; ?>
            </div>
            <?php require_once("_footer.php"); ?>
        </div>

    </div>

</body>
</html>
