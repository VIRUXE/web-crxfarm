<?php

namespace App\Enums;

enum PartCategory: string
{
    case EngineDrivetrain = 'engine_drivetrain';
    case ExteriorBody = 'exterior_body';
    case Interior = 'interior';
    case LightingElectrical = 'lighting_electrical';
    case SuspensionBrakes = 'suspension_brakes';
    case WheelsTires = 'wheels_tires';
    case ExhaustIntake = 'exhaust_intake';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EngineDrivetrain => 'Engine & Drivetrain',
            self::ExteriorBody => 'Exterior & Body',
            self::Interior => 'Interior',
            self::LightingElectrical => 'Lighting & Electrical',
            self::SuspensionBrakes => 'Suspension & Brakes',
            self::WheelsTires => 'Wheels & Tires',
            self::ExhaustIntake => 'Exhaust & Intake',
            self::Other => 'Other / Misc',
        };
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
