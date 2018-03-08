<?php
use Sprout\Helpers\Enc;
use Sprout\Helpers\Fb;
?>

<style>
input.two-factor-code { text-align: center; font: 16px monospace; letter-spacing: 1em; padding-left: 1em; }
</style>


<h3>Step 1 - scan the code</h3>
<div class="white-box" style="text-align: center">
    <p>
        Scan this barcode into your two-factor authentication app:
    </p>
    <p>
        <img src="<?= Enc::html($qr_img); ?>" width="200" height="200">
    </p>
    <p>&nbsp;</p>
    <p>
        You can also enter the secret key directly:
    </p>
    <p><code><?= Enc::html($secret); ?></code></p>
</div>


<h3>Step 2 - verify code</h3>
<form action="<?= $action_url; ?>" method="post">
    <div class="white-box" style="text-align: center">
        <p>
            Enter the generated token, for verification.
        </p>
        <p>
            <?= Fb::text('code', ['class' => 'two-factor-code', 'size' => '7', 'autocomplete' => 'off'], []); ?>
        </p>
        <p>
            <button type="submit" class="button">Verify and enable two-factor auth</button>
        </p>
    </div>
</form>
