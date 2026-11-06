<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;


class PageRevisionModel extends Model
{
    /** @var int */
    public int $page_id;

    /** @var string|null */
    public string|null $type = null;

    /** @var string */
    public string $controller_entrance;

    /** @var string */
    public string $controller_argument;

    /** @var string */
    public string $redirect;

    /** @var string */
    public string $modified_editor;

    /** @var string */
    public string $status;

    /** @var string */
    public string $changes_made;

    /** @var int */
    public int $operator_id;

    /** @var int */
    public int $approval_operator_id;

    /** @var string */
    public string $approval_code;

    /** @var string|null */
    public ?string $date_launch = null;

    /** @var string */
    public string $date_added;

    /** @var string */
    public string $date_modified;


    public static function getTableName(): string
    {
        return 'page_revisions';
    }
}
