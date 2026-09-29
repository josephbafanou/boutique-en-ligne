<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /** Statuts pris en compte dans le chiffre d'affaires */
    public static function revenue(): array
    {
        return [self::Paid, self::Shipped, self::Delivered];
    }
}
