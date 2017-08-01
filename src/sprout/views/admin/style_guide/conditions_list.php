<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
?>


<?php
if (!empty($_POST)) {
    echo '<h3>$_POST</h3>';
    echo '<pre>', Enc::html(print_r($_POST, true)), '</pre>';
    Form::setData($_POST);
} else {
    Form::setData([
        'conditions' => json_encode([
            ['field' => 'age', 'op' => '>=', 'val' => 20],
            ['field' => 'age', 'op' => '<=', 'val' => 40],
            ['field' => 'name', 'op' => 'begin', 'val' => 'Joe'],
        ]),
        'random' => 'Random text',
    ]);
}
?>


<form action="" method="post">

    <h3>Form fields</h3>

    <?php
    Form::nextFieldDetails('Example conditions', false);
    echo Form::conditionsList('conditions', [], [
        'fields' => [
            'name' => 'Name',
            'age' => 'Age',
            'gender' => 'Gender',
        ],
        'url' => 'admin_ajax/style_guide_demo_conditions',
    ]);
    ?>

    <?php
    Form::nextFieldDetails('Random text field', false);
    echo Form::text('random', [], []);
    ?>

    <button type="submit" class="button right">Submit form</button>
</form>