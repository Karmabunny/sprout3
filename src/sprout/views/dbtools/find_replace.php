<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;

/** @var string[] $find */
/** @var iterable<array> $results */
?>

<form method="post">
    <?php echo Csrf::token(); ?>

    <div class="white-box">
        <div class="field-group-wrap -clearfix">
            <div class="field-group-item col col--one-fourth">
                <?php
                echo Form::nextFieldDetails('Find', true, 'Search with regex (delimiter = !)');
                echo Form::text('find', ['required' => true]);
                ?>
            </div>
            <div class="field-group-item col col--one-fourth">
                <?php
                echo Form::nextFieldDetails('Replace', false, '--');
                echo Form::text('replace');
                ?>
            </div>
            <div class="field-group-item col col--one-third">
                <div class="field-element">
                    <div class="field-label">
                        <label>&nbsp;</label>
                        <div class="field-helper">&nbsp;</div>
                    </div>
                    <div class="field-input">
                        <button name="action" value="find" class="button">Search</button>
                        <button name="action" value="replace" class="button" <?= $finds ? '' : 'disabled' ?>>Replace</button>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" name="dry" value="" />
        <input type="hidden" name="settings[ignore_case]" value="" />
        <?= Form::checkboxList([
            'settings[ignore_case]' => 'Ignore case',
            'dry' => 'Dry run',
        ]) ?>
        <a href="#" class="select-all-none">Select all/none</a>
    </div>

    <table class="main-list main-list-no-js">
        <thead>
            <th style="width: 1px"></th>
            <th>Name</th>
            <th>Count</th>
            <th>Sample</th>
            <th></th>
        </thead>
        <tbody>
        <?php foreach ($results as $result): ?>
            <tr>
                <td><input type="checkbox" name="keys[<?= Enc::html($result['key']) ?>]" checked></td>
                <td><?= Enc::html($result['name']); ?></td>
                <td><?= Enc::html($result['count']); ?></td>
                <td><?= $result['sample']; ?></td>
                <td><a href="<?= Enc::html($result['url']) ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</form>

<script type="text/javascript">
$('.select-all-none').on('click', event => {
    event.preventDefault();

    var all_checked = true;
    var $targets = $(".main-list input[type=checkbox]");

    $targets.each((index, target) => {
        if (!target.checked) {
            all_checked = false;
        }
    });

    $targets.prop('checked', !all_checked);
});
</script>