/*
* Javascript for the shopping cart
*/


/**
* Adds commas to a number
**/
function addCommas(str) {
    var x = str.split('.');
    str = x[0];

    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(str)) {
        str = str.replace(rgx, '$1,$2');
    }

    return str + '.' + x[1];
}


/**
* The cart view page
**/
function cart_view() {

    function recalc_total() {
        var data = {};

        $('tr.product input.textbox-qty').each(function(){
            data[$(this).attr('name')] = $(this).val();
        });

        $.post('cart/json_update', data, function(data) {
            var money = function(val) {
                var prefix = '$';
                val = Number(val);
                if (val < 0) prefix = '-$';
                val = Math.abs(val);
                return prefix + addCommas(val.toFixed(2));
            };
            var order = data.order;

            $('tr.subtotal td.total').html(money(order.products_subtotal));
            $('tr.freight td.total').html(money(order.freight));
            $('tr.promo td.total').html(money(order.promo_discount));
            $('tr.total td.total').html(money(order.total));
            $('tr.total-tax td.total').html(money(order.total_tax));
        });
    }

    $(document).ready(function() {

        $('tr.product').each(function() {
            var $tr = $(this);

            // Qty textbox
            $tr.find('input.textbox-qty').change(function() {
                var price = $tr.find('td.price').html();
                price = price.replace(/[^\.0-9]/g, '');
                price = parseInt(price, 10);

                var qty = parseInt($(this).val(), 10);

                if (qty < 1) { qty = 1; $(this).val(1) }

                var total = price * qty;

                if (isNaN(total)) {
                    $tr.find('td.total').html('??');
                } else {
                    $tr.find('td.total').html('$' + addCommas(total.toFixed(2)));
                }

                $tr.animate({'backgroundColor' : '#FFFFE0'}, 250).delay(500).animate({'backgroundColor' : '#FFFFFF'}, 1000);

                recalc_total();
            });

            var timeout = 0;
            $tr.find('input.textbox-qty').keypress(function() {
                var $input = $(this);
                window.clearTimeout(timeout);
                timeout = window.setTimeout(function() {
                    if ($input.val() != '') $input.change();
                }, 300);
            });

            // Remove button
            $tr.find('a.btn-remove').click(function() {
                $tr.animate({'opacity' : 0.2}, 1000);
                $tr.find('input.textbox-qty').val('0');
                $tr.find('td.total').html('$0.00');

                recalc_total();
            });

        });

    });

}


