<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Profiling;
use Sprout\Helpers\Url;

Form::setData($_GET);

$cur_url = Url::current();

$get = $_GET;
$get['page'] = max($get['page'] ?? 0, 1) + 1;
$next_query = http_build_query($get);

// - 2 to negate the above addition
$get['page'] = $get['page'] - 2;
$prev_query = http_build_query($get);

$profiling_enabled = Profiling::isEnabledForUrl('');
?>

<div class="mainbar mainbar--wide">

    <form action="" method="get" class="white-box">
        <h3 style="margin-top: 0">Search</h3>

        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-third">
                <?php
                Form::nextFieldDetails('Category', false);
                echo Form::text('category', ['-wrapper-class' => 'white']);
                ?>
            </div>

            <div class="field-group-item col col--one-third">
                <?php
                Form::nextFieldDetails('URL', false);
                echo Form::text('url', ['-wrapper-class' => 'white']);
                ?>
            </div>

            <div class="field-group-item col col--one-third">
                <?php
                Form::nextFieldDetails('Tag', false);
                echo Form::text('tag', ['-wrapper-class' => 'white']);
                ?>
            </div>
        </div>

        <div style="text-align: right">
            <button type="submit" class="button icon-after icon-search">Search</button>
        </div>
    </form>

    <div style="display: inline-block">
        <div><?php echo $total_row_count; ?> records</div>
        <div><?php echo sprintf('%.4f', $total_time); ?> second</div>
    </div>

    <form action="dbtools/profilingLogSessionOverride" method="post" style="display: inline-block; float: right">
        <input type="hidden" name="enabled" value="<?= (int)!$profiling_enabled ?>" />
        <button type="submit" class="button">
            Profiling: <?= $profiling_enabled ? 'Disable' : 'Enable' ?>
        </button>
    </form>

    <?php echo $itemlist; ?>

    <div class="paginate-bar">
        <div class="paginate-bar-total">
            <?php echo $total_row_count; ?> records
        </div>
        <div class="paginate-bar-buttons">

            <?php if ($page > 1): ?>
                <a href="<?= Enc::html($cur_url . '?' . $prev_query); ?>" class="paginate-bar-button  paginate-bar-previous button button-blue button-small icon-before icon-keyboard_arrow_left">Prev</a>
            <?php endif; ?>

            <div class="paginate-bar-current-page">
                Page <?php echo $page; ?> of <?php echo $total_page_count; ?>
            </div>

            <?php if ($row_count == $page_size): ?>
                <a href="<?= Enc::html($cur_url . '?' . $next_query); ?>" class="paginate-bar-button paginate-bar-next button button-blue button-small icon-after icon-keyboard_arrow_right">Next</a>
            <?php endif; ?>
        </div>
    </div>
</div>
