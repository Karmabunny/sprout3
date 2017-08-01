/**
 * Initialisation for various FB helper methods
 */
var Fb = {
    /**
     * Common options for all instances of the date range picker
     */
    dateRangePickerOptsCommon: {
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'DD/MM/YYYY'
        },
        buttonClasses: "button button-small",
        applyClass: "",
        cancelClass: ""
    },


    lnkform: function($elems)
    {
        $elems.each(function() {
            var $wrapper = $(this);
            var $hidden = $wrapper.find('input[type=hidden]');
            var $select = $wrapper.find('.lnk-type');
            var $div = $wrapper.find('.lnk-form');

            $select.change(function() {
                var opts = {};
                opts.field = '_value';
                opts.type = $select.val();
                opts.val = '';

                if ($hidden.val()) {
                    var obj = JSON.parse($hidden.val());
                    if (obj['class'] == $select.val()) {
                        opts.val = obj['data'];
                    }
                }

                $.post(ROOT + 'admin_ajax/lnk_editor', opts, function(data) {
                    $div.html(data.html);

                    // Need to activate file selector if 'Document' link type chosen
                    Fb.initAll($div);
                }, 'json');
            });

            if ($hidden.val()) {
                var obj = JSON.parse($hidden.val());
                $select.val(obj['class']);
                $select.triggerHandler('change');
            }

            $select.closest('form').submit(function() {
                if ($select.val()) {
                    var obj = { 'class': $select.val(), 'data': $wrapper.find('[name=_value]').val() };
                    $hidden.val(JSON.stringify(obj));
                } else {
                    $hidden.val('');
                }
            });
        });
    },


    /**
     * Init a Fb::datepicker field
     * @param jQuery $elem
     */
    datepicker: function($elems)
    {
        $elems.each(function() {
            var $elem = $(this);
            var $hidden = $elem.parent().find('.fb-hidden');

            (function($elem){
                var opts = $.extend({}, Fb.dateRangePickerOptsCommon, {
                    singleDatePicker: true,
                    showDropdowns: $elem.data('dropdowns') !== undefined
                });
                if ($elem.attr('data-min') !== undefined) {
                    opts.minDate = moment($elem.attr('data-min'));
                }
                if ($elem.attr('data-max') !== undefined) {
                    opts.maxDate = moment($elem.attr('data-max'));
                }
                $elem.daterangepicker(opts);
            })($elem);

            $hidden.on('change', function() {
                var date = moment($hidden.val());
                if (!date.isValid()) {
                    $elem.val('');
                    return;
                }
                $elem.data('daterangepicker').setStartDate(date);
                $elem.data('daterangepicker').setEndDate(date);
                $elem.val(date.format('DD/MM/YYYY'));
            });

            $elem.on('apply.daterangepicker', function(ev, picker) {
                $elem.val(picker.startDate.format('DD/MM/YYYY'));
                $hidden.val(picker.startDate.format('YYYY-MM-DD'));
            });

            $elem.on('cancel.daterangepicker', function(ev, picker) {
                $elem.val('');
                $hidden.val('');
            });

            if ($hidden.val() != '' && $hidden.val() != '0000-00-00') {
                $hidden.triggerHandler('change');
            }
        });
    },


    /**
     * Init a Fb::daterangepicker field
     * @param jQuery $elem
     */
    daterangepicker: function($elems)
    {
        $elems.each(function() {
            var $elem = $(this);
            var $hidden = $elem.parent().find('.fb-hidden');
            var $startHidden = $elem.parent().find('.fb-daterangepicker--start');
            var $endHidden = $elem.parent().find('.fb-daterangepicker--end');

            (function($elem){
                var opts = $.extend({}, Fb.dateRangePickerOptsCommon, {
                    linkedCalendars: true,
                    showDropdowns: $elem.data('dropdowns') !== undefined
                });

                if ($elem.attr('data-dir') == 'future') {
                    opts.ranges = {
                        'Today': [moment(), moment()],
                        'Tomorrow': [moment().add(1, 'days'), moment().add(1, 'days')],
                        'Week': [moment(), moment().add(7, 'days')],
                        'Month': [moment(), moment().add(31, 'days')]
                    }
                } else if ($elem.attr('data-dir') == 'past') {
                    opts.ranges = {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Week': [moment().subtract(7, 'days'), moment()],
                        'Month': [moment().subtract(31, 'days'), moment()]
                    }
                }
                if ($elem.attr('data-min') !== undefined) {
                    opts.minDate = moment($elem.attr('data-min'));
                }
                if ($elem.attr('data-max') !== undefined) {
                    opts.maxDate = moment($elem.attr('data-max'));
                }
                $elem.daterangepicker(opts);
            })($elem);

            $hidden.on('change', function() {
                var startDate = moment($startHidden.val());
                var endDate = moment($endHidden.val());
                $elem.data('daterangepicker').setStartDate(startDate);
                $elem.data('daterangepicker').setEndDate(endDate);
                $elem.val(startDate.format('DD/MM/YYYY') + ' - ' + endDate.format('DD/MM/YYYY'));
            });

            $elem.on('apply.daterangepicker', function(ev, picker) {
                $elem.val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                $startHidden.val(picker.startDate.format('YYYY-MM-DD'));
                $endHidden.val(picker.endDate.format('YYYY-MM-DD'));
            });

            $elem.on('cancel.daterangepicker', function(ev, picker) {
                $elem.val('');
                $hidden.val('');
            });

            if ($hidden.val() != '' && $hidden.val() != '0000-00-00') {
                $hidden.triggerHandler('change');
            }
        });
    },


    /**
     * Init a Fb::datetimerangepicker field
     * @param jQuery $elem
     */
    datetimerangepicker: function($elems)
    {
        $elems.each(function() {
            var $elem = $(this);
            var $hidden = $elem.parent().find('.fb-hidden');
            var $startHidden = $elem.parent().find('.fb-datetimerangepicker--start');
            var $endHidden = $elem.parent().find('.fb-datetimerangepicker--end');

            (function($elem){
                var opts = $.extend({}, Fb.dateRangePickerOptsCommon, {
                    timePicker: true,
                    linkedCalendars: true,
                    showDropdowns: $elem.data('dropdowns') !== undefined
                });

                if ($elem.attr('data-dir') == 'future') {
                    opts.ranges = {
                        'Today': [moment().startOf('day'), moment().endOf('day')],
                        'Tomorrow': [moment().add(1, 'days').startOf('day'), moment().add(1, 'days').endOf('day')],
                        'Week': [moment().startOf('day'), moment().add(7, 'days').endOf('day')],
                        'Month': [moment().startOf('day'), moment().add(31, 'days').endOf('day')]
                    }
                } else if ($elem.attr('data-dir') == 'past') {
                    opts.ranges = {
                        'Today': [moment().startOf('day'), moment().endOf('day')],
                        'Yesterday': [moment().subtract(1, 'days').startOf('day'), moment().subtract(1, 'days').endOf('day')],
                        'Week': [moment().subtract(7, 'days').startOf('day'), moment().endOf('day')],
                        'Month': [moment().subtract(31, 'days').startOf('day'), moment().endOf('day')]
                    }
                }
                if ($elem.attr('data-min') !== undefined) {
                    opts.minDate = moment($elem.attr('data-min'));
                }
                if ($elem.attr('data-max') !== undefined) {
                    opts.maxDate = moment($elem.attr('data-max'));
                }
                if ($elem.attr('data-incr') !== undefined) {
                    opts.timePickerIncrement = parseInt($elem.attr('data-incr'), 10);
                }
                $elem.daterangepicker(opts);
            })($elem);

            $hidden.on('change', function() {
                var startDate = moment($startHidden.val());
                var endDate = moment($endHidden.val());
                $elem.data('daterangepicker').setStartDate(startDate);
                $elem.data('daterangepicker').setEndDate(endDate);
                $elem.val(startDate.format('DD/MM/YYYY [at] h:mm a') + ' - ' + endDate.format('DD/MM/YYYY [at] h:mm a'));
            });

            $elem.on('apply.daterangepicker', function(ev, picker) {
                $elem.val(picker.startDate.format('DD/MM/YYYY [at] h:mm a') + ' - ' + picker.endDate.format('DD/MM/YYYY [at] h:mm a'));
                $startHidden.val(picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
                $endHidden.val(picker.endDate.format('YYYY-MM-DD HH:mm:ss'));
            });

            $elem.on('cancel.daterangepicker', function(ev, picker) {
                $elem.val('');
                $hidden.val('');
            });

            if ($hidden.val() != '' && $hidden.val() != '0000-00-00 00:00:00') {
                $hidden.triggerHandler('change');
            }
        });
    },


    /**
     * Init a Fb::datetimepicker field
     * @param jQuery $elem
     */
    datetimepicker: function($elems)
    {
        $elems.each(function() {
            var $elem = $(this);
            var $hidden = $elem.parent().find('.fb-hidden');

            (function($elem){
                var opts = $.extend({}, Fb.dateRangePickerOptsCommon, {
                    singleDatePicker: true,
                    timePicker: true,
                    showDropdowns: $elem.data('dropdowns') !== undefined
                });
                if ($elem.attr('data-min') !== undefined) {
                    opts.minDate = moment($elem.attr('data-min'));
                }
                if ($elem.attr('data-max') !== undefined) {
                    opts.maxDate = moment($elem.attr('data-max'));
                }
                if ($elem.attr('data-incr') !== undefined) {
                    opts.timePickerIncrement = parseInt($elem.attr('data-incr'), 10);
                }
                $elem.daterangepicker(opts);
            })($elem);

            $hidden.on('change', function() {
                var date = moment($hidden.val());
                $elem.data('daterangepicker').setStartDate(date);
                $elem.data('daterangepicker').setEndDate(date);
                $elem.val(date.format('DD/MM/YYYY [at] h:mm a'));
            });

            $elem.on('apply.daterangepicker', function(ev, picker) {
                $elem.val(picker.startDate.format('DD/MM/YYYY [at] h:mm a'));
                $hidden.val(picker.startDate.format('YYYY-MM-DD HH:mm:ss'));
            });

            $elem.on('cancel.daterangepicker', function(ev, picker) {
                $elem.val('');
                $hidden.val('');
            });

            if ($hidden.val() != '' && $hidden.val() != '0000-00-00 00:00:00') {
                $hidden.triggerHandler('change');
            }
        });
    },


    /**
     * Init a Fb::timepicker field
     * @param jQuery $elems
     */
    timepicker: function($elems)
    {
        $elems.each(function() {
            var $input = $(this);
            var config = $input.data('config');
            fb_timepicker($input, config['min'], config['max'], config['increment']);
        });
    },


    google_map: function($elems)
    {
        if ($elems.length === 0) return;

        // If the Google Maps API is not found, try again in 250ms
        // Up to 20 attempts ~= 5000ms; if this limit is reached, give up.
        if (!window.google || !window.google.maps) {
            if (!window.fbGoogleMapCount) window.fbGoogleMapCount = 0;
            window.fbGoogleMapCount++;

            if (window.fbGoogleMapCount < 20) {
                window.setTimeout(function() {
                    Fb.google_map($elems);
                }, 250);
            } else {
                $elems.find('.fb-google-map--inner').html('Error: Google maps API not loaded');
            }

            return;
        }

        $elems.each(function() {
            var $elem = $(this);
            var $map = $elem.find('.fb-google-map--inner');
            var $lat = $elem.find('input[data-field="lat"]');
            var $lng = $elem.find('input[data-field="lng"]');
            var $zoom = $elem.find('input[data-field="zoom"]');
            var $search = $elem.find('.fb-google-map--search-name');
            var $search_go = $elem.find('.fb-google-map--search-go');
            var map = null;
            var marker = null;
            var init_center = { lat: -34.9290, lng: 138.6010 };
            var init_zoom = 8;

            $map.css('height', $map.width() * 0.4);

            if ($lat.val() && $lng.val()) {
                init_center = { lat: parseFloat($lat.val()), lng: parseFloat($lng.val()) };
                init_zoom = 12;
            }
            if ($zoom.val()) {
                init_zoom = parseInt($zoom.val());
            }

            map = new google.maps.Map($map.get(0), {
                center: init_center,
                zoom: init_zoom,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                scrollwheel: false
            });
            var geocoder = new google.maps.Geocoder();

            marker = new google.maps.Marker({
                position: init_center,
                map: map,
                visible: true,
                draggable: true
            });

            if ($lat.val() && $lng.val()) {
                marker.setVisible(true);
            }

            map.addListener('click', function(e) {
                marker.setPosition(e.latLng);
            });

            marker.addListener('position_changed', function() {
                var pos = this.getPosition();
                $lat.val(pos.lat).trigger('change');
                $lng.val(pos.lng).trigger('change');
                $zoom.val(map.zoom).trigger('change');
            });

            if ($zoom.length) {
                map.addListener('zoom_changed', function() {
                    $zoom.val(this.getZoom()).trigger('change');
                });
            }

            // Geo search button
            $search_go.click(function() {
                geocoder.geocode({'address': $search.val(), "region": "au"}, function(results, status) {
                    if (status === google.maps.GeocoderStatus.OK) {
                        map.setCenter(results[0].geometry.location);
                        map.setZoom(14);
                        marker.setPosition(results[0].geometry.location);
                    } else {
                        alert('Map search failed: ' + status);
                    }
                });
            });

            // Change enter key behaviour so searching doesn't submit the form
            $search.keypress(function(event) {
                var charCode = event.charCode || event.keyCode;
                if (charCode == 13) {
                    window.setTimeout(function(){  $search_go.click();  }, 0);
                    return false;
                }
            });

            // Set the maps to display once the tab is visible, e.g. for multiedits
            var $tab = $map.closest('.tab.ui-tabs-panel');
            $map.closest('.main-tabs').on('clickTab', function(event, tab_id) {
                window.setTimeout(function() {
                    if (tab_id != $tab.attr('id')) return;

                    if ($map.height() == 0) $map.css('height', $map.width() * 0.4);

                    // Deal with 0,0 point which has no data at close zoom
                    if (map.getCenter().lat() == 0 && map.getZoom() > 6) {
                        map.setZoom(6);
                    }

                    google.maps.event.trigger(map, "resize");
                    map.setCenter(marker.getPosition());

                }, 10);
            });
        });
    },

    /**
     * Javascript for the Fb::conditionsList component
     */
    conditions_list: function($elems) {
        $elems.each(function() {
            var $elem = $(this);
            var params = JSON.parse($elem.attr('data-params'));
            var $data = $elem.find('.fb-conditions--data');
            var $list = $elem.find('.fb-conditions--list');
            var $btn = $elem.find('.fb-conditions--add');
            var type_sel_html = createTypeSelectorHTML();
            var collection = [];

            function loadData() {
                collection = JSON.parse($data.val());
                _.each(collection, function(item) {
                    item.$el = renderItem(item);
                    $list.append(item.$el);
                });
            }

            function createTypeSelectorHTML() {
                var html = '<div class="field-element field-element--dropdown">';
                html += '<div class="field-input">';
                html += '<select name="field" class="js--field"><option>';
                _.each(params.fields, function(val, key) {
                    if (_.isObject(val)) {
                        html += '<optgroup label="' + _.escape(key) + '">';
                        _.each(val, function(val, key) {
                            html += '<option value="' + _.escape(key) + '">' + _.escape(val) + '</option>';
                        });
                        html += '</optgroup>';
                    } else {
                        html += '<option value="' + _.escape(key) + '">' + _.escape(val) + '</option>';
                    }
                });
                html += '</select>';
                html += '</div>';
                html += '</div>';
                return html;
            }

            function addItem() {
                var item = { field: '', op: '', val: '' };
                item.$el = renderItem(item);
                collection.push(item);
                $list.append(item.$el);
            }

            function deleteItem(item) {
                item.$el.remove();
                collection = _.without(collection, item);
            }

            function renderItem(item) {
                var html = '<div class="fb-conditions--item -clearfix">';
                html += '<div class="column fb-conditions--field">' + type_sel_html + '</div>';
                html += '<div class="column fb-conditions--op"></div>';
                html += '<div class="column fb-conditions--val"></div>';
                html += '<div class="column fb-conditions--actions">';
                html += '<button class="js--delete button button-grey button-icon icon-before icon-close" type="button"><span class="-vis-hidden">Delete</span></button>';
                html += '</div>';
                html += '</div>';

                var $el = $(html);
                $el.on('click', '.js--delete', _.partial(deleteItem, item));

                $el.on('change', '.js--field', function() {
                    item.field = $(this).val();
                    item.op = '';
                    item.val = '';

                    if (item.field == '') {
                        $el.find('.fb-conditions--op').html('');
                        $el.find('.fb-conditions--val').html('');
                    } else {
                        typeChangeAjaxRequest();
                    }
                });

                function typeChangeAjaxRequest() {
                    $.ajax({
                        url: params.url,
                        type: 'GET',
                        data: _.omit(item, '$el'),
                        dataType: 'json',
                        success: typeChangeAjaxSuccess
                    });
                }

                function typeChangeAjaxSuccess(data) {
                    if (typeof(data.success) !== 'undefined' && data.success == 0) {
                        alert('AJAX Error: ' + data.message);
                        return;
                    }
                    $el.find('.fb-conditions--op').html(data.op);
                    $el.find('.fb-conditions--val').html(data.val);
                    item.op = $el.find('select[name="op"]').val().trim();
                    item.val = $el.find('select[name="val"],input[name="val"]').val().trim();
                }

                $el.on('change', 'select[name="op"]', function() {
                    item.op = $(this).val();
                });
                $el.on('change', 'select[name="val"],input[name="val"]', function() {
                    item.val = $(this).val();
                });

                if (item.field != '') {
                    $el.find('.js--field').val(item.field);
                    typeChangeAjaxRequest();
                }

                return $el;
            }

            function nonSubmitLocalFields() {
                $elem.find('select[name="field"]').attr('name', '');
                $elem.find('select[name="op"]').attr('name', '');
                $elem.find('select[name="val"],input[name="val"]').attr('name', '');
            }

            function saveData() {
                var coll = [];
                _.each(collection, function(i) {
                    if (i.field != '' && i.op != '') {
                        coll.push(_.omit(i, '$el'));
                    }
                });
                $data.val(JSON.stringify(coll));
            }

            loadData();

            $btn.on('click', addItem);
            $elem.closest('form').on('submit', nonSubmitLocalFields);
            $elem.closest('form').on('submit', saveData);
        });
    },

    /**
     * Init a Fb::multipleFileSelect field
     * @param jQuery $elems
     */
    multiple_file_select: function($elems) {
        $elems.each(function() {
            var $elem = $(this);
            var $list = $elem.find('.file-upload__uploads');

            // Enable sorting of the list
            $list.sortable({
                placeholder: 'drag-drop__reorder-placeholder',
                handle: '.drag-drop__file--reorder',
                cancel: '',
                axis: 'y',
                revert: 150,
                cursor: '-webkit-grabbing',
                helper: 'clone',
            });

            // Enable the drag-and-drop
            var opts = $.parseJSON($elem.attr('data-opts'));
            if (!opts.scope_el) {
                opts.scope_el = $(this);
            }
            init_dragdrop(opts);

            // Handle drop completions
            $elem.find('.drag-drop__upload').on('drag-drop-upload.complete', function(e, upload) {
                upload.$elem.append(
                    '<input type="hidden" name="' + $elem.attr('data-name') + '" value="' + upload.file_id + '">'
                );
                upload.$elem.addClass('-clearfix');
                $('#edit-form').triggerHandler('setDirty');
                postAdd(-1, upload.$elem);
            });

            // Handle clicks on the "existing file" link
            $elem.find('.select-existing-file').on('click', function() {
                var type = parseInt($(this).data('filter'), 10);

                // This bound handler will only execute once
                $(document).one('file.selected', function (e, file_id, filename) {
                    var html = '<div class="file-upload__item file-upload__item--existing">'
                        + '<input type="hidden" name="' + $elem.attr('data-name') + '" value="' + file_id + '">'
                        + '<img class="file-upload__item__feedback__existing-image" src="' + SITE + 'file/redirect_resize/m200x133/' + file_id + '">'
                        + '<p class="file-upload__item__feedback__name">' + filename + '</p>'
                        + '</div>';

                    var $file = $(html);
                    $list.append($file);
                    $('#edit-form').triggerHandler('setDirty');
                    $elem.find('.file-upload__uploads').show();
                    postAdd(-1, $file);
                });

                $.facebox({"ajax": SITE + 'admin/call/file/selectorPopup?f_type=' + type + '&upload=0&browse=1'});
                return false;
            });

            // Run the postAdd method for all existing children
            if ($list.children().length > 0) {
                $list.children().each(postAdd);
                $elem.find('.file-upload__uploads').show();
            }

            // Attach remove btn handler
            $list.on('click', '.file-upload__item__remove', function() {
                $(this).closest('.file-upload__item').remove();
            });

            /**
             * This is called after each DIV is added to the list
             */
            function postAdd(index, el) {
                var $elem = $(el);
                $elem.append(
                    '<button type="button" class="file-upload__item__remove"><span class="file-upload__item__remove__text">Remove</span></button>'
                );
                $elem.append(
                    '<button type="button" class="file-upload__item__reorder drag-drop__file--reorder"><span class="file-upload__item__remove__text">Reorder</span></button>'
                );
                $list.sortable('refresh');
            }
        });
    },

    /**
     * Init a Fb::chunkedUpload field
     * @param jQuery $elems
     */
    chunked_upload: function($elems) {
        $elems.each(function() {
            var opts = $(this).data('opts');
            opts.scope_el = $(this);
            init_dragdrop(opts);
        });
    },

    /**
     * Init an Fb::fileSelector field
     * @param jQuery $elems
     * @param array filename_lookup_ids File IDs which need to be sent via AJAX to look up the filenames
     */
    file_selector: function($elems, filename_lookup_ids) {
        $elems.each(function() {
            var $host = $(this);

            // Prevent double initialization
            if ($host.data('init')) return true;

            var filter = $host.attr('data-filter');
            if (filter == '') return true;

            // button click
            $('button.popup-button', $host).click(function() {
                // This bound handler will only execute once
                $(document).one('file.selected', function (e, file_id, filename) {
                    $host.addClass("fs-file-selected");
                    $('.fs-hidden', $host).val(file_id);
                    $('.fs-filename', $host).html(filename);
                    $('.fs-preview', $host).html('<img src="' + SITE + 'file/redirect_resize/c50x50/' + file_id + '">');
                    $('#edit-form').triggerHandler('setDirty');
                });

                $.facebox({"ajax": SITE + 'admin/call/file/selectorPopup?f_type=' + filter});
                return false;
            });

            // Filename and preview
            if ($('.fs-hidden', $host).val() != '') {
                var filename = $host.data('filename');
                if (filename) {
                    $('.fs-filename', $host).html(filename);
                } else {
                    filename_lookup_ids.push($('.fs-hidden', $host).val());
                }

                $('.fs-preview', $host).html('<img src="' + SITE + 'file/redirect_resize/c50x50/' + $('.fs-hidden', $host).val() + '">');
                $host.addClass('fs-file-selected');
            }

            // remove click
            $('.fs-remove', $host).click(function() {
                $host.removeClass("fs-file-selected");
                $('.fs-hidden', $host).val('');
                $('.fs-filename', $host).html('No file selected');
                $('.fs-preview', $host).children("img").remove();
                return false;
            });

            $host.data('init', true);
        });
    },


    /**
     * Update a file selector field with a file name pulled from AJAX (when the field is only aware of the file id)
     *
     * @param jQuery $elems
     * @param array filenames Map of id => filename, returned by AJAX
     */
    file_selector_filenames_loaded: function($elems, filenames) {
        $elems.each(function() {
            var $host = $(this);

            // No need to insert file name if it's already there
            if ($host.data('filename')) {
                return;
            }

            var file_id = $('.fs-hidden', $host).val();
            var filename = 'Unknown file: ' + file_id;

            if (filenames[file_id]) {
                filename = filenames[file_id];
                $host.data('filename', filename);
            }

            $('.fs-filename', $host).html(filename);
        });
    },


    /**
     * Init a Fb::autocomplete field
     * N.B. jQuery UI MUST be loaded to use this function
     */
    autocomplete: function($elems) {
        $elems.each(function() {
            var $id_input;
            var $value_input = $(this);
            var url = $value_input.data('lookup-url');
            var min_length = Math.floor(Number($value_input.data('chars')));

            if ($value_input.data('save-id') == '1') {
                var val = $value_input.val();
                var name = $value_input.attr('name');
                $value_input.attr('name', name.replace(/(\]?)$/, '_lookup$1'));
                $id_input = $('<input type="hidden">');
                $id_input.attr('id', $value_input.attr('id') + '_id');
                $id_input.attr('name', name);
                $id_input.val(val);
                $value_input.after($id_input);

                if (val) {
                    $value_input.val('');
                    $.get(url, {'id': val}, function(data) {
                        if (data[0] && data[0].value) {
                            $value_input.val(data[0].value);
                        } else if (data[0] && data[0].label) {
                            $value_input.val(data[0].label);
                        }
                    });
                }
            }

            $value_input.autocomplete({
                source: url,
                minLength: min_length,
                select: function(event, ui) {
                    if ($value_input.data('save-id') == '1' && ui.item.id) {
                        $id_input.val(ui.item.id);
                    }
                },
                change: function (event, ui) {
                    if ($value_input.data('save-id') == '1' && ui.item == null) {
                        $id_input.val('');
                    }
                }
            });
            if (min_length == 0) {
                $value_input.focus(function() {
                    $(this).autocomplete('search', '');
                });
                $value_input.click(function() {
                    $(this).autocomplete('search', '');
                });
            }
        });
    },

    /**
     * Init everything which has standard class names
     */
    initAll: function($root)
    {
        var filename_lookup_ids = [];

        Fb.lnkform($root.find('.lnk-wrap'));
        Fb.datepicker($root.find('.fb-datepicker'));
        Fb.daterangepicker($root.find('.fb-daterangepicker'));
        Fb.datetimerangepicker($root.find('.fb-datetimerangepicker'));
        Fb.datetimepicker($root.find('.fb-datetimepicker'));
        Fb.timepicker($root.find('.fb-timepicker'));
        Fb.google_map($root.find('.fb-google-map'));
        Fb.conditions_list($root.find('.fb-conditions-list'));
        Fb.multiple_file_select($root.find('.fb-multiple-file-select'));
        Fb.chunked_upload($root.find('.fb-chunked-upload'));
        Fb.file_selector($root.find('.fb-file-selector'), filename_lookup_ids);
        Fb.autocomplete($root.find('input.autocomplete, textarea.autocomplete'));

        if (filename_lookup_ids.length > 0) {
            $.post('file/name_lookup', {ids: filename_lookup_ids.join(',')}, function(filenames) {
                Fb.file_selector_filenames_loaded($root.find('.fb-file-selector'), filenames);
            });
        }
    }
};

$(document).ready(function(){
    Fb.initAll($('body'));

    // Prevent autocomplete width from overflowing
    if (jQuery.ui) {
        jQuery.ui.autocomplete.prototype._resizeMenu = function() {
            var ul = this.menu.element;
            ul.outerWidth(this.element.outerWidth());
        }
    }
});
