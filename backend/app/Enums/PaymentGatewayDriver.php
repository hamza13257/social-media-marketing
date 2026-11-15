<?php

namespace App\Enums;

enum PaymentGatewayDriver: string
{
    case Manual = 'manual';
    case Stripe = 'stripe';
}