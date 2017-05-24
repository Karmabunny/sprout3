$(document).ready(function() {
    $("table.main-list").tablesorter();

    $(window).resize(function() {
        $('div.sqlresult').width($('#main-content').width());
    }).resize();
});

$(document).ready(function() {
    var $submit = $('textarea.sql').closest('form').find('.save');

    $('textarea.sql').keypress(function() {
        if ($(this).val().match(/UPDATE|DELETE|REPLACE|ALTER|TRUNCATE|DROP/i)) {
            $submit.addClass('warn');
        } else {
            $submit.removeClass('warn');
        }
    });
});

// Variable replacement new row button
$(document).ready(function(){
    $(".variable-replacement-button-add-row").click(function(){
        var idx = $('.variable-replacement-row').length;
        var $new_row = $('.variable-replacement-row:first-child').clone();

        $new_row.find('.field-input input').attr('value', '');

        $new_row.find('label').first().attr('for', 'variable-' + idx);
        $new_row.find('input').first().attr('id', 'variable-' + idx).attr('name', 'vars[' + idx + '][key]');

        $new_row.find('label').first().attr('for', 'replacement-' + idx);
        $new_row.find('input').last().attr('id', 'replacement-' + idx).attr('name', 'vars[' + idx + '][val]');

        $new_row.find('.field-element').addClass('field-element--hidden-label');
        $new_row.appendTo(".variable-replacement-rows");
    });
});

$(document).ready(function() {
    // Textarea inserting fun
    jQuery.fn.extend({
        insertAtCaret: function(myValue){
            return this.each(function(i) {
                if (document.selection) {
                    //For browsers like Internet Explorer
                    this.focus();
                    var sel = document.selection.createRange();
                    sel.text = myValue;
                    this.focus();

                } else if (this.selectionStart || this.selectionStart == '0') {
                    //For browsers like Firefox and Webkit based
                    var startPos = this.selectionStart;
                    var endPos = this.selectionEnd;
                    var scrollTop = this.scrollTop;
                    this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
                    this.focus();
                    this.selectionStart = startPos + myValue.length;
                    this.selectionEnd = startPos + myValue.length;
                    this.scrollTop = scrollTop;

                } else {
                    this.value += myValue;
                    this.focus();
                }
            });
        }
    });

    var clicks = 0, single_click = null;

    // Double-click on a table -> insert table name
    $('select.table-list').dblclick(function() {
        clicks = 0;
        $('#table-details').hide();
        $('textarea.sql').insertAtCaret($(this).val());
    });

    // Load column list when table clicked
    $('select.table-list').click(function() {
        var db_table = $(this).val();
        db_table = db_table.replace('~', '');

        if (clicks != 0) return;
        clicks = 1;

        window.setTimeout(function() {
            if (clicks != 1) return;

            $.get(ROOT + 'dbtools/ajaxTableDefn/' + encodeURIComponent(db_table), function(data) {
                var $table = $('<table class="table--content-standard table--content-small">'), $tr, $td, $span;
                for (i in data) {
                    if (i == 0) {
                        $tr = $('<tr>');
                        $table.append($tr);
                        for (key in data[i]) {
                            $td = $('<th>');
                            $tr.append($td);
                            $td.text(key);
                        }
                    }
                    $tr = $('<tr>');
                    $table.append($tr);
                    for (key in data[i]) {
                        $td = $('<td>');
                        $tr.append($td);
                        $td.text(data[i][key]);
                    }
                }
                $('#table-details table').remove();
                $table.width($('query-box').width());
                $('#table-details').append($table).show();
                clicks = 0;
            });
        }, 200);
    });

    $('.button-hide-table-preview').click(function() {
        $('#table-details').hide();
    });

    // Refine bar for table list
    $('#table-list-wrap input').keyup(function() {
        var val = $(this).val();
        $('#table-list-wrap option').each(function() {
            if ($(this).val().indexOf(val) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
