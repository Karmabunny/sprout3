<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;

Form::setData($_GET);
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
    <table class="main-list">
        <thead>
            <tr>
                <th class="header">Date</th>
                <th class="header">Class</th>
                <th class="header">Message</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo Enc::html($log['date_generated']); ?></td>
                <td><a href="dbtools/exceptionLog?class=<?php echo Enc::html(Enc::url($log['class_name'])); ?>"><?php echo Enc::html($log['class_name']); ?></a></td>
                <td><?php echo Enc::html($log['message']); ?></td>
            </tr>
        </tbody>
    </table>

    <h3>Exception object</h3>
    <pre><?php echo Enc::html(print_r(json_decode($log['exception_object'], true), true)); ?></pre>

    <h3>Exception trace</h3>
    <pre><?php echo Enc::html(print_r(json_decode($log['exception_trace'], true), true)); ?></pre>

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
        <div class="save-changes-box">
            <h2 class="icon-before icon-keyboard_arrow_right">Go to #</h2>
            <form action="dbtools/exceptionDetail" method="get">
                <?php echo Form::text('id', ['placeholder' => '2400']); ?>
                <div class="save-changes-box-bottom -clearfix">
                    <button type="submit" class="button button-blue right icon-after icon-play_arrow">Go</button>
                </div>
            </form>
        </div>
    </div>
</div>
