<?php

namespace App\Notifications;

use App\Models\CashierShift;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CashDifferenceNotification extends Notification
{
    use Queueable;

    public function __construct(public CashierShift $shift) {}

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
        $difference = (float) $this->shift->difference;
        $label = $difference < 0 ? 'kurang' : 'lebih';

        return [
            'type' => 'cash',
            'title' => 'Selisih kas shift',
            'message' => 'Kas '.$label.' Rp'.number_format(abs($difference), 0, ',', '.').'.',
            'shift_id' => $this->shift->id,
        ];
    }
}
