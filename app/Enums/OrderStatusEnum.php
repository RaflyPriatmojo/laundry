<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    case BARU = 'Baru';
    case PROSES = 'Proses';
    case DIAMBIL = 'Diambil';
    case DIBATALKAN = 'Dibatalkan';
}
