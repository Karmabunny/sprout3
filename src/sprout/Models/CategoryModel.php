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
            ->join($join_table, ['cat_id = id'])
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


    /**
     * Save this model.
     *
     * @param string $main_table
     * @return bool
     * @throws InvalidArgumentException
     * @throws QueryException
     * @throws ConnectionException
     * @throws Exception
     * @throws TransactionRecursionException
     * @throws PDOException
     */
    public function save(string $main_table): bool
    {
        $pdb = static::getConnection();
        $cat_table = Category::tableMain2cat($main_table);

        $now = Pdb::now();
        $data = iterator_to_array($this);
        $conditions = [ 'id' => $this->id ];


        if ($this->id > 0) {
            $data['date_modified'] = $now;

            $pdb->update($cat_table, $data, $conditions);
        }
        else {
            $data['date_added'] = $now;
            $data['date_modified'] = $now;

            // TODO Add shared transaction support.
            $ts_id = 0;
            if (!$pdb->inTransaction()) {
                $ts_id = 1;
                $pdb->transact();
            }

            $this->id = $pdb->insert($cat_table, $data);

            if ($ts_id === 1) {
                $pdb->commit();
            }
        }

        $this->date_added = $data['date_added'];
        $this->date_modified = $data['date_modified'];

        return (bool) $this->id;
    }


    /**
     * Delete this model.
     *
     * @param string $main_table
     * @return bool
     * @throws InvalidArgumentException
     * @throws QueryException
     * @throws ConnectionException
     */
    public function delete(string $main_table): bool
    {
        $pdb = static::getConnection();
        $cat_table = Category::tableMain2cat($main_table);
        return (bool) $pdb->delete($cat_table, ['id' => $this->id]);
    }
}
