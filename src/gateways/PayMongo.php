<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace pdaleramirez\commercepaymongo\gateways;

use Craft;
use craft\commerce\base\Plan;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\base\SubscriptionGateway;
use craft\commerce\base\SubscriptionResponseInterface;
use craft\commerce\elements\Subscription;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\payments\CreditCardPaymentForm;
use craft\commerce\models\payments\DummyPaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\responses\Dummy as DummyRequestResponse;
use craft\commerce\models\responses\DummySubscriptionResponse;
use craft\helpers\UrlHelper;
use pdaleramirez\commercepaymongo\models\responses\PayMongoRequestResponse;
use craft\commerce\models\subscriptions\CancelSubscriptionForm;
use craft\commerce\models\subscriptions\DummyPlan;
use craft\commerce\models\subscriptions\SubscriptionForm;
use craft\commerce\models\subscriptions\SwitchPlansForm;
use craft\commerce\models\Transaction;
use craft\elements\User;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\web\Response as WebResponse;
use craft\web\View;
use http\Exception\InvalidArgumentException;
use pdaleramirez\commercepaymongo\Plugin;
use pdaleramirez\commercepaymongo\web\assets\VueAsset;
use yii\base\NotSupportedException;

/**
 * Dummy represents a dummy gateway.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class PayMongo extends SubscriptionGateway
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
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params): ?string
    {
       Craft::$app->getView()->registerAssetBundle(VueAsset::class);

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

        $html = Craft::$app->getView()->renderTemplate('commerce-paymongo/_components/gateways/_creditCardFields', $params);

        $view->setTemplateMode($previousMode);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): DummyPaymentForm
    {
        return new DummyPaymentForm();
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
        if (!$form instanceof CreditCardPaymentForm) {
            throw new InvalidArgumentException(sprintf('%s only accepts %s objects passed to $form.', __METHOD__, CreditCardPaymentForm::class));
        }

        return new PayMongoRequestResponse();
    }

    /**
     * @inheritdoc
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        return new PayMongoRequestResponse();
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
        Plugin::getInstance()->getPayment()->setClientKey($transactionDecode['attributes']['client_key']);
        $paymentIntent = Plugin::getInstance()->getPayment()->getPaymentIntent($transactionDecode['id']);
        $paymentMethodContent = Json::decode($paymentIntent->getBody()->getContents());

        $status = $paymentMethodContent['data']['attributes']['status'];


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
        Plugin::getInstance()->getPayment()->setSecretKey($this->secret);

        $expiry = explode('/', $form->expiry);

        $details = [
            'card_number' => $form->number,
            'exp_month' => (int) $expiry[0],
            'exp_year' => (int) $expiry[1],
            'cvc' => $form->cvv
        ];

        $response = Plugin::getInstance()->getPayment()->payMongoRequest('payment_methods', [
            'attributes' => [
                'type' => 'card',
                'details' => $details
            ]
        ]);
        $paymentMethodContent = Json::decode($response->getBody()->getContents());
        $paymentMethodId = $paymentMethodContent['data']['id'];

        $amount = number_format($transaction->amount, 2, '', '');

        $response = Plugin::getInstance()->getPayment()->payMongoRequest('payment_intents', [
            'attributes' => [
                'amount' => (int)$amount,
                'payment_method_allowed' => ['card'],
                'currency' => 'PHP'
            ]
        ]);

        $paymentIntentContent = Json::decode($response->getBody()->getContents());;
        $paymentIntentId = $paymentIntentContent['data']['id'];

        Craft::$app->getSession()->set('payMongoPaymentIntentId', $paymentIntentId);
        $paymentIntentClientKey = $paymentIntentContent['data']['attributes']['client_key'];

        $response = Plugin::getInstance()->getPayment()->payMongoRequest('payment_intents/' . $paymentIntentId . '/attach', [
            'attributes' => [
                'payment_method' => $paymentMethodId,
                'return_url' => UrlHelper::actionUrl('commerce/payments/complete-payment', ['commerceTransactionId' => $transaction->id, 'commerceTransactionHash' => $transaction->hash]),
            ]
        ]);

        $paymentMethodContent = Json::decode($response->getBody()->getContents());
        $paymentMethodContentData = $paymentMethodContent['data'];
        $status = $paymentMethodContentData['attributes']['status'];

        if ($status === 'awaiting_next_action') {

            return new PayMongoRequestResponse($paymentMethodContentData);
        }

        if (!$form instanceof CreditCardPaymentForm) {
            throw new InvalidArgumentException(sprintf('%s only accepts %s objects passed to $form.', __METHOD__, CreditCardPaymentForm::class));
        }

        return new PayMongoRequestResponse($paymentMethodContentData);
    }

    /**
     * @inheritdoc
     */
    public function processWebHook(): WebResponse
    {
        throw new NotSupportedException(__CLASS__ . ' does not support processWebhook()');
    }

    /**
     * @inheritdoc
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        $form = new DummyPaymentForm();

        if ($transaction->note != 'fail') {
            $form->number = '4242424242424242';
        } else {
            $form->number = '378282246310005';
        }

        return new DummyRequestResponse($form);
    }

    /**
     * @inheritdoc
     */
    public function supportsAuthorize(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsCapture(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsCompleteAuthorize(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsCompletePurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPaymentSources(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsRefund(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPartialRefund(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getCancelSubscriptionFormHtml(Subscription $subscription): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getCancelSubscriptionFormModel(): CancelSubscriptionForm
    {
        return new CancelSubscriptionForm();
    }

    /**
     * @inheritdoc
     */
    public function getPlanSettingsHtml(array $params = []): ?string
    {
        return '<input type="hidden" name="reference" value="dummy.reference"/>';
    }

    /**
     * @inheritdoc
     */
    public function getPlanModel(): Plan
    {
        return new DummyPlan();
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionFormModel(): SubscriptionForm
    {
        return new SubscriptionForm();
    }

    /**
     * @inheritdoc
     */
    public function getSwitchPlansFormModel(): SwitchPlansForm
    {
        return new SwitchPlansForm();
    }

    /**
     * @inheritdoc
     */
    public function cancelSubscription(Subscription $subscription, CancelSubscriptionForm $parameters): SubscriptionResponseInterface
    {
        $response = new DummySubscriptionResponse();
        $response->setIsCanceled(true);
        return $response;
    }

    /**
     * @inheritdoc
     */
    public function getNextPaymentAmount(Subscription $subscription): string
    {
        return '-';
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPayments(Subscription $subscription): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPlanByReference(string $reference): string
    {
        return 'dummy.plan';
    }

    /**
     * @inheritdoc
     */
    public function getSubscriptionPlans(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function subscribe(User $user, Plan $plan, SubscriptionForm $parameters): SubscriptionResponseInterface
    {
        $subscription = new DummySubscriptionResponse();
        $subscription->setTrialDays($parameters->trialDays);

        return $subscription;
    }

    /**
     * @inheritdoc
     */
    public function switchSubscriptionPlan(Subscription $subscription, Plan $plan, SwitchPlansForm $parameters): SubscriptionResponseInterface
    {
        return new DummySubscriptionResponse();
    }

    /**
     * @inheritdoc
     */
    public function supportsReactivation(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsPlanSwitch(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getBillingIssueDescription(Subscription $subscription): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getBillingIssueResolveFormHtml(Subscription $subscription): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getHasBillingIssues(Subscription $subscription): bool
    {
        return false;
    }
}
