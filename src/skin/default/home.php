<?php
use Sprout\Helpers\ContentReplace;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Jquery;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Skin;
use Sprout\Helpers\Widgets;


$page['text'] = ContentReplace::executeChain('main_content', $page['text']);
?>
<!DOCTYPE html>
<html lang="en" class="no-js">

<head>
    <?php require_once 'partials/_meta-data.php'; ?>

    <title><?php echo Enc::html($browser_title); ?></title>

    <script type="text/javascript">var ROOT = 'SITE/';</script>
    <?= Jquery::script('jquery', 'front'); ?>
    <needs />

    <?php Skin::common(); ?>
    <?php Skin::css('normalize', 'flexboxgrid', 'global', 'frankenmenu'); ?>
    <?php Skin::js('frankenmenu','skin'); ?>

    <?php require_once 'partials/_google_analytics.php'; ?>
</head>
<body>
    <!--[if IE]><div class="old-browser"><p>This website uses modern construction techniques, which may not render correctly in your old browser. <br>We recommend updating your browser for the best online experience.</p> <p>Visit <a href="http://browsehappy.com/">browsehappy.com</a> to help you select an upgrade.</p></div><![endif]-->

    <a class="-vis-hidden" href="#content">Skip to Content</a>

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

                            <?php echo $page['text']; ?>

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
