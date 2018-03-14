<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
?>

<style>
input.two-factor-code { text-align: center; font: 16px monospace; letter-spacing: 1em; padding-left: 1em; }
</style>


<form action="<?= Enc::html($action_url); ?>" method="post" autocomplete="off">
    <input type="hidden" name="redirect" value="<?= Enc::html(@$_GET['redirect']); ?>">

    <?php
    Form::nextFieldDetails('Enter your two factor code', true);
    echo Form::text('code', ['class' => 'two-factor-code', 'size' => '7', 'autofocus' => 'autofocus'], []);
    ?>

    <div class="text-align-right">
        <a href="admin/logout">Cancel</a>
        &nbsp;
        <button type="submit" class="login-button button button-regular button-green">Log in</button>
    </div>
</form>
