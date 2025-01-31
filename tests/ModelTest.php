<?php

use karmabunny\kb\Uuid;
use karmabunny\pdb\Exceptions\ConnectionException;
use karmabunny\pdb\PdbParser;
use karmabunny\pdb\PdbSync;
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Pdb;

require_once __DIR__ . '/Models/ModelItem.php';

class ModelTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        try {
            Pdb::query("SELECT 1", [], 'null');
        } catch (ConnectionException $ex) {
            self::markTestSkipped('mysql is not available right now');
        }
    }


    public function setUp(): void
    {
        $pdb = Pdb::getInstance();

        $sync = new PdbSync($pdb);

        $struct = new PdbParser();
        $struct->loadXml(__DIR__ . '/config/db_struct.xml');
        $struct->sanityCheck();

        $sync->updateDatabase($struct);
    }


    public function testSproutModel()
    {
        // A new model.
        $model = new ModelItem();
        $model->name = 'sprout test';

        $expected = [
            'date_added',
            'name',
            'uid',
        ];

        $actual = $model->getSaveData();
        $actual = array_keys($actual);
        sort($actual);

        $this->assertEquals($expected, $actual);

        $this->assertNull($model->uid);
        $this->assertNull($model->date_added);

        // Pdo is opened here.
        $this->assertTrue($model->save());
        $this->assertGreaterThan(0, $model->id);

        sleep(1);

        $this->assertNotEquals(Uuid::NIL, $model->uid);
        $this->assertTrue(Uuid::valid($model->uid, 5));

        $this->assertLessThanOrEqual(date('Y-m-d H:i:s'), $model->date_added);

        $uid = $model->uid;
        $added = $model->date_added;

        usleep(500 * 1000);

        $model->name = 'something else';
        $this->assertTrue($model->save());

        // Some things change, others stay the same.
        $this->assertEquals($added, $model->date_added);
        $this->assertEquals($uid, $model->uid);

        // Fetch a fresh one.
        $other = ModelItem::findOne(['id' => $model->id]);

        // Test it all again.
        $this->assertEquals($model->id, $other->id);
        $this->assertEquals($model->uid, $other->uid);
        $this->assertEquals($model->date_added, $other->date_added);
        $this->assertEquals($model->name, $other->name);
    }
}
