<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class OperatorModel extends Model
{
    /** @var int */
    public $active;

    /** @var string */
    public $username;

    /** @var string */
    public $email;

    /** @var int */
    public $firstrun;

    /** @var string */
    public $password;

    /** @var int */
    public $password_algorithm;

    /** @var string */
    public $password_salt;

    /** @var string */
    public $tfa_method;

    /** @var string */
    public $tfa_secret;

    /** @var string */
    public $completed_tours;



    public static function getTableName(): string
    {
        return 'operators';
    }
}
