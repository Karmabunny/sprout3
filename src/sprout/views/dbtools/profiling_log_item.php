<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Profiling;

$token = $item['token'] ?? '';
$trace = $item['trace'] ?? [];

unset($item['token']);
unset($item['trace']);
?>

<style>
.backtrace { margin:0; padding:0 6px; list-style:none; line-height: 1; }
.backtrace li > span { font-weight: bold; padding: 0 5px 5px }
</style>

<?php if (empty($item)): ?>
    <strong>Not found</strong>

<?php else: ?>

<div>
    <a href="dbtools/profilingLog?category=<?= Enc::html($item['category']) ?>">Filter by category</a><br>
    <a href="dbtools/profilingLog?tag=<?= Enc::html($item['request.tag']) ?>">Filter by tag</a><br>
    <a href="dbtools/profilingLog?url=<?= Enc::html($item['request.url']) ?>">Filter by URL</a><br>
</div>

<table class="main-list">
    <tbody>
    <?php foreach ($item as $key => $value): ?>
        <tr>
            <td><strong><?php echo Enc::html($key); ?></strong></td>
            <?php if (is_array($value)): ?>
                <td><?php echo Enc::html(json_encode($value, JSON_PRETTY_PRINT)); ?></td>
            <?php elseif (is_float($value)): ?>
                <td><?php echo sprintf('%.4f', $value); ?></td>
            <?php else: ?>
                <td><?php echo Enc::html($value); ?></td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h3>Token</h3>

<pre><?php echo Enc::html($token); ?></pre>


<h3>Trace</h3>


<ul class="backtrace">
<?php echo Profiling::backtrace($trace); ?>
</ul>

<?php endif; ?>
