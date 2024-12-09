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

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Sprout\Exceptions\JwtException;

/**
 * Common Jws/Jwt methods
 *
 */
class Jwt
{

    /**
     * Unserialize a JWS from a string
     *
     * @param string $jws
     * @return JWS
     */
    public static function unserialize(string $jws): JWS
    {
        $serializer = new CompactSerializer();
        return  $serializer->unserialize($jws);
    }


    /**
     * Get the payload from either a raw string or a JWS object
     *
     * @param JWS|string $jws
     * @return array
     */
    public static function getPayload(JWS|string $jws): array
    {
        if (!$jws instanceof JWS) {
            $jws = self::unserialize($jws);
        }

        return json_decode($jws->getPayload(), true);
    }


    /**
     * Common payload validation on expiry and issuance dates
     *
     * @param array $payload
     * @param bool $throw_on_error
     * @return bool
     * @throws JwtException
     */
    public static function validatePayload(array $payload, bool $throw_on_error): bool
    {
        if (empty($payload['exp'])) {
            if ($throw_on_error) {
                throw new JwtException('Token expiry not set');
            }
            return false;
        }

        if ($payload['exp'] < time()) {
            if ($throw_on_error) {
                throw new JwtException('Token expired');
            }
            return false;
        }

        if (!empty($payload['iat']) and $payload['iat'] > time()) {
            if ($throw_on_error) {
                throw new JwtException('Token not yet issued');
            }
            return false;
        }

        return true;
    }


    /**
     * Verify a JWS with a specified key, within a range of acceptable algorithms
     *
     * @param JWS $jws
     * @param string $signing_key A key string to validate against
     * @param array $algorithm_managers e.g. $algorithm_managers = [new HS256()];
     * @return bool
     */
    public static function verifyWithKeyJWS(JWS $jws, string $signing_key, array $algorithm_managers): bool
    {
        $algorithmManager = new AlgorithmManager($algorithm_managers);
        $jwsVerifier = new JWSVerifier($algorithmManager);

        $jwk = JWKFactory::createFromSecret($signing_key);

        return $jwsVerifier->verifyWithKey($jws, $jwk, 0);
    }

}
