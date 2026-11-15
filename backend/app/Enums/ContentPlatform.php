<?php

namespace App\Enums;

enum ContentPlatform: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';

    public static function fromSocialPlatform(SocialPlatform $platform): self
    {
        return match ($platform) {
            SocialPlatform::InstagramProfessional => self::Instagram,
            SocialPlatform::FacebookPage => self::Facebook,
        };
    }
}