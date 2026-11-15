<?php

namespace App\Enums;

enum PublishingLogStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Failed = 'failed';
}