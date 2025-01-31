<?php

use karmabunny\kb\Uuid;
use karmabunny\kb\ValidationException;
use karmabunny\pdb\Exceptions\ConnectionException;
use karmabunny\pdb\Models\PdbRawCondition;
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

        $pdb->delete(ModelItem::getTableName(), [new PdbRawCondition('1=1')]);
    }


    public function testSproutModel()
    {
        // A new model.
        $model = new ModelItem();
        $model->name = 'sprout test';
        $model->status = 'pending';

        $expected = [
            'date_added',
            'name',
            'status',
            'uid',
        ];

        $actual = $model->getSaveData();
        $actual = array_keys($actual);
        sort($actual);

        $this->assertEquals($expected, $actual);

        $this->assertNull($model->uid);
        $this->assertNull($model->date_added);

        // Pdo is opened here.
        $this->assertTrue($model->save(false));
        $this->assertGreaterThan(0, $model->id);

        sleep(1);

        $this->assertNotEquals(Uuid::NIL, $model->uid);
        $this->assertTrue(Uuid::valid($model->uid, 5));

        $this->assertLessThanOrEqual(date('Y-m-d H:i:s'), $model->date_added);

        $uid = $model->uid;
        $added = $model->date_added;

        usleep(500 * 1000);

        $model->name = 'something else';
        $this->assertTrue($model->save(false));

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



    public function testModelRules()
    {
        // A new model.
        $model = new ModelItem();
        $model->name = 'sprout test';
        $model->status = 'pending';

        $ok = $model->save();
        $this->assertTrue($ok);

        // Mess it up a bit.
        $model->id = 0;
        $model->name = '';
        $model->status = 'lol what';
        $errors = $model->valid();

        $this->assertNotTrue($errors);
        $this->assertArrayHasKey('uid', $errors);
        $this->assertStringContainsStringIgnoringCase('unique', $errors['uid'][0]);

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('required', $errors['name']);

        $this->assertArrayHasKey('status', $errors);
        $this->assertStringContainsStringIgnoringCase('invalid', $errors['status'][0]);

        // Still not OK, but we're past the required rule.
        $model->name = '1234';
        $errors = $model->valid();

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayNotHasKey('required', $errors['name']);
        $this->assertStringContainsStringIgnoringCase('shorter', $errors['name'][0]);

        // One more for luck.
        $other = new ModelItem();
        $other->name = 'sprout test';
        $other->status = 'ready';

        try {
            $other->save();
            $this->fail('Expected validation error');

        } catch (ValidationException $exception) {
            $errors = $exception->getErrors();
            $this->assertArrayHasKey('name', $errors);
            $this->assertStringContainsStringIgnoringCase('unique', $errors['name'][0]);
            $this->assertStringContainsStringIgnoringCase('name', $exception->getMessage());
        }

        // Fix it.
        $other->name = 'other test';
        $other->save();
    }
}
