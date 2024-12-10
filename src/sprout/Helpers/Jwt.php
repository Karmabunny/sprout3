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

use InvalidArgumentException;
use Jose\Component\Core\Algorithm;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\JWS;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use LogicException;
use RuntimeException;
use Sprout\Exceptions\JwtException;

/**
 * Common Jws/Jwt methods
 *
 * For spec and definitions,
 * @see: https://datatracker.ietf.org/doc/html/rfc7519#section-4.1
 */
class Jwt
{

    /**
     * Serialize a JWS to a string
     *
     * @param JWS $jws
     * @return string
     */
    public static function serialize(JWS $jws): string
    {
        $serializer = new CompactSerializer();
        return $serializer->serialize($jws, 0);
    }

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
     * Create a JWS with a specified key
     *
     * @param array $payload
     * @param string $signing_key A key string to sign with
     * @param Algorithm $algorithm A specific algorithm eg RS256 or HS256
     * @param array $additional_values
     * @return JWS
     */
    public static function createJwsWithKeyString(array $payload, string $signing_key, Algorithm $algorithm, array $additional_values = []): JWS
    {
        $jwk = JWKFactory::createFromSecret($signing_key, $additional_values);

        return self::buildWithJwk($payload, $jwk, $algorithm);
    }


    /**
     * Create a JWS with a specified key (pem) via file path
     *
     * @param array $payload
     * @param string $signing_key_path A key location to sign with
     * @param Algorithm $algorithm A specific algorithm eg RS256 or HS256
     * @param array $additional_values
     * @return JWS
     */
    public static function createJwsWithKeyFile(array $payload, string $signing_key_path, Algorithm $algorithm, array $additional_values = []): JWS
    {
        $jwk = JWKFactory::createFromKeyFile(
            $signing_key_path,
            null,
            $additional_values
        );

        return self::buildWithJwk($payload, $jwk, $algorithm);
    }


    /**
     * Create a JWS with a specified key and a given algorithm
     *
     * @param array $payload
     * @param JWK $jwk
     * @param Algorithm $algorithm
     * @return JWS
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function buildWithJwk(array $payload, JWK $jwk, Algorithm $algorithm): JWS
    {
        $algorithm_manager = new AlgorithmManager([$algorithm]);

        // Create a JWS builder using the algorithm manager
        $jws_builder = new JWSBuilder($algorithm_manager);

        // Build the JWS using the provided payload and signing key
        $jws = $jws_builder
            ->create()
            ->withPayload(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
            ->addSignature(
                $jwk,
                [
                    'alg' => $algorithm->name(), // e.g. "HS256"
                    'typ' => 'JWT'
                ]
            )
            ->build();

        return $jws;
    }


    /**
     * Verify a JWS with a specified key, within a range of acceptable algorithms
     *
     * @param JWS $jws
     * @param string $signing_key A key string to validate against
     * @param array $algorithms e.g. $algorithms = [new HS256()];
     * @return bool
     */
    public static function verifyWithKeyString(JWS $jws, string $signing_key, array $algorithms): bool
    {
        $algorithmManager = new AlgorithmManager($algorithms);
        $jwsVerifier = new JWSVerifier($algorithmManager);

        $jwk = JWKFactory::createFromSecret($signing_key);

        return $jwsVerifier->verifyWithKey($jws, $jwk, 0);
    }


    /**
     * Verify a JWS with a specified key file, within a range of acceptable algorithms
     *
     * @param JWS $jws
     * @param string $signing_key_path A key location to validate against
     * @param array $algorithms e.g. $algorithms = [new HS256()];
     * @return bool
     */
    public static function verifyWithKeyFile(JWS $jws, string $signing_key_path, array $algorithms): bool
    {
        $algorithmManager = new AlgorithmManager($algorithms);
        $jwsVerifier = new JWSVerifier($algorithmManager);

        $jwk = JWKFactory::createFromKeyFile($signing_key_path);

        return $jwsVerifier->verifyWithKey($jws, $jwk, 0);
    }

}
