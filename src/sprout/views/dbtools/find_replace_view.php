<?php
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\FindReplace;
use Sprout\Helpers\Form;

/** @var string $key */
/** @var string[] $finds */
/** @var array[] $found */
?>

<form method="post">
    <?php echo Csrf::token(); ?>

    <input type="hidden" name="keys[<?= Enc::html($key) ?>]" value="1">

    <input type="hidden" name="key" value="<?= Enc::html($key) ?>">
    <input type="hidden" name="find" value="<?= Enc::html($finds[0] ?? '') ?>">

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
    </div>

    <table class="main-list main-list-no-js">
        <thead>
            <th>ID</th>
            <th>Sample</th>
            <th>Count</th>
            <th></th>
        </thead>
        <tbody>
        <?php foreach ($result as $item): ?>
            <tr>
                <td><?= Enc::html($item['id']); ?></td>
                <td><?= FindReplace::getSample($item['text'], $item['indexes'][0]); ?></td>
                <td><?= Enc::html($item['count']); ?></td>
                <?php if ($item['url']): ?>
                    <td><a href="<?= Enc::html($item['url']) ?>">View</a></td>
                <?php else: ?>
                    <td>--</td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</form>
