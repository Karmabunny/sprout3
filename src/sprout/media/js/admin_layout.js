/**
* Sidebar collapse, with sessionStorage persistance
**/
$(document).ready(function() {
    var $html = $('html');

    // Handle a click on the toggle button
    function toggleSidebar() {
        $html.addClass('sidebar-animate');
        if ($html.is('.sidebar-collapse')) {
            $html.removeClass('sidebar-collapse');
            sessionStorage.setItem('sidebar-collapse', "N");
        } else {
            $html.addClass('sidebar-collapse');
            sessionStorage.setItem('sidebar-collapse', "Y");
        }
    }
    $(".sidebar-collapse-button").click(toggleSidebar);

    // Initial page load
    if (sessionStorage.getItem('sidebar-collapse') === "Y") {
        $html.addClass('sidebar-collapse');
    }
});


/* Top bar dropdowns */
$(document).ready(function () {
    var $topBarNav = $('#top-bar-nav');

    // Checks if click is out of bounds of top bar nav, and will close dropdown
    function topbarClick(e){
        if(!$(e.target).parents("#top-bar-nav").length) {
            $("body").off("click", topbarClick);
            $(".top-bar-nav-item-active").removeClass("top-bar-nav-item-active")
        }
    }

    // Top bar nav button click opens or closes dropdown
    $('.top-bar-nav-button').click(function(e) {
        $parentItem = $(this).closest(".top-bar-nav-item");
        var itemActive = false;
        if($parentItem.hasClass("top-bar-nav-item-active")){
            itemActive = true;
        }
        $topBarNav.find(".top-bar-nav-item-active").not(this).removeClass("top-bar-nav-item-active");
        if(itemActive === true){
            $parentItem.removeClass("top-bar-nav-item-active");
            $("body").off("click", topbarClick);
        } else if(itemActive === false){
            $parentItem.addClass("top-bar-nav-item-active");
            $("body").on("click", topbarClick);
        }
    });

});

function heartbeat() {
    var data = {};
    if (typeof(currlock) !== 'undefined') data.lock_id = currlock.id;

    $.get(SITE + 'admin/heartbeat', data);
    window.setTimeout(heartbeat, 60 * 1000);        // once a minute
}

$(document).ready(function () {
    // Heartbeat
    window.setTimeout(heartbeat, 60 * 1000);        // once a minute

    // Footer popups
    $('.footer-js-pop').click(function() {
        show_ajax_popup($(this).text(), $(this).attr('href'));
        return false;
    });

    $('td.sidebar div.sidebar-box h3:first-child').addClass('first');


});


// Closes the foldout box
function close_foldout(event) {
    $('#foldout-box').fadeOut(250, function() {
        $('#foldout-box').remove();
    });

    $(window).unbind('click', close_foldout);
}

function click_hide() {
    $(window).bind('click', close_foldout);
}

// Shows the foldout box
function show_foldout(content, anchor) {
    close_foldout();

    $('body').append(
        '<div id="foldout-box" style="display: none">' + content + '</div>'
    );

    $('#foldout-box')
        .css('position', 'absolute')
        .css('z-index', 300)
        ;

    $('#foldout-box').fadeIn(250);

    var pos = $(anchor).offset();
    $('#foldout-box').css('top', pos.top + $(anchor).height() + 2);
    $('#foldout-box').css('left', pos.left);

    $('#foldout-box a').bind('click', close_foldout);

    $('#foldout-box').click(function(event) {
        event.stopPropagation();
    });

    window.setTimeout('click_hide()', 500);
}

// Shows an ajax foldout
function show_ajax_foldout(title, url) {
    $.get(url, function(data) {
        show_foldout(title, data);
    });
}

// Navigation
$(document).ready(function(){

    // Sub nav matchHeight
    if($(".giant-popup-item").length){
        $(".giant-popup-item").matchHeight();
    }

    // On states when opening sub nav
    $('.has-sub-nav input[type="checkbox"]').change(function(){
        if($(this).attr("checked")){
            $(".depth-1.on").removeClass("on").addClass("on-hidden");
        } else if(!$(this).attr("checked")){
            $(".depth-1.on-hidden").removeClass("on-hidden").addClass("on");
        }
    });

    // If clicking out of bounds, sub nav
    $(".giant-popup-wrapper").click(function(e){
        if(!$(e.target).is(".giant-popup-content") && !$(e.target).parents(".giant-popup-content").length && !$(e.target).is(".giant-popup-close-button")) {
            $(this).closest(".giant-popup-outer").siblings(".giant-popup-checkbox").attr("checked", false);
        }
    });
});

/* Subsite selector */
$(document).ready(function(){
    $('#subsite-selector').on('change', function(){
        this.form.submit();
    });
});

/**
* Hook up tree list expandos, and the settings menus
**/
$(document).ready(function () {
    var $sidebar = $('.sidebar-box-content');

    // Checks if click is out of bounds of top bar nav, and will close dropdown
    function pageTreeSettingsClick(e){
        if(!$(e.target).parents(".tree-list-settings-dropdown").length && !$(e.target).is('.tree-list-settings-dropdown') && !$(e.target).is('.tree-list-settings-button')) {
            $("body").off("click", pageTreeSettingsClick);
            $(".settings-visible").removeClass("settings-visible")
        }
    }

    // Settings button click
    $('.tree-list-settings-button').click(function(e) {
        $closestNode = $(this).closest(".node");
        var nodeActive = false;
        if($closestNode.hasClass("settings-visible")){
            nodeActive = true;
        }
        $(this).closest(".tree-list").find(".settings-visible").not(this).removeClass("settings-visible");
        if(nodeActive === true){
            $closestNode.removeClass("settings-visible");
            $("body").off("click", pageTreeSettingsClick);
        } else if(nodeActive === false){
            $closestNode.addClass("settings-visible");
            $("body").on("click", pageTreeSettingsClick);
        }
    });

    // AJAX archiving and unarchiving of categories
    $('.tree-list').on('click', '.js--ajax-archive a', function() {
        var $elem = $(this);
        var auto_hide = ($('select[name="category_type"]').val() != '3');

        $.post($elem.attr('href'), {edit_token: window.csrfToken}, function() {
            if (auto_hide) {
                $elem.closest('li.node').hide();
            } else {
                $elem.closest('li.node').find('.tree-list-settings-button').triggerHandler('click');
                $elem.closest('li').remove();    // Can't do the action again
            }
        });

        return false;
    });

    // Expand buttons
    $(".tree-list-expand-button").click(function(){
        $(this).parent().next().slideToggle(300, function(){
            $(this).css("overflow", "visible");
        });
        $(this).closest(".node").toggleClass("collapsed");
    });

    $('.tree-list-settings-dropdown-list-item.popup a').click(function() {
        $.facebox({'ajax':this.href});
        return false;
    });
});
