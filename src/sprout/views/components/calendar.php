<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Needs;


Needs::fileGroup('sprout/calendar');
?>

<div class="calendar-container -clearfix">

    <ul class="calendar-weekdays-list">
        <?php foreach ($day_names as $day): ?>
        <li class="calendar-weekdays-list__item"><?php echo Enc::html($day); ?></li>
        <?php endforeach; ?>
    </ul>

    <?php while ($date_start <= $date_end): ?>

        <?php if ($date_start->format('N') == $week_begins): ?>
        <ul class="calendar-days-list">
        <?php endif; ?>

            <?php if ($date_start->format('Y-m') == date('Y-m', strtotime($year . '-' . $month))): ?>
            <li class="calendar-days-list__item">
            <?php else: ?>
            <li class="calendar-days-list__item calendar-days-list__item__other-month">
            <?php endif; ?>

                <div class="calendar-days-list__item__date"><?php echo Enc::html($date_start->format('d')); ?></div>

                <?php echo $callback($date_start); ?>

            </li>

        <?php if ($date_start->format('N') == $week_ends): ?>
        </ul>
        <?php endif; ?>

        <?php $date_start->modify('+1 day'); ?>
    <?php endwhile; ?>

</div>
