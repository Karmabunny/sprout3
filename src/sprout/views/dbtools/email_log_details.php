<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;

if (empty($log)) {
    echo '<p>Not found</p>';
    return;
}
?>

<style>
pre {
    height: 200px;
    overflow: auto;
    resize: vertical;
}
</style>


<div class="mainbar-with-right-sidebar">
    <table class="main-list main-list-no-js" style="margin-top: 0">
        <thead>
            <tr>
                <th class="header">Reference</th>
                <th class="header">Date</th>
                <th class="header">Time Taken</th>
                <th class="header">Success</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo Enc::html($log->id); ?></td>
                <td><?php echo Enc::html($log->subject); ?></td>
                <td><?php echo Enc::html(sprintf("%.3fs", $log->time_taken)); ?></td>
                <td><?php echo $log->success ? 'yes' : 'no' ?></td>
            </tr>
            <tr>
                <td colspan="4"><strong>Addresses</strong></td>
            </tr>
            <tr>
                <td><strong>From</strong></td>
                <td colspan="3"><?php echo Enc::html($log->from_address); ?></td>
            </tr>
            <tr>
                <td><strong>Reply-To</strong></td>
                <td colspan="3"><?php echo Enc::html($log->reply_to_address ?: '--'); ?></td>
            </tr>
            <tr>
                <td><strong>To</strong></td>
                <td colspan="3"><?php echo Enc::html($log->to_address ?: '--'); ?></td>
            </tr>
            <tr>
                <td><strong>Cc</strong></td>
                <td colspan="3"><?php echo Enc::html($log->cc_address ?: '--'); ?></td>
            </tr>
            <tr>
                <td><strong>Bcc</strong></td>
                <td colspan="3"><?php echo Enc::html($log->bcc_address ?: '--'); ?></td>
            </tr>
        </tbody>
    </table>

    <h3>Subject</h3>
    <pre style="height: 3em"><?php echo Enc::html($log->subject); ?></pre>

    <h3>Body</h3>
    <pre><?php echo Enc::html($log->body); ?></pre>

    <?php if ($log->error): ?>
        <h3>Error</h3>
        <pre><?php echo Enc::html($log->error); ?></pre>
    <?php endif; ?>
</div>
