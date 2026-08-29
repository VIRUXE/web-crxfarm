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
     * @return array{honda: bool, type: ListingType, category: ?PartCategory, chassis: list<string>}
     */
    public static function classify(string $title, ?string $description = null): array
    {
        $text = trim($title.' '.($description ?? ''));

        $honda = self::isHondaRelated($text);
        $type = self::detectType($title);
        $chassis = self::detectChassis($text);

        // Cars carry their single chassis on the model's own column, so the
        // part category does not apply to them.
        $category = $type === ListingType::Car ? null : self::detectCategory($text);

        return [
            'honda' => $honda,
            'type' => $type,
            'category' => $category,
            'chassis' => $chassis,
        ];
    }

    public static function isHondaRelated(string $text): bool
    {
        if (preg_match(self::EXCLUDE, $text)) {
            return false;
        }

        return (bool) (preg_match(self::HONDA, $text) || preg_match(self::UNIVERSAL, $text));
    }

    private static function detectType(string $title): ListingType
    {
        if (preg_match('/\bpart(?:ing)?[ -]?out\b|racing shell|donor|\bshell\b/i', $title)) {
            return ListingType::Car;
        }

        // "1992 Honda civic VX Hatchback 2D", "1993 Honda del sol VTEC Coupe 2D"
        if (preg_match('/\b(19|20)\d{2}\b/', $title)
            && preg_match('/\b(hatchback|sedan|coupe|wagon|minivan|sport utility|suv)\b/i', $title)) {
            return ListingType::Car;
        }

        return ListingType::Part;
    }

    /**
     * @return list<string>
     */
    private static function detectChassis(string $text): array
    {
        $map = [
            'CRX' => '/\bcrx\b|cr-?z/i',
            'Del Sol' => '/\bdel ?sol\b/i',
            'EF' => '/\bef\b/i',
            'EG' => '/\beg\b/i',
            'EK' => '/\bek\b/i',
            'EM1' => '/\bem1\b/i',
            'DA Integra' => '/\bda9?\b.*integra|integra.*\bda9?\b/i',
            'DC2 Integra' => '/\bdc2\b|\bgsr\b|integra.*type ?r/i',
            'Integra' => '/\bintegra\b|\bteg\b/i',
            'Prelude' => '/\bprelude\b/i',
            'Accord' => '/\baccord\b/i',
            'CR-V' => '/\bcr-?v\b/i',
            'Element' => '/\belement\b/i',
            'S2000' => '/\bs2000\b|\bs2k\b|\bap[12]\b/i',
            'RSX' => '/\brsx\b|\bdc5\b|\bep3\b/i',
            'Civic Wagon' => '/civic wagon|wagovan/i',
        ];

        $found = [];
        foreach ($map as $name => $pattern) {
            if (preg_match($pattern, $text)) {
                $found[] = $name;
            }
        }

        // A generic "Civic" with no chassis code stays chassis-less on purpose:
        // it fits too many generations to tag usefully. Universal parts (rims,
        // seats) usually match nothing here, which is correct.
        return array_values(array_unique($found));
    }

    private static function detectCategory(string $text): PartCategory
    {
        // Order matters: a "steering wheel" is interior, not a road wheel; an
        // exhaust header is exhaust, not engine. Specific rules come first.
        $rules = [
            [PartCategory::Interior, '/steering wheel|shift ?(?:knob|boot)|\bseats?\b|door panel|carpet|center console|\bdash\b|dashboard|floor ?mats?|\bvisor\b|headliner|cargo (?:divider|net)|arm ?rest|\bcluster\b|gauges?/i'],
            [PartCategory::ExhaustIntake, '/\bexhaust\b|\bheader\b|down ?pipe|cat ?back|\bmuffler\b|\bintake\b|manifold|throttle body|\bturbo\b/i'],
            [PartCategory::EngineDrivetrain, '/\bengine\b|\bmotor\b|\bswap\b|\bhmo\b|long ?block|bare ?block|\bhead\b|\bvtec\b|transmission|\btrans\b|gearbox|\blsd\b|\bcrank\b|cam ?(?:shaft|gear)?|\bpulley\b|\bclutch\b|flywheel|\baxles?\b|\baxels?\b|sub ?frame|oil ?(?:pan|pickup)|water pump|timing|\bblock\b|shift linkage/i'],
            [PartCategory::SuspensionBrakesWheels, '/coilovers?|suspension|sway ?bar|torsion|control arm|trailing arm|\bbrakes?\b|caliper|\brotors?\b|steering rack|\brims?\b|\bwheels?\b|hubcaps?|\btires?\b|4x100|4x114|spindle|knuckle|\bstrut\b/i'],
            [PartCategory::LightingElectrical, '/head ?lights?|tail ?lights?|corner lights?|brake light|light ?bar|\bleds?\b|\becu\b|harness|wiring|\balarm\b|\bradio\b|stereo|speakers?|tape deck|cd player|antenna|distributor|ignition|alternator|starter/i'],
            [PartCategory::ExteriorBody, '/\bfenders?\b|\bbumper\b|\bhood\b|spoiler|\bwing\b|mud ?flaps?|nose panel|garnish|body kit|\bdoors?\b|\brocker\b|\btarga\b|sunroof|side skirt|\blips?\b|\bglass\b|windshield|mirrors?|\bgrill\b|badge|molding|valance|wide ?body/i'],
        ];

        foreach ($rules as [$category, $pattern]) {
            if (preg_match($pattern, $text)) {
                return $category;
            }
        }

        return PartCategory::Other;
    }
}
