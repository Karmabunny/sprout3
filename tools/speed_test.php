<?php
use Sprout\Helpers\Pdb;

// The phpunit bootsrap gets confused if this doesn't exist
class PHPUnit_Framework_TestCase {}

// And FirePHP gets confused with cli access if no route
$_SERVER['argv'][1] = 'speedtest';

// Bring in Sprout without actually running a controller
require __DIR__ . '/../src/sprout/phpunit_bootstrap.php';
Kohana::closeBuffers();


class Timing {
    private static $sections;
    private static $section;
    private static $start;

    public static function start($section)
    {
        self::$section = $section;
        self::$start = microtime(true);
    }

    public static function stop()
    {
        $end = microtime(true);
        self::$sections[self::$section] = $end - self::$start;
    }

    public static function report()
    {
        foreach (self::$sections as $name => $time_us) {
            echo str_pad($name, 60);
            echo str_pad(number_format($time_us * 1000, 2), 10, ' ', STR_PAD_LEFT), 'ms';
            echo PHP_EOL;
        }
    }

    public static function clear()
    {
        self::$sections = [];
    }
}


speedTestPdb();
Timing::report();


function speedTestPdb()
{
    $q = "CREATE TEMPORARY TABLE ~speedtest (
        id INT NOT NULL PRIMARY KEY
    )";
    Pdb::query($q, [], 'null');

    Pdb::transact();
    Timing::start('Inserts via Pdb::query');
    for ($i = 1; $i <= 500; $i += 1) {
        $q = "INSERT INTO ~speedtest SET id = ?";
        Pdb::query($q, [$i], 'null');
    }
    Timing::stop();
    Pdb::commit();

    Pdb::transact();
    Timing::start('Inserts via Pdb::insert');
    for ($i = 501; $i <= 1000; $i += 1) {
        Pdb::insert('speedtest', ['id' => $i]);
    }
    Timing::stop();
    Pdb::commit();

    Pdb::transact();
    Timing::start('Inserts via Pdb::prepare + Pdb::execute');
    $q = "INSERT INTO ~speedtest SET id = ?";
    $stmt = Pdb::prepare($q);
    for ($i = 1001; $i <= 1500; $i += 1) {
        Pdb::execute($stmt, [$i], 'null');
    }
    Timing::stop();
    Pdb::commit();

    $q = "DROP TABLE ~speedtest";
    Pdb::query($q, [], 'null');
}
