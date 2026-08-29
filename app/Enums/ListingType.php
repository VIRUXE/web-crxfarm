<?php

namespace App\Enums;

enum ListingType: string
{
    case Part = 'part';
    case Car = 'car';

    public function label(): string
    {
        return match ($this) {
            self::Part => 'Individual part',
            self::Car => 'Donor car',
        };
    }

    public function isCar(): bool
    {
        return $this === self::Car;
    }

    public function isPart(): bool
    {
        return $this === self::Part;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
