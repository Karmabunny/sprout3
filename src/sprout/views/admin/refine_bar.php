<?php
use Sprout\Helpers\Form;

Form::setData($_GET);
?>

<div class="refine-bar -clearfix">
    <h3>Search</h3>
    <form action="" method="GET">
        <?php
        echo Form::conditionsList('conditions', [], [
            'fields' => $fields,
            'url' => sprintf('admin/call/%s/refineBarConditions', $controller_name),
        ]);
        ?>

        <button type="submit" class="refine-submit button button-green icon-after icon-search">Search</button>
    </form>
</div>
