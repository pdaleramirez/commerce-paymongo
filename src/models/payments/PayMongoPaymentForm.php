<?php

namespace pdaleramirez\commercepaymongo\models\payments;

use craft\commerce\models\payments\CreditCardPaymentForm;
use craft\commerce\models\PaymentSource;

/**
 * Credit Card Payment form model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class PayMongoPaymentForm extends CreditCardPaymentForm
{
    public function populateFromPaymentSource(PaymentSource $paymentSource): void
    {
        $this->token = (string)$paymentSource->id;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        if ($this->token) {
            return []; //No validation of form if using a token
        }

        return parent::defineRules();
    }
}
