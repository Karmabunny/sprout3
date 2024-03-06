/**
* A JS application to enable AJAX / JSON usage of generic sprout form components and handlers
* Set any current controller to return via JSON instead of a new page and it should hook in
*
* Activate on a form by including the file and the following line of JS:
* formApp.bindForm($formElem, submitUrl, $contentWrapper);
*
*
* There are a number of useful *** OPTIONS *** to control the way the app works, including:
*
* $docTop: This is the point in the document where notification messages are added
* - This can be called with an optional argument 'prepend' - by default, messages are added AFTER the target $element
* - If prepend is set to true, messages will be added as the first child of the target $element
* - Default: Try to find breadcrumbs and append, if not look for .mainbar and prepend
*
* pageHeader: The header $element of the page, used when there is a sticky header
* - Scrolls around the page will be offset by the height of this $element
*
* scrollTime: How long (in ms) the scroll animation will take.
* - This can be overriden when calling scroll function, or will fall back to the main app setting

*
* The primary *** FUNCTIONS *** are based around form handling, chief of which is this:
*
* doBindForm : This is the main vein of the operation master funcitoning cortex zone.
* - (bindForm from public call)
* - This sets a specified form up to submit itself via AJAX post to a specified URL
* - When data is returned, either a set of errors or a new page is rendered
* -
* - Params are $formElem (the form), submitUrl (where to post to) and $contentWrapper (the div or other content which will hold the response html)
* -
* - Return values should be via JSON data as follows:
* - Success: [success: 1, html: 'rendered new content', message: 'notification confirmation message']
* - Error: [success: 1, error: 1, fieldErrors: "standard sprout error array as per $valid->getFieldErrors()", message: 'notification error message']
*
* Other functions include:
*
* messageToggle(message, msgType) : Display and scroll to a message of type matching Notifications
* scrollPage($elem, timeScroll) : scroll the page to $elem and take timeScroll seconds to do it (or default time)
* scrollTop() : Scroll to the top of the page, taking the default number of seconds to do it
*
**/
"use strict";

var formApp = function () {
    var $docTop, pageHeader, scrollOffset;
    var docPrepend = false;
    var $contentWrapper = false;
    var headOffset = 0;
    var scrollTime = 500;

    var loadElems = function() {
        $docTop = $('ul.breadcrumb');
        pageHeader = $('#header');

        if ($docTop.length == 0) {
            doSetDocTop($('div.mainbar'), true);
        }

        doSetHeaderOffset();
    };

    var doSetScrollTime = function(sTime) {
        scrollTime = sTime;
    };

    var doSetHeader$elem = function($elem) {
        pageHeader = $elem;
    };

    var doSetHeaderOffset = function() {
        if (typeof pageHeader === 'undefined' || pageHeader.length != 1) headOffset = 0;

        if (pageHeader.css('position') == 'fixed') {
            headOffset = pageHeader.outerHeight();
        }
    };

    var doSetDocTop = function($elem, prepend) {
        $docTop = $elem;
        docPrepend = prepend;
    };

    var validSelector = function ($elem) {
        if ($elem instanceof jQuery) {
            return $elem;
        }

        if (typeof($elem) !== 'string' || !$elem.length) {
            console.log('Error: Invalid selector provided to formApp');
        }

        try {
            $elem = $($elem);
        } catch (error) {
            console.log('Error: Unable to load selector in formApp');
        }

        if (!($elem instanceof jQuery) || !$elem.length) {
            console.log('Error: Selector not loaded in formApp');
        }

        return $elem;
    };

    var resetSubmitBtn = function ($elem, btnText) {
        if ($elem.is('button')) {
            $elem.text(btnText);
        } else {
            $elem.val(btnText);
        }
        $elem.removeAttr('disabled');
    };

    var doBindForm = function($formElem, submitUrl, $contentWrapper) {
        $formElem.on('submit', function(e) {
            e.preventDefault();
            var btnIsBtn;
            var $submitBtn = $formElem.find('[type="submit"]');

            if ($submitBtn.is('button')) {
                var submitBtnOrigText = $submitBtn.text();
                btnIsBtn = true;
                $submitBtn.text('Please wait...');
            } else {
                var submitBtnOrigText = $submitBtn.val();
                btnIsBtn = false;
                $submitBtn.val('Please wait...');
            }

            $('.field-element--error').removeClass('field-element--error');
            $('.field-error').remove();

            $.post(submitUrl, $formElem.serialize(), function(data) {
                if (data.error) {
                    formApp.messageToggle(data.message, 'error');
                    if (data.fieldErrors) {
                        $.each(data.fieldErrors, function(fieldName,errorList){

                            var errField = $formElem.find($('[name="' + fieldName + '"]')).eq(0);
                            var errFieldWrap = errField.closest('.field-element');
                            errFieldWrap.addClass('field-element--error');

                            if (errorList) {
                                var errMsgs = '<div class="field-error">';
                                errMsgs += '<ul class="field-error__list">';

                                $.each(errorList, function(key,val){
                                    errMsgs += '<li class="field-error__list__item">' + val + '</li>';
                                });

                                errMsgs += '</ul>';
                                errMsgs += '</div>';

                                errFieldWrap.append(errMsgs);
                            }

                        });
                    }

                } else {

                    $contentWrapper.html(data.html);

                    if (data.message) {
                        formApp.messageToggle(data.message, 'confirm');
                    } else {
                        formApp.scrollTop();
                    }
                }

                resetSubmitBtn($submitBtn, submitBtnOrigText);

            }, 'json').fail(function() {

                resetSubmitBtn($submitBtn, submitBtnOrigText);
                alert('Sorry, something went wrong processing your submission. Please try again');

            });
        });
    };

    /**
    * Scroll a page to a given $element's location over a given time frame
    * If no time frame is given, us the app set one
    **/
    var doScrollPage = function($elem, thisTime) {
        if (typeof $elem === 'undefined' || !$elem || $elem.length == 0) {
            scrollOffset = 0;
        } else {
            scrollOffset = $('content').scrollTop() + $elem.offset().top;
        }

        if (typeof thisTime === 'undefined') thisTime = scrollTime;

        scrollOffset -= headOffset;

        $('content,html').animate({
            scrollTop: scrollOffset
        }, thisTime);
    }

    /**
    * Display a site message as per notifications, using the same msgTypes (confirm, error etc)
    **/
    var doMessageToggle = function(message, msgType) {
        var currMsgs = $('ul.messages');
        if (currMsgs.length > 0) {
            currMsgs.remove();
        }
        if (typeof message === 'undefined') return;

        var msgHtml = '<ul class="messages all-type-' + msgType +  '">';
        msgHtml += '<li class="' + msgType +  '"><span class="notification--text">';
        msgHtml += message;
        msgHtml += '</span></li></ul>';

        if (docPrepend) {
            $docTop.prepend(msgHtml);
        } else {
            $(msgHtml).insertAfter($docTop);
        }

        doScrollPage($docTop, scrollTime);
    };


	return {
        // Set up vars
        init: function() {
            loadElems();
        },

        testLoader: function($elem) {
            $elem = validSelector($elem);
        },

        // Trigger a new site message and scroll to it
        messageToggle: function (message, msgType) {
            doMessageToggle(message, msgType);
        },

        // Scroll window to a specific $element, in a given time frame
        scrollPage: function ($elem, timeScroll) {
            $elem = validSelector($elem);
            doScrollPage($elem, timeScroll);
        },

        // public function to scroll fully to the top
        scrollTop: function() {
            doScrollPage();
        },

        // Override the default top of the content section - where messages will be rendered
        // @param bool prepend Do we prepend the messages to the $element? Default is append
        setScrollTime: function(sTime) {
            sTime = parseInt(sTime);
            doSetScrollTime(sTime);
        },

        // Override the default page header $element - useful for fixed headers that are not header#header
        // Expects a selected jQuery $element
        setHeader$elem: function($elem) {
            $elem = validSelector($elem);
            doSetHeader$elem($elem);
            doSetHeaderOffset();
        },

        // Override the default top of the content section - where messages will be rendered
        // @param bool prepend Do we prepend the messages to the $element? Default is append
        set$docTop: function($elem, prepend) {
            $elem = validSelector($elem);
            doSetDocTop($elem, prepend);
        },

        // Override the default page header $element - useful for fixed headers that are not #header
        // URL can be blank to submit to current URL
        bindForm: function($form, submitUrl, $contentWrapper) {
            $form = validSelector($form);
            // Expects a selected jQuery $element
            $contentWrapper = validSelector($contentWrapper);

            if (typeof $form === 'undefined' || $form.length == 0) {
                console.log('ERROR: invalid form binding');
                return;
            }

            doBindForm($form, submitUrl, $contentWrapper);
        }
	};

}();

$(document).ready(function () {
    formApp.init();
});