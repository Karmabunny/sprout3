/**
* A list of widgets
**/
function widget_list(field_name) {
    var $list = $('#wl-' + field_name);
    var $listInner = $list.find('.widgets-sel');
    var list = this;
    $list.data('wl', this);

    this.field_name = field_name;

    // Common index counter across all widget lists
    if (typeof(widget_list.next_widget_id) === 'undefined') {
        widget_list.next_widget_id = 0;
    }


    function onAjaxFailure(data) {
        alert('Error loading content block settings form; failed to parse JSON response: ' + data.responseText);
    }


    /**
    * Adds a widget to the list
    *
    * @param add_opts Object with keys:
    *     type       string    Class name, e.g. 'RichText'
    *     label      string    Label shown to user, e.g. 'Formatted text',
    *     settings   string    Opaque JSON string passed to backend
    *     conditions string    Opaque JSON string
    *     active     bool      True if widget is active, false if it's disabled
    *     Heading    string    HTML H2 rendered on front-end with widget
    **/
    this.add_widget = function(add_opts) {
        var wid_id = widget_list.next_widget_id++;
        var field_name = list.field_name;

        // Add empty div immediately, to be replaced upon AJAX return. This allows multiple
        // requests to return out of order without them becoming disordered in the UI
        var html_id = 'widget_' + field_name + '_' + wid_id;
        var $widget_placeholder = $('<div id="' + html_id + '"></div>');
        $listInner.append($widget_placeholder);

        $.ajax({
            url: 'admin_ajax/widget_settings/' + encodeURIComponent(add_opts.type),
            method: 'POST',
            dataType: 'json',
            data: {
                settings: add_opts.settings,
                prefix: 'widget_settings_' + wid_id
            },
            success: onAjaxSuccess,
            error: onAjaxFailure
        });

        /**
         * AJAX success callback - create widget div and inject onto the page
         */
        function onAjaxSuccess(data) {
            if (data.success == 0) {
                alert('Error loading addon settings form: ' + data.message);
                return;
            }

            var html = '';
            html += '<div class="widget' + (add_opts.active ? ' widget-enabled' : ' widget-disabled content-block-collapsed') + '" id="' + html_id + '">';
            html += '<input type="hidden" name="widgets[' + field_name + '][]" value="' + wid_id + ',' + add_opts.type + '">';
            html += '<input type="hidden" name="widget_active[' + field_name + '][' + wid_id + ']" value="' + (add_opts.active ? '1' : '0') + '">';
            html += '<input type="hidden" name="widget_deleted[' + field_name + '][' + wid_id + ']" value="0">';
            html += '<input type="hidden" name="widget_conds[' + field_name + '][' + wid_id + ']" value="' + _.escape(add_opts.conditions) + '" class="js--widget-conds">';
            html += '<input type="hidden" name="widget_heading[' + field_name + '][' + wid_id + ']" value="' + _.escape(add_opts.heading) + '" class="js--widget-heading">';

            // Wrapper around header
            html += '<p class="content-block-title">Content block</p>';
            html += '<div class="widget-header -clearfix">';
            html += '<div class="widget-header--main -clearfix">';

            // Right: Buttons includeing dropdown menu
            html += '<div class="widget-header-buttons -clearfix">';
            html += '<button type="button" class="widget-header-button content-block-toggle-open-button icon-before icon-keyboard_arrow_up" title="Collapse"><span class="-vis-hidden">Collapse widget block</span></button>';
            html += '<div class="content-block-settings-wrapper">';
            html += '<button type="button" class="widget-header-button content-block-settings-button icon-before icon-settings" title="Settings"><span class="-vis-hidden">Content block settings</span></button>';
            html += '<div class="dropdown-box content-block-settings-dropdown">';
            html += '<ul class="content-block-settings-dropdown-list list-style-2">';
            html += '<li class="content-block-settings-dropdown-list-item"><button type="button" class="content-block-toggle-active">' + (add_opts.active ? 'Disable' : 'Enable') + '</button></li>';
            html += '<li class="content-block-settings-dropdown-list-item"><button type="button" class="content-block-disp-conds">Context engine</button></li>';
            html += '<li class="content-block-settings-dropdown-list-item"><button type="button" class="content-block-edit-heading">Add/edit heading</button></li>';
            html += '</ul>';
            html += '</div>';
            html += '</div>';
            html += '<button type="button" class="widget-header-button content-block-reorder-button icon-before icon-open_with" title="Reorder"><span class="-vis-hidden">Reorder content block</span></button>';
            html += '<button type="button" class="widget-header-button content-block-remove-button icon-before icon-close" title="Remove"><span class="-vis-hidden">Remove content block</span></button>';
            html += '</div>';

            // Left: Type and description
            html += '<div class="widget-header-text">';
            html += '<h3 class="widget-header-content-block-title">' + add_opts.label + '<div class="widget-status-labels"></div></h3>';
            html += '<p class="widget-header-description">' + data.description + '</p>';
            html += '</div>';

            // End of header
            html += '</div>';
            html += '</div>';

            html += '<div class="settings">';
            html += data.settings;
            html += '</div>';

            if (data.edit_url) {
                html += '<p><a href="' + data.edit_url + '" target="_blank" class="button button-small button-grey icon-after icon-edit">edit content</a></p>';
            }
            html += '</div>';
            delete add_opts.settings;

            // Create element; inject into the page
            var $widget = $(html);
            $widget_placeholder.replaceWith($widget);

            // Nuke the 'empty' message if any widgets have been added
            $list.find('.widgets-empty').remove();

            // Init any extra FB bits which may be required
            Fb.initAll($widget);

            // Disabled widgets get collapsed
            if ($widget.is('.widget-disabled')) {
                list.uiDisableWidget($widget);
                list.uiCollapseWidget($widget, 0);
            }

            // Update UI if there are "context engine" conditions
            if (add_opts.conditions != '') {
                list.uiConditions($widget);
            }

            // Event handler -- remove widget button
            $widget.find('.content-block-remove-button').on('click', function() {
                $('#edit-form').triggerHandler('setDirty');

                list.deleteWidget($widget);

                return false;
            });

            // Event handler -- set widget active toggle
            $widget.find('.content-block-toggle-active').on('click', function() {
                $('#edit-form').triggerHandler('setDirty');

                // Hide cog menu
                $(".content-block-settings-visible").removeClass("content-block-settings-visible");

                var $input = $widget.find('input[name^="widget_active["]');

                if ($input.val() == '1') {
                    $input.val('0');
                    list.uiDisableWidget($widget);
                    list.uiCollapseWidget($widget, 800);
                } else {
                    $input.val('1');
                    list.uiEnableWidget($widget);
                    list.uiExpandWidget($widget, 800);
                }
                return false;
            });

            $widget.find('.content-block-disp-conds').on('click', function() {
                // Hide cog menu
                $(".content-block-settings-visible").removeClass("content-block-settings-visible");

                var $conds_hidden = $widget.find('input.js--widget-conds');

                // Load the conditions UI form via ajax
                $.post(
                    'admin_ajax/widget_disp_conds',
                    { 'conds': $conds_hidden.val() },
                    handleAjaxFormLoad
                );

                // Response has come back; load and bind
                function handleAjaxFormLoad(html) {
                    var $popup = $(html);
                    Fb.initAll($popup);
                    $popup.on('click', '.js--cancel', onCancel);
                    $popup.on('submit', '.js--widget-conds-form', onSubmit);
                    $.facebox($popup);
                }

                // Click on the cancel button
                function onCancel() {
                    $(document).trigger('close.facebox');
                }

                // Click on submit button - push value through to hidden field
                function onSubmit() {
                    $('#edit-form').triggerHandler('setDirty');
                    var conds_json = $('.js--widget-conds-form input[name="conds"]').val();
                    $conds_hidden.val(conds_json);
                    list.uiConditions($widget);
                    $(document).trigger('close.facebox');
                }
            });

            // Event handler -- edit widget heading
            $widget.find('.content-block-edit-heading').on('click', function() {
                var id = $widget.attr('id');
                var heading = $widget.find('.js--widget-heading').eq(0).val() || '';

                var html = '<div class="field-element field-element--text"><div class="field-label">';
                html += '<label for="' + id + '--field-element-heading">Content block heading</label></div><div class="field-input">';
                html += '<input id="' + id + '--field-element-heading" class="textbox" type="text" name="heading" value="' + heading + '"></div></div>';
                html += '<div class="-clearfix"><button class="save-changes-save-button button button-green icon-after icon-save" type="submit">Save changes</button></div>';

                var $popup = $(html);
                $popup.on('click', '.save-changes-save-button', function() {
                    $widget.find('.js--widget-heading').eq(0).val($popup.find('input[name="heading"]').eq(0).val());
                    $(document).trigger('close.facebox');
                });

                $.facebox($popup);
            });

            // Event handler -- toggle the widget area open or closed
            $widget.find('.content-block-toggle-open-button').on('click', function() {
                if ($widget.hasClass('content-block-collapsed')) {
                    list.uiExpandWidget($widget, 800);
                } else {
                    list.uiCollapseWidget($widget, 800);
                }
            });

            // Expand collapsed content blocks when they're clicked
            $widget.on('click', function(e){
                if($(this).hasClass("content-block-collapsed") && !$(this).hasClass('widget-disabled')){
                    $widget.find('.content-block-toggle-open-button').triggerHandler('click');
                }
            });

            // Settings (cog) menu button click -- toggle the menu
            $widget.on('click', '.content-block-settings-button', function(){
                var nodeActive = false;
                if($widget.hasClass("content-block-settings-visible")){
                    nodeActive = true;
                }

                $listInner.find(".content-block-settings-visible").not(this).removeClass("content-block-settings-visible");

                if(nodeActive === true){
                    $widget.removeClass("content-block-settings-visible");
                    $("body").off("click", widgetSettingsClick);
                } else if(nodeActive === false){
                    $widget.addClass("content-block-settings-visible");
                    $("body").on("click", widgetSettingsClick);
                }
            });

            // Checks if click is out of bounds of settings (cog) menu, and close dropdown
            function widgetSettingsClick(e){
                if(!$(e.target).parents(".content-block-settings-dropdown").length && !$(e.target).is('.content-block-settings-dropdown') && !$(e.target).is('.content-block-settings-button')) {
                    $("body").off("click", widgetSettingsClick);
                    $(".content-block-settings-visible").removeClass("content-block-settings-visible");
                }
            }

        }  // onAjaxSuccess
    };

    /**
     * UI changes for disabling a widget; doesn't change hidden field
     */
    this.uiDisableWidget = function($widget) {
        $widget.find('.content-block-toggle-active').html('Enable');
        $widget.removeClass("widget-enabled").addClass("widget-disabled");
        $widget.find('.content-block-toggle-open-button').hide();
        $widget.find('.widget-status-labels').append('<span data-type="disabled">Disabled</span>');
    }

    /**
     * UI changes for enabling a widget; doesn't change hidden field
     */
    this.uiEnableWidget = function($widget) {
        $widget.find('.content-block-toggle-active').html('Disable');
        $widget.removeClass("widget-disabled").addClass("widget-enabled");
        $widget.find('.content-block-toggle-open-button').show();
        $widget.find('.widget-status-labels span[data-type="disabled"]').remove();
    }

    /**
     * UI changes for collapsing a widget
     */
    this.uiCollapseWidget = function($widget, time) {
        var $button = $widget.find('.content-block-toggle-open-button');
        $button.removeClass('icon-keyboard_arrow_up').addClass('icon-keyboard_arrow_down');
        $button.attr('title', 'Expand').find('.-vis-hidden').html("Collapse content block");

        var collapsedHeight = $widget.find(".widget-header--main").height() + $widget.find(".content-block-title").height() + 33;
        $widget.attr("data-expanded-height", $widget.outerHeight());
        $widget.stop().animate({height: collapsedHeight}, time, "easeInOutCirc", function(){
            $(this).addClass("content-block-collapsed");
        });
    };

    /**
     * UI changes for expanding a widget
     */
    this.uiExpandWidget = function($widget, time) {
        var $button = $widget.find('.content-block-toggle-open-button');
        $button.removeClass('icon-keyboard_arrow_down').addClass('icon-keyboard_arrow_up');
        $button.attr('title', 'Collapse').find('.-vis-hidden').html("Collapse content block");

        var animateHeight = $widget.attr("data-expanded-height");
        $widget.removeClass("content-block-collapsed").stop().animate({height: animateHeight}, time, "easeInOutCirc", function(){
            $(this).css({"height": ""});
        });
    }

    /**
     * Collapse all widgets
     */
    this.uiCollapseAll = function(time) {
        $list.find(".widget:not(.widget-disabled)").each(function(){
            list.uiCollapseWidget($(this), time);
        });
    }

    /**
     * Expand all widgets
     */
    this.uiExpandAll = function(time) {
        $list.find(".widget:not(.widget-disabled)").each(function(){
            list.uiExpandWidget($(this), time);
        });
    }

    /**
     * Mark a widget for deletion - updates the UI and also the hidden field
     */
    this.deleteWidget = function($widget) {
        var $deletedHidden = $widget.find('input[name^="widget_deleted["]');
        $deletedHidden.val('1');

        var widgetTitle = $widget.find(".widget-header-content-block-title").html();
        var $undoButton = $(
            '<div class="content-block-button-wrap">'
            + '<button type="button" class="button button-grey button-regular button-block icon-after icon-delete undo-content-block-button">'
            + '<span class="button-unhover-state">' + widgetTitle + ' removed</span>'
            + '<span class="button-hover-state">undo</span>'
            + '</button>'
            + '</div>'
        );
        $undoButton.on('click', function() {
            list.undoDeleteWidget($widget, $undoButton);
        });
        $undoButton.insertBefore($widget);

        $widget.addClass('content-block-removed').removeClass('content-block-settings-visible').slideUp(300, function(){
            $widget.hide();
        });
    }

    /**
     * Undo a widget deletion - updates the UI and also the hidden field
     */
    this.undoDeleteWidget = function($widget, $undoButton) {
        var $deletedHidden = $widget.find('input[name^="widget_deleted["]');
        $deletedHidden.val('0');

        $widget.show();

        if ($widget.closest('.widget-list').hasClass('all-collapsed')) {
            $widget.addClass('content-block-collapsed');
        } else {
            $widget.removeClass('content-block-collapsed');
        }

        $widget.css({'height': ''});
        $widget.slideDown(300, function(){
            $widget.removeClass('content-block-removed');
            $undoButton.remove();
        });
    }

    /**
     * Show or hide the conditions indicator
     */
    this.uiConditions = function($widget) {
        var $hidden = $widget.find('input.js--widget-conds');
        var $label = $widget.find('.widget-status-labels span[data-type="conds"]');

        if ($hidden.val() == '' || $hidden.val() == '[]') {
            $label.remove();
        } else if ($label.length == 0) {
            $label = $('<span data-type="conds">Has context rules</span>');
            $label.on('click', function() {
                $widget.find('.content-block-disp-conds').trigger('click');
            });
            $widget.find('.widget-status-labels').append($label);
        }
    }

    // Sorting for widgets
    $listInner.sortable({
        placeholder: 'content-block-placeholder',
        handle: '.content-block-reorder-button',
        cancel: '',
        axis: "y",
        revert: 150,
        cursor: "-webkit-grabbing",
        helper: "clone",
        start: function(e, ui){
            // Disable TinyMCE because it breaks when being dragged
            var $richtext = ui.helper.find('.field-element--richtext');
            if ($richtext.length) {
                var mce_id = $richtext.find('textarea').first().attr('id');
                tinyMCE.execCommand('mceRemoveEditor', false, mce_id);
            }

            placeholderHeight = ui.item.outerHeight();
            ui.placeholder.height(placeholderHeight+25);
            $('<div class="content-block-placeholder-animator" data-height="' + placeholderHeight + '"></div>').insertAfter(ui.placeholder);
        },
        change: function( event, ui ){
            ui.placeholder.stop().height(0).animate({
                height: ui.item.outerHeight()+25
            }, 300);
            placeholderAnimatorHeight = parseInt($(".content-block-placeholder-animator").attr("data-height"));
            $(".content-block-placeholder-animator").stop().height(placeholderAnimatorHeight+25).animate({
                height: 0
            }, 300, function(){
                $(this).remove();

                placeholderHeight = ui.item.outerHeight();
                $('<div class="content-block-placeholder-animator" data-height="' + placeholderHeight + '"></div>').insertAfter(ui.placeholder);
            });
        },
        stop: function(e, ui){
            // Re-enable TinyMCE because it breaks when being dragged
            var $richtext = ui.item.find('.field-element--richtext');
            if ($richtext.length) {
                var mce_id = $richtext.find('textarea').first().attr('id');
                tinyMCE.execCommand('mceAddEditor', false, mce_id);
            }

            $(".content-block-placeholder-animator").remove();

            $('#edit-form').triggerHandler('setDirty');
        },
    });
};


/**
 * Expand/collapse all button
 * Just calls uiExpandAll/uiCollapseAll to do the work
 */
$(document).ready(function() {
    $('.content-block-collapse-button').on('click', function() {
        var $btn = $(this);
        var $list = $("#" + $btn.attr("data-target"));
        var widget_list = $list.data('wl');

        $btn.toggleClass('icon-keyboard_arrow_up icon-keyboard_arrow_down');

        if ($list.hasClass('all-collapsed')) {
            // Already collapsed; expand widgets
            widget_list.uiExpandAll(800);
            $btn.html('Collapse all');
            $list.removeClass('all-collapsed');
        } else {
            // Already expanded; collapse widgets
            widget_list.uiCollapseAll(800);
            $btn.html("Expand all");
            $list.addClass('all-collapsed');
        }
    });
});
