<?php
use Sprout\Helpers\ContentReplace;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Jquery;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Recaptcha3;
use Sprout\Helpers\Skin;
use Sprout\Helpers\SocialMeta;
use Sprout\Helpers\Tags;
use Sprout\Helpers\Url;


if (empty($browser_title)) $browser_title = Navigation::buildBrowserTitle($page_title);
if (!SocialMeta::hasTitle()) SocialMeta::setTitle($page_title);
$main_content = ContentReplace::executeChain('main_content', $main_content);
if (empty($banner)) $banner = Navigation::banner();
?>
<!DOCTYPE html>

<html lang="en" class="no-js">

<head>
    <?php require_once 'partials/_meta-data.php'; ?>

    <title><?php echo Enc::html($browser_title); ?></title>

    <?php if (!empty($canonical_url)): echo Url::canonical($canonical_url); endif; ?>

    <script type="text/javascript">var ROOT = 'SITE/';</script>
    <?= Jquery::script('jquery', 'front'); ?>
    <needs />
    <?php echo SocialMeta::render(); ?>

    <?php Skin::common(); ?>
    <?php Skin::modules(); ?>
    <?php Skin::css('normalize', 'flexboxgrid', 'global', 'frankenmenu'); ?>
    <?php Skin::js('frankenmenu', 'skin'); ?>
    <?php Recaptcha3::skin(); ?>

    <?php include 'partials/_google_analytics.php'; ?>
</head>
<body class="<?= Enc::html(@$controller_name); ?>">
    <!--[if IE]><div class="old-browser"><p>This website uses modern construction techniques, which may not render correctly in your old browser. <br>We recommend updating your browser for the best online experience.</p> <p>Visit <a href="http://browsehappy.com/">browsehappy.com</a> to help you select an upgrade.</p></div><![endif]-->

    <?php require 'partials/_mobile-header.php'; ?>

    <div id="wrap">
        <a class="-vis-hidden" href="#content">Skip to Content</a>

        <?php require 'partials/_header.php'; ?>

        <div id="content" class="section section--content bg-white">

            <div class="container">

                <div class="mainbar mainbar--wide">

                    <h1><?php echo Enc::html($page_title); ?></h1>

                    <?php if (Navigation::matchedNode()): ?>
                        <ul class="breadcrumb"><?php echo Navigation::breadcrumb('<li>', @$post_crumbs, '</li>'); ?></ul>
                    <?php endif; ?>

                    <?php echo Notification::checkMessages(); ?>

                    <?php echo $main_content; ?>

                    <?php Tags::getList(@$tags); ?>

                </div>

            </div>

        </div>


        <?php require 'partials/_footer.php'; ?>

    </div>

</body>
</html>
