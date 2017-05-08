/**
* Class to save the "open" nav items in a cookie
**/
var visibleStorage = {
    vals: [],
    add: function(id) {
        var index = $.inArray(id, this.vals);
        if (index == -1) {
            this.vals.push(id);
            $.cookie('admin.page-nav', this.vals.join('|'), {path:'/'});
        }
    },
    remove: function(id) {
        var index = $.inArray(id, this.vals);
        if (index != -1) {
            this.vals.splice(index, 1);
            $.cookie('admin.page-nav', this.vals.join('|'), {path:'/'});
        }
    },
    get: function() {
        if ($.cookie('admin.page-nav')) {
            this.vals = $.cookie('admin.page-nav').split('|');
        } else {
            this.vals = [];
        }
        return this.vals;
    }
};


