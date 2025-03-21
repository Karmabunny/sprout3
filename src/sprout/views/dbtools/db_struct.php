<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Html;

Form::setData($_GET);
?>

<script>
/**
 * Sort the table by clicking on the column headers
 */
$(document).ready(function()
{
    $('th').click(function()
    {
        var table = $(this).parents('table').eq(0);
        var rows = table.find('tr:gt(0)').toArray().sort(comparer($(this).index()));
        this.asc = !this.asc;
        if (!this.asc){rows = rows.reverse();}
        for (var i = 0; i < rows.length; i++){table.append(rows[i]);}
    })

    function comparer(index)
    {
        return function(a, b)
        {
            var valA = getCellValue(a, index), valB = getCellValue(b, index);
            return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.toString().localeCompare(valB);
        }
    }

    function getCellValue(row, index)
    {
        return $(row).children('td').eq(index).attr('data-value');
    }
});
</script>

<style>
th { cursor: pointer; }
</style>

<form action="" method="get" class="field-group-wrap -clearfix">

    <div class="field-group-item col col--one-half">
        <?php
        Form::nextFieldDetails('Search', true);
        echo Form::text('search');
        ?>
    </div>

    <div class="field-group-item col col--one-half" style="margin-top: 2em;">
        <button class="button icon-before icon-search" type="submit">Search</button>
    </div>

</form>

<h3><?= empty($_GET['search']) ? 'All' : 'Matching' ; ?> tables</h3>

<table class="main-list main-list-no-js">
<thead>
    <tr>
        <?php foreach ($headings as $heading): ?>
        <th><?= Enc::html($heading); ?></th>
        <?php endforeach; ?>
    </tr>
</thead>
<tbody>
    <?php foreach ($results as $idx => $row): ?>
    <tr>
        <?php foreach ($row as $name => $val): ?>
        <td data-value="<?= Enc::html($raw_results[$idx][$name]); ?>">
            <?php
            if ($name == 'Name'):
                $val = empty($val) ? '&nbsp;' : $val;
                $suf = !empty($_GET['search']) ? '&search=' . $_GET['search'] : '';
                echo Html::anchor('dbtools/struct/'.$val.$suf, $val);
            else:
                echo Enc::html($val);
            endif;
            ?>
        </td>
        <?php endforeach; ?>
    </tr>
    <?php endforeach; ?>
</tbody>
</table>
