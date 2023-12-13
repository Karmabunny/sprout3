<?php

use Sprout\Helpers\AI\AI;
use Sprout\Helpers\Form;
use Sprout\Helpers\MultiEdit;
use Sprout\Helpers\Pdb;

$methods = AI::classesAndMethod();
$heading_opts = array_combine($headings, $headings);

Form::setData($data ?? []);
?>

<h3>AI Content Generation</h3>

<script id="js--ai-methods" type="application/json">
<?php echo json_encode($methods); ?>
</script>

<script>
    function connectAiClasses($div,data,idx) {
        // Update the method dropdown when the class dropdown changes
        var methods = $('#js--ai-methods').html();
        methods = JSON.parse(methods);

        let $classField = $div.find('.ai_class');
        let $methodField = $div.find('.ai_method');

        $classField.on('change', function() {
            var class_name = $(this).val();
            var options = methods[class_name];

            var html = '';
            for (var key in options) {
                html += '<option value="' + key + '">' + options[key] + '</option>';
            }

            $methodField.html(html);
        }).change();
    }
</script>

<p>You may add as much AI generated content as you like. Each entry will be used to generate content for the new record.
    <br>Each simply needs a column in the import for the "prompt" text, then an assigned column in our database to store the generated content.</p>
<p>For each AI content section, all fields are required, or the field will be silently skipped.</p>

<div id="multiedit-ai_fields">
    <div class="columns -clearfix">
        <div class="column column-6">
            <?php
            Form::nextFieldDetails('AI Class', true, 'Which AI system will we use for content creation?');
            echo Form::dropdown('m_ai_class', ['class' => 'ai_class'], AI::AI_CLASSES);
            ?>
        </div>

        <div class="column column-6">
            <?php
            Form::nextFieldDetails('AI Endpoint', true, 'Which tool of this system are we using?');
            echo Form::dropdown('m_ai_method', ['class' => 'ai_method'], []);
            ?>
        </div>
    </div>

    <div class="columns -clearfix">
        <div class="column column-6">
            <?php
            Form::nextFieldDetails('Prompt column', true, 'This is the prompt we will use to generate content');
            echo Form::dropdown('m_prompt_col', [], $heading_opts);
            ?>
        </div>

        <div class="column column-6">
            <?php
            Form::nextFieldDetails('Target column', true, 'This is where we will add AI content to the new record');
            echo Form::dropdown('m_target_col', [], $db_columns);
            ?>
        </div>
    </div>
</div>

<?php
MultiEdit::itemName('AI Content Entry');
MultiEdit::setPostAddJavaScriptFunc('connectAiClasses');
MultiEdit::display('ai_fields', $data['multiedit_ai_fields'] ?? []);
?>

<br>
<h5>Status change after AI processing</h5>
<p>You may update records to become active or inactive after processing.
    <br>Select 'default' to leave them as they are by default post-import, or if this data type does not use an 'active' field.
</p>

<?php
    Form::nextFieldDetails('Change activation status?', true, 'Do you want to change the activation status of the selected records after processing?');
    echo Form::dropdown('ai_activation_status', ['-dropdown-top' => ''], Pdb::extractEnumArr('ai_content_queue', 'activation_status'));
    ?>
</div>
