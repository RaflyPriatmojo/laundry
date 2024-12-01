<?php

namespace App\Enums;

enum OrderPaymentEnum: string
{
    case TERTUNDA = 'Belum Bayar';
    case LUNAS = 'Lunas';
}
