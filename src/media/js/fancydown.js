(function($) {
    var $currfancydown = null;

    /**
    * Creates FancyDown fields - fancy HTML dropdowns
    * For when you need a little more styling control...
    * The selector should be for one or more SELECT elements
    **/
    $.fn.FancyDown = function() {
        return this.each(function() {
            var $fancydown = null;
            var $hidden = null;
            var items = {};
            var klass = $(this).attr('class');

            var html = null, sel = null;


            // Find items
            $(this).find('option').each(function() {
                items[$(this).attr('value')] = $(this).text();

                if (sel === null && ($(this).attr('selected') === 'selected' || $(this).attr('selected') === true)) {
                    sel = $(this).attr('value');
                }
            });

            if (sel == null) {
                sel = $(this).find('option').attr('value');
            }


            // Build the dropdown
            html  = '<div class="fancydown ' + klass + '" data-name="' + $(this).attr('name') + '">';
            html += '<span class="t">' + items[sel] + '</span>';
            html += '<span class="b"></span>';
            html +=' <input type="hidden" name="' + $(this).attr('name') + '" value="' + sel + '">';
            html += '</div>';

            $fancydown = $(html);
            $hidden = $fancydown.find('input');

            $(this).replaceWith($fancydown);


            // Free mem
            html = null;
            sel = null;


            // Attach handlers
            $fancydown.click(function() {
                var html;
                var $drop;

                $('#fancydown').remove();

                if ($currfancydown == $fancydown) {
                    $currfancydown = null;
                    return;
                }

                html  = '<div id="fancydown" class="' + klass + '">';
                for (val in items) {
                    html += '<span class="i" data-val="' + val + '">' + items[val] + '</span>';
                }
                html += '</div>';
                $('body').append(html);

                $drop = $('#fancydown');

                $drop.css('position', 'absolute');
                $drop.css('top', $fancydown.offset().top + $fancydown.outerHeight());
                $drop.css('left', $fancydown.offset().left);
                $drop.css('width', $fancydown.width());

                $drop.find('span[data-val=' + $hidden.val() + ']').addClass('h');

                $drop.find('span.i').mouseover(function () {
                    $('#fancydown').find('span.i').removeClass('h');
                    $(this).addClass('h');
                });

                $drop.find('span.i').click(function() {
                    $fancydown.find('span.t').text($(this).text());
                    $hidden.val($(this).attr('data-val'));

                    $currfancydown = null;
                    $('#fancydown').remove();

                    $fancydown.change();
                });

                $currfancydown = $fancydown;
            });

        });
    };

})(jQuery);
