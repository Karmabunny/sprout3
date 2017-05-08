/**
* Performs a search
**/
function doSearch() {
    $.getJSON($('#file-selector-search').attr('action'), $('#file-selector-search').serialize(), function(data) {
        var row, html;

        var stats = data[0];
        data[0] = null;

        if (stats.num_results == 1) {
            $('#file-selector-stats').html('1 file found');
        } else {
            $('#file-selector-stats').html(stats.num_results + ' files found');
        }

        html = '<table class="file-select-table">';
        for (idx in data) {
            row = data[idx];
            if (row == null) continue;

            html += '<tr>';
            if (row.preview) html += '<td class="file-select-table-thumbnail">' + row.preview + '</td>';
            html += '<td class="file-select-table-text"><a href="javascript:;" data-id="' + row.id + '" data-filename="' + row.filename + '" data-preview-large="' + row.preview_large + '" class="item">' + row.name + '</a></td>';
            html += '</tr>';
        }
        html += '</table>';

        $('#file-selector-results').html(html);

        $('.file-selector-search-wrapper').addClass("searched");
        if(stats.num_results == 0) {
            $('.file-selector-search-wrapper').addClass("no-results");
        } else {
            $('.file-selector-search-wrapper').removeClass("no-results");
        }

        $('#file-selector-results tr').click(function() {
            $(this).find('a').click();
        });

        $('#file-selector-results tr').hover(function() {
            var url = $(this).find('a').attr('data-preview-large');
            if (url) {
                $('#file-selector-preview .preview-box').html('<img src="' + ROOT + url + '">');
            }
        }, function() {
            $('#file-selector-preview .preview-box').html('');
        });

        $('#file-selector-results .item').click(function() {
            $(document).trigger('file.selected', [ $(this).attr('data-id'), $(this).attr('data-filename') ]);
            $(document).trigger('close.facebox');
            return false;
        });

        html = '';
        if (stats.curr_page > 0) {
            html += '<button type="button" class="button button-grey button-small icon-before icon-keyboard_arrow_left" id="file-selector-prev">Prev</button>';
        }
        if (stats.curr_page < (stats.num_pages - 1)) {
            html += '<button type="button" class="button button-grey button-small icon-after icon-keyboard_arrow_right" id="file-selector-next">Next</button>';
        }
        $('#file-selector-paginate').html(html);


        $('#file-selector-prev').click(function() {
            $('#file-selector-page').val(stats.curr_page - 1);
            doSearch();
            return false;
        });

        $('#file-selector-next').click(function() {
            $('#file-selector-page').val(stats.curr_page + 1);
            doSearch();
            return false;
        });

        $('#file-selector-result-wrap').show();
    });
}

function init_fileselector_popup() {
    $('#file-selector-search').submit(function() {
        $('#file-selector-page').val(0);
        doSearch();
        return false;
    });

    // N.B need to manually init dragdrop because this HTML doesn't exist at load time
    var opts = $('#file-selector-upload').find('.fb-chunked-upload').data('opts');
    if (opts) {
        opts['scope_el'] = '#file-selector-upload .fb-chunked-upload';
        init_dragdrop(opts);
    }

    $('#quick-upload').on("load", function() {
        var f_type = $('#quick-upload').attr('data-type');
        var nfo = $('#quick-upload').contents().find('div').text();
        if (nfo == '') return;

        nfo = JSON.parse(nfo);

        if (typeof(nfo.error) != 'undefined') {
            alert(nfo.error);
            return;
        }

        if (f_type && nfo.type != f_type) {
            alert('Your file has been uploaded successfully, but is the wrong type for this field.');
            return;
        }

        $(document).trigger('file.selected', [ nfo.id, nfo.filename ]);
        $(document).trigger('close.facebox');
    });

    $('#file-selector-result-wrap').hide();

    $('#file-selector-upload select[name="category_id"]').change(function() {
        if ($(this).val() == '_new') {
            $("#file-selector-upload").addClass("new-category");
            $('#file-selector-upload input[name="category_new"]').select();
        }
    }).change();
}