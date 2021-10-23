<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Html;

Form::setData($_GET);
?>

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
    <?php foreach ($results as $row): ?>
    <tr>
        <?php foreach ($row as $name => $val): ?>
        <td>
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
