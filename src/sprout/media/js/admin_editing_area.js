/**
* jQuery tabs
**/
function initTabs() {

    $("#main-tabs").addClass('main-tabs');

    var $tabs = $(".main-tabs");
    if ($tabs.length == 0) return;

    var currentPage = location.href.replace(/#.*$/, "");
    $tabs.find('> ul:first-child > li > a').each(function() {
        var href = $(this).attr('href');

        if (href.substring(0, 1) == '#') {

            // Work around an change in jQuery UI commit 18a3b53
            $(this).attr('href', currentPage + href);

            var $div = $(href);
            if ($div.length == 1) {
                var errorCount = $div.find('div.field-error').length;
                if (errorCount > 0) {
                    $(this).parent().addClass('tab--has-error');
                }
            }

        }

        $(this).click(function() {
            var id = $(this).parent().attr('aria-controls');
            $tabs.trigger('clickTab', id);
        });

    });

    // If tabs are within a form then HTML5 validation won't work properly so turn it off
    $tabs.each(function() {
        $(this).closest('form').attr('novalidate', 'novalidate');
    });


    $tabs.tabs();


    // Overflowing unlimited scrolling tabs
    $tabs.each(function(){
        var $bl = $(this).find(".ui-tabs-nav");
        $bl.wrap("<div class='ui-tabs-nav-wrapper'></div>");
        var $th = $bl.parent(".ui-tabs-nav-wrapper");

        var mPadd  = 150,  // Mousemove Padding
            damp   = 5,  // Mousemove response softness
            mX     = 0,   // Real mouse position
            mX2    = 0,   // Modified mouse position
            posX   = 0,
            blW    = 0,
            blSW   = 0,
            mmAA   = 0, // The mousemove available area
            mmAAr  = 0,
            wDiff  = 0; // widths difference ratio

        $th.mousemove(function(e){

            blW    = $bl.outerWidth();
            blSW   = $bl[0].scrollWidth;

            // Only scroll if tabs are actually overflowing container
            if (blW < blSW) {

                // If tab width is not wide enough to accomodate mousemove padding, set it to 0
                if(blW <= 340){
                    mPadd = 0;
                }

                mmAA   = blW-(mPadd*2);
                mmAAr  = (blW/mmAA);
                wDiff  = (blSW/blW)-1;

                mX = e.pageX - $(this).offset().left;
                mX2 = Math.min( Math.max(0, mX-mPadd), mmAA ) * mmAAr;

                window.requestAnimationFrame(function(){
                    posX += (mX2 - posX) / damp; // zeno's paradox equation "catching delay"
                    $bl.css({"transform": "translateX("+ (-posX*wDiff) + "px)" });
                });

            } else {
                $bl.css({ 'transform': '' });
            }

        });

        $th.mouseout(function(e){

            if(posX < 45) {
                // Lock left
                $bl.css({"transform": "translateX(0px)" });
            } else if(posX > (blW - 45)) {
                // Lock right
                $bl.css({"transform": "translateX("+ (-blW*wDiff) + "px)" });
            }
        });

    });


}

$(document).ready(function() {
    initTabs();
});


/**
* Handling of "dirty" forms
**/
$(document).ready(function() {
    var $form = $('#edit-form');
    var dirty = false;

    if ($form.length === 0) return;

    // Event handler for change events on the form. Sets dirty flag. Only fires once.
    function setDirty() {
        $form.off('change', 'input,select,textarea', setDirty);
        $form.off('setDirty', setDirty);

        dirty = true;
        document.title = document.title.replace(' | SproutCMS', ' (unsaved changes) | SproutCMS');
        $('#main-heading-options').prepend('<span class="main-heading--unsaved">(unsaved changes)</span>');
    }

    // Only attach after page has fully loaded and all JavaScript has run
    $(window).on('load', function() {
        $form.on('change', 'input,select,textarea', setDirty);
        $form.on('setDirty', setDirty);
    });

    // Saving the form (but not preview) to clear the dirty flag
    $form.on('click', '.save-changes-save-button', function() {
        dirty = false;
    });

    // Prompt the user if they leave and the dirty flag is set
    window.onbeforeunload = function(e) {
        e = e || window.event;

        var message = null;
        if (dirty) {
            message = 'You have unsaved changes!';
        } else {

            return;
        }

        if (e) {
            e.returnValue = message;
        }

        return message;
    }
});


/**
* Misc
* TODO: Organise and improve this
**/
$(document).ready(function() {

    // Top button tabs
    function closePageEditTab(target, $origin){
        var $target = $("#"+target),
        targetID = $target.attr("id");

        $target.removeClass("page-edit-tab-active").stop().slideUp(500, "easeInOutCirc");

        $('.page-edit-tab-button[data-target="'+ targetID +'"]').removeClass("button-blue").addClass("button-grey");
    }
    function openPageEditTab(target, $origin){
        var $target = $("#"+target),
        targetID = $target.attr("id");

        if($(".page-edit-tab.page-edit-tab-active").length){
            // If another tab was already active, close it first
            $(".page-edit-tab.page-edit-tab-active").not($target).removeClass("page-edit-tab-active").stop().slideUp(500, "easeInOutCirc", function(){
                $target.addClass("page-edit-tab-active").stop().slideDown(800, "easeInOutCirc");
            });
        } else {
            $target.addClass("page-edit-tab-active").stop().slideDown(800, "easeInOutCirc");
        }
    }

    $(".page-edit-tab-button").click(function(){
        var $this = $(this),
        target = $this.attr("data-target");
        if($this.hasClass("button-blue")){
            $(".page-edit-tab-button").removeClass("button-blue");
            $this.addClass("button-grey");
            closePageEditTab(target, $this);
        } else if($this.hasClass("button-grey")){
            $(".page-edit-tab-button").removeClass("button-blue");
            $this.addClass("button-blue");
            openPageEditTab(target, $this);
        }
    });

    $(".page-edit-tab-close").click(function(){
        var $this = $(this),
        target = $this.attr("data-target");
        closePageEditTab(target, $this);
    });


    // Sticky sidebar
    var $rightSidebar = $(".right-sidebar");

    if($rightSidebar.length) {
        var $window = $(window),
        $html = $("html"),
        $rightSidebarInner = $(".right-sidebar-inner"),
        rightSidebarSticky = false;

        function checkStickyRightSideBar() {
            var rightSidebarPosition = $window.scrollTop() - $rightSidebarAnchor.offset().top + 30;
            var rightSidebarHeight = $window.height() - ($rightSidebar.outerHeight() + 30);

            if(rightSidebarSticky === false && rightSidebarPosition >= 0 && rightSidebarHeight > 0) {
                $html.addClass("sticky-right-sidebar");
                rightSidebarSticky = true;
            } else if(rightSidebarSticky === true && rightSidebarPosition < 0) {
                $html.removeClass("sticky-right-sidebar");
                rightSidebarSticky = false;
            }
        }

        // Set width of right sidebar to same as anchor to get around position fixed limitations
        function checkStickyRightSideBarWidth() {
            $rightSidebarInner.width($rightSidebarAnchor.outerWidth());
        }

        var $rightSidebarAnchor = $('<div class="right-sidebar-anchor"></div>');
        $rightSidebarAnchor.prependTo($rightSidebar);
        $window.on("scroll resize load", checkStickyRightSideBar);
        checkStickyRightSideBar();

        $window.on("resize load", checkStickyRightSideBarWidth);
        checkStickyRightSideBarWidth();

        $(".sidebar-collapse-button").click(function() {
            setTimeout(function(){
                checkStickyRightSideBarWidth();
            }, 800);
        });
    }

    // Preview link
    $('.save-changes-preview-button').on('click', function(ev) {
        ev.preventDefault();

        var $form = $(this).closest('form');
        var orig_action = $form.attr('action');

        // Open preview in new window
        $form.attr('action', $(this).attr('href'));
        $form.attr('target', '_blank');
        $form.submit();

        // Restore original form behaviour
        $form.find('[type=submit]').removeAttr('disabled');
        $form.attr('action', orig_action);
        $form.removeAttr('target');
        return false;
    });

    // Main list - autolinking of items based on the first A link found
    $('.main-list tbody tr').each(function() {
        if ($(this).closest('table').is('.main-list-no-js')) return;

        var first_a = $(this).find('td:not(.actions) a:first');
        if (first_a.length == 0) return;

        $(this).find('td:not(.actions,.selection)').click(function() {
            first_a.triggerHandler('click');

            if (typeof(first_a.attr('href')) != 'undefined') {
                window.location = first_a.attr('href');
            }
        }).css('cursor', 'pointer');

        first_a.addClass('hide');
    });

    // Sort headings
    $('.main-list thead th').each(function() {
        if ($(this).find('img.sort').length > 0) {
            $(this).click(function() {
                window.location = $(this).find('a').attr('href');
            }).css('cursor', 'pointer');
        }
    });

    // Main list - SHIFT key checkboxes
    var $lastCheckedRow = null;
    var $checkboxes = $('.main-list td.selection input[type="checkbox"]');
    var $checkboxRows = $checkboxes.closest("tr");

    $('.main-list td.selection input[type="checkbox"] + label').on("click", function(e) {
        var $checkbox = $(this).prev();
        var $checkboxRow = $(this).closest("tr");

        if(!$lastCheckedRow) {
            $lastCheckedRow = $(this).closest("tr");
            return;
        }

        if(e.shiftKey) {

            // Making sure the checkbox actually check/unchecks due to stupid browser shift click behaivour
            if(!$checkbox.is(":checked")){
                $checkboxRow.addClass("row-selected");
                $checkbox.prop('checked', true);
            } else if($checkbox.is(":checked")){
                $checkboxRow.removeClass("row-selected");
                $checkbox.prop('checked', false);
            }

            // Select rows inbetween
            var start = $checkboxRows.index($checkboxRow);
            var end = $checkboxRows.index($lastCheckedRow);

            var $selectedRows = $checkboxRows.slice(Math.min(start,end)+1, Math.max(start,end));

            $selectedRows.each(function(){
                var $selectedRowCheckbox = $(this).find('td.selection input[type="checkbox"]');

                if($selectedRowCheckbox.is(":checked")) {
                    $(this).removeClass("row-selected");
                    $selectedRowCheckbox.prop('checked', false);
                } else if(!$selectedRowCheckbox.is(":checked")) {
                    $(this).addClass("row-selected");
                    $selectedRowCheckbox.prop('checked', true);
                }

            });

        }

        $lastCheckedRow = $(this).closest("tr");

    });

    // Main list - select all/none
    $('.main-list th.selection-all input[type="checkbox"]').change(function() {
        var check = $(this).prop('checked');
        $(this).closest('table').find('td.selection input').each(function() {
            $(this).prop('checked', check);
            if($(this).is(":checked")) {
                $(this).closest("tr").addClass("row-selected");
            } else if($(this).not(":checked")) {
                $(this).closest("tr").removeClass("row-selected");
            }
        });
    });
    // Main list - selected rows
    $('.main-list td.selection input[type="checkbox"]').change(function(){
        if($(this).is(":checked")) {
            $(this).closest("tr").addClass("row-selected");
        } else if($(this).not(":checked")) {
            $(this).closest("tr").removeClass("row-selected");
        }
    });
    // Main list - shift select checkboxes
    var lastChecked = null;
    var $chkboxes = $('.main-list td.selection input[type="checkbox"]');
    $chkboxes.click(function(e) {
        if(!lastChecked) {
            lastChecked = this;
            return;
        }

        if(e.shiftKey) {
            var start = $chkboxes.index(this);
            var end = $chkboxes.index(lastChecked);

            $chkboxes.slice(Math.min(start,end), Math.max(start,end)+ 1).prop('checked', lastChecked.checked);

        }

        lastChecked = this;
    });

    // Expandos
    $('div.expando').each(function() {
        var div = $(this);

        div.prev('h3').toggle(function() {
            $(this).removeClass('expando-link-closed').addClass('expando-link-open');
            div.slideDown();
        }, function() {
            $(this).removeClass('expando-link-open').addClass('expando-link-closed');
            div.slideUp();
        })
        .css('cursor', 'pointer');

        div.hide();
        div.prev('h3').addClass('expando-link-closed');
    });

    // Helptext boxes
    if ($('img.subheading-help-icon').length > 0) {
        $('#main-content').css('position', 'relative').append('<div id="subheading-help"></div>');
        $('#subheading-help').hide();

        $('img.subheading-help-icon').click(function() {
            var top = $(this).parent().offset().top - $('#main-content').offset().top + 35;
            top += 'px';

            var right = ($('#main-content').offset().left + $('#main-content').width()) - ($(this).parent().offset().left + $(this).parent().outerWidth()) + 60;
            right += 'px';

            if ($('#subheading-help').css('top') == top && $('#subheading-help').is(':visible')) {
                $('#subheading-help').hide();
            } else {
                $('#subheading-help').css('top', top);
                $('#subheading-help').css('right', right);
                $('#subheading-help').html($(this).attr('alt') + '<p class="close"><small>Close popup</small></p>');
                $('#subheading-help').show();

                $('#subheading-help p.close').click(function() { $('#subheading-help').hide(); });
            }
        });

        $('#main-tabs').bind('tabsselect', function(event, ui) {
            $('#subheading-help').hide();
        });
    }

    // On-the-fly category adding
    $('#onthefly-catadd-button').click(function() {
        var $wrap = $(this).parents('.onthefly-catadd');

        var postdata = {
            'name' : $wrap.find('input[type=text]').val(),
            'table' : $wrap.attr('data-tablename'),
            'edit_token': $wrap.find('input[name=edit_token]').val(),
        };

        $.post(SITE + 'admin_ajax/add_category', postdata, function(data) {
            if (data.success == 0) {
                alert (data.message);
                return;
            }

            var field = $wrap.attr('data-field');
            var rand = Math.floor(Math.random()*10000);

            name = name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

            var html = "<div class=\"fieldset-input\">";
                if ($wrap.attr('data-singlecat') == '1') {
                    html += '<input type="radio" id="g' + rand + '" value="' + data.id + '" name="' + field + '" checked>';
                } else {
                    html += '<input type="checkbox" id="g' + rand + '" value="' + data.id + '" name="' + field + '" checked>';
                }
                html += '<label for="g' + rand + '">' + data.name + '</label>';
            html += "</div>";



            $targetRow = $wrap.closest(".onthefly-catadd-wrapper").find('.field-element__input-set .fieldset-input').last();

            if($targetRow.length) {
                $targetRow.after(html);
            } else {
                checkboxListHtml = '<div class="checkbox-list-wrapper">';
                    checkboxListHtml += '<div class="checkbox-list category-selection">';
                        checkboxListHtml += "<div class=\"field-element field-element--checkbox'\">";
                            checkboxListHtml += "<fieldset class=\"fieldset--checkboxboollist\">";
                                checkboxListHtml += "<legend class=\"fieldset__legend\">Categories</legend>";
                                checkboxListHtml += "<div class=\"field-element__input-set\">";
                                    checkboxListHtml += html;
                                checkboxListHtml += "</div>";
                            checkboxListHtml += "</fieldset>";
                        checkboxListHtml += "</div>";
                    checkboxListHtml += '</div>';
                checkboxListHtml += '</div>';

                $(".onthefly-catadd-table-wrapper").append(checkboxListHtml);
            }

            $wrap.find('input[type=text]').val('');

        }, 'json');
    });
});

/**
* The first H3 needs to have less margin up the top so it looks nice
**/
$(document).ready(function() {
    $('#main-content h3:first').addClass('first-heading');
});


/**
* When the resolution is a bit wider, we move the tools into an anchored sidebar box
**/
$(document).ready(function() {
    if (! $('#content .main').is('.do-action-box')) return;

    $('.action-bar').clone().insertAfter('#main-tags').removeClass('action-bar').addClass('action-box');
    $('.action-box').prepend('<div class="submit-wrap"></div>');
    $('.action-box input[type=submit]').prependTo('.action-box .submit-wrap');

    var currExpanded = false;
    function resize() {
        var nuExpanded;
        if ($(window).width() > 1260) {
            $('#edit-form').css('width', 'auto');
            $('#edit-form').css('width', $('#main-content').width() - 250);
            nuExpanded = true;
        } else {
            nuExpanded = false;
        }

        if (currExpanded == nuExpanded) return;
        currExpanded = nuExpanded;

        if (currExpanded) {
            $('.action-bar').hide();
            $('.action-bar-middle').hide().addClass('action-bar-middle-hidden');
            $('.action-box').show();
        } else {
            $('#edit-form').css('width', 'auto');
            $('.action-bar').show();
            $('.action-bar-middle').show().removeClass('action-bar-middle-hidden');
            $('.action-box').hide();
        }
    }
    $(window).resize(resize);
    resize();

    var $hdr = $('.action-box');
    var $win = $(window);
    var currFixed = false;

    function doFade() {
        var nuFixed, pos = $win.scrollTop();
        var anchor = $('#main-content').offset().top + 65 - 30;

        if (pos > anchor) {
            nuFixed = true;
        } else {
            nuFixed = false;
        }

        if (nuFixed == currFixed) return;
        currFixed = nuFixed;

        if (currFixed) {
            $hdr.addClass('fixed');
        } else {
            $hdr.removeClass('fixed');
        }
    }

    $(window).scroll(doFade);
    doFade();
});


/**
* Update a category label (in the navbar) to indicate the new value
**/
function increase_category_count(cat_id) {
    var $elem = $('li.category a[rel=' + cat_id + '] span');
    if ($elem.length !== 1) return;
    $elem.html(parseInt($elem.html(), 10) + 1);
    $elem.parent().animate({color: '#000'}, 500).animate({color: '#333'}, 500);
}


/**
* If there is a #edit-form, submit it on ctrl+s
**/
$(document).ready(function() {
    if ($('#edit-form').length == 0) return;

    $(document).bind('keypress', function(e) {
        if (e.ctrlKey && e.which == 115) {
            $('#edit-form').submit();
            return false;
        }
    });
});


/**
* If there is a lock in place, unlock it when the page is unloaded
**/
$(document).ready(function() {
    if (typeof(currlock) !== 'undefined') {
        $(window).bind('beforeunload', function() {
            $.ajax({
                type: 'POST',
                url: SITE + 'admin/ajax_unlock',
                data: currlock,
                async: false
            });
        });
    }
});


/**
* Navigation bar js bits for the archive dropdown
**/
$(document).ready(function() {
    var $dropdown = $('#navigation .category-archive');
    if ($dropdown.length == 0) return;

    $dropdown.FancyDown();
    $dropdown = $('#navigation .category-archive');

    // On change, redirect to the same page but with a GET parameter
    $dropdown.change(function() {
        var type = $('input[name=category_archive]').val();

        var url = window.location.href;
        if (url.match(/category_type=/)) {
            url = url.replace(/category_type=[0-9]+/, 'category_type=' + type);
        } else if (url.match(/\?/)) {
            url += '&category_type=' + type;
        } else {
            url += '?category_type=' + type;
        }
        window.location = url;
    });
});


/**
 * Handle 'all categories' tickbox when setting per-record permissions
 */
$(document).ready(function() {
    $('#_prm_all').change(function() {
        var $other_boxes = $(this).closest('.field-element__input-set').find('input[name="_prm_categories[]"]');
        if ($(this).prop('checked')) {
            $other_boxes.prop('disabled', true);
            $other_boxes.closest('div').addClass('field-element--disabled');
        } else {
            $other_boxes.prop('disabled', false);
            $other_boxes.closest('div').removeClass('field-element--disabled');
        }
    });
    $('#_prm_all').change();
});
