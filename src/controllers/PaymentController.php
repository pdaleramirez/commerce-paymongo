<?php

namespace pdaleramirez\commercepaymongo\controllers;

use craft\web\Controller;
use pdaleramirez\commercepaymongo\Plugin;
use pdaleramirez\commercepaymongo\services\Payment;

class PaymentController extends Controller
{
    public array|bool|int $allowAnonymous = true;

    public function actionPay()
    {
        $params = \Craft::$app->getRequest()->getBodyParams();

       // Plugin::getInstance()->getPayment()->payMongoRequest();
    }
}