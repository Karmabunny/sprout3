<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;
use Sprout\Helpers\Pdb;

class SproutAiApiRequest extends Model
{

    /** @var int */
    public $id;

    /** @var string */
    public $date_added;

    /** @var string|null */
    public $date_modified;

    /** @var string */
    public $uid;

    /** @var string */
    public $ai_provider_class;

    /** @var string */
    public $ai_provider_function;

    /** @var string */
    public $endpoint = '';

    /** @var string */
    public $request = '';

    /** @var string|null */
    public $response = '';

    /** @var string|null */
    public $response_status;

    /** @var float */
    public $timing;


    public static function getTableName(): string
    {
        return 'ai_api_requests';
    }


    /** @inheritdoc */
    public function save($validate = true): bool
    {
        // Keep the log at under 100k entries
        $q = "SELECT MAX(id) FROM ~ai_api_requests";
        $max_id = (int) Pdb::query($q, [], 'val');

        if ($max_id > 100000) {
            Pdb::delete('ai_api_requests', [['id', '<', $max_id - 100000]]);
        }

        return parent::save($validate);
    }

}
