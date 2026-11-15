<?php

namespace App\Enums;

enum SocialConnectionStatus: string
{
    case Connected = 'connected';
    case Expired = 'expired';
    case Error = 'error';
    case Disconnected = 'disconnected';
}