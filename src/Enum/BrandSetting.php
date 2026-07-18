<?php

namespace App\Enum;

enum BrandSetting: string
{
    case SITE_NAME = 'site_name';
    case GSC_TOKEN = 'gsc_token';
    case BIO_FR = 'bio_fr';
    case BIO_EN = 'bio_en';
}
