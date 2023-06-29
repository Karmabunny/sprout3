<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\JsErrors;

JsErrors::needs();
?>

<script>
async function triggerJsError(message) {
    async function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    setTimeout(() => {
        location.reload();
    }, 500);

    await sleep(250);

    const error = new Error(message);
    error.custom_property = 'hello world';
    throw error;
}
</script>

<?php if (!empty($last_error)): ?>
<table class="main-list" style="margin-top: 0">
    <thead>
        <tr>
            <th class="header">Reference</th>
            <th class="header">Date</th>
            <th class="header">Class</th>
            <th class="header">Message</th>
            <th class="header">Caught</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><a href="dbtools/exceptionDetail?id=<?= $last_error['id'] ?>"><?= Enc::html($last_error['reference']); ?></td>
            <td><?php echo Enc::html($last_error['date_generated']); ?></td>
            <td><?php echo Enc::html($last_error['class_name']); ?></td>
            <td><?php echo Enc::html($last_error['message']); ?></td>
            <td><?php echo $last_error['caught'] ? 'yes' : 'no' ?></td>
        </tr>
    </tbody>
</table>
<?php else: ?>
    <p></p>
<?php endif; ?>

<div>
    <form method="POST">
        <input type="hidden" name="throw" value="Testing PHP errors">
        <button type="submit" class="button">Trigger PHP error</button>
    </form>
    <br>
    <button type="button" class="button" onclick="triggerJsError('Testing JS errors')">Trigger JS error</button>
</div>
