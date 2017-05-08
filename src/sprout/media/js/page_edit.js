function approval_operator_status() {
    var val = $('select[name="status"]').val();

    if (val == 'need_approval') {
        $('#opts-need-check.save-option').addClass("save-option-visible");
    } else {
        $('#opts-need-check.save-option').removeClass("save-option-visible");
    }

    if (val == 'auto_launch') {
        $('#opts-autolaunch.save-option').addClass("save-option-visible");
    } else {
        $('#opts-autolaunch.save-option').removeClass("save-option-visible");
    }
}

$(document).ready(function() {
    // Approval operator field
    $('select[name="status"]').change(approval_operator_status);
    approval_operator_status();

    // Admin permissions
    $('#edit-form input[name=admin_perm_specific]').change(function() {
        if ($('input[name=admin_perm_specific]:checked').val() == '1') {
            $('.admin_perms input').removeAttr('disabled');
        } else {
            $('.admin_perms input').attr('disabled', 'disabled');
        }
    }).change();

    // User permissions
    $('#edit-form input[name=user_perm_specific]').change(function() {
        if ($('input[name=user_perm_specific]:checked').val() == '1') {
            $('.user_perms input').removeAttr('disabled');
        } else {
            $('.user_perms input').attr('disabled', 'disabled');
        }
    }).change();

    // Entrance controller change - load new items using ajax
    $('#edit-form select[name=controller_entrance]').change(function() {
        if ($(this).val() == '') return;

        $.getJSON(SITE + 'admin_ajax/get_entrance_arguments/' + encodeURIComponent($(this).val()), function(data) {
            var html = '';
            var i;

            if (data.success == 0) {
                alert(data.message);
            } else {
                for (i in data) {
                    html += '<option value="' + i + '">' + data[i] + '</option>';
                }
            }

            $('#edit-form select[name=controller_argument]').html(html);
        });
    });

    // Menu groups
    $('select[name=parent_id]').change(function() {
        var parent_id = $(this).val();

        if ($(this).val() == '') {
            $('select[name=menu_group]').html('<option style="font-style: italic;" value="">None -- Top level page</option>');
            return;
        }

        $.getJSON(SITE + 'admin/call/page/ajaxGetMenuGroups/' + $(this).val(), function(data) {
            if (data.success == 0) {
                alert(data.message);
                return;
            }

            var html = '<option style="font-style: italic;" value="">None -- Don\'t show in menu</option>';
            var i;
            for (i in data.groups) {
                html += '<option value="' + i + '">' + data.groups[i] + '</option>';
            }

            var previous = $('select[name=menu_group]').val();
            $('select[name=menu_group]').html(html).val(previous);
        });
    }).change();

    // Widgets
    $('.widget-list-new-widget').click(function() {
        $widget_list_div = $(this).closest('.widget-list');

        // Close the giant popup by unticking an invisible checkbox. Crazy!
        $widget_list_div.find('.giant-popup-checkbox').click();

        // Add new widget to appropriate widget list
        $widget_list_div.trigger('add-widget', [$(this).attr('data-name'), $(this).text()])
    });
});
