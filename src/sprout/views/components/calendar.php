<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Needs;


Needs::fileGroup('sprout/calendar');
?>

<div class="calendar-container -clearfix">

    <?php if (!empty($options['show_month'])): ?>
    <h4 class="calendar-heading"><?php echo Enc::html(date($options['month_format'], strtotime('01-' . $month . '-' . $year))); ?></h4>
    <?php endif; ?>

    <ul class="calendar-weekdays-list">
        <?php foreach ($day_names as $idx => $day): ?>
        <li class="calendar-weekdays-list__item" data-day="<?php echo Enc::html($idx); ?>"><?php echo Enc::html($day); ?></li>
        <?php endforeach; ?>
    </ul>

    <?php while ($options['date_start'] <= $options['date_end']): ?>

        <?php if ($options['date_start']->format('N') == $options['week_begins']): ?>
        <ul class="calendar-days-list">
        <?php endif; ?>

            <?php if ($options['date_start']->format('Y-m') == date('Y-m', strtotime($year . '-' . $month))): ?>
            <li class="calendar-days-list__item" data-date="<?php echo Enc::html($options['date_start']->format('Y-m-d')); ?>">
            <?php else: ?>
            <li class="calendar-days-list__item calendar-days-list__item__other-month" data-date="<?php echo Enc::html($options['date_start']->format('Y-m-d')); ?>">
            <?php endif; ?>

                <time class="calendar-days-list__item__date" datetime="<?php echo Enc::html($options['date_start']->format('Y-m-d')); ?>">
                    <span class="calendar-days-list__item__date__extra"><?php echo Enc::html($options['date_start']->format('D')); ?></span>
                    <?php echo Enc::html($options['date_start']->format('d')); ?><span class="calendar-days-list__item__date__extra"><?php echo Enc::html($options['date_start']->format('/m/Y')); ?></span>
                </time>

                <?php echo $callback($options['date_start']); ?>

            </li>

        <?php if ($options['date_start']->format('N') == $options['week_ends']): ?>
        </ul>
        <?php endif; ?>

        <?php $options['date_start']->modify('+1 day'); ?>
    <?php endwhile; ?>

</div>
