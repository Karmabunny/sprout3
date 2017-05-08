<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2015 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Sprout\Helpers\Constants;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
?>


<p><b>Name:</b>
<br><?= Enc::html($data['name']); ?></p>

<p><b>Status:</b>
<br><span class="job-status"><?= Enc::html($data['status']); ?></span></p>

<p><b>Date started:</b>
<br><?= Enc::html($data['date_started']); ?></p>

<!-- Completed or failed -->
<?php if ($data['date_success']): ?>
    <p><b>Completed:</b>
    <br><?= Enc::html($data['date_success']); ?></p>

<?php elseif ($data['date_failure']): ?>
    <p><b>Failed:</b>
    <br><?= Enc::html($data['date_failure']); ?></p>

<?php endif; ?>

<!-- If running, PID and mem use -->
<?php if ($data['pid']): ?>
    <p><b>PID:</b>
    <br><?= Enc::html($data['pid']); ?></p>

    <p><b>RAM usage:</b>
    <br><?= Enc::html(File::humanSize($data['memuse'])); ?></p>
<?php endif; ?>

<!-- Metrics -->
<?php if ($data['metric1name']): ?>
    <p><b><?= Enc::html($data['metric1name']); ?>:</b>
    <br><span class="job-metric1"><?= Enc::html($data['metric1val']); ?></span></p>
<?php endif; ?>

<?php if ($data['metric2name']): ?>
    <p><b><?= Enc::html($data['metric2name']); ?>:</b>
    <br><span class="job-metric2"><?= Enc::html($data['metric2val']); ?></span></p>
<?php endif; ?>

<?php if ($data['metric3name']): ?>
    <p><b><?= Enc::html($data['metric3name']); ?>:</b>
    <br><span class="job-metric3"><?= Enc::html($data['metric3val']); ?></span></p>
<?php endif; ?>

<h3>Log</h3>
<pre class="log"><?= Enc::html($data['log']); ?></pre>

<script>
$(document).ready(function() {
    var intervalID;
    var status_names = <?php echo json_encode(Constants::$job_status); ?>;

    function updateStatus() {
        $.getJSON(SITE + 'admin/call/worker_job/jsonStatus/' + <?php echo $id; ?>, function(data) {
            $('.job-status').text(status_names[data.status]);
            if ($('.job-metric1').length) $('.job-metric1').text(data.metric1val);
            if ($('.job-metric2').length) $('.job-metric2').text(data.metric2val);
            if ($('.job-metric3').length) $('.job-metric3').text(data.metric3val);
            $('pre.log').text(data.log);

            if (data.status != 'Running') window.clearInterval(intervalID);
        });
    }

    intervalID = window.setInterval(updateStatus, 1000);
});
</script>

