<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Initial = 'initial';
    case Sale = 'sale';
    case Adjustment = 'adjustment';
    case Purchase = 'purchase';
    case Refund = 'refund';
    case Return = 'return';
    case Opname = 'opname';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
}
