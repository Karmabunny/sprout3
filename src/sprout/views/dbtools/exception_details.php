<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;

if (empty($log)) {
    echo '<p>Not found</p>';
    return;
}

Form::setData([
    'id' => $log->reference(),
]);
?>

<style>
pre {
    height: 200px;
    overflow: auto;
    resize: vertical;
}
</style>


<div class="mainbar-with-right-sidebar">
    <?php if (!empty($log)): ?>
    <table class="main-list main-list-no-js" style="margin-top: 0">
        <thead>
            <tr>
                <th class="header">Reference</th>
                <th class="header">Date</th>
                <th class="header">Class</th>
                <th class="header">Caught</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo Enc::html($log['reference']); ?></td>
                <td><?php echo Enc::html($log['date_generated']); ?></td>
                <td><?php echo Enc::html($log['class_name']); ?></td>
                <td><?php echo $log['caught'] ? 'yes' : 'no' ?></td>
            </tr>
        </tbody>
    </table>

    <h3>Exception message</h3>
    <pre><?php echo Enc::html($log['message']); ?></pre>

    <h3>Exception object</h3>
    <pre><?php echo Enc::html(print_r(json_decode($log['exception_object'], true), true)); ?></pre>

    <h3>Exception trace</h3>
    <pre><?php echo Enc::html($log->renderTrace()); ?></pre>

    <h3>$_SERVER</h3>
    <pre><?php echo Enc::html(print_r(json_decode($log['server'], true), true)); ?></pre>

    <h3>$_GET</h3>
    <pre><?php echo Enc::html(print_r(json_decode($log['get_data'], true), true)); ?></pre>

    <h3>$_POST</h3>
    <p><em>This is not stored for security reasons.</em></p>

    <h3>$_SESSION</h3>
    <pre><?php echo Enc::html(print_r(json_decode($log['session'], true), true)); ?></pre>

    <?php else: ?>
        <p><em>Exception not found</em></p>
        <p><a href="dbtools/exceptionLog" class="button button-blue icon-before icon-keyboard_arrow_left">Back</a></p>
    <?php endif; ?>
</div>

<div class="right-sidebar">
    <div class="right-sidebar-anchor"></div>
    <div class="right-sidebar-inner">
        <div class="white-box">
            <h3 style="margin-top: 0">Lookup</h3>
            <form action="dbtools/exceptionDetail" method="get" class="-clearfix">
                <div class="field-group-item col col--two-third">
                    <?php
                    echo Form::text('id', ['placeholder' => 'SE2400']);
                    ?>
                </div>
                <div class="field-group-item col col--one-third">
                    <button type="submit" class="button button-block icon-after icon-keyboard_arrow_right">Lookup</button>
                </div>
            </form>
            <h4 style="margin-top: 0">Filter by...</h4>
            <div class="field-group-item">
                <a href="dbtools/exceptionLog?session_id=<? echo Enc::html($log['session_id']) ?>">
                    Session
                </a>
            </div>
            <div class="field-group-item">
                <a href="dbtools/exceptionLog?ip_address=<? echo Enc::html($log['ip_address']) ?>">
                    IP Address
                </a>
            </div>
            <div class="field-group-item">
                <a href="dbtools/exceptionLog?class=<? echo Enc::html($log['class_name']) ?>&type=<? echo $log['type'] ?>">
                    Class
                </a>
            </div>
            <br>
            <div class="field-group-item">
                <a href="dbtools/exceptionLog">
                    View all
                </a>
            </div>
        </div>
    </div>
</div>
