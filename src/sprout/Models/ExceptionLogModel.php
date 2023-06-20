<?php
namespace Sprout\Models;

use Sprout\Exceptions\HttpException;
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
        return 'exception_log';
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

        $timestamp = date('Y-m-d H:i:s', strtotime($payload['timestamp']));
        $name = $payload['error']['name'];
        $message = $payload['message'] ?? $payload['error']['message'] ?? '';

        $error_trace = json_encode($payload['error']['stack'] ?? []);

        unset($payload['error']['stack']);
        $error_object = json_encode($payload['error'] ?? []);

        $data = json_encode($payload);
        $session = json_encode($_SESSION);
        $server = json_encode($_SERVER);

        $this->type = 'js';
        $this->date_generated = $timestamp;
        $this->class_name = $name;
        $this->message = $message;
        $this->caught = false;
        $this->exception_object = $error_object;
        $this->exception_trace = $error_trace;
        $this->session = $session;
        $this->get_data = $data;
        $this->server = $server;
    }
}
