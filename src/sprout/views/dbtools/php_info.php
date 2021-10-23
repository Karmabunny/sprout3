<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Subsites;
?>

<style>
table.main-list {
    margin-bottom: 2em;
}
#phpinfo table {
    background-color: white;
    border: 1px #B8B7B7 solid;
    width: 100%;
    margin-bottom: 2em;
}
#phpinfo table tr {
    background-color: #F9F9FB;
}
#phpinfo table tr:nth-child(even) {
    background-color: white;
}
#phpinfo table td.e,
#phpinfo table th {
    text-align: left;
    padding: 4px 6px;
    font-weight: bold;
}
#phpinfo table td {
    padding: 4px 6px;
    max-width: 500px;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>

<?php if (!empty($vals) and count($vals) > 0): ?>
<table class="main-list">
    <caption>Basics</caption>
    <thead>
        <tr>
            <th>Key</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($vals as $key => $val): ?>
        <tr>
            <td><strong><?php echo Enc::html($key); ?></strong></th>
            <td><?php echo Enc::html($val); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php if (!empty($subsites) and count($subsites)): ?>
<table class="main-list">
    <caption>Sub-sites</caption>
    <thead>

        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Code</th>
            <th>Domain</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($subsites as $subsite): ?>
        <tr>
            <td><?= Enc::html($subsite['id']); ?></td>
            <td><?= Enc::html($subsite['name']); ?></td>
            <td><?= Enc::html($subsite['code']); ?></td>
            <td><?= Enc::html(Subsites::getAbsRoot($subsite['id'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php
ob_start();
phpinfo();
$phpinfo = ob_get_contents();
ob_end_clean();
$phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
echo "<div id='phpinfo'>{$phpinfo}</div>";
?>
