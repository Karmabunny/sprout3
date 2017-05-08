<?php
use Sprout\Helpers\Enc;
?>

<div id="mobile-header">
    <div class="container">

        <div class="mobile-logo">
            <a href="ROOT/" onclick="ga('send', 'event', 'Skin', 'LogoClick');"><img class="logo" src="SKIN/images/logo.svg" alt="<?php echo Enc::html(Kohana::config('sprout.site_title')); ?>"></a>
        </div>

        <button type="button" id="mobile-menu-button">Toggle menu</button>
    </div>
</div>