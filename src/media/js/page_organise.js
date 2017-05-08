$(document).ready(function() {
    $.jstree.defaults.contextmenu.show_at_node = false;
    $.jstree.defaults.contextmenu.items = function(obj) {
        var ref = $.jstree.reference('#jstree_demo_div');
        var opts = {};

        var single = (ref.get_selected().length == 1);
        var contains = {};
        $.each(ref.get_selected(), function(key, val) {
            contains[ref.get_type(val)] = true;
        });

        if (single) {
            opts.rename = {
                label: 'Rename page',
                action: function (data) {
                    var inst = $.jstree.reference(data.reference),
                        obj = inst.get_node(data.reference);
                    inst.edit(obj);
                }
            };
        }

        if (admin_auth.is_remote) {
            if (contains.default) {
                opts.delete = {
                    label: (single ? 'Delete page' : 'Delete pages'),
                    action: function (data) {
                        var inst = $.jstree.reference(data.reference);
                        inst.set_type(inst.get_selected(), 'deleted')
                        updateDeleteList();
                    }
                };
            }
            if (contains.deleted) {
                opts.undelete = {
                    label: (single ? 'Undo delete' : 'Undo delete'),
                    action: function (data) {
                        var inst = $.jstree.reference(data.reference);
                        inst.set_type(inst.get_selected(), 'default')
                        updateDeleteList();
                    }
                };
            }
        }

        return opts;
    };
    $.jstree.defaults.types = {
        'default': { icon: 'icon-insert_drive_file-default' },
        'deleted': { icon: 'icon-insert_drive_file-deleted' }
    };

    $('#jstree_demo_div').jstree({
        "core": {
            "check_callback": true,
            "themes": {
                "name" : "proton",
                'responsive': true
            },
            "force_text": true,
        },
        "plugins": [
            "contextmenu", "dnd", "types"
        ]
    });

    $('#main-form').submit(function() {
        var ref = $.jstree.reference('#jstree_demo_div');

        var data = [];
        var index = 0;
        function procChildren(node) {
            var order = 1;

            $.each(node.children, function(key, val) {
                var child = ref.get_node(val);
                ++index;

                if (ref.get_type(child) == 'deleted') {
                    data.push({
                        id: child.id,
                        deleted: 1
                    });
                } else {
                    data.push({
                        id: child.id,
                        name: child.text,
                        parent: (child.parent == '#' ? '0' : child.parent),
                        order: order
                    });
                }

                procChildren(child);

                ++order;
            });
        }

        procChildren(ref.get_node('#'));

        $('#hidden-data').val(JSON.stringify(data));
    });


    function updateDeleteList() {
        var ref = $.jstree.reference('#jstree_demo_div');
        var list = [];

        function procChildren(node, depth) {
            $.each(node.children, function(key, val) {
                var child = ref.get_node(val);
                if (ref.get_type(child) == 'deleted') {
                    list.push(child.text);
                    procTwo(child, 1);
                } else {
                    procChildren(child);
                }
            });
        }

        function procTwo(node, depth) {
            $.each(node.children, function(key, val) {
                var child = ref.get_node(val);
                list.push(new Array(depth+1).join(' &nbsp; &nbsp; &nbsp; ') + child.text);
                procTwo(child, depth + 1);
            });
        }

        procChildren(ref.get_node('#'));

        if (list.length == 0) {
            $('.del').html('');
        } else if (list.length == 1) {
            $('.del').html('<h3>1 page scheduled for deletion</h3><p>' + list[0] + '</p>');
        } else {
            $('.del').html(
                '<h3>' + list.length + ' pages scheduled for deletion</h3>' +
                '<div class="info">All pages marked for deletion as well as their children pages are listed below.</div>' +
                '<p style="line-height: 1.5em">' + list.join('<br>') + '</p>'
            );
        }
    }
});
