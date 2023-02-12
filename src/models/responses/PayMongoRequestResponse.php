<?php

namespace pdaleramirez\commercepaymongo\models\responses;

use craft\commerce\base\RequestResponseInterface;

class PayMongoRequestResponse implements RequestResponseInterface
{
    /**
     * @var array the response data
     */
    protected array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function isSuccessful(): bool
    {
        return true;
    }

    public function isProcessing(): bool
    {
        return true;
    }

    public function isRedirect(): bool
    {
        return false;
    }

    public function getRedirectMethod(): string
    {
        return '';
    }

    public function getRedirectData(): array
    {
        return [];
    }

    public function getRedirectUrl(): string
    {
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
        return [];
    }

    public function getMessage(): string
    {
        return "Payment is a success";
    }

    public function redirect(): void
    {
        // TODO: Implement redirect() method.
    }
}