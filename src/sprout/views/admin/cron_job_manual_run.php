<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Form;
?>


<div class="info">
    This tool will manually run a cron job manually from within a browser request.
    Note that as they're running in a browser, the jobs may run out of ram or execution time.
</div>


<form action="admin/call/cron_job/manualRunAction" method="post">
    <?= Csrf::token(); ?>

    <?php
    Form::nextFieldDetails('Job', true);
    echo Form::dropdown('job', [], $jobs);
    ?>


    <div class="action-log">
        <button class="button" type="submit">Run cron job</button>
    </div>
</form>