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
     * @param string $val The subscription ID as a string
     * @param string $field_name
     * @param array $row
     * @return string
     */
    public function modify($val, $field_name, $row): string
    {
        $subscription_id = (int)$val;
        $subscription = Pdb::get('content_subscriptions', $subscription_id);

        try {
            $inst = Sprout::instance($subscription['handler_class'], 'Sprout\\Helpers\\Subscribe');
        } catch (InvalidArgumentException $ex) {
            return sprintf('Error: %s', $ex->getMessage());
        }

        return $inst->getName(json_decode($subscription['handler_settings'], true));
    }
}
