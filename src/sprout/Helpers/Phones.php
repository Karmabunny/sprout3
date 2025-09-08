<?php
/*
 * Copyright (C) 2024 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Helpers;

use karmabunny\kb\ValidationException;
use Kohana;
use libphonenumber\geocoding\PhoneNumberOfflineGeocoder;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Normalizer;
use Sprout\Helpers\Locales\LocaleInfo;
use Sprout\Helpers\PhpView;
use utf8;

/**
 * Helpers for common phone number functions
 *
 * RECOMMENDATION:
 * In your data, store a `phone` and a `phone_code` side by side
 * Use Phones::compoundFormField() to render a compound form field for a phone number
 * Run the `phone` value through one of the `cleanNumber` helpers to get a clean number to store
 *
 * USAGE:
 * Use the `Phones::compareNumbers()` helper to compare two phone numbers
 * Use the `Phones::compareNumbersWithCode()` helper to compare two phone numbers by country code string
**/
class Phones
{
    /**
     * Normalisation patterns.
     *
     * https://www.php.net/manual/en/regexp.reference.unicode.php#128778
     */
    const NORMALIZE = [
        ' ' => '/\s+/u',
        '-' => '/\p{Pd}+/u',
        '(' => '/\p{Ps}+/u',
        ')' => '/\p{Pc}+/u',
        '' => '/[\p{Cc}\p{Cf}]+/u',
    ];


    /**
     * Get the default country code in alpha2 based on alpha3 core config
     *
     * @return string
     */
    private static function getDefaultCountryAlpha2(): string
    {
        static $default_country;
        if ($default_country) {
            return $default_country;
        }

        $alpha3 = Kohana::config('config.default_country_code');
        $default_country = array_search($alpha3, CountryConstants::$alpha2to3);
        return $default_country;
    }


    /**
     * Render a compound form field for a phone number. Data should come from `Form`
     *
     * <?php echo Phones::compoundFormField(true, 'Phone (mobile)'); ?>
     *
     * @param bool $required
     * @param string $label
     * @param array|null $field_names Optionally override the default field names [phone field, phone_code field]
     * @param array|null $common Optionally override the default list of common country codes
     * @return string
     */
    public static function compoundFormField(bool $required, string $label = null, ?array $field_names = null, ?array $common = null): string
    {
        $field_names = $field_names ?? ['phone', 'phone_code'];
        $view = new PhpView('sprout/phone_field_compound');
        $view->required = $required;
        $view->label = $label;
        $view->field_names = $field_names;
        $view->common = $common;

        return $view->render();
    }


    /**
     * Get a list of country codes and their names for dropdown use
     *
     * @return array
     */
    public static function getCountryPhoneCodeOptions(): array
    {
        static $codes;
        if ($codes) {
            return $codes;
        }

        // Iterate all LocaleInfoXX classes to get phone codes and country names
        $codes = [];
        foreach (CountryConstants::$alpha3 as $code => $country_name) {
            $class = "Sprout\Helpers\Locales\LocaleInfo{$code}";
            if (class_exists($class)) {
                $instance = new $class();
                $phone_code = ltrim($instance->getPhoneCode(), '+');
                $codes[$phone_code] = "{$country_name} (+{$phone_code})";
            }
        }

        $codes = array_filter($codes, function($k) {
            return !empty($k);
        }, ARRAY_FILTER_USE_KEY);

        // Join US and Canada, make first array entry. Retain original keys
        unset($codes['1']);
        $codes = ['1' => 'United States & Canada (+1)'] + $codes;

        return $codes;
    }


    /**
     * Get a list of country codes and their names
     *
     * Set common to an explicitly empty array `[]` for a single level list
     *
     * @param array|null $common Override the default list of common country codes, using alpha3 codes
     * @return array
     */
    public static function countryPhoneCodeOptGroups(?array $common = null): array
    {
        $base_opts = self::getCountryPhoneCodeOptions();
        if ($common === []) {
            return $base_opts;
        }

        $common = $common ?? Kohana::config('config.common_phone_codes');
        $common_codes = array_intersect_key($base_opts, array_flip($common));
        $other_codes = array_diff_key($base_opts, $common_codes);

        return [
            'Popular' => $common_codes,
            'Others' => $other_codes,
        ];
    }


    /**
     * Add a country phone code to a phone number
     *
     * @param string $phone_number
     * @param string $phone_code
     * @return string
     */
    public static function numberWithCountryCode(string $phone_number, string $phone_code): string
    {
        $phone_number = self::cleanStripCountryCode($phone_number, $phone_code);
        return "{$phone_code}{$phone_number}";
    }


    /**
     * Trim, clean + normalise.
     *
     * Our data can get pretty dirty. So dirty that our formatter/parser
     * library can't do the basics.
     *
     * This doesn't normalise aggressively, only to get some characters codes
     * into basic ASCII. We leave the rest to the parser.
     *
     * @param string $number
     * @param int $form Normalizer::NFC
     * @return string
     */
    public static function cleanNumber(string $number, $form = Normalizer::NFC): string
    {
        // First, remove all non-numeric characters except the plus sign
        $number = utf8::clean($number);
        $number = preg_replace('/[^0-9+]/', '', $number);
        $number = Normalizer::normalize($number, $form);

        $number = preg_replace(array_values(self::NORMALIZE), array_keys(self::NORMALIZE), $number);

        return $number;
    }


    /**
     * Clean a phone number then remove leading 0, +, 00, country code and non-numeric characters
     *
     * @param string $number
     * @param string $phone_code
     * @param int $form Normalizer::NFC
     * @return string
     */
    public static function cleanStripCountryCode(string $number, string $phone_code, $form = Normalizer::NFC): string
    {
        $number = self::cleanNumber($number, $form);

        // Ensure we have a '+61' format code
        if (substr($phone_code, 0, 1) === '+') {
            $phone_code_no_plus = substr($phone_code, 1);
        } else {
            $phone_code_no_plus = $phone_code;
            $phone_code = "+{$phone_code}";
        }

        // Trim country code from start of number if present as a number or a +code
        if (str_starts_with($number, $phone_code)) {
            $number = substr($number, strlen($phone_code));
        } elseif (str_starts_with($number, $phone_code_no_plus)) {
            $number = substr($number, strlen($phone_code_no_plus));
        }

        // Check for leading double zeros (international format)
        if (str_starts_with($number, '00' . $phone_code_no_plus)) {
            $number = substr($number, strlen('00' . $phone_code_no_plus));
        }

        // Remove leading 0
        $number = ltrim($number, '0');

        // Remove any remaining non-numeric characters
        return preg_replace('/[^0-9]/', '', $number);
    }


    /**
     * Format a phone number.
     *
     * @param string $number
     * @param int|PhoneNumberFormat $format
     * @return string
     * @throws NumberParseException
     */
    public static function format(string $number, $format = PhoneNumberFormat::E164): string
    {
        $lib = PhoneNumberUtil::getInstance();
        $default_country = self::getDefaultCountryAlpha2();
        $parsed = self::parse($lib, $number, $default_country);
        return $lib->format($parsed, $format);
    }


    /**
     * A sprout compatible validator.
     *
     * @param string $number
     * @param string|null $country
     * @throws ValidationException
     */
    public static function validate(string $number, ?string $country = null)
    {
        $number = self::cleanNumber($number);

        // Empty strings, not a problem.
        if (strlen($number) == 0) {
            return;
        }

        try {
            $lib = PhoneNumberUtil::getInstance();
            $parsed = $lib->parse($number, $country ?? self::getDefaultCountryAlpha2());

            if (!$lib->isValidNumber($parsed)) {
                throw new NumberParseException(NumberParseException::NOT_A_NUMBER, "Invalid phone number: {$number}");
            }
        }
        catch (NumberParseException $error) {
            throw new ValidationException($error->getMessage());
        }
    }


    /**
     * Is this a mobile number?
     *
     * @param string $number
     * @return bool
     * @throws NumberParseException
     */
    public static function isMobileNumber(string $number): bool
    {
        $lib = PhoneNumberUtil::getInstance();
        $parsed = self::parse($lib, $number, self::getDefaultCountryAlpha2());
        $type = $lib->getNumberType($parsed);

        return $type === PhoneNumberType::MOBILE;
    }


    /**
     * The number type as a string.
     *
     * - FIXED_LINE
     * - MOBILE
     * - FIXED_LINE_OR_MOBILE
     * - TOLL_FREE
     * - PREMIUM_RATE
     * - SHARED_COST
     * - VOIP
     * - PERSONAL_NUMBER
     * - PAGER
     * - UAN
     * - UNKNOWN
     * - EMERGENCY
     * - VOICEMAIL
     * - SHORT_CODE
     * - STANDARD_RATE
     *
     * @param string $number
     * @return string
     * @throws NumberParseException
     */
    public static function getNumberType(string $number): string
    {
        $lib = PhoneNumberUtil::getInstance();
        $types = PhoneNumberType::values();

        $parsed = self::parse($lib, $number, self::getDefaultCountryAlpha2());
        $type = $lib->getNumberType($parsed);

        return $types[$type] ?? 'UNKNOWN';
    }


    /**
     * Get the alpha-2 country code.
     *
     * Unlike the other helpers, this doesn't use the default country code.
     *
     * @param string $number
     * @return string|null ISO alpha-2 country code
     * @throws NumberParseException
     */
    public static function lookupCountry(string $number): ?string
    {
        $lib = PhoneNumberUtil::getInstance();

        if (strpos(trim($number), '+') !== 0) {
            return null;
        }

        $parsed = self::parse($lib, $number, 'ZZ');
        $country = $parsed->getCountryCode();

        if ($country === null) {
            return null;
        }

        $code = $lib->getRegionCodeForCountryCode($country);
        return $code;
    }


    /**
     * Get the region name.
     *
     * Like; Adelaide, Redfern, India, New Zealand, etc.
     *
     * @param string $number
     * @param string $locale
     * @return string
     * @throws NumberParseException
     */
    public static function lookupRegion(string $number, string $locale = 'en'): string
    {
        $lib = PhoneNumberUtil::getInstance();
        $geo = PhoneNumberOfflineGeocoder::getInstance();

        $parsed = self::parse($lib, $number, self::getDefaultCountryAlpha2());
        $region = $geo->getDescriptionForValidNumber($parsed, $locale);

        return $region;
    }


    /**
     * Compare two phone numbers, using default config country code
     *
     * Note: it's better to specify your country code wherever possible, using self::compareWithCode
     *
     * @param string $number_1
     * @param string $number_2
     * @return bool Whether the numbers are the same
     */
    public static function compareNumbers(string $number_1, string $number_2): bool
    {
        // Use the local default country code in case it's present
        $locale =  LocaleInfo::get(Kohana::config('config.default_country_code'));
        $clean_1 = self::numberWithCountryCode($number_1, $locale->getPhoneCode());
        $clean_2 = self::numberWithCountryCode($number_2, $locale->getPhoneCode());

        return $clean_1 === $clean_2;
    }


    /**
     * Compare two phone numbers by their clean numbers, using country code strings
     *
     * @param string $number_1
     * @param string $code_1
     * @param string $number_2
     * @param string $code_2
     * @return bool Whether the numbers are the same
     */
    public static function compareWithCode(string $number_1, string $code_1, string $number_2, string $code_2): bool
    {
        $clean_1 = self::numberWithCountryCode($number_1, $code_1);
        $clean_2 = self::numberWithCountryCode($number_2, $code_2);

        return $clean_1 === $clean_2;
    }


    /**
     * Internal parser with cleaning, returning a lib PhoneNumber.
     *
     * @param PhoneNumberUtil $lib
     * @param string $number
     * @param string $country ISO alpha-2 country code
     * @return PhoneNumber
     * @throws NumberParseException
     */
    public static function parse(PhoneNumberUtil $lib, string $number, string $country): PhoneNumber
    {
        $number = self::cleanNumber($number);
        
        // Check if the country code is valid before parsing
        $supportedRegions = $lib->getSupportedRegions();
        if ($country != 'ZZ' and !in_array($country, $supportedRegions)) {
            throw new NumberParseException(NumberParseException::INVALID_COUNTRY_CODE, "Invalid country code: {$country}");
        }

        // If the number starts with +, we can determine the country code from it
        if (strpos($number, '+') === 0) {
            try {
                $parsed = $lib->parse($number, 'ZZ');
                $country = $lib->getRegionCodeForCountryCode($parsed->getCountryCode());
            } catch (NumberParseException $e) {
                // If we can't parse with ZZ, use the provided country
                // But if the provided country is invalid, throw an exception
                if (!in_array($country, $lib->getSupportedRegions())) {
                    throw new NumberParseException(NumberParseException::INVALID_COUNTRY_CODE, "Invalid country code: {$country}");
                }
            }
        }

        // Parse again with the correct country code
        $parsed = $lib->parse($number, $country);

        if (!$lib->isValidNumber($parsed)) {
            throw new NumberParseException(NumberParseException::NOT_A_NUMBER, "Invalid phone number: {$number}");
        }

        return $parsed;
    }

}
