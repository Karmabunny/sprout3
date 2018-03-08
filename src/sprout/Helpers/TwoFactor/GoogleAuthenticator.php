<?php
/*
 * Copyright (c) 2011 Liip AG
 * Copyright (c) 2018 Karmabunny Pty Ltd
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Sprout\Helpers\TwoFactor;


class GoogleAuthenticator
{
    private static $PASS_CODE_LENGTH = 6;
    private static $SECRET_LENGTH = 20;   // 20 bytes = 160 bits



    /**
     * Check a given code against a given secret
     *
     * Does the check three times, for current time, one period before, and one period after
     * This is to allow for clock drift
     *
     * @param string $secret The secret key, as a base32 string
     * @param string $code Numeric code entered by the user to be checked
     * @return bool True if code matches, false if it does not
     */
    public function checkCode($secret, $code)
    {
        $time = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            if ($this->getCode($secret, $time + $i) == $code) {
                return true;
            }
        }

        return false;
    }


    /**
     * Calculate what the code for a given secret/time should be
     *
     * @param string $secret The secret key, as a base32 string
     * @param int $time Unix timestamp, floored to a 30-sec increment
     * @return string The calculated code, numeric
     */
    protected function getCode($secret, $time)
    {
        $base32 = new FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', TRUE, TRUE);
        $secret = $base32->decode($secret);

        // Compute the HMAC hash H with C as the message and K as the key
        // K should be passed as it is, C should be passed as a raw 64-bit unsigned integer.
        $time = pack('N', $time);
        $time = str_pad($time, 8, chr(0), STR_PAD_LEFT);
        $hash = hash_hmac('sha1', $time, $secret, true);

        // Take the least 4 significant bits of H and use it as an offset, O.
        $offset = ord(substr($hash, -1));
        $offset = $offset & 0xF;

        // Take 4 bytes from H starting at O bytes MSB, discard the most significant bit
        // and store the rest as an (unsigned) 32-bit integer, I.
        $portion = substr($hash, $offset, 4);
        $truncatedHash = unpack('N', $portion);
        $truncatedHash = $truncatedHash[1] & 0x7FFFFFFF;

        // The token is the lowest N digits of I in base 10.
        // If the result has fewer digits than N, pad it with zeroes from the left.
        $pinModulo = pow(10, self::$PASS_CODE_LENGTH);
        $pinValue = str_pad($truncatedHash % $pinModulo, 6, '0', STR_PAD_LEFT);

        return $pinValue;
    }


    /**
     * For a given set of details, return the otpauth:// url for use in a QR code
     *
     * @param string $issuer The name of the entity issuing the token (e.g. the website, company, etc)
     * @param string $user Username who is receiving the token
     * @param string $host Hostname where the token is issued
     * @param string $secret Randomly-generated secret key, as a base32 string
     * @return string URL with the otpauth:// scheme
     */
    public function getQRData($issuer, $user, $host, $secret)
    {
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
        ]);
        return 'otpauth://totp/' . urlencode($issuer . ':' . $user . '@' . $host) . '?' . $params;
    }


    /**
     * Return a Google Charts url which generates a QR code image from some QR code data
     *
     * @param string $qr_data Data for the QR code
     * @return string Image URL
     */
    public function getQRImageUrl($qr_data)
    {
        return 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode($qr_data);
    }


    /**
     * Generate a secret key and encode as base32
     *
     * NOTE: Only cryptographically secure in PHP 7.0 onwards (uses "random_bytes" method)
     * On earlier versions of PHP this method isn't available so rand() is used instead
     *
     * @return string Randomly generated secret key
     */
    public function generateSecret()
    {
        if (function_exists('random_bytes')) {
            $secret = random_bytes(self::$SECRET_LENGTH);
        } else {
            $secret = '';
            for ($i = 1; $i <= self::$SECRET_LENGTH; $i++) {
                $c = rand(0, 255);
                $secret .= pack('c', $c);
            }
        }
        $base32 = new FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', TRUE, TRUE);
        return $base32->encode($secret);
    }

}
