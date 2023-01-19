<?php
use Sprout\Helpers\Enc;
?>

<script>
grecaptcha.ready(function()
{
    grecaptcha.execute('<?= Enc::js($key); ?>',{action: 'login'}).then(function(token)
    {
        document.getElementById('g-recaptcha-response').setAttribute('value', token);
    });
});
</script>
<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="">
