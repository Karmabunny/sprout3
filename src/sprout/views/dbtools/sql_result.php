<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
?>

<div class="sqlresult">
    <table class="main-list main-list-no-js">
        <thead>
            <tr>
                <?php foreach ($columns as $column): ?>
                <th><?= Enc::html($column); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
            <tr>
                <?php foreach ($row as $val): ?>
                <td><?= $val === null ? '<i>null</i>' : Enc::html($val); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($results->rowCount()): ?>
<form action="SITE/dbtools/sqlcsv" method="post" target="_blank">
    <?= Csrf::token(); ?>
    <input type="hidden" name="sql" value="<?= Enc::html($results->queryString); ?>">
    <div class="action-bar">
        <button type="submit" class="button icon-after icon-save">Download CSV</button>
    </div>
</form>
<?php endif; ?>
