<?php

namespace pdaleramirez\commercepaymongo;

use craft\base\Plugin as BasePlugin;
use craft\commerce\services\Gateways;
use Craft;
use craft\events\RegisterComponentTypesEvent;
use pdaleramirez\commercepaymongo\gateways\PayMongo;
use pdaleramirez\commercepaymongo\plugin\Services;
use pdaleramirez\commercepaymongo\services\Payment;
use yii\base\Event;

class Plugin extends BasePlugin
{
    use Services;

    /**
     * @inheritDoc
     */
    public string $schemaVersion = '1.0.0';

    public function init()
    {
        parent::init();

        Craft::setAlias('@commerce-paymongo', $this->getBasePath());

        Event::on(
            Gateways::class,
            Gateways::EVENT_REGISTER_GATEWAY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = PayMongo::class;
            }
        );
    }

    public static function config(): array
    {
        return [
          'components' => [
              'payment' => ['class' => Payment::class],
          ]
        ];
    }
}