<?php

namespace App\Services\Payments\Contracts;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;

interface PaymentGatewayContract
{
    /**
     * Initiate a payment for the given bill and user.
     *
     * @return array{payment: Payment, checkout: array}
     */
    public function initiate(Bill $bill, User $user): array;
}
