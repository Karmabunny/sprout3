<?php
use Sprout\Helpers\Enc;
?>

<div id="seo-wrapper" class="page-edit-tab">
    <div class="heading-with-buttons">
        <button class="button button-small button-grey icon-close icon-after page-edit-tab-close" type="button" data-target="seo-wrapper">Close</button>
        <h3 class="h2 icon-before icon-search">SEO</h3>
    </div>

    <div class="white-box">

        <?php if (!empty($disabled)): ?>
            <ul class="messages"><li class="neutral">SEO analysis can't be determined from content.</li></ul>
            </div></div>
            <?php return; ?>
        <?php endif; ?>

        <?php if (!empty($keywords) and count($keywords) > 0): ?>
        <h2 class="icon icon-before icon-equalizer">Keyword density</h2>

        <div class="seo-keywords">
            <p>
                <?php foreach ($keywords as $word => $count): ?>
                <button class="button button-small button-grey" type="button"><?php echo Enc::html($word); ?> <sup><?php echo Enc::html($count); ?></sup></button>
                <?php endforeach; ?>
            </p>
        </div>
        <?php endif; ?>

        <h2 class="icon icon-before icon-view_list">Analysis</h2>

        <div class="seo-analysis">
            <?php if (!empty($seo_problems) and count($seo_problems) > 0): ?>
            <h4>Problems (<?php echo Enc::html(count($seo_problems)) ?>)</h4>
            <ul class="messages">
                <?php foreach ($seo_problems as $item): ?>
                <li class="error"><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <?php if (!empty($seo_improvements) and count($seo_improvements) > 0): ?>
            <h4>Improvements (<?php echo Enc::html(count($seo_improvements)) ?>)</h4>
            <ul class="messages">
                <?php foreach ($seo_improvements as $item): ?>
                <li class="neutral"><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <?php if (!empty($seo_considerations) and count($seo_considerations) > 0): ?>
            <h4>Considerations (<?php echo Enc::html(count($seo_considerations)) ?>)</h4>
            <ul class="messages">
                <?php foreach ($seo_considerations as $item): ?>
                <li class="neutral neutral-grey"><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <?php if (!empty($seo_goodresults) and count($seo_goodresults) > 0): ?>
            <h4>Good results (<?php echo Enc::html(count($seo_goodresults)) ?>)</h4>
            <ul class="messages">
                <?php foreach ($seo_goodresults as $item): ?>
                <li class="confirm"><?php echo $item; ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

    </div>
</div>
