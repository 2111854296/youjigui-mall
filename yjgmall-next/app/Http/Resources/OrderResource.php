<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'amount' => (string) $this->amount,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'receiver_address' => $this->receiver_address,
            'remark' => $this->remark,
            'paid_at' => optional($this->paid_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'username' => $this->user?->username,
                'company_name' => $this->user?->company_name,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'id' => $i->id,
                'title' => $i->title,
                'price' => (string) $i->price,
                'quantity' => (int) $i->quantity,
            ])),
        ];
    }
}
