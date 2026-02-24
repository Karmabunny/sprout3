<?php

namespace Sprout\Helpers\Locales;


/**
 * Locale info for the Eurozone; see {@see LocaleInfo}
 *
 * N.B. This is for all countries using the Euro, not just EU member states
 *
 * @see https://en.wikipedia.org/wiki/Eurozone
 */
abstract class LocaleInfoEurozone extends LocaleInfo
{
    protected $decimal_seperator = ',';
    protected $group_seperator = '.';
    protected $currency_symbol = '€';
    protected $currency_name = 'Euro';
    protected $currency_iso = 'EUR';
}
