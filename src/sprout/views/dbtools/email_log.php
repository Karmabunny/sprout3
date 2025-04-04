<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Url;
use Sprout\Helpers\Form;

Form::setData($_GET);
?>


<div class="">

    <?php echo $itemlist; ?>

    <div>
        <?php
            $cur_url = Url::current();

            $get = $_GET;
            $get['page'] = max($get['page'] ?? 0, 1) + 1;
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
