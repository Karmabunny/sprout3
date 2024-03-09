<?php
use Sprout\Helpers\Enc;
?>

<table class="main-list">
    <thead>
        <tr>
            <th>Module</th>
            <th>Class</th>
            <th>Version</th>
            <th>Full Path</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($modules as $module): ?>
        <tr>
            <td><?= Enc::html($module->getName()); ?></td>
            <td><?= Enc::html(get_class($module)); ?></td>
            <td><?= Enc::html($module->getVersion()); ?></td>
            <td><?= Enc::html($module->getPath()); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>