// jQuery TimePicker plugin - http://github.com/wvega/timepicker
//
// A jQuery plugin to enhance standard form input fields helping users to select
// (or type) times.
//
// Copyright (c) 2010 Willington Vega <wvega@wvega.com>
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.


// Define a cross-browser window.console.log method.
// For IE and FF without Firebug, fallback to using an alert.
//if (!window.console) {
//    var log = window.opera ? window.opera.postError : alert;
//    window.console = { log: function(str) { log(str) } };
//}

if(typeof jQuery != 'undefined') {
    (function($, undefined){

        function pad(str, ch, length) {
            return Array(length + 1 - str.length).join(ch) + str;
        }

        function normalize() {
            if (arguments.length == 1) {
                return new Date(1988, 7, 24, arguments[0].getHours(), arguments[0].getMinutes(), 00);
            } else if (arguments.length == 2) {
                return new Date(1988, 7, 24, arguments[0], arguments[1], 00);
            } else {
                return new Date(1988, 7, 24);
            }
        }

        $.TimePicker = function() {
            var widget = this;

            widget.ui = $('<ul></ul>').addClass('ui-timepicker ui-timepicker-hidden')
                                    .addClass('ui-widget ui-widget-content ui-menu')
                                    .addClass('ui-corner-all ui-helper-hidden')
                                    .appendTo('body');

            if ($.fn.jquery >= '1.4.2') {
                widget.ui.delegate('a', 'mouseenter.timepicker', function(event) {
                    // passing false instead of an instance object tells the function
                    // to use the current instance
                    widget.activate(false, $(this).parent());
                }).delegate('a', 'mouseleave.timepicker', function(event) {
                    widget.deactivate(false);
                }).delegate('a', 'click.timepicker', function(event) {
                    event.preventDefault();
                    widget.select(false, $(this).parent());
                });
            }

            widget.ui.bind('click.timepicker, scroll.timepicker', function(event) {
                clearTimeout(widget.closing);
            });
        };

        $.TimePicker.count = 0;
        $.TimePicker.instance = function() {
            if (!$.TimePicker._instance) {
                $.TimePicker._instance = new $.TimePicker();
            }
            return $.TimePicker._instance;
        }

        $.TimePicker.prototype = {
            // extracted from from jQuery UI Core
            // http://github,com/jquery/jquery-ui/blob/master/ui/jquery.ui.core.js
            keyCode: {
                ALT: 18,
                BLOQ_MAYUS: 20,
                CTRL: 17,
                DOWN: 40,
                END: 35,
                ENTER: 13,
                HOME: 36,
                LEFT: 37,
                NUMPAD_ENTER: 108,
                PAGE_DOWN: 34,
                PAGE_UP: 33,
                RIGHT: 39,
                SHIFT: 16,
                TAB: 9,
                UP: 38,
                BACKSPACE: 8
            },

            _items: function(i, startTime) {
                var widget = this, ul = $('<ul></ul>'), item = null, time, end;

                if (startTime) {
                    time = normalize(startTime);
                } else if (i.options.startTime) {
                    time = normalize(i.options.startTime);
                } else {
                    time = normalize(i.options.startHour, i.options.startMinutes)
                }

                end = new Date(time.getTime() + 24 * 60 * 60 * 1000);

                while(time < end) {
                    if (widget._isValidTime(i, time)) {
                        item = $('<li>').addClass('ui-menu-item').appendTo(ul);
                        $('<a>').addClass('ui-corner-all').text($.fn.timepicker.formatTime(i.options.timeFormat, time)).appendTo(item);
                    }
                    time = new Date(time.getTime() + i.options.interval * 60 * 1000);
                }

                return ul.children();
            },

            _isValidTime: function(i, time) {
                var min = null, max = null;

                time = normalize(time);

                if (i.options.minTime !== null) {
                    min = normalize(i.options.minTime);
                } else if (i.options.minHour !== null || i.options.minMinutes !== null) {
                    min = normalize(i.options.minHour, i.options.minMinutes);
                }

                if (i.options.maxTime !== null) {
                    max = normalize(i.options.maxTime)
                } else if (i.options.maxHour !== null || i.options.maxMinutes !== null) {
                    max = normalize(i.options.maxHour, i.options.maxMinutes);
                }

                if (min !== null && max !== null) {
                    return time >= min && time <= max;
                } else if (min !== null) {
                    return time >= min;
                } else if (max !== null) {
                    return time <= max;
                }

                return true;
            },

            _hasScroll: function() {
                // KB: scrolling is always enabled unless explicitly overridden
                //     by this attribute
                if (this.ui.attr('scrollHeight') === undefined) {
                    return true;
                }

                return this.ui.height() < this.ui.attr('scrollHeight');
            },

            /**
             * TODO: Write me!
             *
             * @param i
             * @param direction
             * @param edge
             * */
            _move: function(i, direction, edge) {
                var widget = this;
                if (widget.closed()) {
                    widget.open(i);
                }
                if (!widget.active) {
                    widget.activate(i, widget.ui.children(edge));
                    return;
                }
                var next = widget.active[direction + 'All']('.ui-menu-item').eq(0);
                if (next.length) {
                    widget.activate(i, next);
                } else {
                    widget.activate(i, widget.ui.children(edge));
                }
            },

            //
            // protected methods
            //

            register: function(node, options) {
                var widget = this, i = {};// timepicker instance object

                i.element = $(node);

                if (i.element.data('TimePicker')) { return; }

                i.element.data('TimePicker', i);
                i.options = $.metadata ? $.extend({}, options, i.element.metadata()) : options;
                i.widget = widget;

                // proxy functions for the exposed api methods
                $.extend(i, {
                    next: function() {return widget.next(i);},
                    previous: function() {return widget.previous(i);},
                    first: function() {return widget.first(i);},
                    last: function() {return widget.last(i);},
                    selected: function() {return widget.selected(i);},
                    open: function() {return widget.open(i);},
                    close: function(force) {return widget.close(i, force);},
                    closed: function() {return widget.closed(i);},
                    destroy: function() {return widget.destroy(i)},
                    getTime: function() {return widget.getTime(i);},
                    setTime: function(time) {return widget.setTime(i, time);}
                });

                i.element.bind('keydown.timepicker', function(event) {
                    switch (event.which || event.keyCode) {
                        case widget.keyCode.ENTER:
                        case widget.keyCode.NUMPAD_ENTER:
                            event.preventDefault();
                            if (widget.closed()) {
                                i.element.trigger('change.timepicker');
                            } else {
                                // TODO rename _select to select
                                widget.select(i, widget.active);
                            }
                            break;
                        case widget.keyCode.UP:
                            i.previous();
                            break;
                        case widget.keyCode.DOWN:
                            i.next();
                            break;
                        case widget.keyCode.BACKSPACE:
                            break; // KB: don't hide the dropdown on backspace
                        default:
                            if (!widget.closed()) {
                                i.close(true);
                            }
                            break;
                    }
                }).bind('focus.timepicker', function(event) {
                    i.open();
                }).bind('blur.timepicker', function(event) {
                    i.close();
                }).bind('change.timepicker', function(event) {
                    if (i.closed()) {
                        i.setTime($.fn.timepicker.parseTime(i.element.val()));
                    }
                });
            },

            select: function(i, item) {
                var widget = this, instance = i === false ? widget.instance : i;
                clearTimeout(widget.closing);
                widget.setTime(instance, $.fn.timepicker.parseTime(item.children('a').text()));
                widget.close(instance, true);
            },

            activate: function(i, item) {
                var widget = this, instance = i === false ? widget.instance : i;

                if (instance !== widget.instance) {
                    return;
                } else {
                    widget.deactivate();
                }

                if (widget._hasScroll()) {
                    var offset = item.offset().top - widget.ui.offset().top,
                        scroll = widget.ui.scrollTop(),
                        height = widget.ui.height();
                    if (offset < 0) {
                        widget.ui.scrollTop(scroll + offset);
                    } else if (offset >= height) {
                        widget.ui.scrollTop(scroll + offset - height + item.height());
                    }
                }

                widget.active = item.eq(0).children('a').addClass('ui-state-hover')
                                                        .attr('id', 'ui-active-item')
                                          .end();
            },

            deactivate: function() {
                var widget = this;
                if (!widget.active) { return; }
                widget.active.children('a').removeClass('ui-state-hover').removeAttr('id');
                widget.active = null;
            },

//            option: function(key, value) {
//                var self = this;
//                // TODO: handle variable updates
//                if (arguments.length > 1) {
//                    if (self.options.hasOwnProperty(key)) {
//                        self.options[key] = value;
//                    }
//                    return self;
//                }
//                return self.options[key];
//            },

            /**
             * _activate, _deactivate, first, last, next, previous, _move and
             * _hasScroll were extracted from jQuery UI Menu
             * http://github,com/jquery/jquery-ui/blob/menu/ui/jquery.ui.menu.js
             */

            //
            // public methods
            //

            next: function(i) {
                if (this.closed() || this.instance === i) {
                    this._move(i, 'next', '.ui-menu-item:first');
                }
            },

            previous: function(i) {
                if (this.closed() || this.instance === i) {
                    this._move(i, 'prev', '.ui-menu-item:last');
                }
            },

            first: function(i) {
                if (this.instance === i) {
                    return this.active && !this.active.prevAll('.ui-menu-item').length;
                }
                return false;
            },

            last: function(i) {
                if (this.instance === i) {
                    return this.active && !this.active.nextAll('.ui-menu-item').length;
                }
                return false;
            },

            selected: function(i) {
                if (this.instance === i)  {
                    return this.active ? this.active : null;
                }
                return null;
            },

            open: function(i) {
                var widget = this, zindex;

                // if a date is already selected and options.dynamic is true,
                // arrange the items in the list so the first item is
                // cronologically right after the selected date.
                // TODO: set selectedTime
                if (!i.items || (i.options.dynamic && i.selectedTime)) {
                    i.items = widget._items(i);
                }

                // remove old li elements but keep associated events, then append
                // the new li elements to the ul
                if (widget.instance !== i) {

                    // handle menu events when using jQuery versions previous to
                    // 1.4.2 (thanks to Brian Link)
                    // http://github.com/wvega/timepicker/issues#issue/4
                    if ($.fn.jquery < '1.4.2') {
                        widget.ui.children().remove();
                        widget.ui.append(i.items);
                        widget.ui.find('a').bind('mouseover.timepicker', function(event) {
                            widget.activate(i, $(this).parent());
                        }).bind('mouseout.timepicker', function(event) {
                            widget.deactivate(i);
                        }).bind('click.timepicker', function(event) {
                            event.preventDefault();
                            widget.select(i, $(this).parent());
                        });
                    } else {
                        widget.ui.children().detach();
                        widget.ui.append(i.items);
                    }
                }

                // theme
                widget.ui.removeClass('ui-helper-hidden ui-timepicker-hidden ui-timepicker-standard ui-timepicker-corners');

                switch (i.options.theme) {
                    case 'standard':
                        widget.ui.addClass('ui-timepicker-standard');
                        break;
                    case 'standard-rounded-corners':
                        widget.ui.addClass('ui-timepicker-standard ui-timepicker-corners');
                        break;
                    default:
                        break;
                }

                // position
                // zindex = i.element.offsetParent().css('z-index'); zindex == 'auto' ? 'auto' : parseInt(zindex, 10) + 1
                widget.ui.css($.extend(i.element.offset(), {
                    width: i.element.innerWidth(),
                    zIndex: i.element.offsetParent().css('z-index')
                }));
                widget.ui.css('top', parseInt(widget.ui.css('top'), 10) + i.element.outerHeight());

                widget.instance = i;

                // don't break the chain
                return i.element;
            },

            close: function(i, force) {
                var widget = this;
                if (widget.closed() || force) {
                    clearTimeout(widget.closing);
                    if (widget.instance === i) {
                        widget.ui.scrollTop(0).addClass('ui-helper-hidden ui-timepicker-hidden');
                        widget.ui.children().removeClass('ui-state-hover');
                    }
                } else {
                    widget.closing = setTimeout(function() {
                        widget.close(i, true);
                    }, 150);
                }
                return i.element;
            },

            closed: function() {
                return this.ui.is(':hidden');
            },

            destroy: function(i) {
                var widget = this;
                widget.close(i, true);
                return i.element.unbind('.timepicker').data('TimePicker', null);
            },

            getTime: function(i) {
                return i.selectedTime ? i.selectedTime : null;
            },

            setTime: function(i, time) {
                var widget = this;
                if (time && time.getMinutes) {
                    i.selectedTime = time;
                    i.element.val($.fn.timepicker.formatTime(i.options.timeFormat, time));
                    // custom change event and change callback
                    i.element.trigger('time-change', [time]);
                    if ($.isFunction(i.options.change)) {
                        i.options.change.apply(i.element, [time]);
                    }
                }
            }
        };

        $.TimePicker.defaults =  {
            timeFormat: 'hh:mm p',
            minHour: null,
            minMinutes: null,
            minTime: null,
            maxHour: null,
            maxMinutes: null,
            maxTime: null,
            startHour: null,
            startMinutes: null,
            startTime: null,
            interval: 30,
            dynamic: true,
            theme: 'standard',
            // callbacks
            change: function(time) {}
        };

        $.fn.timepicker = function(options) {
            // TODO: see if it works with previous versions
            if ($.fn.jquery < '1.3') {
                return this;
            }

            // Calling the constructor again returns a reference to a TimePicker object.
            if (this.length == 1 && this.data('TimePicker')) {
                return this.data('TimePicker');
            }

            var globals = $.extend({}, $.TimePicker.defaults, options);

            return this.each(function() {
                $.TimePicker.instance().register(this, globals);
            });
        };

        /**
         * TODO: documentation
         */
        $.fn.timepicker.formatTime = function(format, time) {
            var hours = time.getHours(),
                hours12 = hours % 12,
                minutes = time.getMinutes(),
                seconds = time.getSeconds(),
                replacements = {
                    hh: pad((hours12 === 0 ? 12 : hours12).toString(), '0', 2),
                    HH: pad(hours.toString(), '0', 2),
                    mm: pad(minutes.toString(), '0', 2),
                    ss: pad(seconds.toString(), '0', 2),
                    h: (hours12 === 0 ? 12 : hours12),
                    H: hours,
                    m: minutes,
                    s: seconds,
                    p: hours > 11 ? 'PM' : 'AM'
                },
                str = format, k = '';
            for (k in replacements) {
                if (replacements.hasOwnProperty(k)) {
                    str = str.replace(new RegExp(k,'g'), replacements[k]);
                }
            }
            return str;
        };

        /**
         * Convert a string representing a given time into a Date object.
         *
         * The Date object will have attributes others than hours, minutes and
         * seconds set to current local time values. The function will return
         * false if given string can't be converted.
         *
         * If there is an 'a' in the string we set am to true, if there is a 'p'
         * we set pm to true, if both are present only am is setted to true.
         *
         * All non-digit characters are removed from the string before trying to
         * parse the time.
         *
         * ''       can't be converted and the function returns false.
         * '1'      is converted to     01:00:00 am
         * '11'     is converted to     11:00:00 am
         * '111'    is converted to     01:11:00 am
         * '1111'   is converted to     11:11:00 am
         * '11111'  is converted to     01:11:11 am
         * '111111' is converted to     11:11:11 am
         *
         * Only the first six (or less) characters are considered.
         *
         * Special case:
         *
         * When hours is greater than 24 and the last digit is less or equal than 6, and minutes
         * and seconds are less or equal than 60, we append a trailing zero and
         * start parsing process again. Examples:
         *
         * '95' is treated as '950' and converted to 09:50:00 am
         * '46' is treated as '460' and converted to 05:00:00 am
         * '57' can't be converted and the function returns false.
         *
         * For a detailed list of supported formats check the unit tests at
         * http://github.com/wvega/timepicker/tree/master/tests/
         */
        $.fn.timepicker.parseTime = (function() {
            var patterns = [
                    // 1, 12, 123, 1234, 12345, 123456
                    [/^(\d+)$/, '$1'],
                    // :1, :2, :3, :4 ... :9
                    [/^:(\d)$/, '$10'],
                    // :1, :12, :123, :1234 ...
                    [/^:(\d+)/, '$1'],
                    // 6:06, 5:59, 5:8
                    [/^(\d):([7-9])$/, '0$10$2'],
                    [/^(\d):(\d\d)$/, '$1$2'],
                    [/^(\d):(\d{1,})$/, '0$1$20'],
                    // 10:8, 10:10, 10:34
                    [/^(\d\d):([7-9])$/, '$10$2'],
                    [/^(\d\d):(\d)$/, '$1$20'],
                    [/^(\d\d):(\d*)$/, '$1$2'],
                    // 123:4, 1234:456
                    [/^(\d{3,}):(\d)$/, '$10$2'],
                    [/^(\d{3,}):(\d{2,})/, '$1$2'],
                    //
                    [/^(\d):(\d):(\d)$/, '0$10$20$3'],
                    [/^(\d{1,2}):(\d):(\d\d)/, '$10$2$3']];

            return function(str) {
                var time = new Date(),
                    am = false, pm = false, h = false, m = false, s = false, k = 0;
                str = str.toLowerCase();
                am = /a/.test(str);
                pm = am ? false : /p/.test(str);
                str = str.replace(/[^0-9:]/g, '').replace(/:+/g, ':');

                for (k in patterns) {
                    if (patterns[k][0].test(str)) {
                        str = str.replace(patterns[k][0], patterns[k][1]);
                        break;
                    }
                }
                str = str.replace(/:/g, '');

                if (str.length == 1) {
                    h = str;
                } else if (str.length == 2) {
                    h = str;
                } else if (str.length == 3 || str.length == 5) {
                    h = str.substr(0, 1);
                    m = str.substr(1, 2);
                    s = str.substr(3, 2);
                } else if (str.length == 4 || str.length > 5) {
                    h = str.substr(0, 2);
                    m = str.substr(2, 2);
                    s = str.substr(4, 2);
                }

                if (str.length > 0 && str.length < 5) {
                    if (str.length < 3) {
                        m = 0;
                    }
                    s = 0;
                }

                if (h === false || m === false || s === false) {
                    return false;
                }

                h = parseInt(h, 10);m = parseInt(m, 10);s = parseInt(s, 10);

                if (am && h == 12) {
                    h = 0;
                } else if (pm && h < 12) {
                    h = h + 12;
                }

                if (h > 24 && (h % 10) <= 6 && m <= 60 && s <= 60) {
                    return $.fn.timepicker.parseTime(str + '0' + (am ? 'a' : '') + (pm ? 'p' : ''));
                } else if (h <= 24 && m <= 60 && s <= 60) {
                    time.setHours(h, m, s);
                    return time;
                } else {
                    return false;
                }
            };
        })();
    })(jQuery);
}


/**
* All of the logic for the timepicker implementation into the fb helper.
*
* This is called by the fb helper for you - you only need it if you are
* using the datepicker inside a multiedit, in which case, you need to use
* fb::set_js(false) as well.
*
* This code is smart enough to handle the situation where you run the setup
* on a given datepicker multiple times - it will detect it's already set up
* and skip that datepicker.
**/
function fb_timepicker($elem, mintime, maxtime, interval) {
	if (typeof(mintime) === 'undefined') var mintime = '00:00';
	if (typeof(maxtime) === 'undefined') var maxtime = '23:59';
	if (typeof(interval) === 'undefined') var interval = 30;

	mintime = mintime.split(':');
	mintime = new Date(1988, 1, 1, parseInt(mintime[0], 10), parseInt(mintime[1], 10), 0);

	maxtime = maxtime.split(':');
	maxtime = new Date(1988, 1, 1, parseInt(maxtime[0], 10), parseInt(maxtime[1], 10), 0);


	$elem.each(function() {
		var $e = $(this);
		if ($e.data('done')) return true;
		
		$e.find('.tm').timepicker({timeFormat:'h:mm p', minTime: mintime, maxTime: maxtime, interval: interval});
		
		$e.find('.tm').bind('time-change', function(e, d) {
			if (d == false) {
				$e.find('.hid').val('');
			} else {
				$e.find('.hid').val(d.asString('hh:min:ss'));
			}
		});
		
		$e.find('.tm').bind('change', function() {
			if ($(this).val() === '') $e.find('.hid').val('');
		});
		
		if ($e.find('.hid').val()) {
			var d = Date.fromString($e.find('.hid').val(), 'hh:min:ss');
			if (d) {
				$e.find('.tm').val($.fn.timepicker.formatTime('h:mm p', d)).trigger('change.timepicker');
			} else {
				$e.find('.tm,.hid').val('');
			}
		}
	});
}

