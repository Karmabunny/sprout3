$(window).load(function(){
    var tour_name = 'page_edit';

    var steps = [{
        content: '<p>Since this is your first time editing a page, let\'s give you a quick tour!</p><p><button class="tour-content-link tour-content-link--small -r-arrow-after" onclick="tour.stop(true);">Skip tour</button></p>',
        highlightTarget: false,
        nextButton: true,
        target: $('#main-heading'),
        my: 'bottom left',
        at: 'top left',
        setup: function(tour, options) {
            $('html, body').animate({
                scrollTop: this.target.offset().top - 500
            }, 800);
        }
    }, {
        content: '<p>The Content area is where you manage what displays in the main content of your page.</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('#tour-content-area'),
        my: 'bottom left',
        at: 'top left'
    }, {
        content: '<p>Content Blocks build the structure of your page.</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('#wl-embedded'),
        my: 'bottom left',
        at: 'top left'
    }, {
        content: '<p>Use these buttons to collapse, disable, reorder, or remove Content Blocks.</p>',
        highlightTarget: true,
        nextButton: true,
        my: 'bottom left',
        at: 'top left',
        setup: function(tour, options) {
            return { target: $('#wl-embedded .widget:first-child .widget-header-buttons') };
        }
    }, {
        content: '<p>You can add new content blocks with this button.</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('#wl-embedded .content-block-button-wrap'),
        my: 'bottom left',
        at: 'top left',
        setup: function(tour, options) {
            $('html, body').animate({
                scrollTop: this.target.offset().top - 500
            }, 800);
        }
    }, {
        content: '<p>The Sidebar area is where you manage what displays in the sidebar of your page. You can add content blocks in the same way as the Content area.</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('.sidebar-widgets'),
        my: 'bottom left',
        at: 'top left',
        setup: function(tour, options) {
            $('html, body').animate({
                scrollTop: this.target.offset().top - 500
            }, 800);
        }
    }, {
        content: '<p>This is the Page Settings button. You can edit settings associated with this page such as banner images, metadata, and permissions.</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('.page-settings-button'),
        my: 'bottom right',
        at: 'top right',
        setup: function(tour, options) {
            $('html, body').animate({
                scrollTop: this.target.offset().top - 500
            }, 800);
        }
    }, {
        content: '<p>This is the Revisions button. You can revert the page back to a previous state if needed.</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('.revisions-button'),
        my: 'bottom right',
        at: 'top right',
        setup: function(tour, options) {
            $('html, body').animate({
                scrollTop: this.target.offset().top - 500
            }, 800);
        }
    }, {
        content: '<p>You can choose different methods of saving the page. You can publish the changes now, save them as a work in progress, or automatically publish them at a later date.</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('#publish-options'),
        my: 'bottom right',
        at: 'top right',
        setup: function(tour, options) {
            $('html, body').animate({
                scrollTop: this.target.offset().top - 500
            }, 800);
        }
    }, {
        content: '<p>You can preview your page changes with the Preview button</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('.save-changes-preview-button'),
        my: 'bottom right',
        at: 'top right',
        setup: function(tour, options) {
            $('html, body').animate({
                scrollTop: this.target.offset().top - 500
            }, 800);
        }
    }, {
        content: '<p>Once you\'re finished making changes, remember to hit the Save Changes button!</p>',
        highlightTarget: true,
        nextButton: true,
        target: $('.save-changes-save-button'),
        my: 'bottom right',
        at: 'top right',
    }]

    tour = new Tourist.Tour({
        steps: steps,
        tipOptions:{ showEffect: 'slidein' },
        cancelStep: {
            setup: function(tour, options) {
                $.get(SITE + 'admin_ajax/tour_complete/' + tour_name);
            }
        },
        successStep: {
            setup: function(tour, options) {
                $.get(SITE + 'admin_ajax/tour_complete/' + tour_name);
            }
        }
    });

    tour.start();
});

