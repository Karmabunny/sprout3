<?php

use Sprout\Helpers\AI\AI;
use Sprout\Helpers\Enc;
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
        let $srcField = $div.find('.ai_source_select');
        let $srcDataFields = $div.find('.ai_data_source');

        $classField.on('change', function() {
            var class_name = $(this).val();
            var options = methods[class_name];

            var html = '';
            for (var key in options) {
                html += '<option value="' + key + '">' + options[key] + '</option>';
            }

            $methodField.html(html);
        }).change();

        $srcField.on('change', function() {
            var src = $(this).val();
            $srcDataFields.hide();
            $srcDataFields.filter('[data-src="' + src + '"]').show();
        }).change();

        $('body').on('click', '.js--insert-db-col', function(e){
            console.log($(this));
            console.log($(e.target));
            var col = $(this).attr('data-col');
            var $textarea = $div.find('.ai_data_source[data-src="manual"] textarea');
            var text = $textarea.val();
            text += ' {{ ' + col + ' }}';
            $textarea.val(text).focus();
            return false;
        });
    }
</script>

<p>You may add as much AI generated content as you like. Each entry will be used to generate content for the new record.
    <br>Each simply needs a column in the import for the "prompt" text, then an assigned column in our database to store the generated content.</p>
<p>For each AI content section, all fields are required, or the field will be silently skipped.</p>

<div id="multiedit-ai_fields">
    <div class="columns -clearfix">
        <div class="column column-6">
            <?php
            Form::nextFieldDetails('AI class', true, 'Which AI system will we use for content creation?');
            echo Form::dropdown('m_ai_class', ['class' => 'ai_class'], AI::AI_CLASSES);
            ?>
        </div>

        <div class="column column-6">
            <?php
            Form::nextFieldDetails('AI endpoint', true, 'Which tool of this system are we using?');
            echo Form::dropdown('m_ai_method', ['class' => 'ai_method'], []);
            ?>
        </div>
    </div>

    <div class="columns -clearfix">

        <div class="column column-6">
            <?php
            Form::nextFieldDetails('Data source', true, 'Where are we getting the prompt from?');
            echo Form::dropdown('m_prompt_source', ['class' => 'ai_source_select'], ['db_col' => 'Database column', 'manual' => 'Build manually']);
            ?>
        </div>

        <div class="column column-6">
            <?php
            Form::nextFieldDetails('Target column', true, 'This is where we will add AI content to the new record');
            echo Form::dropdown('m_target_col', [], $db_columns);
            ?>
        </div>
    </div>

    <div class="columns -clearfix ai_data_source" data-src="db_col">
        <div class="column column-6">
            <?php
            Form::nextFieldDetails('Prompt column', true, 'This is the prompt we will use to generate content');
            echo Form::dropdown('m_prompt_col', [], $heading_opts);
            ?>
        </div>
    </div>

    <div class="columns -clearfix ai_data_source" data-src="manual">
        <div class="column column-6">
            <?php
            Form::nextFieldDetails('Prompt text', true, 'This is the prompt we will use to generate content');
            echo Form::multiline('m_prompt_text', ['rows' => 10]);
            ?>
        </div>
        <div class="column column-6">
            <h3>Database field options</h3>
            <ul class="ai_db_cols">
                <?php foreach ($db_columns as $col_name => $col_label): ?>
                    <li><a href="javascript:;" class="js--insert-db-col" data-col="<?php echo Enc::html($col_name); ?>"><?php echo Enc::html($col_label); ?></a></li>
                <?php endforeach; ?>
            </ul>
            <p>Click to use the value inside your prompt</p>
            <hr>
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
