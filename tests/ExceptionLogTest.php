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
use Sprout\Helpers\Pdb;


class ExceptionLogTest extends TestCase
{


    public function dataRollbacks()
    {
        return [
            ['test: ' . uniqid()],
            ['test: ' . uniqid()],
            ['test: ' . uniqid()],
        ];
    }


    /** @dataProvider dataRollbacks */
    public function testNoRollback($message)
    {
        Pdb::transact();

        $id = Pdb::insert('login_attempts', [
            'date_added' => Pdb::now(),
            'date_modified' => Pdb::now(),
            'username' => 'test',
            'ip' => bin2hex(inet_pton('127.0.0.1')),
            'success' => 1,
            'active' => 1,
        ]);

        $row = Pdb::find('login_attempts')->where(['id' => $id])->one(false);
        $this->assertNotNull($row);

        $exception = new Exception($message);
        $id = Kohana::logException($exception);
        $this->assertNotEmpty($id);

        Pdb::rollback();

        // This proves the rollback worked.
        $row = Pdb::find('login_attempts')->where(['id' => $id])->one(false);
        $this->assertNull($row);

        // However, the exception was not included in the transaction.
        $row = Pdb::find('exception_log')->where(['id' => $id])->one(false);
        $this->assertNotNull($row);
        $this->assertEquals($exception->getMessage(), $row['message']);
    }
}
