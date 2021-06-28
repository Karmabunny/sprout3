<?php
use Sprout\Helpers\Form;
use Sprout\Helpers\Needs;


Needs::fileGroup('underscore');
?>


<script>
$(document).ready(function() {
    window.setTimeout(init, 1);         // Needs to be after TinyMCE has loaded
    function init() {
        var mce = tinymce.get('richtext');
        var $pre = $('.preview');
        function updatePreview() {
            $pre.text(mce.getContent());
        }
        mce.on('change keyup', _.throttle(updatePreview, 250));
        updatePreview();
    }
});
</script>


<div class="-clearfix">
    <div class="col col--one-half">
        <?php
        echo Form::richtext('richtext', []);
        ?>
    </div>
    <div class="col col--one-half">
        <pre class="preview"></pre>
    </div>
</div>
