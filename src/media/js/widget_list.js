/**
* A list of widgets
**/
function widget_list(field_name) {
    this.field_name = field_name;
    this.next_widget_id = 0;

    this.embed = 0;

    // Packery
    this.container = $('#wl-' + this.field_name + ' .widgets-sel');


    /**
    * Adds a widget to the list
    **/
    this.add_widget = function (widget_name, english_name, widget_settings, widget_title, widget_key, is_new, is_active) {
        var list = this;

        var wid_id = list.next_widget_id++;
        var field_name = list.field_name;

        var request_data = {
            method: 'POST',
            dataType: 'json',
            data: {
                settings: widget_settings,
                prefix: 'widget_settings_' + widget_key
            }
        };

        // Add empty div immediately, to be replaced upon AJAX return. This allows multiple
        // requests to return out of order without them becoming disordered in the UI
        var html_id = 'widget_' + field_name + '_' + wid_id;
        var $elem_placeholder = $('<div id="' + html_id + '"></div>');
        var $widget_group = $('#wl-' + field_name + ' .widgets-sel');
        $widget_group.append($elem_placeholder);

        $.ajax('admin_ajax/widget_settings/' + encodeURIComponent(widget_name), request_data)
        .done(function(data) {
            if (data.success == 0) {
                alert('Error loading addon settings form: ' + data.message);
                return;
            }

            if (is_new) {
                data.edit_url = null;
                data.info_labels = null;
            }

            var html = '';
            html += '<div class="widget' + (is_active ? ' widget-enabled' : ' widget-disabled content-block-collapsed') + '" ' + (is_active ? "" : 'style="height: 55px;"' ) + ' id="' + html_id + '">';
                html += '<p class="content-block-title">Content block</p>';
                html += '<input type="hidden" name="widgets[' + field_name + '][]" value="' + wid_id + ',' + widget_name + ',' + widget_key + '">';
                html += '<input type="hidden" name="widget_active[' + field_name + '][]" value="' + (is_active ? '1' : '0') + '">';
                html += '<input type="hidden" name="widget_deleted[' + field_name + '][]" value="0">';
                html += '<div class="widget-header -clearfix">';
                    html += '<div class="widget-header--main -clearfix">';
                        html += '<div class="widget-header-buttons -clearfix">';
                            if (is_active) html += '<button type="button" class="widget-header-button content-block-toggle-open-button icon-before icon-keyboard_arrow_up" title="Collapse"><span class="-vis-hidden">Collapse widget block</span></button>';
                            html += '<div class="content-block-settings-wrapper">';
                                html += '<button type="button" class="widget-header-button content-block-settings-button icon-before icon-settings" title="Settings"><span class="-vis-hidden">Content block settings</span></button>';
                                html += '<div class="dropdown-box content-block-settings-dropdown">';
                                    html += '<ul class="content-block-settings-dropdown-list list-style-2">';
                                        html += '<li class="content-block-settings-dropdown-list-item"><button type="button" class="content-block-set-title-button">Set title</button></li>';
                                        html += '<li class="content-block-settings-dropdown-list-item"><button type="button" class="content-block-toggle-active">' + (is_active ? 'Disable' : 'Enable') + '</button></li>';
                                    html += '</ul>';
                                html += '</div>';
                            html += '</div>';
                            html += '<button type="button" class="widget-header-button content-block-reorder-button icon-before icon-open_with" title="Reorder"><span class="-vis-hidden">Reorder content block</span></button>';
                            html += '<button type="button" class="widget-header-button content-block-remove-button icon-before icon-close" title="Remove"><span class="-vis-hidden">Remove content block</span></button>';
                        html += '</div>';
                        html += '<div class="widget-header-text">';
                            html += '<h3 class="widget-header-content-block-title">' + english_name + '</h3>';
                            html += '<p class="widget-header-custom-title">' + widget_title + '</p>';
                            html += '<p class="widget-header-description">' + data.description + '</p>';
                        html += '</div>';
                    html += '</div>';
                    html += '<div class="widget-header-title-input">';
                        html += '<div class="field-element field-element--text">';
                            html += '<div class="field-label">';
                                html += '<label for="content-block-title-' + field_name + '_' + wid_id + '">Content block title</label>';
                                html += '<div class="field-helper">';
                                html += 'This will appear on the page as a heading of the content block';
                            html += '</div>';
                            html += '</div>';
                            html += '<div class="field-input">';
                                html += '<input id="content-block-title-' + field_name + '_' + wid_id + '" class="textbox widget-title-textbox" name="widget_settings[' + field_name + '][' + wid_id + '][widget_title]" type="text" value="' + widget_title + '" placeholder="Enter a title">';
                            html += '</div>';
                        html += '</div>';
                        html += '<button type="button" class="button button-green button-regular widget-title-update-button">Update</button>';
                    html += '</div>';
                html += '</div>';

                html += '<div class="settings">';
                    html += data.settings;
                html += '</div>';

                if (data.edit_url) {
                    html += '<p><a href="' + data.edit_url + '" target="_blank" class="button button-small button-grey icon-after icon-edit">edit content</a></p>';
                }
            html += '</div>';

            var $elem = $(html);

            $elem_placeholder.replaceWith($elem);

            // Nuke the 'empty' message if any widgets have been added
            $('#wl-' + field_name + ' > .widgets-empty').remove();

            // Init any extra FB bits which may be required
            Fb.initAll($elem);

            // Event handler -- remove widget button
            $elem.find('.content-block-remove-button').on('click', function() {
                $('#edit-form').triggerHandler('setDirty');

                var $widget = $(this).closest('div.widget'),
                widgetTitle = $widget.find(".widget-header-content-block-title").html(),
                $undoButton = $('<div class="content-block-button-wrap"><button type="button" class="button button-grey button-regular button-block icon-after icon-delete undo-content-block-button"><span class="button-unhover-state">'+widgetTitle+' removed</span> <span class="button-hover-state">undo</span></button></div>');

                var $deletedHidden = $widget.find('input[name^="widget_deleted["]');

                $undoButton.insertBefore($widget);

                $widget.addClass('content-block-removed').removeClass('content-block-settings-visible').slideUp(300, function(){
                    $widget.hide();
                    $undoButton.click(undoDelete);
                    $deletedHidden.val('1');
                });

                function undoDelete() {
                    $widget.show();
                    $deletedHidden.val('0');

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

                return false;
            });

            // Event handler -- set title widget dropdown
            $elem.find('.content-block-set-title-button').on('click', function() {
                $(this).closest('div.widget').removeClass("content-block-settings-visible").find(".widget-header-title-input").fadeToggle(300).find(".widget-title-textbox").focus();

                return false;
            });

            // Event handler -- set widget active toggle
            $elem.find('.content-block-toggle-active').on('click', function() {
                $(".content-block-settings-visible").removeClass("content-block-settings-visible");

                var $widget = $(this).closest('div.widget');
                var $input = $widget.find('input[name^="widget_active["]');
                if ($input.val() == '1') {
                    $(this).html('Enable');
                    $widget.removeClass("widget-enabled").addClass("widget-disabled");
                    $input.val('0');

                    var collapsedHeight = $widget.find(".widget-header--main").height() + $widget.find(".content-block-title").height() + 33;
                    $widget.attr("data-expanded-height", $widget.outerHeight());
                    $widget.stop().animate({height: collapsedHeight}, 800, "easeInOutCirc", function(){
                        $(this).addClass("content-block-collapsed");
                    });
                } else {
                    $(this).html('Disable');
                    $widget.removeClass("widget-disabled").addClass("widget-enabled");
                    $input.val('1');

                    if(!$widget.closest(".widget-list").hasClass("all-collapsed")){
                        var animateHeight = $widget.attr("data-expanded-height");
                        $widget.removeClass("content-block-collapsed").stop().animate({height: animateHeight}, "easeInOutCirc", function(){
                            $(this).css({"height": ""});
                        });
                    }
                }
                return false;
            });

            // Event handler -- toggle the widget area open or closed
            $elem.find('.content-block-toggle-open-button').on('click', function() {
                var $button = $(this);
                var $widget = $(this).closest('div.widget');

                if ($widget.hasClass('content-block-collapsed')) {
                    // Open
                    $button.removeClass('icon-keyboard_arrow_down').addClass('icon-keyboard_arrow_up').attr('title', 'Collapse').find('.-vis-hidden').html("Collapse content block");
                    var animateHeight = $widget.attr("data-expanded-height");
                    $widget.removeClass("content-block-collapsed").stop().animate({height: animateHeight}, "easeInOutCirc", function(){
                        $(this).css({"height": ""});
                    });
                } else {
                    // Close
                    $button.removeClass('icon-keyboard_arrow_up').addClass('icon-keyboard_arrow_down').attr('title', 'Expand').find('.-vis-hidden').html("Collapse content block");
                    $widget.attr("data-expanded-height", $widget.outerHeight());
                    var collapsedHeight = $widget.find(".widget-header--main").height() + $widget.find(".content-block-title").height() + 33;
                    $widget.stop().animate({height: collapsedHeight}, 800, "easeInOutCirc", function(){
                        $widget.addClass("content-block-collapsed");
                    });
                }
            });

            // Expand content blocks on click of entire block
            $elem.on('click', function(e){
                var $widget = $(this);
                var $button = $widget.find(".content-block-toggle-open-button");

                if($(this).hasClass("content-block-collapsed") && !$(this).hasClass('widget-disabled')){
                    if(!$(e.target).parents(".widget-header-buttons").length) {
                        // Open
                        $button.removeClass('icon-keyboard_arrow_down').addClass('icon-keyboard_arrow_up').attr('title', 'Collapse').find('.-vis-hidden').html("Collapse content block");
                        var animateHeight = $widget.attr("data-expanded-height");
                        $widget.removeClass("content-block-collapsed").stop().animate({height: animateHeight}, "easeInOutCirc", function(){
                            $(this).css({"height": ""});
                        });
                    }
                }
            });

            // Updates widget title
            function updateWidgetTitle($widgetTitleEl) {
                $widgetTitleEl.fadeOut(200);
                newTitle = $widgetTitleEl.find('.widget-title-textbox').val();
                $widgetTitleEl.closest("div.widget").find(".widget-header-custom-title").html(newTitle);
            }

            // Binds update widget title to press of enter key
            $elem.on('keypress', '.widget-title-textbox', function(e) {
                if ( e.keyCode === 13 ) {
                    $widgetTitleEl = $(this).closest(".widget-header-title-input");
                    updateWidgetTitle($widgetTitleEl);
                    e.preventDefault();
                    return false;
                }
            });

            $elem.on('click', '.widget-title-update-button', function(){
                $widgetTitleEl = $(this).closest(".widget-header-title-input");
                updateWidgetTitle($widgetTitleEl);
            });

            // Checks if click is out of bounds of widget settings, and will close dropdown
            function widgetSettingsClick(e){
                if(!$(e.target).parents(".content-block-settings-dropdown").length && !$(e.target).is('.content-block-settings-dropdown') && !$(e.target).is('.content-block-settings-button')) {
                    $("body").off("click", widgetSettingsClick);
                    $(".content-block-settings-visible").removeClass("content-block-settings-visible");
                }
            }

            // Settings button click
            $elem.on('click', '.content-block-settings-button', function(){
                $closestWidget = $(this).closest(".widget");

                var nodeActive = false;
                if($closestWidget.hasClass("content-block-settings-visible")){
                    nodeActive = true;
                }
                $(this).closest(".widgets-sel").find(".content-block-settings-visible").not(this).removeClass("content-block-settings-visible");
                if(nodeActive === true){
                    $closestWidget.removeClass("content-block-settings-visible");
                    $("body").off("click", widgetSettingsClick);
                } else if(nodeActive === false){
                    $closestWidget.addClass("content-block-settings-visible");
                    $("body").on("click", widgetSettingsClick);
                }
            });


        })
        .fail(function(data) {
            alert('Error loading content block settings form; failed to parse JSON response: ' + data.responseText);
        });
    };

    this.generate_random_key = function() {
        var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
        var string_length = 16;

        var randomstring = '';
        for (var i = 0; i < string_length; i++) {
            var rnum = Math.floor(Math.random() * chars.length);
            randomstring += chars.substr(rnum, 1);
        }

        return randomstring;
    };
}

$(document).ready(function() {
    // Sorting for 'selected' side
    $(".widgets-sel").sortable({
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

    $(".add-widget-btn").click(function(){
        $.facebox({
            ajax: "admin_ajax/add_addon/" + $(this).attr('data-area-id') + '/' + $(this).attr('data-field-name')
        });
    });
});

/* Collapse content blocks */
$(document).ready(function() {

    $(".content-block-collapse-button").click(function() {

        $target = $("#" + $(this).attr("data-target"));

        $(this).toggleClass("icon-keyboard_arrow_up icon-keyboard_arrow_down");

        if(!$target.hasClass("all-collapsed")) {

            // Close
            $target.find(".widget:not(.widget-disabled)").each(function(){
                $(this).attr("data-expanded-height", $(this).outerHeight());
                var collapsedHeight = $(this).find(".widget-header--main").height() + $(this).find(".content-block-title").height() + 33;
                $(this).find('.content-block-toggle-open-button').removeClass('icon-keyboard_arrow_up').addClass('icon-keyboard_arrow_down');
                $(this).stop().animate({height: collapsedHeight}, 800, "easeInOutCirc", function(){
                    $(this).addClass("content-block-collapsed");
                });
            });

            $(this).html("Expand all");
            $target.addClass("all-collapsed");

        } else if($target.hasClass("all-collapsed")) {

            // Open
            $target.find(".widget:not(.widget-disabled)").each(function(){
                var animateHeight = $(this).attr("data-expanded-height");
                $(this).find('.content-block-toggle-open-button').removeClass('icon-keyboard_arrow_down').addClass('icon-keyboard_arrow_up');
                $(this).removeClass("content-block-collapsed").stop().animate({height: animateHeight}, "easeInOutCirc", function(){
                    $(this).css({"height": ""});
                });
            });

            $(this).html("Collapse all");
            $target.removeClass("all-collapsed");

        }

    });

});

