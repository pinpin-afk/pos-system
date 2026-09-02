<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
        public float $quantity,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'stock',
            'title' => 'Stok menipis',
            'message' => "{$this->product->name} sisa {$this->quantity}.",
            'product_id' => $this->product->id,
        ];
    }
}
