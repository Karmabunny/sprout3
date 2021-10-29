<?php
namespace Sprout\Models;

use DateTimeImmutable;
use Sprout\Helpers\Category;
use karmabunny\kb\Collection;
use karmabunny\pdb\Pdb;
use karmabunny\pdb\PdbQuery;


class CategoryModel extends Collection
{
    /** @var int */
    public $id = 0;

    /** @var string */
    public $name;

    /** @var string */
    public $date_added;

    /** @var string */
    public $date_modified;


    /** @inheritdoc */
    public static function getConnection(): Pdb
    {
        return \Sprout\Helpers\Pdb::getInstance();
    }


    /**
     *
     * @return DateTimeInterface
     */
    public function getDateAdded()
    {
        return new DateTimeImmutable($this->date_added);
    }


    /**
     *
     * @return DateTimeInterface
     */
    public function getDateModified()
    {
        return new DateTimeImmutable($this->date_modified);
    }


/**
 * Create a query for this model.
 *
 * @param array $main_table
 * @param array $conditions
 * @return PdbQuery
 */
public static function find(string $main_table, array $conditions = []): PdbQuery
{
    $pdb = static::getConnection();
    $cat_table = Category::tableMain2cat($main_table);
    $join_table = Category::tableMain2joiner($main_table);
    return (new PdbQuery($pdb))
        ->join($join_table, [['cat_id', '=', `id`]])
        ->find($cat_table, $conditions)
        ->as(static::class);
}


    /**
     * Find one model.
     *
     * @param array $main_table
     * @param array $conditions
     * @return static
     */
    public static function findOne(string $main_table, array $conditions)
    {
        /** @var static */
        return self::find($main_table, $conditions)->one();
    }


    /**
     * Find a list of models.
     *
     * @param array $main_table
     * @param array $conditions
     * @return static[]
     */
    public static function findAll(string $main_table, array $conditions = [])
    {
        return self::find($main_table, $conditions)->all();
    }
}
