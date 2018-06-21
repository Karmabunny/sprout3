<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Url;
use Sprout\Helpers\Form;

Form::setData($_GET);
?>


<div class="mainbar-with-right-sidebar">
    <form action="" method="get">
        <h3>Refinement</h3>
        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-third">
                <?php
                Form::nextFieldDetails('Class', false);
                echo Form::text('class', ['-wrapper-class' => 'white']);
                ?>
            </div>

            <div class="field-group-item col col--one-third">
                <?php
                Form::nextFieldDetails('Message', false);
                echo Form::text('message', ['-wrapper-class' => 'white']);
                ?>
            </div>

            <div class="field-group-item col col--one-third">
                <?php
                Form::nextFieldDetails('Show', false);
                echo Form::checkboxBoolList('include_404', ['-wrapper-class' => 'white'], [
                    'show_404' => '404 exceptions',
                    'show_row_missing' => 'Row missing exceptions'
                ]);
                ?>
            </div>
        </div>

        <button type="submit" class="button icon-after icon-build">Refine</button>
    </form>

    <?php echo $itemlist; ?>

    <div>
        <?php
            $cur_url = Url::current();

            $get = $_GET;
            $get['page'] = max(@$get['page'], 1) + 1;
            $next_query = http_build_query($get);

            // - 2 to negate the above addition
            $get['page'] = $get['page'] - 2;
            $prev_query = http_build_query($get);
        ?>
        <?php if ($page > 1): ?>
            <a href="<?= Enc::html($cur_url . '?' . $prev_query); ?>" class="button icon-before icon-keyboard_arrow_left">Previous page</a>
        <?php endif; ?>

        <?php if ($row_count == $page_size): ?>
            <a href="<?= Enc::html($cur_url . '?' . $next_query); ?>" class="button right icon-after icon-keyboard_arrow_right">Next page</a>
        <?php endif; ?>
    </div>
</div>

<div class="right-sidebar">
    <div class="right-sidebar-anchor"></div>
    <div class="right-sidebar-inner">
        <div class="save-changes-box">
            <h2 class="icon-before icon-keyboard_arrow_right">Search</h2>
            <form action="dbtools/exceptionDetail" method="get">
                <?php echo Form::text('id', ['placeholder' => '2400']); ?>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="button button-blue right icon-after icon-search">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>
