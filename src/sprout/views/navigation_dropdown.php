<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\NavigationMenu;
?>


<div class="mega-menu">
    <div class="container">

        <div class="mega-menu-preview">
            <h2 class="mega-menu-preview-title"><?php echo Enc::html($parent_node->getNavigationName()); ?></h2>

            <?php if (!empty($extra['text'])): ?>
                <p><?php echo Enc::html($extra['text']); ?></p>
            <?php endif; ?>

            <?php if (!empty($extra['image']) and File::exists($extra['image'])): ?>
                <div class="mega-menu-preview-image" style="background-image: url(<?php echo File::resizeUrl($extra['image'], 'c230x120-cc'); ?>);"></div>
            <?php else: ?>
                <div class="mega-menu-preview-image"></div>
            <?php endif; ?>
        </div>

        <div class="mega-menu-columns -clearfix">
            <?php
            $column = 0;
            foreach ($groups as $name => $items) {
                $column++;

                echo '<div class="mega-menu-column mega-menu-column', $column, ' -clearfix">', PHP_EOL;

                if (substr($name, 0, 1) === '-') {
                    echo '<h2 class="mega-menu-column-title blank">&nbsp;</h2>', PHP_EOL;
                } else {
                    echo '<h2 class="mega-menu-column-title">', Enc::html($name), '</h2>', PHP_EOL;
                }

                echo '<ul class="mega-menu-submenu -clearfix mega-menu-depth2">', PHP_EOL;

                foreach ($items as $node) {
                    $classes = NavigationMenu::determineClasses($node, 2, null, $selected_node, $selected_ancestors, false);

                    echo '<li class="', Enc::html(implode(' ', $classes)), '">';
                    echo '<a href="', Enc::html($node->getFriendlyUrl()), '">', Enc::html($node->getNavigationName()), '</a>';
                    echo '</li>', PHP_EOL;
                }

                echo '</ul>', PHP_EOL;
                echo '</div>', PHP_EOL;
            }
            ?>
        </div>

    </div>
</div>