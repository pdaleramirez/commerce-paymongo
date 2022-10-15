<?php

namespace pdaleramirez\gateways;

use craft\commerce\base\Gateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\Transaction;
use craft\web\Response as WebResponse;

class PayMongo extends Gateway
{

    public function getPaymentFormHtml(array $params): ?string
    {
        // TODO: Implement getPaymentFormHtml() method.
    }

    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // TODO: Implement authorize() method.
    }

    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        // TODO: Implement capture() method.
    }

    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completeAuthorize() method.
    }

    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completePurchase() method.
    }

    public function createPaymentSource(BasePaymentForm $sourceData, int $customerId): PaymentSource
    {
        // TODO: Implement createPaymentSource() method.
    }

    public function deletePaymentSource(string $token): bool
    {
        // TODO: Implement deletePaymentSource() method.
    }

    public function getPaymentFormModel(): BasePaymentForm
    {
        // TODO: Implement getPaymentFormModel() method.
    }

    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // TODO: Implement purchase() method.
    }

    public function refund(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement refund() method.
    }

    public function processWebHook(): WebResponse
    {
        // TODO: Implement processWebHook() method.
    }

    public function supportsAuthorize(): bool
    {
        // TODO: Implement supportsAuthorize() method.
    }

    public function supportsCapture(): bool
    {
        // TODO: Implement supportsCapture() method.
    }

    public function supportsCompleteAuthorize(): bool
    {
        // TODO: Implement supportsCompleteAuthorize() method.
    }

    public function supportsCompletePurchase(): bool
    {
        // TODO: Implement supportsCompletePurchase() method.
    }

    public function supportsPaymentSources(): bool
    {
        // TODO: Implement supportsPaymentSources() method.
    }

    public function supportsPurchase(): bool
    {
        // TODO: Implement supportsPurchase() method.
    }

    public function supportsRefund(): bool
    {
        // TODO: Implement supportsRefund() method.
    }

    public function supportsPartialRefund(): bool
    {
        // TODO: Implement supportsPartialRefund() method.
    }

    public function supportsWebhooks(): bool
    {
        // TODO: Implement supportsWebhooks() method.
    }
}