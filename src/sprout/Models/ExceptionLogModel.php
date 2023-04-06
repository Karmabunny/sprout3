<?php
namespace Sprout\Models;

use Sprout\Exceptions\HttpException;
use Sprout\Exceptions\ValidationException;
use Sprout\Helpers\Record;
use Sprout\Helpers\Validator;

/**
 *
 * @package dashboard\Models
 */
class ExceptionLogModel extends Record
{

    const TYPE_PHP = 'php';
    const TYPE_JS = 'js';

    /** @var string */
    public $date_generated;

    /** @var string */
    public $class_name;

    /** @var string */
    public $type;

    /** @var string */
    public $message;

    /** @var int */
    public $caught;

    /** @var string */
    public $exception_object;

    /** @var string */
    public $exception_trace;

    /** @var string */
    public $server;

    /** @var string */
    public $get_data;

    /** @var string */
    public $session;


    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'exception_logs';
    }


    /**
     *
     * @return string
     */
    public function getUid(): string
    {
        $pdb = self::getConnection();
        return $pdb->generateUid('exception_log', $this->id);
    }


    /**
     *
     * @param array $payload
     * @return void
     * @throws HttpException
     */
    public function parseJsPayload(array $payload)
    {
        $validator = new Validator($payload);
        $validator->required([
            'timestamp',
            'error',
        ]);

        if ($validator->hasErrors()) {
            // TODO get error message from the validator.
            throw new HttpException(400, 'Missing required fields: timestamp, error');
        }

        $this->type = 'js';
        $this->date_generated = date('Y-m-d H:i:s', strtotime($payload['timestamp']));
        $this->class_name = $payload['error']['name'];
        $this->message = $payload['message'] ?? $payload['error']['message'];
        $this->caught = false;
        $this->exception_object = json_encode($payload['meta']);
        $this->exception_trace = json_encode($payload['error']['trace']);
        $this->session = json_encode($_SESSION);
        $this->get_data = json_encode('');
        $this->server = json_encode('');
    }
}
