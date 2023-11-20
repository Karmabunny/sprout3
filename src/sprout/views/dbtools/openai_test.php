<?php

use Sprout\Helpers\Csrf;
use Sprout\Helpers\Form;
use Sprout\Helpers\AI\OpenAiApi;

?>

<div>
    <form method="POST" action="dbtools/openAiTestSubmit" target="tester">
        <?php echo Csrf::token(); ?>

        <?php Form::nextFieldDetails('AI Endpoint', true); ?>
        <?php echo Form::dropdown('endpoint',[],  OpenAiApi::ENDPOINTS); ?>

        <?php Form::nextFieldDetails('Input prompt', true, 'NOTE: This is a single prompt, not threaded'); ?>
        <?php echo Form::multiline('prompt', []); ?>

        <button type="submit" class="button no-disable js--ai-submit">Get OpenAI response</button>
    </form>
</div>

<p>&nbsp;</p>
<iframe style="width: 100%; height: 1050px" name="tester"></iframe>
