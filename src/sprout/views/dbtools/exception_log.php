<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Url;
use Sprout\Helpers\Form;

Form::setData($_GET);
?>


<div class="">
    <form action="" method="get" class="white-box">
        <h3 style="margin-top: 0">Search</h3>

        <div class="field-group-wrap -clearfix">
            <div class="row">
                <div class="field-group-item col col--one-fourth">
                    <?php
                    Form::nextFieldDetails('Class', false);
                    echo Form::text('class', ['-wrapper-class' => 'white']);
                    ?>
                </div>

                <div class="field-group-item col col--one-fourth">
                    <?php
                    Form::nextFieldDetails('Message', false);
                    echo Form::text('message', ['-wrapper-class' => 'white']);
                    ?>
                </div>

                <div class="field-group-item col col--one-fourth">
                    <?php
                    Form::nextFieldDetails('Error Type', false);
                    echo Form::dropdown('type', ['-wrapper-class' => 'white', '-dropdown-top' => 'All'], [
                        'php' => 'Server (PHP)',
                        'js' => 'Browser (Javascript)',
                    ]);
                    ?>
                </div>

                <div class="field-group-item col col--one-fourth">
                    <?php
                    Form::nextFieldDetails('Lookup', false);
                    echo Form::text('id', ['placeholder' => 'SE2400']);
                    ?>
                </div>
            </div>

            <div class="row">
                <div class="field-group-item col col--one-fourth">
                    <?php
                    Form::nextFieldDetails('Show', false);
                    echo Form::checkboxBoolList('include_404', ['-wrapper-class' => 'white'], [
                        'show_404' => '404 exceptions',
                        'show_row_missing' => 'Row missing exceptions',
                        'show_uncaught_only' => 'Uncaught only',
                    ]);
                    ?>
                </div>
            </div>

            <div style="text-align: right">
                <button type="submit" class="button icon-after icon-search">Search</button>
            </div>
        </div>
    </div>
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
