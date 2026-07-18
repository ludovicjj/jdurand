<?php

namespace App\Enum;

enum EntryType: string
{
    case FILMO = 'filmo';

    public function label(): string
    {
        return match ($this) {
            self::FILMO => 'Filmographie',
        };
    }

    /**
     * Slug de base des pages front seedées : chaque type doit avoir
     * ses pages "<slug>_index" et "<slug>_show" dans app:seed-pages.
     */
    public function pageSlug(): string
    {
        return match ($this) {
            self::FILMO => 'filmo',
        };
    }
}
