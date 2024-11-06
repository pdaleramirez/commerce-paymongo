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
        $errors = $this->data['errors'] ?? null;

        if ($errors !== null) return false;

        $attributes = $this->data['attributes'];
        $type = $this->data['type'];

        if ($attributes['status'] === 'succeeded' || ($type === 'refund' && $attributes['status'] === 'pending')) return true;


        return false;
    }

    public function isProcessing(): bool
    {
        return false;
    }

    public function isRedirect(): bool
    {
        $errors = $this->data['errors'] ?? null;
        if ($errors !== null) return false;

        $attributes = $this->data['attributes'];

        return (isset($attributes['next_action']) || ($attributes['status'] === 'pending'));
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
        $errors = $this->data['errors'] ?? null;
        if ($errors !== null) return false;

        $attributes = $this->data['attributes'];

        $nextAction = $attributes['next_action'] ?? null;

        if ($nextAction !== null) {
            return $nextAction['redirect']['url'];
        }

        return '';
    }

    public function getTransactionReference(): string
    {
        $errors = $this->data['errors'] ?? null;
        if ($errors !== null) return '';

        if (empty($this->data)) {
            return '';
        }

        return (string)$this->data['id'];
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
        $errors = $this->data['errors'] ?? null;
        if ($errors !== null && isset($errors[0])) {
            return $errors[0]['detail'];
        }

        $attributes = $this->data['attributes'];
        $type = $this->data['type'];

        if ($attributes['status'] === 'succeeded' || ($type === 'refund' && $attributes['status'] === 'pending')) {
            return \Craft::t('commerce-paymongo', "Payment has succeeded.");
        }

        $failedMessage = $attributes['last_payment_error']['failed_message'] ?? null;

        if ($failedMessage !== null) {
            return $failedMessage;
        }

        return \Craft::t('commerce-paymongo', "Payment authentication has failed or encountered an error.");
    }

    public function redirect(): void
    {

    }

    /**
     * @param $error
     * @return void
     */
    public function setError($error): void
    {
        $this->data['errors'] = [
            [
                'detail' => $error
            ]
        ];
    }
}