<?php

namespace Sprout\Helpers\Locales;


/**
 * Locale info for Vatican City; see {@see LocaleInfo}
 */
class LocaleInfoVAT extends LocaleInfoEurozone
{
    // Uses Italy's code, although formally it has code 379 assigned
    protected $phone_code = '39';
}
