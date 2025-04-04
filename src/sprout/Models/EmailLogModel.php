<?php
namespace Sprout\Models;

use karmabunny\kb\Uuid;
use Sprout\Helpers\Record;

/**
 *
 * @package dashboard\Models
 */
class EmailLogModel extends Record
{

    /** @var string */
    public $uid;

    /** @var string */
    public $date_added;

    /** @var string */
    public $subject;

    /** @var string */
    public $body;

    /** @var string */
    public $from_address;

    /** @var string JSON array of email addresses */
    public $to;

    /** @var string JSON array of email addresses */
    public $cc;

    /** @var string JSON array of email addresses */
    public $bcc;

    /** @var string JSON array of email addresses */
    public $reply_to;

    /** @var string comma separated */
    public $to_address;

    /** @var string comma separated */
    public $cc_address;

    /** @var string comma separated */
    public $bcc_address;

    /** @var string comma separated */
    public $reply_to_address;

    /** @var bool */
    public $success = false;

    /** @var float in seconds (with milliseconds) */
    public $time_taken = 0;

    /** @var string */
    public $error = '';

    /** @var int|null */
    public $error_id;


    /** @inheritdoc */
    public static function getTableName(): string
    {
        return 'email_log';
    }


    /** @inheritdoc */
    public function getSaveData(): array
    {
        $pdb = static::getConnection();
        $now = $pdb->now();

        $data = parent::getSaveData();

        if (empty($data['uid'])) {
            $data['uid'] = Uuid::uuid4();
        }

        if (!$this->id) {
            $data['date_added'] = $now;
        }

        return $data;
    }


    public function setToAddress(array $addresses)
    {
        $this->to_address = implode(', ', array_column($addresses, 0));
        $this->to = json_encode($addresses);
    }


    public function setCcAddress(array $addresses)
    {
        $this->cc_address = implode(', ', array_column($addresses, 0));
        $this->cc = json_encode($addresses);
    }


    public function setBccAddress(array $addresses)
    {
        $this->bcc_address = implode(', ', array_column($addresses, 0));
        $this->bcc = json_encode($addresses);
    }


    public function setReplyToAddress(array $addresses)
    {
        $this->reply_to_address = implode(', ', array_column($addresses, 0));
        $this->reply_to = json_encode($addresses);
    }

}
