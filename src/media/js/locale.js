/**
 * Updates the address fields on a form with fields and labels appropriate for
 * a given country
 *
 * @param string country The DOM node with the country input field; its value
 *        must be an ISO 3166-1 alpha-3 code
 * @param bool required
 */
function update_address_fields(country, required) {
    var fields = {};
    $('#address input,#address select').each(function() {
        fields[$(this).attr('name')] = $(this).val();
    });

    var url = ROOT + 'locale/get_address_fields';
    if (required) url += '_required';
    url += '/' + encodeURIComponent($(country).val());

    // Determine prefix and pass through
    var prefix_match = $(country).attr('name').match(/^(.+)country$/);
    if (prefix_match) {
        url += '?prefix=' + encodeURIComponent(prefix_match[1]);
    }

    $.get(url, function(html) {
        var $new_html = $('<div></div>').html(html);
        var $new_field, $new_label, $replacement_field;
        var $wrapper, $field, $label, id;

        for (var name in fields) {
            $new_field = $new_html.find('[name=' + name + ']');
            if ($new_field.length < 1) continue;

            $replacement_field = $new_field.clone();

            $new_label = $new_html.find('label[for=' + $new_field.attr('id') + ']');

            $field = $('#address').find('[name=' + name + ']');
            id = $field.attr('id');
            $label = $('#address').find('label[for=' + id + ']');
            $wrapper = $field.closest('.field-element');

            $replacement_field.val(fields[name]);

            // Reset selects (e.g. State) which don't contain the desired value to 'Select an option'
            // instead of appearing completely blank
            if ($replacement_field.val() != fields[name]) {
                $replacement_field.val('');
            }

            // Don't clobber id attributes when replacing existing element
            $replacement_field.attr('id', id);

            // Update element and label
            $field.replaceWith($replacement_field);
            $label.html($new_label.html());

            // Hide both label and input if necessary
            if ($new_field.closest('.field-element').css('display') == 'none') {
                $wrapper.hide();
            } else {
                $wrapper.show();
            }
        }
    });
}
