<?php

namespace App\Enums;

enum ContentFormat: string
{
    case SingleImage = 'single_image';
    case Carousel = 'carousel';
    case Reel = 'reel';
    case Story = 'story';
    case Article = 'article';
}