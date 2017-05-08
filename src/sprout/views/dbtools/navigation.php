<?php
use Sprout\Controllers\DbToolsController;
use Sprout\Helpers\Enc;
?>


<p>
    <a href="dbtools" class="button button-small">Overview</a>
</p>

<?php foreach (DbToolsController::$tools as $section => $subtools): ?>
<div class="sidebar-box">
    <h2 class="icon-before icon-settings"><?php echo Enc::html($section); ?></h2>
    <div class="sidebar-box-content">
        <?php
        echo '<ul class="list-style-1">';
        foreach ($subtools as $item) {
            echo '<li class="database-tool"><a href="', $item['url'], '">';
            echo '<span>', Enc::html($item['name']), '</span>';
            echo '</a></li>';
        }
        echo '</ul>';
        ?>
    </div>
</div>
<?php endforeach; ?>