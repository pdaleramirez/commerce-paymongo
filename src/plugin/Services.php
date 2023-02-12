<?php

namespace pdaleramirez\commercepaymongo\plugin;

use pdaleramirez\commercepaymongo\services\Payment;

trait Services
{
    public function getPayment(): Payment
    {
        return $this->get('payment');
    }
}