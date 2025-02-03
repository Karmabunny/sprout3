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

use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Constants;
use Sprout\Helpers\CoreAdminAuth;

class AdminAuthTest extends TestCase
{

    public static function dataAlgorithms()
    {
        return array(
            [Constants::PASSWORD_SHA_SALT],
            [Constants::PASSWORD_SHA_SALT_5000],
            [Constants::PASSWORD_BCRYPT12],
        );
    }


    /**
    * Does hash creation match hash checking?
    * @dataProvider dataAlgorithms
    **/
    public function testHashMatchCheck($alg)
    {
        if (! CoreAdminAuth::checkAlgorithm($alg)) return;

        list ($a, $b, $c) = CoreAdminAuth::hashPassword('Match', $alg);
        $result = CoreAdminAuth::doPasswordCheck($a, $b, $c, 'Match');
        $this->assertTrue($result);
        $this->assertTrue($alg == $b);

        list ($a, $b, $c) = CoreAdminAuth::hashPassword('Match', $alg);
        $result = CoreAdminAuth::doPasswordCheck($a, $b, $c, 'Do not match');
        $this->assertFalse($result);
        $this->assertTrue($alg == $b);
    }


    /**
    * Does two creations create different hashes? (hashes with salts)
    * @dataProvider dataAlgorithms
    **/
    public function testHashWithSalts($alg)
    {
        if (! CoreAdminAuth::checkAlgorithm($alg)) return;
        list ($a1, $b1, $c1) = CoreAdminAuth::hashPassword('Match', $alg);
        list ($a2, $b2, $c2) = CoreAdminAuth::hashPassword('Match', $alg);
        $this->assertTrue($b1 == $b2);
        $this->assertTrue($alg == $b1);
        $this->assertTrue($a1 != $a2);
        $this->assertTrue($c1 != $c2);
    }


    public function testCheckAlgorithm()
    {
        $this->assertTrue(CoreAdminAuth::checkAlgorithm(Constants::PASSWORD_SHA_SALT));
        $this->assertTrue(CoreAdminAuth::checkAlgorithm(Constants::PASSWORD_SHA_SALT_5000));
        $this->assertFalse(CoreAdminAuth::checkAlgorithm(1234));
    }

}
