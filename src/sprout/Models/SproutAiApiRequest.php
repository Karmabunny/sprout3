<?php
namespace Sprout\Models;

use Sprout\Helpers\Model;
use Sprout\Helpers\Pdb;

class SproutAiApiRequest extends Model
{

    /** @var string */
    public string $date_added;

    /** @var string|null */
    public ?string $date_modified = null;

    /** @var string */
    public string $uid;

    /** @var string */
    public string $ai_provider_class;

    /** @var string */
    public string $ai_provider_function;

    /** @var string */
    public string $endpoint = '';

    /** @var string */
    public string $request = '';

    /** @var string|null */
    public string|null $response = '';

    /** @var string */
    public string $response_status = 'none';

    /** @var float|null */
    public ?float $timing = null;


    public static function getTableName(): string
    {
        return 'ai_api_requests';
    }


    /** @inheritdoc */
    public function save(array $fields = [], string|bool $validate = true): bool
    {
        // Keep the log at under 100k entries
        $q = "SELECT MAX(id) FROM ~ai_api_requests";
        $max_id = (int) Pdb::query($q, [], 'val');

        if ($max_id > 100000) {
            Pdb::delete('ai_api_requests', [['id', '<', $max_id - 100000]]);
        }

        return parent::save($fields, $validate);
    }

}
