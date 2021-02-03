$(document).ready(function() {
    var id_idx = 0;

    $.fn.extend({

        /**
        * Creates a multi-edit form
        *
        * @param in_func The function to be called after every item is added.
        *    Used for populating fields.
        *    Receives two parameters, the div (jquery), and the provided data, if any
        *
        * @param initial Initial data for the items which are added.
        *    Should be an array. For each item in the array, a multi-edit item will be created
        *    The function will be called with the array item.
        **/
        'multiedit': function(in_func, initial, options) {
            return this.each(function() {
                var $multi_host = $(this);
                var idx = -1;

                function an(word) {
                    return (word.match(/^[aeiou]/i) ? 'an ' + word : 'a ' + word);
                }

                // Save HTML used for new multiedit items, then wipe it so it doesn't submit POST data
                // Otherwise field names which match those in the parent record could clobber data
                var host_html = $multi_host.html();
                $multi_host.html('');

                // Put in the 'add' button
                $multi_host.css('display', 'none');
                $multi_host.before('<div class="multiedit-container"></div><button type="button" class="multiedit-add button button-regular icon-after icon-add">Add ' + an(options.item_name) + '</button>');
                $multi_host.prev().click(multiedit_add);

                var $container = $multi_host.prev().prev();
                var $add = $multi_host.prev();

                if (typeof(in_func) == 'undefined') in_func = null;

                // Handles the 'add' click - adds an item
                function multiedit_add (e, data, new_records) {
                    idx++;
                    if (typeof(data) == 'undefined') data = null;

                    $container.append('<div class="multiedit-item" style="display:none;"><div class="multiedit-header -clearfix"><div class="widget-header-buttons -clearfix"><button type="button" class="widget-header-button multi-edit-remove-button icon-before icon-close" title="Remove"><span class="-vis-hidden">Remove item</span></button></div></div>' + host_html + '</div>');
                    $div = $container.find('div.multiedit-item:last');

                    $div.slideDown(200)

                    $div.find('.multi-edit-remove-button').click(multiedit_remove);

                    if (options.reorder) {
                        $div.find('.multi-edit-remove-button').before('<button type="button" class="widget-header-button multi-edit-reorder-button icon-before icon-open_with" title="Reorder"><span class="-vis-hidden">Reorder item</span></button>');
                    }

                    if (data !== null) {
                        $.each(options.field_names, function(junk, field_name) {
                            if (typeof(data[field_name]) === 'undefined') return true;

                            $div.find('[name="' + field_name + '"],[name="' + field_name + '[]"],[name="m_' + field_name + '"],[name="m_' + field_name + '[]"]').each(function() {
                                if ($(this).is('[type=radio]')) {
                                    if (data[field_name] == $(this).val()) {
                                        $(this).prop('checked', true);
                                    } else {
                                        $(this).prop('checked', false);
                                    }
                                } else if ($(this).is('[type=checkbox]')) {
                                    // Checkboxes are a little unusual
                                    if (typeof(data[field_name]) !== 'object') {
                                        var valueArr = data[field_name].toString().split(',');
                                    } else {
                                        var valueArr = data[field_name];
                                    }
                                    if ($.inArray($(this).val(), valueArr) === -1) {
                                        $(this).removeAttr('checked');
                                    } else {
                                        $(this).attr('checked', 'checked');
                                    }
                                } else if ($(this).is('span')) {
                                    // Fb::output
                                    $(this).text(data[field_name]);
                                } else {
                                    // Text fields, selects, textareas, etc
                                    $(this).val(data[field_name]);
                                }
                            });
                        });
                    }

                    if (!(data == null || data.error == null || typeof(data.error) == 'undefined' )) {

                        for (i in data.error) {

                            var $error = '<div class="field-error">';
                            $error += '<ul class="field-error__list">';

                            for (var j in data.error[i]) {
                                $error += '<li class="field-error__list__item">' + data.error[i][j] + '</li>';
                            }
                            $error += '    </ul>';
                            $error += '</div>';

                            $div.find('[name=' + i + '],[name=m_' + i + ']', $container).closest(".field-element").addClass("field-element--error");

                            $div.find('[name=' + i + '],[name=m_' + i + ']', $container).parent(".field-input, .field-element__input-set").after($error);

                        }

                        var errorTabTarget = $container.closest(".tab").attr("id");

                        $(".ui-tabs-nav-wrapper li[aria-controls=" + errorTabTarget + "]").addClass("tab--has-error");
                    }

                    // Update id for LABELed elements, and the corresponding for attribute to match
                    // This ensures labels interact with the correct field
                    $div.find('label[for]').each(function() {
                        var id = $(this).attr('for');
                        var $input = $div.find('#' + id);
                        if ($input.length == 0) return true;

                        id = id + '-multiedit-' + id_idx;
                        id_idx++;

                        $(this).attr('for', id);
                        $input.attr('id', id);
                    });

                    $div.find('input[name],select[name],textarea[name]').attr('name', function(index, val) {
                        return val.replace(/(m_)?(\w+)(\[.*\])?/, 'multiedit_' + options.key + '[' + idx + '][$2]$3');
                    });

                    if (e !== null) Fb.initAll($div);
                    if (typeof(new_records) != 'undefined') new_records.push($div[0]);

                    if (in_func != null) {
                        in_func($div, data, idx);
                    }

                    $add.val('Add another ' + options.item_name);

                    // Initialise any file selectors inside new multiedit record
                    if ($.fn.fileselector) $('.fs').fileselector();

                    // Automatically open the file dialogue when there's only a single file input
                    var $button = $div.find('button.popup-button');
                    if (e && !data && $button.length == 1) {
                        if (!$div.find('select, button:not(.popup-button):not([type=hidden]), textarea, iframe').length) $button.click();
                    }

                    return false;
                }

                // Handles the 'remove' click - removes an item
                function multiedit_remove (e) {
                    $(this).closest(".multiedit-item").slideUp(200, function(){
                        $(this).remove()
                    });
                    if ($container.children().length == 0) {
                        $add.val('Add ' + an(options.item_name));
                    }
                    return false;
                }

                if (initial == null || typeof(initial) == 'undefined' || initial.length == 0) {
                    multiedit_add(true, null);
                } else {
                    var new_records = [];
                    for (i in initial) {
                        multiedit_add(null, initial[i], new_records);
                    }
                    Fb.initAll($(new_records));
                }

                if (options.reorder) {
                    $('#multiedit-' + options.key).parent().find('.multiedit-container').sortable({
                        placeholder: 'multi-edit-placeholder',
                        handle: '.multi-edit-reorder-button',
                        cancel: '',
                        axis: 'y',
                        revert: 150,
                        cursor: '-webkit-grabbing',
                        helper: 'clone',
                        start: function(e, ui){
                            placeholderHeight = ui.item.outerHeight();
                            ui.placeholder.height(placeholderHeight+20);
                            $('<div class="content-block-placeholder-animator" data-height="' + placeholderHeight + '"></div>').insertAfter(ui.placeholder);
                        },
                        change: function( event, ui ){
                            ui.placeholder.stop().height(0).animate({
                                height: ui.item.outerHeight()+20
                            }, 300);
                            placeholderAnimatorHeight = parseInt($('.content-block-placeholder-animator').attr('data-height'));
                            $('.content-block-placeholder-animator').stop().height(placeholderAnimatorHeight+20).animate({
                                height: 0
                            }, 300, function(){
                                $(this).remove();

                                placeholderHeight = ui.item.outerHeight();
                                $('<div class="content-block-placeholder-animator" data-height="' + placeholderHeight + '"></div>').insertAfter(ui.placeholder);
                            });
                        },
                        stop: function(e, ui){
                            $('.content-block-placeholder-animator').remove();
                        },
                    });
                }
            });
        }
    });

});
