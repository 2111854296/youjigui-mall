<?php

namespace App\Services\Payment;

/**
 * Placeholder Alipay gateway — swap with real SDK when merchant keys are provided.
 */
class AlipayGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'alipay';
    }

    public function pay(array $order): array
    {
        // Sandbox/mock: mark as redirect to local confirm endpoint
        return [
            'redirect_url' => route('member.orders.pay.mock', ['order' => $order['id']]),
            'trade_no' => 'MOCK'.($order['order_no'] ?? ''),
        ];
    }

    public function verify(array $payload): bool
    {
        return ($payload['status'] ?? '') === 'success';
    }
}
