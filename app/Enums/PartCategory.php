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
     * @return array<string, array{label: string, keywords: list<string>}>
     */
    public function tags(): array
    {
        return match ($this) {
            self::EngineDrivetrain => [
                'transmissions' => ['label' => 'Transmissions', 'keywords' => ['trans', 'transmission', 'gearbox', 'lsd', 'j1', 'y1', 's80', 's40', 'ys1', 's20', 's4c']],
                'swaps_motors' => ['label' => 'Swaps & Motors', 'keywords' => ['swap', 'motor', 'block', 'long block', 'bare block', 'short block', 'engine', 'hmo']],
                'cylinder_heads' => ['label' => 'Cylinder Heads', 'keywords' => ['head', 'heads', 'vtec head', 'valve cover']],
                'b_series' => ['label' => 'B-Series', 'keywords' => ['b16', 'b18', 'b20', 'b series', 'b-series', 'gsr', 'ls trans', 'b18a1', 'b18b1', 'b18c', 'b18c1', 'b18c5', 'b16a', 'b16a2']],
                'd_series' => ['label' => 'D-Series', 'keywords' => ['d15', 'd16', 'd17', 'd series', 'd-series', 'd16z6', 'd16y8', 'd16a6', 'd17a2', 'zc']],
                'k_series' => ['label' => 'K-Series', 'keywords' => ['k20', 'k24', 'k series', 'k-series', 'k20z3', 'k24a4', 'k24a2', 'k20a2']],
                'hf_series' => ['label' => 'H & F-Series', 'keywords' => ['h22', 'h23', 'f20', 'f22', 'f23', 'h-series', 'h series', 'f-series', 'f series', 'h22a', 'h22a4']],
                'cams_valvetrain' => ['label' => 'Cams & Valvetrain', 'keywords' => ['cam', 'camshaft', 'cam gear', 'piston', 'rod', 'crank', 'pulley', 'springs', 'valves']],
                'axles_clutches' => ['label' => 'Axles & Clutches', 'keywords' => ['axle', 'axles', 'axels', 'clutch', 'flywheel', 'half shaft']],
                'cooling_pumps' => ['label' => 'Cooling & Pumps', 'keywords' => ['water pump', 'oil pan', 'thermostat', 'pump', 'housing', 'radiator', 'gas tank']],
                'shift_linkages' => ['label' => 'Shift Linkages & Mounts', 'keywords' => ['shift linkage', 'linkage', 'mount', 'mounts', 'subframe']],
            ],
            self::ExteriorBody => [
                'bumpers_lips' => ['label' => 'Bumpers & Lips', 'keywords' => ['bumper', 'lip', 'lips', 'valance']],
                'fenders' => ['label' => 'Fenders', 'keywords' => ['fender', 'fenders']],
                'hatch_doors' => ['label' => 'Hatch & Doors', 'keywords' => ['hatch', 'door', 'doors', 'tailgate', 'trunk']],
                'garnish_trim' => ['label' => 'Garnish & Trim', 'keywords' => ['garnish', 'nose panel', 'trim', 'panel', 'grill', 'grille', 'badge', 'emblem']],
                'spoilers_wings' => ['label' => 'Spoilers & Wings', 'keywords' => ['spoiler', 'wing', 'wings']],
                'sunroofs_targa' => ['label' => 'Sunroofs & Targa', 'keywords' => ['sunroof', 'targa', 'seal', 'seals']],
                'body_kits' => ['label' => 'Body Kits & Skirts', 'keywords' => ['body kit', 'wide body', 'side skirt', 'skirts', 'rocker']],
                'mud_flaps' => ['label' => 'Mud Flaps', 'keywords' => ['mud flap', 'mudflaps', 'flaps']],
                'mirrors_glass' => ['label' => 'Mirrors & Glass', 'keywords' => ['mirror', 'mirrors', 'glass', 'windshield', 'bra', 'hood bra']],
            ],
            self::Interior => [
                'seats' => ['label' => 'Seats & Rails', 'keywords' => ['seat', 'seats', 'rails', 'seat rails', 'seat fabric', 'leather interior']],
                'gauge_clusters' => ['label' => 'Gauge Clusters', 'keywords' => ['cluster', 'gauge', 'gauges']],
                'steering_wheels' => ['label' => 'Steering Wheels', 'keywords' => ['steering wheel', 'wheel']],
                'door_panels' => ['label' => 'Door Panels & Handles', 'keywords' => ['door panel', 'door panels', 'door card', 'door cards', 'door handle', 'door handles', 'door cup']],
                'consoles_dash' => ['label' => 'Consoles & Dash', 'keywords' => ['console', 'bezel', 'climate control', 'dash', 'dashboard', 'shift knob', 'shift boot']],
                'carpet_mats' => ['label' => 'Carpet & Mats', 'keywords' => ['carpet', 'floor mat', 'mats', 'trunk carpet']],
                'cargo_dividers' => ['label' => 'Cargo Dividers', 'keywords' => ['cargo', 'divider']],
                'audio_radios' => ['label' => 'Audio & Electronics', 'keywords' => ['radio', 'stereo', 'tape deck', 'cassette', 'cd player', 'speaker', 'speakers', 'gathers']],
            ],
            self::LightingElectrical => [
                'headlights' => ['label' => 'Headlights', 'keywords' => ['headlight', 'headlights', 'light bar']],
                'tail_lights' => ['label' => 'Tail & Brake Lights', 'keywords' => ['tail light', 'taillights', 'taillight', 'tail lights', 'brake light', '3rd brake', 'third brake']],
                'corner_lights' => ['label' => 'Corner & Fog Lights', 'keywords' => ['corner light', 'corner lights', 'fog light', 'fog lights']],
                'ecus_sensors' => ['label' => 'ECUs & Electronics', 'keywords' => ['ecu', 'computer', 'pcm', 'sensor', 'sensors', 'relay']],
                'wiring_harnesses' => ['label' => 'Wiring & Harnesses', 'keywords' => ['harness', 'wiring', 'adapter harness', 'antenna']],
                'distributors' => ['label' => 'Distributors & Starters', 'keywords' => ['distributor', 'ignition', 'starter', 'alternator']],
                'audio_speakers' => ['label' => 'Audio & Speakers', 'keywords' => ['radio', 'stereo', 'tape deck', 'cd player', 'speaker', 'speakers', 'gathers']],
            ],
            self::SuspensionBrakes => [
                'struts_bars' => ['label' => 'Struts & Sway Bars', 'keywords' => ['strut', 'struts', 'tower bar', 'sway bar', 'torsion']],
                'steering_subframes' => ['label' => 'Steering & Subframes', 'keywords' => ['steering', 'subframe', 'actuator', 'rack', '4ws']],
                'brakes_drums' => ['label' => 'Brakes & Cables', 'keywords' => ['brake', 'caliper', 'drum', 'drums', 'master cylinder', 'e brake', 'brake cable']],
                'control_arms' => ['label' => 'Control & Trailing Arms', 'keywords' => ['trailing arm', 'control arm', 'camber', 'arms']],
            ],
            self::WheelsTires => [
                'rims_wheels' => ['label' => 'Rims & Wheels', 'keywords' => ['rim', 'rims', 'wheel', 'wheels', 'bbs', 'mugen', 'konig', 'enkei', 'enkie', 'rota', 'drag', 'volk', 'work', 'ssr', 'jdm', '14s', '15s', '16s', '17s', '4x100', '4x114', '5x114', '5x120', 'blade', 'mesh', 'weaves', 'helix', 'tri spoke']],
                'tires' => ['label' => 'Tires', 'keywords' => ['tire', 'tires']],
                'center_caps' => ['label' => 'Center Caps & Hubcaps', 'keywords' => ['center cap', 'caps', 'hubcap', 'hubcaps']],
            ],
            self::ExhaustIntake => [
                'intake_manifolds' => ['label' => 'Intake Manifolds', 'keywords' => ['intake manifold', 'intake']],
                'exhaust_systems' => ['label' => 'Exhaust Systems', 'keywords' => ['exhaust', 'muffler', 'cat back', 'cat-back', 'invidia']],
                'turbos' => ['label' => 'Turbos & Downpipes', 'keywords' => ['turbo', 'downpipe']],
                'headers' => ['label' => 'Headers & Manifolds', 'keywords' => ['header', 'headers', 'exhaust manifold']],
                'throttle_bodies' => ['label' => 'Throttle Bodies', 'keywords' => ['throttle body']],
            ],
            self::Other => [
                'brackets_mounts' => ['label' => 'Brackets & Mounts', 'keywords' => ['bracket', 'brackets', 'mount', 'mounts', 'traction bar', 'core support']],
                'trim_moldings' => ['label' => 'Trim & Moldings', 'keywords' => ['trim', 'molding', 'cover', 'latch', 'delete']],
                'fuel_cooling' => ['label' => 'Fuel & Cooling', 'keywords' => ['gas tank', 'fuel', 'radiator', 'fan', 'ac delete', 'table']],
                'switches_controls' => ['label' => 'Switches & Controls', 'keywords' => ['switch', 'combo switch', 'shift box']],
            ],
        };
    }

    /**
     * @return array<string, array{label: string, keywords: list<string>}>
     */
    public static function tagsFor(PartCategory|string|null $category): array
    {
        if (! $category) {
            return [];
        }

        $enum = $category instanceof self ? $category : self::tryFrom((string) $category);

        return $enum?->tags() ?? [];
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
