<?php
/*
 * Copyright (C) 2026 Karmabunny Pty Ltd.
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

use JsonSerializable;
use karmabunny\interfaces\LogSinkInterface;
use Kohana;
use ReflectionClass;
use ReflectionNamedType;
use Throwable;

/**
 * Base class for worker jobs.
 *
 * This class superceeds the old {@see WorkerBase} class.
 */
abstract class WorkerJob implements WorkerJobInterface, LogSinkInterface
{

    /** @var string */
    public $id;


    /** @inheritdoc */
    public function getId(): string
    {
        return $this->id;
    }


    /** @inheritdoc */
    public function getName(): string
    {
        $name = basename(str_replace('\\', '/', static::class));
        $name = preg_replace('/(worker|job)/i', '', $name);
        $name = Inflector::humanize($name);
        return $name;
    }


    /** @inheritdoc */
    public function getMetricNames(): array
    {
        return [
            1 => '',
            2 => '',
            3 => '',
        ];
    }


    /**
     * Set a metric
     *
     * @param int $num The metric index; 1, 2, or 3
     * @param int $value
     * @return void
     */
    public function metric(int $num, int $value): void
    {
        $pdb = WorkerCtrl::getPdb();
        $pdb->update('worker_jobs', [
            "metric{$num}val" => $value,
            "date_modified" => $pdb->now(),
        ], [
            'id' => $this->id,
        ]);
    }


    /** @inheritdoc */
    public function log($message, ?int $level = null, ?string $_category = null, $_timestamp = null): void
    {
        if ($level === LOG_ERR and $message instanceof Throwable) {
            Kohana::logException($message);
        }

        if ($message instanceof Throwable) {
            $message = $message->getMessage() . "\n" . $message->getTraceAsString();
        }

        if (is_array($message) or $message instanceof JsonSerializable) {
            $message = Json::encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if (!is_string($message)) {
            $message = (string) $message;
        }

        $pdb = WorkerCtrl::getPdb();

        static $insert;
        $insert ??= $pdb->prepare("UPDATE ~worker_jobs
            SET
                date_modified = :now,
                memuse = :memuse,
                log = CONCAT(log, '[', :date, '] ', :message, '\n')
            WHERE id = :id
        ");

        $line = strtok($message, "\n");

        $args = [
            ':now' => $pdb->now(),
            ':date' => date('h:i:s a'),
            ':memuse' => memory_get_usage(true),
            ':id' => $this->id,
        ];

        while ($line !== false) {
            $args[':message'] = $line;
            $pdb->execute($insert, $args, 'null');
            $line = strtok("\n");
        }
    }


    /** @inheritdoc */
    public static function fromJson(array $json): self
    {
        $class = new ReflectionClass(static::class);
        $inst = new static();

        foreach ($json as $key => $item) {
            if (!$class->hasProperty($key)) {
                continue;
            }

            $property = $class->getProperty($key);

            if (!$property->isPublic() or $property->isStatic()) {
                continue;
            }

            if (
                ($type = $property->getType()) instanceof ReflectionNamedType
                and (is_string($item) and class_exists($type->getName()))
                or (is_array($item) and $type->getName() === 'array')
            ) {
                $item = unserialize($item);
            }

            $inst->$key = $item;
        }

        return $inst;
    }


    /** @inheritdoc */
    public function jsonSerialize(): mixed
    {
        $class = new ReflectionClass($this);

        $array = [];

        foreach ($class->getProperties() as $property) {
            if (!$property->isPublic() or $property->isStatic()) {
                continue;
            }

            if (str_starts_with($property->getName(), '_')) {
                continue;
            }

            $value = $property->getValue($this);


            if (is_object($value) or is_array($value)) {
                $value = serialize($value);
            }

            $array[$property->getName()] = $value;
        }

        return $array;
    }

}
