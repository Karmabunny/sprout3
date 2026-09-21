<?php

use karmabunny\pdb\PdbParser;
use karmabunny\pdb\PdbSync;
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Pdb;

require_once __DIR__ . '/Models/Dummy.php';

/**
 * Tests for models using natively-typed properties
 *
 * e.g. `public int $id`, `protected float $some_value`
 */
class DummyModelTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $pdb = Pdb::getInstance();

        $sync = new PdbSync($pdb);

        $struct = new PdbParser();
        $struct->loadXml(__DIR__ . '/config/db_struct.xml');
        $struct->sanityCheck();

        $sync->updateDatabase($struct);
    }

    public function setUp(): void
    {
        Pdb::query('TRUNCATE ~model_property_tests', [], 'null');
    }

    /**
     * Test that saving and loading data using native types works,
     * including SET and JSON fields with default values
     */
    public function testSaveLoad(): void
    {
        $name = 'Test save and load data';
        $bool_val = false;
        $int_val = 3;
        $currency_val = 13.95;
        $float_val = 1234.56789;

        $dummy = new Dummy();
        $dummy->name = $name;
        $dummy->bool_val = $bool_val;
        $dummy->int_val = $int_val;
        $dummy->currency_val = $currency_val;
        $dummy->float_val = $float_val;
        $dummy->save();

        $models = Dummy::findAll();
        $this->assertEquals(count($models), 1);

        // Confirm that all data saved and loaded correctly from native types
        /** @var Dummy $dummy */
        $dummy = reset($models);
        $this->assertEquals($dummy->name, $name);
        $this->assertEquals($dummy->bool_val, $bool_val);
        $this->assertEquals($dummy->bool_default_false, false); // from default defined in DB
        $this->assertEquals($dummy->bool_default_true, true); // from default defined in DB
        $this->assertEquals($dummy->int_val, $int_val);
        $this->assertEquals($dummy->int_default_zero, 0); // from default defined in DB
        $this->assertEquals($dummy->int_default_one, 1); // from default defined in DB
        $this->assertEquals($dummy->currency_val, $currency_val);
        $this->assertEquals($dummy->float_val, $float_val);
        $this->assertEquals($dummy->options_db_default, ['a', 'b']); // from default defined in DB
        $this->assertEquals($dummy->options_model_default, ['c', 'd']); // from default defined in model
        $this->assertEquals($dummy->json_db_default, [1, 2, ['a' => 'b', 3 => 9]]); // from default defined in DB
        $this->assertEquals($dummy->json_model_default, ['trash' => 'panda', 123]); // from default defined in model
        $this->assertEquals($dummy->non_json, '{"do not parse JSON": "for a non-array attribute"}');

        $new_bool = true;
        $new_int = 5;
        $new_options = ['a', 'b', 'c'];
        $new_json = ['x' => 1, 'y' => 543.21, 'z' => ['ciao' => 'hola']];
        $dummy->bool_val = $new_bool;
        $dummy->int_val = $new_int;
        $dummy->options_db_default = $new_options;
        $dummy->options_model_default = $new_options;
        $dummy->json_db_default = $new_json;
        $dummy->json_model_default = $new_json;
        $dummy->save();

        // Confirm that new data saved and loaded correctly from native types
        /** @var Dummy $dummy */
        $dummy = Dummy::find(['id' => $dummy->id])->one();
        $this->assertEquals($dummy->bool_val, $new_bool);
        $this->assertEquals($dummy->int_val, $new_int);
        $this->assertEquals($dummy->options_db_default, $new_options);
        $this->assertEquals($dummy->options_model_default, $new_options);
        $this->assertEquals($dummy->json_db_default, $new_json);
        $this->assertEquals($dummy->json_model_default, $new_json);
    }

    /**
     * Test that overriding defaults works sanely
     */
    public function testOverrideDefaults(): void
    {
        $dummy = new Dummy();
        $dummy->name = 'Test override defaults';
        $dummy->bool_val = true;
        $dummy->int_val = 100;
        $dummy->currency_val = 1.0;
        $dummy->float_val = 123.45;
        $dummy->bool_default_false = true;
        $dummy->bool_default_true = false;
        $dummy->int_default_zero = 1;
        $dummy->int_default_one = 0;
        $dummy->save();

        $models = Dummy::findAll();
        $this->assertEquals(count($models), 1);

        /** @var Dummy $dummy */
        $dummy = reset($models);
        $this->assertEquals($dummy->bool_default_false, true);
        $this->assertEquals($dummy->bool_default_true, false);
        $this->assertEquals($dummy->int_default_zero, 1);
        $this->assertEquals($dummy->int_default_one, 0);
    }

    /**
     * Test that creation using DB defaults doesn't cause a TypeError
     */
    public function testFindOrCreate(): void
    {
        $dummy = Dummy::findOrCreate([
            'name' => 'Test by findOrCreate'
        ]);

        // Useless assertion just to ensure no TypeError occurs
        $this->assertInstanceOf(Dummy::class, $dummy);
    }
}
