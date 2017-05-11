<?php
use Sprout\Helpers\ContentReplace;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Jquery;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Request;
use Sprout\Helpers\Skin;
use Sprout\Helpers\Url;
use Sprout\Helpers\Widgets;


$main_content = ContentReplace::executeChain('main_content', $main_content);
?>
<!DOCTYPE html>
<html lang="en" class="no-js">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo Enc::html($browser_title); ?></title>

    <base href="<?php echo Url::base(false, Request::protocol()); ?>">

    <link rel="apple-touch-icon" sizes="180x180" href="SKIN/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" href="SKIN/images/favicon/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="SKIN/images/favicon/favicon-16x16.png" sizes="16x16">
    <link rel="manifest" href="SKIN/images/favicon/manifest.json">
    <link rel="mask-icon" href="SKIN/images/favicon/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="theme-color" content="#ffffff">

    <script type="text/javascript">var ROOT = 'SITE/';</script>
    <?= Jquery::script('jquery', 'front'); ?>
    <!--[if lt IE 9]><script src="SKIN/js/selectivizr-min.js" type="text/javascript"></script><![endif]-->
    <!--[if lt IE 9]><script src="SKIN/js/respond-min.js" type="text/javascript"></script><![endif]-->
    <!--[if lt IE 9]><script src="SKIN/js/svgeezy.min.js" type="text/javascript"></script><![endif]-->
    <!--[if IE]><script src="SKIN/js/placeholders.min.js" type="text/javascript"></script><![endif]-->
    <!--[if IE]><link href="SKIN/css/flexboxgrid-ie9.css" rel="stylesheet"/><![endif]-->
    <needs />

    <?php Skin::common(); ?>
    <?php Skin::css('normalize', 'flexboxgrid', 'global', 'frankenmenu'); ?>
    <?php Skin::js('frankenmenu', 'jquery.matchHeight-min', 'modernizr', 'skin'); ?>

    <?php require_once 'google_analytics.php'; ?>
</head>
<body>
    <!--[if IE]><div class="old-browser"><p>This website uses modern construction techniques, which may not render correctly in your old browser. <br>We recommend updating your browser for the best online experience.</p> <p>Visit <a href="http://browsehappy.com/">browsehappy.com</a> to help you select an upgrade.</p></div><![endif]-->

    <a class="-vis-hidden" href="#content">Skip to Content</a>

    <?php require 'images/icomoon/symbol-defs.svg'; ?>

    <?php require 'partials/_mobile-header.php'; ?>

    <div id="wrap">

        <?php require 'partials/_header.php'; ?>

        <?php require 'partials/_hero-banner.php'; ?>

        <?php require 'partials/_promos-three.php'; ?>

        <div id="content" class="section section--content bg-white">

            <div class="container">

                <div class="row reverse">

                    <div class="col-xs-12 col-md-8">
                        <div class="mainbar">

                            <h1><?php echo Enc::html(Kohana::config('sprout.site_title')); ?></h1>

                            <?php echo Notification::checkMessages(); ?>

                            <?php echo $main_content; ?>

                        </div>
                    </div>

                    <div class="col-xs-12 col-md-4">
                        <div class="sidebar">

                            <?php echo Widgets::renderArea('sidebar'); ?>

                        </div>
                    </div>

                </div>

            </div>

        </div>

        <?php require 'partials/_footer.php'; ?>

    </div>

</body>
</html>
