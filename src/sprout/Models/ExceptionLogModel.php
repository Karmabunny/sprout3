<?php
namespace Sprout\Models;

use Sprout\Exceptions\HttpException;
use Sprout\Helpers\JsErrors;
use Sprout\Helpers\Record;
use Sprout\Helpers\Request;
use Sprout\Helpers\Security;
use Sprout\Helpers\Session;
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
    public string $date_generated;

    /** @var string */
    public string $class_name;

    /** @var string */
    public string $type;

    /** @var string */
    public string $message;

    /** @var bool */
    public bool $caught = false;

    /** @var string */
    public string $ip_address = '';

    /** @var string */
    public string $session_id = '';

    /** @var string */
    public string $exception_object;

    /** @var string */
    public string $exception_trace;

    /** @var string */
    public string $server;

    /** @var string */
    public string $get_data;

    /** @var string */
    public string $session;


    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'exception_log';
    }


    /**
     * It's made up (not stored).
     *
     * Pretty much just so we're API compliant with kbtrace.
     *
     * @return string
     */
    public function getUid(): string
    {
        $pdb = self::getConnection();
        return $pdb->generateUid('exception_log', $this->id);
    }


    /**
     * The reference string - TYPE + ID.
     *
     * @return string
     */
    public function reference(): string
    {
        if ($this->type == 'php') {
            return 'SE' . $this->id;
        } else {
            return 'CE' . $this->id;
        }
    }


    /**
     * Render a pretty stack trace.
     *
     * This is different for PHP vs JS.
     *
     * @return string
     */
    public function renderTrace(): string
    {
        $trace = json_decode($this->exception_trace, true);

        if ($this->type == 'php') {
            // These are just strings.
            return print_r($trace, true);

        } else {
            return JsErrors::formatError([
                'name' => $this->class_name,
                'message' => $this->message,
                'stack' => $trace,
            ]);
        }
    }


    /**
     * Parses the JSON payload from kbtrace into something that Sprout is
     * happy to store.
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

        $timestamp = date('Y-m-d H:i:s', (int) $payload['timestamp']);
        $name = $payload['error']['name'];
        $message = $payload['message'] ?? $payload['error']['message'] ?? '';

        $error_trace = json_encode($payload['error']['stack'] ?? []);

        // Record the stack separately.
        unset($payload['error']['stack']);
        $error_object = json_encode($payload['error'] ?? []);

        // Tack on a bit more.
        $payload['timestamp_string'] = $timestamp;
        $data = json_encode($payload);

        $secrets = Security::getSecretSanitizer();

        $session = json_encode($secrets->mask($_SESSION));
        $server = json_encode($secrets->mask($_SERVER));

        $this->type = 'js';
        $this->class_name = $name;
        $this->message = $message;
        $this->caught = false;
        $this->exception_object = $error_object;
        $this->exception_trace = $error_trace;
        $this->session = $session;
        $this->get_data = $data;
        $this->server = $server;
    }


    /** @inheritdoc */
    public function getSaveData(): array
    {
        $pdb = static::getConnection();
        $data = parent::getSaveData();

        if ($this->id == 0) {
            $data['date_generated'] = $pdb->now();
            $data['ip_address'] = bin2hex(inet_pton(Request::userIp()));
            $data['session_id'] = Session::id();
        }

        return $data;
    }


    /** @inheritdoc */
    public function fields(): array
    {
        $fields = parent::fields();
        $fields['reference'] = [$this, 'reference'];
        return $fields;
    }


    /** @inheritdoc */
    public function offsetGet(mixed $offset): mixed
    {
        // TODO this should be upstreamed into kbphp.

        $fields = $this->fields();
        $method = $fields[$offset] ?? null;

        if (is_callable($method)) {
            return $method();
        }

        return parent::offsetGet($offset);
    }
}
