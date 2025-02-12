<?php
/*
 * Copyright (C) 2025 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 */
use PHPUnit\Framework\TestCase;
use Sprout\Models\FileModel;


class FileModelTest extends TestCase
{
    public function testDelete()
    {
        $file = STORAGE_PATH . 'temp/test-delete.txt';
        file_put_contents($file, 'test delete');

        $model = FileModel::fromPath([
                'name' => 'Test delete',
                'filename' => $file,
            ],
            $file);

        $model->save();

        $this->assertTrue($model->delete());
        $this->assertFalse(file_exists(WEBROOT . "files/{$model->filename}"));
    }


    public function testMoveFile()
    {
        $file = STORAGE_PATH . 'temp/test-move.txt';
        file_put_contents($file, 'test move');

        $model = FileModel::fromPath([
                'name' => 'Test move',
                'filename' => $file,
            ],
            $file);

        $model->save();

        $this->assertTrue($model->moveFile('test-renamed.txt'));
        $this->assertTrue(file_exists(WEBROOT . "files/test-renamed.txt"));
        $this->assertFalse(file_exists(WEBROOT . "files/test-move.txt"));
    }
}
