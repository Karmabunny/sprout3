<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageRevisionModel extends Model
{
    /** @var int */
    public $page_id;

    /** @var string|null */
    public $type;

    /** @var string */
    public $controller_entrance;

    /** @var string */
    public $controller_argument;

    /** @var string */
    public $redirect;

    /** @var string */
    public $modified_editor;

    /** @var string */
    public $status;

    /** @var string */
    public $changes_made;

    /** @var int */
    public $operator_id;

    /** @var int */
    public $approval_operator_id;

    /** @var string */
    public $approval_code;

    /** @var string */
    public $date_launch;



    public static function getTableName(): string
    {
        return 'page_revisions';
    }
}
