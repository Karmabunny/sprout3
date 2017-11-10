<?php
use Sprout\Helpers\Form;
use Sprout\Helpers\Treenode;
?>


<?php
$form_attributes = [
    'Grey + regular (default) elements' => [],
    'Grey + small elements' => ['-wrapper-class' => 'small'],
    'Grey + large elements' => ['-wrapper-class' => 'large'],
    'White + regular' => ['-wrapper-class' => 'white'],
    'White + small elements' => ['-wrapper-class' => 'white small'],
    'White + large elements' => ['-wrapper-class' => 'white large'],
    'Disabled' => ['disabled' => 'disabled'],
];


$dropdown_tree = new Treenode();
$child = new Treenode(['id' => 10, 'name' => 'A']);
$dropdown_tree->children[] = $child;
$child->parent = $dropdown_tree;

foreach ($form_attributes as $label => $attributes) {
    echo '<h2>', $label, '</h2>';


    Form::nextFieldDetails('Text', false);
    echo Form::text('textz', $attributes);

    Form::nextFieldDetails('Select', false);
    echo Form::dropdown('dropdown', $attributes, [0 => "Lol", 1 => "Rofl", 2 => "Lmao"]);

    Form::nextFieldDetails('Select tree', false);
    echo Form::dropdownTree('dropdown_tree', $attributes, [
        'root' => $dropdown_tree,
        'exclude' => [1, 2, 3],
    ]);

    Form::nextFieldDetails('Number', false);
    echo Form::number('number', $attributes);

    Form::nextFieldDetails('Dollars', false);
    echo Form::money('dollars', $attributes);

    Form::nextFieldDetails('Range', false);
    echo Form::range('range', $attributes);

    Form::nextFieldDetails('Password', false);
    echo Form::password('password', $attributes);

    Form::nextFieldDetails('Upload', false);
    echo Form::upload('upload', $attributes);

    Form::nextFieldDetails('Email', false);
    echo Form::email('email', $attributes);

    Form::nextFieldDetails('Phone', false);
    echo Form::phone('phone', $attributes);

    Form::nextFieldDetails('Multiline', false);
    echo Form::multiline('multiline', $attributes + ['rows' => '5']);

    Form::nextFieldDetails('Multiradio', false);
    echo Form::multiradio('multiradio', $attributes, ['box1' => "I'm a checkbox", 'box2' => "Don't judge me"]);

    Form::nextFieldDetails('Checkbox list', false);
    echo Form::checkboxBoolList('checkboxList', $attributes, ['box1' => "I'm a checkbox", 'box2' => "Don't judge me"]);

    Form::nextFieldDetails('Richtext', false);
    echo Form::richtext('richtext', $attributes);

    Form::nextFieldDetails('More text', false);
    echo Form::text('textz', $attributes);

    Form::nextFieldDetails('Date picker', false);
    echo Form::datepicker('datepicker', $attributes);

    Form::nextFieldDetails('Time picker', false);
    echo Form::timepicker('timepicker', $attributes);

    Form::nextFieldDetails('Date range picker', false);
    echo Form::daterangepicker('Depart, Arrive', $attributes);

    Form::nextFieldDetails('Date/time range picker', false);
    echo Form::datetimerangepicker('Depart,Arrive', $attributes);

    Form::nextFieldDetails('Date/time picker', false);
    echo Form::datetimepicker('datetimepicker', $attributes);

    Form::nextFieldDetails('Colour picker', false);
    echo Form::colorpicker('colorpicker', $attributes);

    Form::nextFieldDetails('Total selector', false);
    echo Form::totalselector('totalselector', $attributes, [
        'singular' => 'guest',
        'plural' => 'guests',
        'fields' => [
            [
                'name' => 'Adults',
                'value' => 1,
                'min' => 1,
                'max' => 10
            ],
            [
                'name' => 'Kids',
                'helper' => '(2-12 yrs)',
            ]
        ]
    ]);
}
?>
