<?php

namespace App\Support;

use App\Enums\ListingType;
use App\Enums\PartCategory;

/**
 * Classifies a scraped Marketplace listing into the catalog's own shape:
 * whether it is Honda-related, a part vs a donor car, its part category, and
 * which chassis it fits. Everything is heuristic on the title (and description
 * as a fallback), so it is deliberately conservative: unknown chassis is left
 * empty, and anything it cannot place lands in PartCategory::Other.
 */
class ListingClassifier
{
    /**
     * Other marques, powersports, and household junk this Honda seller also
     * lists. If one of these matches, the listing is not Honda-related even if
     * a generic part word (seat, rim) also appears.
     */
    private const EXCLUDE = '/\b(jet ?ski|sea ?doo|polaris|yamaha|tigershark|kawasaki|waverunner|kayak|pontoon|boat|trailer|shorelander|haul ?rite|yacht club|gravely|zero turn|mower|toyota|prius|corolla|nissan|frontier|\bford\b|focus|mustang|ranger|volkswagen|jetta|beetle|\bkia\b|mazda|cx-?7|dodge|daytona|isuzu|mitsubishi|subaru|suburu|\bbmw\b|spectrum|cessna|razor|microwave|scaffolding|rtx|geforce|nvidia|thinkcentre|lenovo|\bxbox\b|blackstone|snickers|gaming computer|mancave|amplifier|\bdj\b)\b/i';

    /** Honda / Acura marques, models, chassis codes, and engine codes. */
    private const HONDA = '/\b(honda|acura|civic|crx|cr-?z|del ?sol|integra|prelude|accord|element|cr-?v|odyssey|pilot|s2000|s2k|rsx|\bfit\b|\bef\b|\beg\b|\bek\b|em1|ep3|dc2|dc5|\bda\b|\brd1\b|vtec|\bgsr\b|\bsir?\b|jdm|hasport|skunk2|mugen|spoon|[bdfhjk]1[5-8][a-z]?\d?|[bdfhjk]2[024][a-z]?\d?|zc\b)\b/i';

    /** Generic aftermarket parts a Honda seller carries; kept even with no marque. */
    private const UNIVERSAL = '/\b(rims?|wheels?|hubcaps?|tires?|4x100|4x114|5x114|bolt pattern|seats?|steering wheel|coilovers?|exhaust|header|shift knob|bbs|enke|enkie|konig|\boz\b|rota|\bssr\b|\bwork\b|drag rims?|volk|racing seat)\b/i';

    /**
     * @return array{honda: bool, type: ListingType, category: ?PartCategory, chassis: list<string>, clean_price: ?string, bolt_pattern: ?string}
     */
    public static function classify(string $title, ?string $description = null, ?string $price = null): array
    {
        $text = trim($title.' '.($description ?? ''));

        $honda = self::isHondaRelated($text);
        $type = self::detectType($title, $description, $price);
        $chassis = self::detectChassis($title, $description);

        // Cars carry their single chassis on the model's own column, so the
        // part category and bolt pattern do not apply to them.
        $category = $type === ListingType::Car ? null : self::detectCategory($text);
        $cleanPrice = self::cleanPrice($price);
        $boltPattern = $type === ListingType::Car ? null : self::detectBoltPattern($text);

        return [
            'honda' => $honda,
            'type' => $type,
            'category' => $category,
            'chassis' => $chassis,
            'clean_price' => $cleanPrice,
            'bolt_pattern' => $boltPattern,
        ];
    }

    public static function isHondaRelated(string $text): bool
    {
        if (preg_match(self::EXCLUDE, $text)) {
            return false;
        }

        return (bool) (preg_match(self::HONDA, $text) || preg_match(self::UNIVERSAL, $text));
    }

    /**
     * Determine if a price is a placeholder like $123, $1,234, $123456, etc.
     * typically used for part outs where listing all individual part prices is impractical.
     */
    public static function isPlaceholderPrice(?string $price): bool
    {
        if ($price === null || trim($price) === '') {
            return false;
        }

        $digits = preg_replace('/[^\d]/', '', $price);

        if ($digits === '') {
            return false;
        }

        return in_array($digits, ['123', '1234', '12345', '123456', '1234567', '12345678', '1', '0', '111', '1111', '999999'], true)
            || (bool) preg_match('/^123\d*$/', $digits);
    }

    /**
     * Cleans placeholder prices into null so they display as "Ask for price".
     */
    public static function cleanPrice(?string $price): ?string
    {
        if ($price === null) {
            return null;
        }

        $trimmed = trim($price);

        if ($trimmed === '' || self::isPlaceholderPrice($trimmed)) {
            return null;
        }

        return $trimmed;
    }

    private static function detectType(string $title, ?string $description = null, ?string $price = null): ListingType
    {
        $text = trim($title.' '.($description ?? ''));

        $hasPartOutSignal = (bool) preg_match('/\b(part(?:ing)?[ -]?out|racing shell|donor|\bshell\b|roller|parts car|for parts|will not sell whole|not for sale whole|parts only)\b/i', $text);
        $hasVehicleYear = (bool) preg_match('/\b(19|20)\d{2}\b/', $title);
        $hasBodyStyle = (bool) preg_match('/\b(hatchback|hatch|sedan|coupe|wagon|wagon 4wd|convertible|minivan|sport utility|suv)\b/i', $title);
        $hasVehicleModel = (bool) preg_match('/\b(honda|acura|civic|crx|del ?sol|integra|prelude|accord|element|cr-?v|odyssey|pilot|s2000|rsx|fit)\b/i', $title);
        $hasSpecificPartWord = (bool) preg_match('/\b(cluster|bumper supports?|door panels?|lips?|rims?|wheels?|hood|lights?|brackets?|intake manifold|header|distributor|garnish|mud flaps?|drums?|seats?|steering wheel|motor for parts|engine parts)\b/i', $title);

        if ($hasSpecificPartWord) {
            return ListingType::Part;
        }

        if ($hasVehicleYear && ($hasBodyStyle || ($hasVehicleModel && $hasPartOutSignal))) {
            return ListingType::Car;
        }

        if ($hasPartOutSignal && ($hasBodyStyle || $hasVehicleModel || preg_match('/\b(shell|roller|donor|part ?out)\b/i', $title))) {
            return ListingType::Car;
        }

        if ($hasVehicleYear && $hasBodyStyle) {
            return ListingType::Car;
        }

        return ListingType::Part;
    }

    /**
     * @return list<string>
     */
    public static function detectChassis(string $title, ?string $description = null): array
    {
        // Strip "crx farm" signature to prevent matching CRX on seller business name
        $cleanedText = (string) preg_replace('/\bcrx ?farm\b/i', '', $title.' '.($description ?? ''));
        $cleanedTitle = (string) preg_replace('/\bcrx ?farm\b/i', '', $title);

        $map = [
            'CRX' => '/\bcrx\b|cr-?z/i',
            'Del Sol' => '/\bdel ?sol\b/i',
            'EM1' => '/\bem1\b|\b(?:1999|2000|99|00)\s+(?:honda\s+)?civic\s+si\b/i',
            'EF' => '/\bef\b|\b(?:198[89]|199[01]|8[89]|9[01])\s+(?:honda\s+)?civic\b/i',
            'EG' => '/\beg\b|\b(?:199[2-5]|9[2-5])\s+(?:honda\s+)?civic\b/i',
            'EK' => '/\bek\b|\b(?:199[6-9]|2000|9[6-9]|00)\s+(?:honda\s+)?civic\b/i',
            'DA Integra' => '/\bda[9]?\b|\b(?:199[0-3]|9[0-3])\s+(?:acura\s+)?integra\b/i',
            'DC2 Integra' => '/\bdc2\b|\bgsr\b|\b(?:199[4-9]|200[01]|9[4-9]|0[01])\s+(?:acura\s+)?integra\b/i',
            'Integra' => '/\bintegra\b|\bteg\b/i',
            'Prelude' => '/\bprelude\b/i',
            'Accord' => '/\baccord\b/i',
            'CR-V' => '/\bcr-?v\b|\brd1\b/i',
            'Element' => '/\belement\b/i',
            'Odyssey' => '/\bodyssey\b/i',
            'Pilot' => '/\bpilot\b/i',
            'Fit' => '/\bfit\b/i',
            'S2000' => '/\bs2000\b|\bs2k\b|\bap[12]\b/i',
            'RSX' => '/\brsx\b|\bdc5\b|\bep3\b/i',
            'Civic Wagon' => '/civic wagon|wagovan/i',
        ];

        $found = [];
        foreach ($map as $name => $pattern) {
            if (preg_match($pattern, $cleanedTitle)) {
                $found[] = $name;
            }
        }

        if (empty($found)) {
            foreach ($map as $name => $pattern) {
                if (preg_match($pattern, $cleanedText)) {
                    $found[] = $name;
                }
            }
        }

        return array_values(array_unique($found));
    }

    private static function detectCategory(string $text): PartCategory
    {
        // Order matters: a "steering wheel" is interior, not a road wheel; an
        // exhaust header is exhaust, not engine. Specific rules come first.
        $rules = [
            [PartCategory::Interior, '/steering wheel|shift ?(?:knob|boot)|\bseats?\b|door panel|carpet|center console|\bdash\b|dashboard|floor ?mats?|\bvisor\b|headliner|cargo (?:divider|net)|arm ?rest|\bcluster\b|gauges?/i'],
            [PartCategory::LightingElectrical, '/3rd brake light|third brake light|head ?lights?|tail ?lights?|corner lights?|brake light|light ?bar|\bleds?\b|\becu\b|harness|wiring|\balarm\b|\bradio\b|stereo|speakers?|tape deck|cd player|antenna|distributor|ignition|alternator|starter/i'],
            [PartCategory::SuspensionBrakes, '/(?:4ws|4-wheel-steering|steering actuator)|coilovers?|suspension|sway ?bar|torsion|control arm|trailing arm|\bbrakes?\b|caliper|\brotors?\b|steering rack|spindle|knuckle|\bstrut\b|e[ -]?brake cables?|drums?\b|camber kit/i'],
            [PartCategory::WheelsTires, '/\b(rims?|wheels?|hubcaps?|tires?|4x100|4x114(?:\.3)?|4x113|5x114(?:\.3)?|5x113|5x120|bbs|enke|enkie|konig|\boz\b|rota|\bssr\b|\bwork\b|drag rims?|volk|phone dial|gsr blade|ls mesh|basket weaves?)\b/i'],
            [PartCategory::ExhaustIntake, '/\bexhaust\b|\bheader\b|down ?pipe|cat ?back|\bmuffler\b|\bintake\b|manifold|throttle body|\bturbo\b/i'],
            [PartCategory::EngineDrivetrain, '/\bengine\b|\bmotor\b|\bswap\b|\bhmo\b|long ?block|bare ?block|\bhead\b|\bvtec\b|transmission|\btrans\b|gearbox|\blsd\b|\bcrank\b|cam ?(?:shaft|gear)?|\bpulley\b|\bclutch\b|flywheel|\baxles?\b|\baxels?\b|sub ?frame|oil ?(?:pan|pickup)|water pump|timing|\bblock\b|shift linkage/i'],
            [PartCategory::ExteriorBody, '/\bfenders?\b|\bbumper\b|\bhood\b|spoiler|\bwing\b|mud ?flaps?|nose panel|garnish|body kit|\bdoors?\b|\brocker\b|\btarga\b|sunroof|side skirt|\blips?\b|\bglass\b|windshield|mirrors?|\bgrill\b|badge|molding|valance|wide ?body/i'],
        ];

        foreach ($rules as [$category, $pattern]) {
            if (preg_match($pattern, $text)) {
                return $category;
            }
        }

        return PartCategory::Other;
    }

    /**
     * Detects Honda/Acura stud / bolt pattern: 4x100, 4x114.3, 5x114.3, 5x120
     */
    public static function detectBoltPattern(string $text): ?string
    {
        $patterns = [];

        if (preg_match('/\b4x100\b/i', $text)) {
            $patterns[] = '4x100';
        }
        if (preg_match('/\b(?:4x114(?:\.3)?|4x113)\b/i', $text)) {
            $patterns[] = '4x114.3';
        }
        if (preg_match('/\b(?:5x114(?:\.3)?|5x113)\b/i', $text)) {
            $patterns[] = '5x114.3';
        }
        if (preg_match('/\b5x120\b/i', $text)) {
            $patterns[] = '5x120';
        }

        // Infer OEM Honda and well-known wheel patterns if not explicitly mentioned
        if (empty($patterns)) {
            if (preg_match('/\b(crx (?:si )?rims?|phone dial|hx rims?|em1 (?:si )?rims?|gsr blade|ls mesh|mugen (?:14s )?mr5|del sol hubcaps|oz racing rims|drag rims)\b/i', $text)) {
                $patterns[] = '4x100';
            } elseif (preg_match('/\b(enkie? 17(?:\'s| rims)?)\b/i', $text)) {
                $patterns[] = '4x100';
                $patterns[] = '4x114.3';
            } elseif (preg_match('/\b(crv rims?|rsx rims?)\b/i', $text)) {
                $patterns[] = '5x114.3';
            }
        }

        if (empty($patterns)) {
            return null;
        }

        return implode(', ', array_unique($patterns));
    }
}
