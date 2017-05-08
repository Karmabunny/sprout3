function init_dragdrop(options) {
    var $scope;
    if (typeof(options.scope_el) === 'undefined') {
        $scope = $('body');
    } else {
        $scope = $(options.scope_el);
    }
    var $dropbox = $scope.find('.file-upload__area');
    var $progress = $scope.find('.file-upload__progress-circle');
    var $upload_fields = $scope.find('.file-upload__input');

    var dropbox = $dropbox.get(0);
    var queue = [];
    var timeout = 0;
    var max_size = 500 * 1024 * 1024;
    var uploading = false;


    if (dropbox) {
        dropbox.addEventListener("dragenter", dragenter, false);
        dropbox.addEventListener("dragover", dragover, false);
        dropbox.addEventListener("drop", drop, false);
        dropbox.addEventListener("dragleave", dragleave, false);
    }

    $dropbox.click(function (e) {
        if(!$(e.target).is(".file-upload__item") && !$(e.target).parents(".file-upload__item").length) {
            $upload_fields.trigger('click');
        }
    });

    $upload_fields.each(function() {
        this.addEventListener("change", change, false);


        // Remove button is normally created and activated by the upload, but needs to
        // be ready at init time if it already exists (e.g. due to form redirect)
        $(this).closest('.fb-chunked-upload').find('.file-upload__item').each(function() {
            var $button = $('<button class="file-upload__item__remove" type="button"><span class="file-upload__item__remove__text">Remove</span></button>');

            $(this).children().first().before($button);

            $button.click(function() {
                var $wrapper = $(this).closest('.fb-chunked-upload');
                var code = $(this).closest('.file-upload__item').data('code');

                // Delete uploaded file
                $.ajax({
                    type: 'POST',
                    url: options.cancel_url,
                    data: {
                        result: {
                            tmp_file: $wrapper.find('.file-upload__data input.temp[data-code="' + code + '"]').val()
                        }
                    }
                });

                // Remove file details from DOM
                remove_file_ref($(this).closest('.file-upload__item'));

                // Mark file deleted
                $wrapper.find('.js-delete-notify').val(1);

                return false;
            });
        });
    });

    function dragenter(e) {
        $dropbox.addClass('file-upload__area--dragenter');
        e.stopPropagation();
        e.preventDefault();
    }

    function dragover(e) {
        $dropbox.addClass('file-upload__area--dragenter');
        e.stopPropagation();
        e.preventDefault();
    }

    function dragleave(e) {
        $dropbox.removeClass('file-upload__area--dragenter');
        e.stopPropagation();
        e.preventDefault();
    }

    /**
     * Handle a drop
     */
    function drop(e) {
        var dt, i, num, qd;

        var $uploadArea = $dropbox.find('.file-upload__uploads');

        $dropbox.removeClass('file-upload__area--dragenter');

        e.stopPropagation();
        e.preventDefault();
        dt = e.dataTransfer;

        var max_files = options.max_files;

        var $all_uploads = $uploadArea.find('.file-upload__item');
        while ($all_uploads.length && $all_uploads.length + dt.files.length > max_files) {
            remove_file_ref($all_uploads.first(), false);
            $all_uploads = $uploadArea.find('.file-upload__item');
        }

        for (i = 0, num = dt.files.length; i < num && i < max_files; i++) {
            queue_file(dt.files[i], $uploadArea);
        }

        if (!uploading) do_upload();
    }


    /**
     * Handle a file selection for a normal 'browse' button
     */
    function change(e) {
        var dt, i, num, qd;
        var $uploadArea = $(this).closest('.fb-chunked-upload').find('.file-upload__uploads');

        $(this).closest('.fb-chunked-upload').find(".file-upload__helptext").removeClass("file-upload__helptext--hidden");

        dt = e.target;

        var max_files = options.max_files;

        var $all_uploads = $uploadArea.find('.file-upload__item');
        while ($all_uploads.length && $all_uploads.length + dt.files.length > max_files) {
            remove_file_ref($all_uploads.first(), false);
            $all_uploads = $uploadArea.find('.file-upload__item');
        }

        for (i = 0, num = dt.files.length; i < num && i < max_files; i++) {
            queue_file(dt.files[i], $uploadArea);
        }

        $(this).hide();

        // There's no way to clear a file input in IE11, so get around this by adding a wrapper form,
        // resetting it, then removing it. Fun for all!
        $(this).wrap('<form>').closest('form').get(0).reset();
        $(this).unwrap();

        if (!uploading) do_upload();
    }


    /**
     * Queue an upload of a file.
     */
    function queue_file(file, $uploadArea_arg) {

        var $elem;
        if (!$uploadArea_arg) $uploadArea_arg = $uploadArea;

        if (file.size == 0) {
            $elem = $('<b>' + file.name + '</b> &nbsp; Unable to upload; file size empty.');
            $uploadArea_arg.append($elem);
            return;
        } else if (file.size > max_size) {
            $elem = $('<b>' + file.name + '</b> &nbsp; File is larger than the max of ' + human_size(max_size));
            $uploadArea_arg.append($elem);
            return;
        }

        $uploadArea_arg.closest('.fb-chunked-upload').find(".file-upload__helptext").addClass("file-upload__helptext--hidden");

        $elem = $(
            '<div class="file-upload__item file-upload__item--queued" title="' + $('<div/>').text(file.name).html() + ' (' + human_size(file.size) + ')">'

            + '<button class="file-upload__item__remove" type="button"><span class="file-upload__item__remove__text">Remove</span></button>'

            + '<div class="file-upload__progress-circle">'
                + '<span class="file-upload__progress-circle__amount">0</span><span class="file-upload__progress-circle__symbol">%</span>'
                + '<svg class="file-upload__progress-circle__pie" width="56" height="56">'
                    + '<circle class="file-upload__progress-circle__pie__outer" r="28" cx="28" cy="28" fill="none" stroke="#d7dce0" stroke-width="9"></circle>'
                    + '<circle class="file-upload__progress-circle__pie__piece" r="28" cx="28" cy="28" fill="none" stroke="#35ab75" stroke-width="9" stroke-dasharray="0 176"></circle>'
                + '</svg>'
            + '</div>'
            + '<div class="file-upload__item__feedback"></div>'

            + '</div>'
        );

        $elem.find('.file-upload__item__remove').click(function() {
            remove_file_ref($elem);
        });

        $uploadArea_arg.append($elem);

        queue.push({ file: file, $elem: $elem });
    }

    /**
     * Upload the top file on the queue.
     */
    function do_upload() {
        uploading = true;

        var upload = queue.shift();
        if (!upload) {
            uploading = false;
            return;
        }

        var code = generate_code();
        var chunkSize = Math.ceil(upload.file.size / 100.0);
        if (chunkSize < 64 * 1024) {
            chunkSize = 64 * 1024;
        }
        if (chunkSize > 1024 * 1024) {
            chunkSize = 1024 * 1024;
        }
        var totalSize = upload.file.size;
        var numChunks = Math.ceil(totalSize / chunkSize);
        var maxChunkErrors = Math.ceil(numChunks / 10);

        var xhr = new XMLHttpRequest();

        // Use a queue to allow errors to be re-queued easily
        var chunkQueue = [];
        for (var i = 0; i < numChunks; i++) {
            chunkQueue.push(i);
        }

        upload.$elem.removeClass('file-upload__item--queued');
        upload.$elem.addClass('file-upload__item--uploading');

        var num_failures = 0;

        // Request an upload session
        function beginUpload() {
            if (options.begin_url == undefined || options.begin_url == '') {
                // Only form builder requires this operation
                window.setTimeout(uploadChunk, 0);
                return;
            }

            var fd = new FormData();
            fd.append('code', code);
            fd.append('form_id', options.form_params.form_id);
            fd.append('field_name', options.form_params.field_name);
            xhr.open("POST", ROOT + options.begin_url, true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== XMLHttpRequest.DONE) return;
                var response;

                try {
                    response = $.parseJSON(xhr.responseText);
                } catch (e) {
                    response = null;
                }

                if (xhr.status != 200 || response === null || response.success == 0) {
                    window.setTimeout(postUploadChunk, 0);
                    return;
                }

                window.setTimeout(uploadChunk, 0);
            };

            xhr.send(fd);
        }

        // Upload a chunk
        function uploadChunk() {
            var index = chunkQueue.shift()
            if (typeof(index) === 'undefined') {
                window.setTimeout(postUploadChunk, 0);
                return;
            }

            // If upload cancelled, stop sending chunks and delete sent chunks
            if (!upload.$elem.is(':visible')) {
                $.post(ROOT + options.cancel_url, {
                    partial_upload: {
                        code: code
                    }
                });

                window.clearTimeout(timeout);
                timeout = window.setTimeout(do_upload, 750);
                return;
            }

            var subBlob = upload.file.slice(chunkSize * index, (chunkSize * index) + chunkSize);

            var fd = new FormData();
            fd.append('chunk', subBlob);
            fd.append('index', index);
            fd.append('code', code);

            if (options.form_params !== undefined) {
                fd.append('form_id', options.form_params.form_id);
                fd.append('field_name', options.form_params.field_name);
            }

            xhr.open("POST", ROOT + options.chunk_url, true);
            xhr.onreadystatechange = function() {
                handle_chunk_response(index);
            };
            xhr.send(fd);
        }

        // Handle AJAX response
        function handle_chunk_response(index) {
            if (xhr.readyState !== XMLHttpRequest.DONE) return;
            var response;

            try {
                response = $.parseJSON(xhr.responseText);
            } catch (e) {
                response = null;
            }

            // If the request failed, just re-queue the offending index (unless too many failures, then die)
            if (xhr.status != 200 || response === null || response.success == 0) {
                chunkQueue.push(index);
                num_failures++;
                if (num_failures == maxChunkErrors) {
                    window.setTimeout(postUploadChunk, 0);
                    return;
                }
            } else {
                var percentage = Math.round((numChunks - chunkQueue.length) / numChunks * 100);
                var stroke = (percentage*176) / 100;

                upload.$elem.find('.file-upload__progress-circle__amount').html(percentage);
                upload.$elem.find(".file-upload__progress-circle__pie__piece").attr("stroke-dasharray", stroke + " 176");
            }

            // Fire off the next chunk in the queue
            window.setTimeout(uploadChunk, 0);
        }

        // All processing is complete, do some post processing
        function postUploadChunk() {
            if (num_failures == maxChunkErrors) {
                var response;

                try {
                    response = $.parseJSON(xhr.responseText);
                } catch (e) {
                    response = { message: xhr.status + ': ' + xhr.statusText };
                }

                upload.$elem.find('.file-upload__progress-circle__amount').text('File uploading failed - ' + response.message);

            } else {
                post_upload(upload, numChunks, code);
            }

            window.clearTimeout(timeout);
            timeout = window.setTimeout(do_upload, 750);
        }

        // Begin!
        beginUpload();
    }


    /**
     * Called after the upload is complete
     */
    function post_upload(upload, numChunks, code) {
        var xhr = new XMLHttpRequest();

        var fd = new FormData();
        fd.append('num', numChunks);
        fd.append('code', code);

        if (options.form_params !== undefined) {
            fd.append('form_id', options.form_params.form_id);
            fd.append('field_name', options.form_params.field_name);
        }

        xhr.open("POST", ROOT + options.done_url, false);
        xhr.send(fd);

        if (xhr.status != 200) {
            upload.$elem.find('.file-upload__progress-circle').remove();

            var uploadError = '<div class="file-upload__item__feedback__response file-upload__item__feedback__response--error">'
                + '<p class="file-upload__item__feedback__error__text">Upload failed<br>' + xhr.status + ' - ' + xhr.statusText + '</p>'
                + '</div>';

            upload.$elem.find('.file-upload__item__feedback').html(uploadError);

            upload.$elem.find('.field-upload__item').addClass("file-upload__item--completed");

            return;
        }

        var result = JSON.parse(xhr.responseText);
        if (result.success == 0) {

            upload.$elem.find('.file-upload__progress-circle').remove();

            var uploadError = '<div class="file-upload__item__feedback__response file-upload__item__feedback__response--error">'
                + '<p class="file-upload__item__feedback__error__text">Upload failed<br>' + result.message + '</p>'
                + '</div>';

            upload.$elem.find('.file-upload__item__feedback').html(uploadError);

            upload.$elem.find('.field-upload__item').addClass("file-upload__item--completed");

            return;

        }

        var $uploader = upload.$elem.closest('.fb-chunked-upload');
        var $field_element = $uploader.find('.file-upload__input');
        var $upload_data = $uploader.find('.file-upload__data');

        var $original = $('<input class="original" type="hidden">');
        $original.val(upload.file.name);
        $original.attr('name', options.form_params.field_name + '[]');
        $original.attr('data-code', code);
        $upload_data.append($original);

        var $temp = $('<input class="temp" type="hidden">');
        $temp.val(result.tmp_file);
        $temp.attr('name', options.form_params.field_name + '_temp[]');
        $temp.attr('data-code', code);
        $upload_data.append($temp);

        var form = {};
        if (options.form_el) {
            var serialized = $(options.form_el).find('select,input,textarea').serializeArray();
            $.each(serialized, function(nil, row) {
                form[row.name] = row.value;
            });
        }

        var opts = {
            params: options.form_params,
            form: form,
            file: {
                name: upload.file.name,
                size: upload.file.size,
                type: upload.file.type,
                lastModifiedDate: upload.file.lastModifiedDate
            },
            result: result
        };

        if (options.form_params !== undefined) {
            opts['form_id'] = options.form_params.form_id;
            opts['field_name'] = options.form_params.field_name;
        }

        // Cancel sends AJAX request with same data as original form URL
        function cancel_click_handler($elem) {
            // Delete uploaded file
            $.ajax({
                type: 'POST',
                url: options.cancel_url,
                data: opts
            });

            // Remove file details from DOM
            remove_file_ref(upload.$elem);
        }

        $.get(options.form_url, opts, function(html) {
            upload.$elem.find(".file-upload__progress-circle").remove();
            upload.$elem.find(".file-upload__item__feedback").html(html);
            upload.$elem.removeClass('file-upload__item--uploading');

            upload.$elem.attr('data-code', code);

            upload.$elem.find('form').submit(form_submit_handler);
            upload.$elem.find('.file-upload__item__remove').click(cancel_click_handler);

            if (typeof Fb !== 'undefined') {
                Fb.initAll(upload.$elem);
            }
        });
    }


    /**
     * Handles submits of the upload forms, to save the result using AJAX.
     */
    function form_submit_handler() {
        var $form = $(this);
        var $elem = $(this).closest('.file-upload__item');

        $elem.addClass('file-upload__item--completed');
        $elem.html(
            '<div class="file-upload__item__feedback__response file-upload__item__feedback__response--success file-upload__item__feedback__response--success--not-image">'
            + '<p class="file-upload__item__feedback__name">Saving file...</p>'
            + '</div>'
        );

        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: $form.serialize(),
            dataType: 'json',
            success: function(data) {
                if (data.success == 0) {
                    alert(data.message);
                } else {
                    if (typeof(data.html) === 'undefined') data.html = 'File saved.';

                    $elem.html(data.html);

                    data.$elem = $elem;
                    $dropbox.triggerHandler('drag-drop-upload.complete', [data]);
                }
            },
            error: function(xhr, error, httpStatus) {
                alert('Unable to save file: ' + (httpStatus || error));
            }
        });

        return false;
    }


    /**
     * Make a random code
     */
    function generate_code() {
        var text = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

        for (var i = 0; i < 32; i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        }

        return text;
    }


    /**
     * Calculates the size of a file in bytes, kb, mb, etc
     */
    function human_size(size) {
        var types = [' bytes', ' kb', ' mb', ' gb', ' tb'];

        var type = 0;
        while (size > 1024) {
            size /= 1024;
            type++;
            if (type > 5) break;
        }

        return (Math.round(size * 10) / 10) + types[type];
    }


    /**
     * Remove info about an uploaded file (or one in the process of being uploaded) from the DOM
     * @param jQuery $elem The element to remove
     * @param bool fade Whether to slowly fade the element out; true by default
     */
    function remove_file_ref($elem, fade) {
        if (fade === undefined) fade = true;
        var code = $elem.attr('data-code');

        if (code) {
            $elem.closest('.fb-chunked-upload').find('.file-upload__data input').each(function() {
                if ($(this).data('code') == code) {
                    $(this).remove();
                }
            });
        }

        if (fade) {
            $elem.fadeOut(150,function(){
                if(!$(this).siblings(".file-upload__item").length) {
                    $(this).closest(".file-upload__area").find(".file-upload__helptext").removeClass("file-upload__helptext--hidden");
                }
                $(this).remove();
            });
        } else {
            $elem.remove();
        }
    }
}
