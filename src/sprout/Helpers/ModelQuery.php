<?php
/*
 * Copyright (C) 2021 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */
namespace Sprout\Helpers;

use DateTimeInterface;
use karmabunny\kb\Time;
use karmabunny\pdb\PdbModelQuery;
use karmabunny\pdb\PdbQuery;

/**
 * Base model query
 *
 * @package Sprout\Helpers
 */
class ModelQuery extends PdbModelQuery
{

    /** @var null|int */
    public $id = null;

    /** @var null|string */
    public $uid = null;

    /** @var (DateTimeInterface|null)[] [ after, before ] */
    public $date_added = [null, null];

    /** @var (DateTimeInterface|null)[] [ after, before ] */
    public $date_modified = [null, null];


    /**
     *
     * @param string|int|null $id
     * @return self
     */
    public function id($id)
    {
        $this->id = $id ? (int) $id : null;
        return $this;
    }

    /**
     *
     * @param string|null $uid
     * @return self
     */
    public function uid($uid)
    {
        $this->uid = $uid ?: null;
        return $this;
    }


    /**
     *
     * @param null|int|float|string|DateTimeInterface $date
     * @return self
     */
    public function addedAfter($date)
    {
        $this->date_added[0] = $date ? Time::parse($date) : null;
        return $this;
    }


    /**
     *
     * @param null|int|float|string|DateTimeInterface $date
     * @return self
     */
    public function addedBefore($date)
    {
        $this->date_added[1] = $date ? Time::parse($date) : null;
        return $this;
    }


    /**
     *
     * @param null|int|float|string|DateTimeInterface $after
     * @param null|int|float|string|DateTimeInterface $before
     * @return self
     */
    public function addedBetween($after, $before)
    {
        $this->date_added[0] = $after ? Time::parse($after) : null;
        $this->date_added[1] = $before ? Time::parse($before) : null;
        return $this;
    }

    /**
     *
     * @param null|int|float|string|DateTimeInterface $date
     * @return self
     */
    public function modifiedAfter($date)
    {
        $this->date_modified[0] = $date ? Time::parse($date) : null;
        return $this;
    }


    /**
     *
     * @param null|int|float|string|DateTimeInterface $date
     * @return self
     */
    public function modifiedBefore($date)
    {
        $this->date_modified[1] = $date ? Time::parse($date) : null;
        return $this;
    }


    /**
     *
     * @param null|int|float|string|DateTimeInterface $after
     * @param null|int|float|string|DateTimeInterface $before
     * @return self
     */
    public function modifiedBetween($after, $before)
    {
        $this->date_modified[0] = $after ? Time::parse($after) : null;
        $this->date_modified[1] = $before ? Time::parse($before) : null;
        return $this;
    }


    /** @inheritdoc */
    protected function _beforeBuild(PdbQuery &$query)
    {
        if ($this->id) {
            $query->andWhere(['id' => $this->id]);
        }

        if ($this->uid) {
            $query->andWhere(['uid' => $this->uid]);
        }

        if ($this->date_added[0]) {
            $date = $this->date_added[0]->format('Y-m-d H:i:s');
            $query->andWhere([['>=', 'date_added' => $date]]);
        }

        if ($this->date_added[1]) {
            $date = $this->date_added[1]->format('Y-m-d H:i:s');
            $query->andWhere([['<=', 'date_added' => $date]]);
        }

        if ($this->date_modified[0]) {
            $date = $this->date_modified[0]->format('Y-m-d H:i:s');
            $query->andWhere([['>=', 'date_modified' => $date]]);
        }

        if ($this->date_modified[1]) {
            $date = $this->date_modified[1]->format('Y-m-d H:i:s');
            $query->andWhere([['<=', 'date_modified' => $date]]);
        }
    }
}
