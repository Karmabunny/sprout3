<?php
use Sprout\Helpers\Admin;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
use Sprout\Helpers\Form;
use Sprout\Helpers\JsonForm;



Form::setData($data);
Form::setErrors($errors);

if (!isset($for)) {
    $for = ($id == 0 ? 'add' : 'edit');
}
?>


<div class="main-tabs">
    <?php if (count($config) > 1): ?>
        <ul>
            <?php foreach ($config as $tab => $tab_content): ?>
            <li><a href="#main-tabs-<?= Enc::id($tab); ?>"><?= Enc::html($tab); ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php foreach ($config as $tab => $tab_content): ?>
        <div class="tab" id="main-tabs-<?= Enc::id($tab); ?>">
            <?php
            if (is_array($tab_content)) {
                foreach ($tab_content as $item) {
                    echo JsonForm::renderTabItem($item, $for, $id, $data, $errors);
                }
            } elseif ($tab_content == 'categories') {
                echo Fb::heading('Categories');
                echo Admin::categorySelection('categories[]', $cats, $data['categories']);
            }
            ?>
        </div>
    <?php endforeach; ?>
</div>

