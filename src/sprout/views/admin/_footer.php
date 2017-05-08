<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Sprout;
?>


<div class="footer">
        <ul class="footer-list">
            <li>
                <?php $rev = Kohana::config('core.repo_rev'); if ($rev) { echo ' | Site: r' . $rev; } ?>
                <?php if (Kohana::config('branding.product_url')): ?>
                    <a href="<?= Enc::html(Kohana::config('branding.product_url')); ?>" target="_blank"><?= Enc::html(Kohana::config('branding.product_name')); ?></a>
                <?php else: ?>
                    <?= Enc::html(Kohana::config('branding.product_name')); ?>
                <?php endif; ?>
                <?php echo Sprout::getVersion(); ?>
            </li>

            <?php
            if (Kohana::config('branding.product_url') !== Kohana::config('branding.support_url')) {
                echo '<li>Support: ';
                echo '<a href="', Enc::html(Kohana::config('branding.support_url')), '" target="_blank">';
                echo Enc::html(Kohana::config('branding.support_organisation'));
                echo '</a>';
                echo '</li>';
            }
            ?>

            <li>
                <?php echo Kohana::config('branding.copyright_html'); ?>
            </li>
        </ul>
</div>
