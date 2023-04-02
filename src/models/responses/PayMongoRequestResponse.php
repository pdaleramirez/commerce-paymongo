<?php

namespace pdaleramirez\commercepaymongo\models\responses;

use craft\commerce\base\RequestResponseInterface;

class PayMongoRequestResponse implements RequestResponseInterface
{
    /**
     * @var array the response data
     */
    protected array $data = [];

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function isSuccessful(): bool
    {
        $data = $this->data['attributes'];

        if ($data['status'] === 'succeeded') return true;

        return false;
    }

    public function isProcessing(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        $attributes = $this->data['attributes'];
        return isset($attributes['next_action']);
    }

    public function getRedirectMethod(): string
    {
        return 'GET';
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getRedirectUrl(): string
    {
        $nextAction = $this->data['attributes']['next_action'];

        if ($nextAction !== null) {
            return $nextAction['redirect']['url'];
        }

        return '';
    }

    public function getTransactionReference(): string
    {
        return '';
    }

    public function getCode(): string
    {
        return '';
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMessage(): string
    {
        $data = $this->data['attributes'];

        if ($data['status'] === 'succeeded') {
            return \Craft::t('commerce-paymongo', "Payment has succeeded.");
        }

        return \Craft::t('commerce-paymongo', "Payment authentication has failed or encountered an error.");
    }

    public function redirect(): void
    {

    }
}