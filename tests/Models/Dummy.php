<?php

use Sprout\Helpers\Model;

/**
 * A dummy model to test properties with native types
 */
class Dummy extends Model
{
    public string $date_added;
    public string $date_modified;
    public string $uid;

    public string $name;
    public bool $bool_val;
    public bool $bool_default_false;
    public bool $bool_default_true;
    public int $int_val;
    public int $int_default_zero;
    public int $int_default_one;
    public float $currency_val;
    public float $float_val;

    /** @var string[] */
    public array $options_db_default;

    /** @var string[] */
    public array $options_model_default = ['c', 'd'];

    /** @var string[] (not actually nullable; only in the DB) */
    public array $options_nullable;

    /** @var array<mixed, mixed> */
    public array $json_db_default;

    /** @var array<mixed, mixed> */
    public array $json_model_default = ['trash' => 'panda', 123];

    /** @var array<mixed, mixed> (not actually nullable; only in the DB) */
    public array $json_nullable;

    public string $non_json;

    public static function getTableName(): string
    {
        return 'model_property_tests';
    }
}
