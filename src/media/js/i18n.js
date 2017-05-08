/**
 * Native implementation of the PHP I18n class
 */
var I18n = {
    locale: null,


    /**
     * Set locale parameters.
     * Params match the names in the LocaleInfo class;
     *    decimal_seperator, group_seperator, currency_symbol, currency_decimal
     *
     * @param object locale
     **/
    setLocale: function(locale)
    {
        this.locale = locale;
    },


    /**
     * Format a number according to locale information
     *
     * @param float number Number to format
     * @param int precision Number of digits after the decimal place
     * @return string
     */
    number: function(number, precision)
    {
        var x = number.toFixed(precision);
        var parts = x.toString().split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, this.locale.group_seperator);
        return parts.join(this.locale.decimal_seperator);
    },


    /**
     * Format a money figure according to locale information
     *
     * @param float number Number to format
     * @param int precision Optional precision; defaults to the currency_decimal parameter
     * @return string
     */
    money: function(number, precision)
    {
        if (typeof(precision) === 'undefined') precision = this.locale.currency_decimal;
        return this.locale.currency_symbol + this.number(number, precision);
    }
};