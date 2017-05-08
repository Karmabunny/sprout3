<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Form;
?>


<div class="info">
    This tool will manually run a worker job class from within a browser request.
    Note that as they're running in a browser, the jobs may run out of ram or execution time.
</div>


<form action="admin/call/worker_job/manualRunAction" method="post">
    <?= Csrf::token(); ?>

    <?php
    Form::nextFieldDetails('Worker class', true, 'This must include the namespace');
    echo Form::text('class_name', ['placeholder' => 'e.g. Sprout\\Helpers\\WorkerLinkChecker'], []);
    ?>

    <?php
    Form::nextFieldDetails('Arguments', false, 'Provide this as a JSON array');
    echo Form::multiline('args', ['placeholder' => 'e.g. [ "test@example.com" ]', 'rows' => 5], []);
    ?>


    <div class="action-log">
        <button class="button" type="submit">Run worker job</button>
    </div>
</form>