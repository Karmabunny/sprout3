<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use Jose\Component\Signature\Algorithm\HS256;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWS;
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Jwt;

class JwtTest extends TestCase
{

    const TEST_SECRET = '5c3eeac7-9b2b-9b2b-9b2b-6b25ed879093';

    private function getDefaultPayload(): array
    {
        return [
            'iss' => 'SproutCMS',
            'sub' => 'SproutCMS',
            'aud' => 'SproutCMS',
            'iat' => strtotime('2020-01-01 12:00:00'),
            'exp' => strtotime('2030-01-01 12:00:00'),
        ];
    }


    private function getDefaultJwt()
    {
        return 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.'
        . 'eyJpc3MiOiJTcHJvdXRDTVMiLCJzdWIiOiJTcHJvdXRDTVMiLCJhdWQiOiJTcHJvdXRDTVMiLCJpYXQiOjE1Nzc4NDIyMDAsImV4cCI6MTg5MzQ2MTQwMH0.'
        .'m6pjecPt1NRMFHZcZl646obXF7tA_RYu8YYqjTPZX-M';
    }


    private function getKeyFilePaths(): array
    {
        return [
            'public' => __DIR__ . '/keys/jwt_public_key.pem',
            'private' => __DIR__ . '/keys/jwt_private_key.pem',
        ];
    }


    public function testCreateDecode()
    {
        $payload = $this->getDefaultPayload();
        $jws = Jwt::createJwsWithKeyString($payload, self::TEST_SECRET, new HS256());

        $this->assertInstanceOf(JWS::class, $jws);

        $jwt = Jwt::serialize($jws);
        $this->assertEquals($this->getDefaultJwt(), $jwt);

        $payload = Jwt::getPayload($jwt);
        $this->assertEquals($this->getDefaultPayload(), $payload);
    }


    public function testVerifyWithKeyString()
    {
        $payload = $this->getDefaultPayload();
        $jws = Jwt::createJwsWithKeyString($payload, self::TEST_SECRET, new HS256());

        $valid = Jwt::verifyWithKeyString($jws, self::TEST_SECRET, [new HS256()]);

        $this->assertTrue($valid);
    }


    public function testVerifyWithKeyFile()
    {
        $keys = $this->getKeyFilePaths();

        $payload = $this->getDefaultPayload();
        $jws = Jwt::createJwsWithKeyFile($payload, realpath($keys['private']), new RS256());

        $valid = Jwt::verifyWithKeyFile($jws, realpath($keys['private']), [new RS256()]);

        $this->assertTrue($valid);
    }
}
