<?php

declare(strict_types=1);

namespace App\Enums;

enum ProjectColor: string
{
    case Slate = 'slate';
    case Red = 'red';
    case Orange = 'orange';
    case Amber = 'amber';
    case Green = 'green';
    case Teal = 'teal';
    case Sky = 'sky';
    case Blue = 'blue';
    case Violet = 'violet';
    case Pink = 'pink';

    /**
     * Get the first palette colour not in the given list, so a new project stands out from the others.
     *
     * @param  iterable<self>  $used
     */
    public static function firstUnused(iterable $used): self
    {
        $used = collect($used);

        return collect(self::cases())->first(fn (self $color): bool => ! $used->contains($color)) ?? self::Slate;
    }
}
