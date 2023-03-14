<style>
.custom-tag-row .field-element {
    max-width: 250px;
    display: inline-block;
    padding-right: 20px;
}
.custom-tag-row .custom-tag-div-inline {
    display: inline-block;
    max-width: 50%
}
.custom-tag-row .custom-tag-values {
    border-top: 1px solid #e5e5e5;
    padding-top: 14px;
}
.custom-tag-row h5 {
    margin-top: 0px;
}


</style>

<script type="application/json" id="custom-tags-opts">
    <?php echo json_encode($available_tags); ?>
</script>

<script type="application/json" id="custom-tags-curr">
    <?php echo json_encode($current_tags); ?>
</script>


<div id="custom-tags">
    <div id="custom-tag-row" style="display: none">
        <div class="multiedit-header -clearfix">
            <h5 style="float: left">Tag elements</h5>
            <div class="widget-header-buttons -clearfix">
                <button type="button" class="widget-header-button multi-edit-remove-button icon-before icon-close" title="Remove"><span class="-vis-hidden">Remove item</span></button>
            </div>
        </div>
        <div class="custom-tag-tag-type custom-tag-div-inline">
            <input type="hidden" name="custom_tags[][id]" value="">
        </div>

        <div class="custom-tag-tags custom-tag-div-inline">
        </div>

        <div class="custom-tag-attrs custom-tag-div-inline">
        </div>

        <div class="custom-tag-values" style="display: none">
        </div>
    </div>
</div>

<button id="js-add-row" class="button button-regular icon-after icon-add" type="button">Add a custom head tag</button>

<script>
    $(document).ready(function() {
        const available_tags = JSON.parse($('#custom-tags-opts').html());
        const current_tags = JSON.parse($('#custom-tags-curr').html());

        var $row;
        var $tagTypeDiv;
        var $tagDiv;
        var $attrDiv;
        var $valuesDiv;
        var rowIdx;

        const addRow = function() {
            const $row = $('#custom-tag-row').clone();
            const tagIdx = $('.custom-tag-row').length + 1;

            $row.removeAttr('id');
            $row.addClass('custom-tag-row');
            $row.addClass('multiedit-item');
            $row.attr('data-idx', tagIdx);

            $row.find('select').val('');
            $row.appendTo('#custom-tags');

            const tags = available_tags;

            const $select = $('<select name="custom_tags[' + tagIdx + '][tag_type]"></select>');
            $select.append('<option value="">Select a tag</option>');
            $select.addClass('dropdown');

            //iterate the tags object and add an option for each object key
            for (const tag of Object.keys(tags)) {
                $select.append(`<option value="${tag}">${tag}</option>`);
            }

            $tagTypeDiv = $row.find('.custom-tag-tag-type');

            $select.on('change', tagTypeChange);
            addField($select, $tagTypeDiv, 'Head Tag');

            $row.show();

            const $removeBtn = $row.find('.multi-edit-remove-button');
            $removeBtn.on('click', removeRow);
        };

        const removeRow = function() {
            const $row = $(this).closest('.custom-tag-row');
            $row.remove();
        };

        const addField = function($fieldElem, $targetElem, label) {
            const $inputDiv = $('<div></div>');
            $inputDiv.addClass('field-element field-element--dropdown field-element--white field-element--small');

            const $labelDiv = $('<div></div>');
            $labelDiv.addClass('field-label');
            $labelDiv.append(`<label>${label}</label>`);
            $inputDiv.append($labelDiv);

            const $fieldDiv = $('<div></div>');
            $fieldDiv.addClass('field-input');

            $fieldDiv.append($fieldElem);
            $inputDiv.append($fieldDiv);
            $targetElem.append($inputDiv);
        };

        const allocateDivs = function($elem)
        {
            $row = $elem.closest('.custom-tag-row');
            rowIdx = $row.attr('data-idx');

            $tagTypeDiv = $row.find('.custom-tag-tag-type');
            $tagDiv = $row.find('.custom-tag-tags');
            $attrDiv = $row.find('.custom-tag-attrs');
            $valuesDiv = $row.find('.custom-tag-values');
        }

        const tagTypeChange = function() {
            allocateDivs($(this));
            const tag_type = $(this).val();

            if (tag_type === '') {
                $tagDiv.html('');
                return;
            }

            // Remove an existing tags dropdown to rebuild it
            $tagDiv.html('');

            // Remove any existing attributes dropdown to rebuild it
            $attrDiv.html('');
            $valuesDiv.html('').hide();
            const tags = available_tags[tag_type];

            const $select = $('<select name="custom_tags[' + rowIdx + '][tag]"></select>');
            $select.append('<option value="">Select a tag</option>');
            $select.addClass('dropdown');

            //iterate the tags object and add an option for each object key
            for (const tag of Object.keys(tags)) {
                $select.append(`<option value="${tag}">${tag}</option>`);
            }

            $select.on('change', tagChange);
            addField($select, $tagDiv, 'Type');
        };

        const tagChange = function() {
            allocateDivs($(this));

            // Remove any existing attributes dropdown to rebuild it
            $attrDiv.html('');
            $valuesDiv.html('').hide();

            const tag = $(this).val();
            const tag_type = $row.find('select[name="custom_tags[' + rowIdx + '][tag_type]"]').val();

            if (tag === '') {
                $attrDiv.html('');
                return;
            }

            const tags = available_tags[tag_type];
            const attrs = tags[tag];

            const $select = $('<select name="custom_tags[' + rowIdx + '][attribute]"></select>');
            $select.append('<option value="">Select an attribute</option>');
            $select.addClass('dropdown');

            //iterate the tags object and add an option for each object key
            for (const tag of Object.keys(attrs)) {
                $select.append(`<option value="${tag}">${tag}</option>`);
            }

            $select.on('change', attrChange);
            addField($select, $attrDiv, 'Attribute');

        };

        const attrChange = function() {
            allocateDivs($(this));

            // Remove any existing attributes dropdown to rebuild it
            $valuesDiv.html('').hide();

            const attr = $(this).val();
            const tag_type = $row.find('select[name="custom_tags[' + rowIdx + '][tag_type]"]').val();
            const tag= $row.find('select[name="custom_tags[' + rowIdx + '][tag]"]').val();

            if (tag === '') {
                return;
            }

            const tags = available_tags[tag_type];
            const attrs = available_tags[tag_type][tag][attr];

            $valuesDiv.html('<h5>Values</h5>');
            addAttrFields(attrs);
        };

        // Note this expects allocateFields to have been called first
        const addAttrFields = function(attrs) {
            // Iterate each attr in attrs and add a select for each
            attrs.forEach(function(attr) {
                const $input = $('<input type="text" name="custom_tags[' + rowIdx + '][attr_values][' + attr + ']" />');
                $input.addClass('textbox');
                addField($input, $valuesDiv, attr);
            });

            $valuesDiv.show();
        }

        const pageLoadExisting = function () {
            // Add a row for each existing tag on page load
            for (const tag of current_tags) {
                addRow();
                const tagIdx = $('.custom-tag-row').length;
                const $row = $('#custom-tags').find('.custom-tag-row').last();

                $row.find('select[name="custom_tags[' + tagIdx + '][tag_type]"]').val(tag.tag_type).change();
                $row.find('select[name="custom_tags[' + tagIdx + '][tag]"]').val(tag.tag).change();
                $row.find('select[name="custom_tags[' + tagIdx + '][attribute]"]').val(tag.attribute).change();

                for (const field in tag.attr_values) {
                    $row.find('input[name="custom_tags[' + tagIdx + '][attr_values][' + field + ']"]').val(tag.attr_values[field]);
                }
            }
        }

        pageLoadExisting();

        $('#js-add-row').on('click', addRow);

    });
</script>
