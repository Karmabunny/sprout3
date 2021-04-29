<?php
namespace Sprout\Helpers;

use InvalidArgumentException;

use Sprout\Helpers\Pdb;
USE Sprout\Helpers\Sprout;


class ColModifierContentSubscription extends ColModifier
{
    /**
     * Renders user friendly settings for given subscription record
     *
     * @param int $record_id
     */
    public function modify($subscription_id, $field_name)
    {
        $subscription = Pdb::get('content_subscriptions', $subscription_id);

        try {
            $inst = Sprout::instance($subscription['handler_class'], 'Sprout\\Helpers\\Subscribe');
        } catch (InvalidArgumentException $ex) {
            return sprintf('Error: %s', $ex->getMessage());
        }

        return $inst->getName(json_decode($subscription['handler_settings'], true));
    }
}
