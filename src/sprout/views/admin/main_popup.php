<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Jquery;
use Sprout\Helpers\Media;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Sprout;

$merged_js = 'media/merged/admin.' . Sprout::getVersion() . '.js';
$merged_css = 'media/merged/admin.' . Sprout::getVersion() . '.css';

$body_classes = array();
if (!empty($locked)) {
    $body_classes[] = 'record-locked';
}
if (!$nav and !$nav_tools) {
    $body_classes[] = 'no-sidebar';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title><?php echo Enc::html($browser_title); ?> | SproutCMS</title>

    <base href="<?php echo Enc::html(Sprout::absRoot()); ?>">

    <script>var ROOT = 'ROOT/'; var SITE = 'SITE/';</script>

    <?php
    // Provide lock details to JavaScript to allow for unlocking on page unload
    if (!empty($currlock)) {
        echo '<script>var currlock = ', json_encode($currlock), ';</script>', "\n";
    }
    ?>

    <?php
    // Allow AJAX to use the CSRF token
    echo '<script>var csrfToken = "', Csrf::getTokenValue(), '";</script>', "\n";
    ?>
    <!-- Styles -->
    <?php if (file_exists(DOCROOT . $merged_css)): ?>
    <link href="ROOT/<?php echo Enc::html($merged_css); ?>" rel="stylesheet" type="text/css">
    <?php else: ?>
    <?php echo Media::tag('core/css/normalize.css') ?>
    <?php echo Media::tag('core/css/common.css') ?>
    <?php echo Media::tag('core/css/ui.core.css') ?>
    <?php echo Media::tag('sprout/css/admin_layout.css') ?>
    <?php echo Media::tag('sprout/css/admin_editing_area.css') ?>
    <?php echo Media::tag('core/css/facebox.css') ?>
    <?php endif; ?>

    <!-- jQuery + jQuery UI -->
    <?= Jquery::script('jquery', 'admin'); ?>
    <?= Jquery::script('jqueryui', 'admin'); ?>

    <!-- Libraries -->
    <?php if (file_exists(DOCROOT . $merged_js)): ?>
    <script src="ROOT/<?php echo Enc::html($merged_js); ?>"></script>
    <?php else: ?>
    <?php echo Media::tag('core/js/jquery.cookie.js') ?>
    <?php echo Media::tag('core/js/common.js') ?>
    <?php echo Media::tag('core/js/jquery.matchHeight-min.js') ?>
    <?php echo Media::tag('sprout/js/admin_layout.js') ?>
    <?php echo Media::tag('sprout/js/admin_editing_area.js') ?>
    <?php echo Media::tag('core/js/facebox.js') ?>
    <?php endif; ?>

    <needs />

</head>
<body class="<?php echo Enc::html(implode(' ', $body_classes)); ?>">

    <div id="content" class="-clearfix">
        <div class="container">

            <div id="main" class="mainbar mainbar-reverse <?= empty($main_class) ? '' : Enc::html($main_class); ?>">

            <?php if ($controller_name !== '_dashboard'): ?>
                <?php
                // Encode the title, but preserve STRONG tags as they mark the actual name of the item being edited
                $main_title = str_replace(['<strong>','</strong>'], ['###strong###','###/strong###'], $main_title);
                $main_title = Enc::htmlNoDup($main_title);
                $main_title = str_replace(['###strong###','###/strong###'], ['<strong>','</strong>'], $main_title);

                echo '<h2>', $main_title, '</h2>';
                ?>
            <?php endif; ?>

            <!-- Main Content -->
            <div id="main-content" class="-clearfix">
                <?php echo Notification::checkMessages(); ?>
                <?php if (!empty($locked)) echo '<ul class="messages all-type-neutral"><li class="neutral">This record currently locked for editing by ', Enc::html($locked['operator_name']), ' as of ', date('g:i a', strtotime($locked['date_modified'])), '</li></ul>'; ?>
                <?php echo $main_content; ?>
            </div>

        </div>
    </div>
</body>
</html>
