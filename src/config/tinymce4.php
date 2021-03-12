<?php


/**
* The default group of settings to load
**/
$config['default_group'] = 'Standard';


/**
* Toolbar buttons for the Standard group
**/
$config['Standard']['toolbar'] = array(
    'bold italic strikethrough subscript superscript link unlink anchor | removeformat | code fullscreen',
    'styleselect | style-h2 style-h3 style-h4 style-p | bullist numlist indent outdent | alignleft alignright | image sprout_gallery media table '
);

/**
* Tables for the Standard group
**/
$config['Standard']['table_default_attributes'] = array('class' => 'table--content-standard');
$config['Standard']['table_appearance_options'] = false;
$config['Standard']['table_advtab'] = false;
$config['Standard']['table_cell_advtab'] = false;
$config['Standard']['table_row_advtab'] = false;
$config['Standard']['table_class_list'] = array(
    array('title' => 'Standard', 'value' => 'table--content-standard'),
    array('title' => 'Unstyled', 'value' => 'table__no-styles'),
    array('title' => 'Small', 'value' => 'table--content-standard table--content-small'),
    array('title' => 'Responsive', 'value' => 'table--content-standard table--responsive'),
);

/**
* Link classes for the Standard group
**/
$config['Standard']['link_class_list'] = array(
    array('title' => 'Standard', 'value' => ''),
    array('title' => 'Button', 'value' => 'button'),
    array('title' => 'Popup Page', 'value' => 'js-popup-page'),
    array('title' => 'Popup Image', 'value' => 'js-popup-image'),
);

/**
* Image classes for the Standard group
**/
$config['Standard']['image_class_list'] = array(
    array('title' => 'Inline', 'value' => ''),
    array('title' => 'Align right', 'value' => 'right'),
    array('title' => 'Align left', 'value' => 'left'),
    array('title' => 'Center', 'value' => 'center'),
);

/**
* Formats dropdown for the Standard group
**/
$config['Standard']['style_formats'] = array(
    array('title' => 'Headings', 'items' => array(
        array('title' => 'Heading 2', 'format' => 'h2'),
        array('title' => 'Heading 3', 'format' => 'h3'),
        array('title' => 'Heading 4', 'format' => 'h4'),
    )),
    array('title' => 'Block', 'items' => array(
        array('title' => 'Paragraph', 'format' => 'p'),
        array('title' => 'Blockquote', 'format' => 'blockquote'),
        array('title' => 'Blockquote to the right', 'block' => 'blockquote', 'classes' => 'blockquote--right', 'wrapper' => true),
        array('title' => 'Blockquote to the left', 'block' => 'blockquote', 'classes' => 'blockquote--left', 'wrapper' => true),
    )),
    array('title' => 'Inline', 'items' => array(
        array('title' => 'Bold', 'format' => 'bold'),
        array('title' => 'Italic', 'format' => 'italic'),
    )),
    array('title' => 'Wrappers', 'items' => array(
        array('title' => 'Expando', 'block' => 'div', 'classes' => 'expando', 'wrapper' => true),
        array('title' => 'Highlight', 'block' => 'div', 'classes' => 'highlight', 'wrapper' => true),
        array('title' => 'Highlight to the right', 'block' => 'div', 'classes' => 'highlight--right', 'wrapper' => true),
        array('title' => 'Highlight to the left', 'block' => 'div', 'classes' => 'highlight--left', 'wrapper' => true),
    )),
);


/**
* Toolbar buttons for the Lite group
**/
$config['Lite']['toolbar'] = array(
    'bold italic'
);

