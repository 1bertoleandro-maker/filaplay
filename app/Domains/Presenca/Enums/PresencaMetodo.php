<?php

declare(strict_types=1);

namespace App\Domains\Presenca\Enums;

enum PresencaMetodo: string
{
    case Tablet = 'tablet';
    case QrCode = 'qrcode';
    case Gps = 'gps';
    case Facial = 'facial';

    public function label(): string
    {
        return match ($this) {
            self::Tablet => 'Tablet',
            self::QrCode => 'QR Code',
            self::Gps => 'GPS',
            self::Facial => 'Reconhecimento facial',
        };
    }
}
