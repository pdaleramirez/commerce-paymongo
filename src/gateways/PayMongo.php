<?php

/**
 * PayMongo for Craft Commerce Gateway plugin for Craft CMS 4.x
 *
 * Search elements with filter using Vue JS
 *
 * @link      https://github.com/pdaleramirez
 * @copyright Copyright (c) 2023 Dale Ramirez
 */

namespace pdaleramirez\commercepaymongo\gateways;

use Craft;
use craft\commerce\base\Gateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\CreditCardPaymentForm;
use craft\commerce\models\PaymentSource;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use pdaleramirez\commercepaymongo\models\payments\PayMongoPaymentForm;
use pdaleramirez\commercepaymongo\models\responses\PayMongoRequestResponse;
use craft\commerce\models\Transaction;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Response as WebResponse;
use craft\web\View;
use pdaleramirez\commercepaymongo\Plugin;
use yii\base\NotSupportedException;

/**
 * PayMongo represents a PayMongo gateway.
 *
 * @author Dale Ramirez <https://github.com/pdaleramirez>
 * @since 1.0
 */
class PayMongo extends Gateway
{
    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $secret;

    /**
     * @var string
     */
    public $testMode;

    /**
     * @var string
     */
    public ?string $paymentGatewayType = null;

    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params): ?string
    {
        $paymentFormModel = $this->getPaymentFormModel();

        if (Craft::$app->getConfig()->general->devMode) {
            $paymentFormModel->firstName = 'Jenny';
            $paymentFormModel->lastName = 'Andrews';
            $paymentFormModel->number = '5234000000000106';
            $paymentFormModel->expiry = '01/2025';
            $paymentFormModel->cvv = '123';
        }

        $defaults = [
            'paymentForm' => $paymentFormModel,
        ];

        $params = array_merge($defaults, $params);

        $view = Craft::$app->getView();
        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        if ($this->paymentGatewayType === 'gcash') {
            $html = Craft::$app->getView()->renderTemplate('commerce-paymongo/_components/gateways/_gcash', $params);
        } else {
            $html = Craft::$app->getView()->renderTemplate('commerce-paymongo/_components/gateways/_creditCardFields', $params);
        }


        $view->setTemplateMode($previousMode);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): PayMongoPaymentForm
    {
        return new PayMongoPaymentForm();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('commerce-paymongo/gatewaysettings/intentsSettings', ['gateway' => $this]);
    }

    /**
     * @inheritdoc
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return $this->authorizeOrPurchase($transaction, $form, 'manual');
    }

    /**
     * @inheritdoc
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        Plugin::getInstance()->getPayment()->setSecretKey($this->secret);
        $amount = (int)number_format($transaction->amount, 2, '', '');
        $response = Plugin::getInstance()->getPayment()->payMongoRequest('payment_intents/' . $reference . '/capture', [
            'attributes' => [
                'amount' => $amount
            ]
        ]);

        $paymentMethodContent = Json::decode($response->getBody()->getContents());
        $paymentMethodContentData = $paymentMethodContent['data'];

        return new PayMongoRequestResponse($paymentMethodContentData);
    }

    /**
     * @inheritdoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        return new PayMongoRequestResponse();
    }

    /**
     * @inheritdoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {

        $transactionDecode = Json::decode($transaction->response);

        Plugin::getInstance()->getPayment()->setSecretKey($this->secret);

        $clientKey = $transactionDecode['attributes']['client_key'] ?? null;
        if ($clientKey !== null) {
            Plugin::getInstance()->getPayment()->setClientKey($clientKey);
        }

        if ($transactionDecode['type'] === 'source') {
            $response = Plugin::getInstance()->getPayment()->getSource($transactionDecode['id']);
        } else {
            $response = Plugin::getInstance()->getPayment()->getPaymentIntent($transactionDecode['id']);
        }

        $paymentMethodContent = Json::decode($response->getBody()->getContents());

        return new PayMongoRequestResponse($paymentMethodContent['data']);
    }

    /**
     * @inheritdoc
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $customerId): PaymentSource
    {
        /** @var CreditCardPaymentForm $sourceData */

        $paymentSource = new PaymentSource();
        $paymentSource->customerId = $customerId;
        $paymentSource->gatewayId = $this->id;
        $paymentSource->token = StringHelper::randomString();
        $paymentSource->response = '';
        $paymentSource->description = 'Card ending with ' . StringHelper::last($sourceData->number, 4);

        return $paymentSource;
    }

    /**
     * @inheritdoc
     */
    public function deletePaymentSource(string $token): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        return $this->authorizeOrPurchase($transaction, $form);
    }

    private function authorizeOrPurchase(Transaction $transaction, BasePaymentForm $form, $capture = 'automatic')
    {
        $baseCurrency = \craft\commerce\Plugin::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();

        Plugin::getInstance()->getPayment()->setSecretKey($this->secret);

        $paymentMethodAttributes = [];
        if ($form->type === 'gcash') {
            $paymentMethodAttributes['attributes']['type'] = 'gcash';
        } else {
            $expiry = explode('/', $form->expiry);
            $paymentMethodAttributes['attributes']['type'] = 'card';

            $paymentMethodAttributes['attributes']['details'] = [
                'card_number' => $form->number,
                'exp_month' => (int)$expiry[0],
                'exp_year' => (int)$expiry[1],
                'cvc' => $form->cvv
            ];
        }

        try {
            $response = Plugin::getInstance()->getPayment()->payMongoRequest('payment_methods', $paymentMethodAttributes);
        } catch (\Exception $exception) {
          $content = $exception->getResponse()->getBody()->getContents();

          return $this->getError($content);
        }

        $paymentMethodContent = Json::decode($response->getBody()->getContents());
        $paymentMethodId = $paymentMethodContent['data']['id'];

        $amount = number_format($transaction->amount, 2, '', '');

        try {
            $response = Plugin::getInstance()->getPayment()->payMongoRequest('payment_intents', [
                'attributes' => [
                    'amount' => (int)$amount,
                    'payment_method_allowed' => ['card', 'gcash'],
                    'currency' => App::env('PAYMONGO_PHP') ? "PHP" : $baseCurrency,
                    'capture_type' => $capture
                ]
            ]);
        } catch (\Exception $exception) {
            $body = Json::decode($exception->getResponse()->getBody()->getContents());
            return new PayMongoRequestResponse($body);
        }


        $paymentIntentContent = Json::decode($response->getBody()->getContents());;
        $paymentIntentId = $paymentIntentContent['data']['id'];
        try {
            $response = Plugin::getInstance()->getPayment()->payMongoRequest('payment_intents/' . $paymentIntentId . '/attach', [
                'attributes' => [
                    'payment_method' => $paymentMethodId,
                    'return_url' => UrlHelper::actionUrl('commerce/payments/complete-payment', ['commerceTransactionId' => $transaction->id, 'commerceTransactionHash' => $transaction->hash]),
                ]
            ]);
        } catch (\Exception $exception) {
            $content = $exception->getResponse()->getBody()->getContents();
            return $this->getError($content);
        }

        $paymentMethodContent = Json::decode($response->getBody()->getContents());
        $paymentMethodContentData = $paymentMethodContent['data'];
        $status = $paymentMethodContentData['attributes']['status'];

        if ($status === 'awaiting_next_action' || ($status === 'pending'
                && isset($paymentMethodContentData['attributes']['type'])
                && $paymentMethodContentData['attributes']['type'] === 'gcash'
            )) {

            $response = new PayMongoRequestResponse($paymentMethodContentData);

            return $response;
        }

        if (!$form instanceof CreditCardPaymentForm) {
            throw new \InvalidArgumentException(sprintf('%s only accepts %s objects passed to $form.', __METHOD__, CreditCardPaymentForm::class));
        }

        return new PayMongoRequestResponse($paymentMethodContentData);
    }

    /**
     * @inheritdoc
     */
    public
    function processWebHook(): WebResponse
    {
        throw new NotSupportedException(__CLASS__ . ' does not support processWebhook()');
    }

    /**
     * @inheritdoc
     */
    public
    function refund(Transaction $transaction): RequestResponseInterface
    {
        $response = Json::decode($transaction->parent->response);
        $payment = ArrayHelper::firstValue($response['attributes']['payments']);

        $paymentId = $payment['id'];
        $amount = (int)number_format($transaction->amount, 2, '', '');
        Plugin::getInstance()->getPayment()->setSecretKey($this->secret);
        $response = Plugin::getInstance()->getPayment()->payMongoRequest('refunds', [
            'attributes' => [
                'amount' => $amount,
                'payment_id' => $paymentId,
                'reason' => 'requested_by_customer',
            ]
        ]);

        $paymentMethodContent = Json::decode($response->getBody()->getContents());
        $paymentMethodContentData = $paymentMethodContent['data'];


        return new PayMongoRequestResponse($paymentMethodContentData);
    }

    /**
     * @inheritdoc
     */
    public
    function supportsAuthorize(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public
    function supportsCapture(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public
    function supportsCompleteAuthorize(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public
    function supportsCompletePurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public
    function supportsPaymentSources(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public
    function supportsPurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public
    function supportsRefund(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public
    function supportsPartialRefund(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public
    function supportsWebhooks(): bool
    {
        return false;
    }

    /**
     * @param $content
     * @return PayMongoRequestResponse
     */
    public function getError($content): PayMongoRequestResponse
    {
        $body = Json::decode($content);
        $request = new PayMongoRequestResponse();
        $detail = $body['errors'][0]['detail'] ?? null;
        $detail = str_replace('details.', '', $detail);
        $detail = ucfirst(str_replace('_', ' ', $detail));
        $request->setError($detail);
        return $request;
    }
}