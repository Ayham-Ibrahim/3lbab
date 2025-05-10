<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'قيد الانتظار';
    case Processing = 'قيد المعالجة';
    case Shipped = 'تم الشحن';
    case Completed = 'مكتمل';
    case Cancelled = 'ملغي';
}
