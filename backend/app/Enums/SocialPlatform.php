<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case FacebookPage = 'facebook_page';
    case InstagramProfessional = 'instagram_professional';

    public function label(): string
    {
        return match ($this) {
            self::FacebookPage => 'Facebook Page',
            self::InstagramProfessional => 'Instagram Professional',
        };
    }
}