<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class OperatorModel extends Model
{
    /** @var string */
    public string $name;

    /** @var string|null */
    public ?string $date_added = null;

    /** @var string|null */
    public ?string $date_modified = null;

    /** @var bool */
    public bool $active = true;

    /** @var string */
    public string $username;

    /** @var string */
    public string $email;

    /** @var bool */
    public bool $firstrun = false;

    /** @var string */
    public string $password;

    /** @var int */
    public int $password_algorithm;

    /** @var string */
    public string $password_salt;

    /** @var string */
    public string $tfa_method;

    /** @var string */
    public string $tfa_secret;

    /** @var string */
    public string $completed_tours;



    public static function getTableName(): string
    {
        return 'operators';
    }
}
