<?php
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Enc;
?>

<?php if (empty($usage)): ?>
    <p>Unable to locate any live instances of this file.</p>
<?php else: ?>
    <table class="main-list">
        <thead>
            <tr>
                <th>Table</th>
                <th>Record ID or name</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($usage as $row) {
                echo '<tr>';

                $ctlr_name = Inflector::singular($row[0]);
                printf('<td><a href="SITE/admin/edit/%s/%s">%s</a></td>', Enc::html($ctlr_name), Enc::html($row[1]), Enc::html($row[0]));

                echo '<td>', Enc::html($row[2] ? $row[2] : 'ID # ' . $row[1]), '</td>';
                echo '</tr>';
                echo "\n";
            }
            ?>
        </tbody>
    </table>
<?php endif; ?>
