<?php

namespace App\Services\Payment;

interface PaymentGateway
{
    public function name(): string;

    /** @return array{redirect_url?:string, html?:string, trade_no?:string} */
    public function pay(array $order): array;

    public function verify(array $payload): bool;
}
