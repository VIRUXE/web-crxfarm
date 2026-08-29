<?php

namespace App\Support;

class TitleNormalizer
{
    /**
     * Exact-case / regex replacement table for Honda/automotive domain terms.
     */
    private static array $replacements = [
        // Facebook scrape artifacts
        '/\[hidden information\]/i' => '',

        // Typos & corrections
        '/\bcr-z crx\b/i' => 'CRX',
        '/\benkie\b/i' => 'Enkei',
        '/\baxels\b/i' => 'axles',
        '/\btry y\b/i' => 'Tri-Y',

        // Makes & Brands
        '/\bhonda\b/i' => 'Honda',
        '/\bacura\b/i' => 'Acura',
        '/\bhasport\b/i' => 'Hasport',
        '/\bskunk ?2\b/i' => 'Skunk2',
        '/\bmugen\b/i' => 'Mugen',
        '/\bspoon\b/i' => 'Spoon',
        '/\bnardi\b/i' => 'Nardi',
        '/\benkei\b/i' => 'Enkei',
        '/\bkonig\b/i' => 'Konig',
        '/\brota\b/i' => 'Rota',
        '/\bwhelen\b/i' => 'Whelen',
        '/\binvidia\b/i' => 'Invidia',
        '/\bblackworks\b/i' => 'Blackworks',
        '/\bsparco\b/i' => 'Sparco',
        '/\blebra\b/i' => 'LeBra',
        '/\bgathers\b/i' => 'Gathers',
        '/\btranstop\b/i' => 'Transtop',
        '/\btop fuel\b/i' => 'Top Fuel',
        '/\bcharge speed\b/i' => 'Charge Speed',
        '/\bbuddy club\b/i' => 'Buddy Club',
        '/\bstanley\b/i' => 'Stanley',
        '/\bbride\b/i' => 'Bride',

        // Models
        '/\bcivic\b/i' => 'Civic',
        '/\bcrx\b/i' => 'CRX',
        '/\bdel ?sol\b/i' => 'Del Sol',
        '/\bcr-?v\b/i' => 'CR-V',
        '/\bcr-?z\b/i' => 'CR-Z',
        '/\bintegra\b/i' => 'Integra',
        '/\bprelude\b/i' => 'Prelude',
        '/\baccord\b/i' => 'Accord',
        '/\belement\b/i' => 'Element',
        '/\bodyssey\b/i' => 'Odyssey',
        '/\bpilot\b/i' => 'Pilot',
        '/\bs2000\b/i' => 'S2000',
        '/\bs2k\b/i' => 'S2K',
        '/\brsx\b/i' => 'RSX',
        '/\bnsx\b/i' => 'NSX',
        '/\btsx\b/i' => 'TSX',
        '/\btlx\b/i' => 'TLX',
        '/\bcl\b/i' => 'CL',
        '/\btl\b/i' => 'TL',
        '/\brl\b/i' => 'RL',
        '/\bmdx\b/i' => 'MDX',
        '/\brdx\b/i' => 'RDX',
        '/\bfit\b/i' => 'Fit',
        '/\bwagovan\b/i' => 'Wagovan',

        // Chassis codes
        '/\bef\b/i' => 'EF',
        '/\beg\b/i' => 'EG',
        '/\beg6\b/i' => 'EG6',
        '/\bek\b/i' => 'EK',
        '/\bek4\b/i' => 'EK4',
        '/\bek9\b/i' => 'EK9',
        '/\bem1\b/i' => 'EM1',
        '/\bep3\b/i' => 'EP3',
        '/\bdc2\b/i' => 'DC2',
        '/\bdc5\b/i' => 'DC5',
        '/\bdc\b/i' => 'DC',
        '/\bda\b/i' => 'DA',
        '/\bda9\b/i' => 'DA9',
        '/\bdb8\b/i' => 'DB8',
        '/\brd1\b/i' => 'RD1',
        '/\bap1\b/i' => 'AP1',
        '/\bap2\b/i' => 'AP2',
        '/\bbb4\b/i' => 'BB4',
        '/\bbb6\b/i' => 'BB6',
        '/\bra1\b/i' => 'RA1',
        '/\b3g\b/i' => '3G',

        // Trims
        '/\btype[ -]r\b/i' => 'Type R',
        '/\btype[ -]s\b/i' => 'Type S',
        '/\bsir\b/i' => 'SiR',
        '/\bsi\b/i' => 'Si',
        '/\bgsr\b/i' => 'GSR',
        '/\bdx\b/i' => 'DX',
        '/\bex\b/i' => 'EX',
        '/\blx\b/i' => 'LX',
        '/\bcx\b/i' => 'CX',
        '/\bhx\b/i' => 'HX',
        '/\bhf\b/i' => 'HF',
        '/\bse\b/i' => 'SE',
        '/\bgs\b/i' => 'GS',
        '/\bls\b/i' => 'LS',
        '/\bvx\b/i' => 'VX',
        '/\bex-l\b/i' => 'EX-L',
        '/\brt4wd\b/i' => 'RT4WD',

        // Honda / Auto Acronyms
        '/\boem\b/i' => 'OEM',
        '/\bjdm\b/i' => 'JDM',
        '/\bedm\b/i' => 'EDM',
        '/\bukdm\b/i' => 'UKDM',
        '/\busdm\b/i' => 'USDM',
        '/\bvtec\b/i' => 'VTEC',
        '/\bsohc\b/i' => 'SOHC',
        '/\bdohc\b/i' => 'DOHC',
        '/\bmpfi\b/i' => 'MPFI',
        '/\bdpfi\b/i' => 'DPFI',
        '/\blsd\b/i' => 'LSD',
        '/\bawd\b/i' => 'AWD',
        '/\b4wd\b/i' => '4WD',
        '/\b4ws\b/i' => '4WS',
        '/\bfwd\b/i' => 'FWD',
        '/\brwd\b/i' => 'RWD',
        '/\bnon[ -]?srs\b/i' => 'Non-SRS',
        '/\bsrs\b/i' => 'SRS',
        '/\becu\b/i' => 'ECU',
        '/\blhd\b/i' => 'LHD',
        '/\brhd\b/i' => 'RHD',
        '/\bps\b/i' => 'PS',
        '/\bpw\b/i' => 'PW',
        '/\bpl\b/i' => 'PL',
        '/\bobd[ -]?0\b/i' => 'OBD0',
        '/\bobd[ -]?1\b/i' => 'OBD1',
        '/\bobd[ -]?2\b/i' => 'OBD2',
        '/\bleds\b/i' => 'LEDs',
        '/\bled\b/i' => 'LED',
        '/\bcd\b/i' => 'CD',
        '/\bac\b/i' => 'AC',
        '/\bhmo\b/i' => 'HMO',
        '/\bnrg\b/i' => 'NRG',
        '/\bhks\b/i' => 'HKS',
        '/\bjnc\b/i' => 'JNC',
        '/\bbbs\b/i' => 'BBS',
        '/\bssr\b/i' => 'SSR',
        '/\boz\b/i' => 'OZ',
        '/\b2d\b/i' => '2D',
        '/\b4d\b/i' => '4D',
        '/\b3dr\b/i' => '3-Door',

        // Specific codes / transmissions
        '/\bzc\b/i' => 'ZC',
        '/\bys1\b/i' => 'YS1',
        '/\bm24a\b/i' => 'M24A',
        '/\bmpxa\b/i' => 'MPXA',
        '/\batc6\b/i' => 'ATC6',
        '/\bs4c\b/i' => 'S4C',
        '/\ba000\b/i' => 'A000',
        '/\bp72\b/i' => 'P72',
        '/\bp28\b/i' => 'P28',
        '/\bp06\b/i' => 'P06',
        '/\bpr4\b/i' => 'PR4',
        '/\bp30\b/i' => 'P30',
        '/\bp73\b/i' => 'P73',
        '/\bk13\b/i' => 'K13',
        '/\bj1\b/i' => 'J1',
    ];

    /**
     * Normalizes a listing title:
     * - Cleans whitespace and newlines
     * - Removes scraping junk like [hidden information]
     * - Applies Title Case to standard English words
     * - Corrects domain-specific acronyms, engine codes, chassis codes, and brand names
     */
    public static function normalize(string $title): string
    {
        // 1. Unify whitespace, strip newlines/tabs
        $cleaned = preg_replace('/[\r\n\t]+/', ' ', $title);
        $cleaned = preg_replace('/\s+/', ' ', (string) $cleaned);
        $cleaned = trim((string) $cleaned);

        if ($cleaned === '') {
            return '';
        }

        // 2. Remove scrape artifacts
        $cleaned = (string) preg_replace('/\[hidden information\]/i', '', $cleaned);
        $cleaned = trim((string) preg_replace('/\s+/', ' ', $cleaned));

        // 3. Convert to title case baseline
        $titleCased = self::toTitleCase($cleaned);

        // 4. Apply engine code uppercase regex
        // Covers patterns like B16, B16A, B16A2, D16Z6, K20A3, K24A4, F20B, H22A4, A20A1, etc.
        $titleCased = (string) preg_replace_callback(
            '/\b([bdfhjk]1[5-8][a-z]?\d?|[bdfhjk]2[0234][a-z]?\d?|a20[a-z]?\d?|k20[a-z]?\d?|k24[a-z]?\d?|d15[a-z]?\d?|d16[a-z]?\d?|d17[a-z]?\d?|b16[a-z]?\d?|b18[a-z]?\d?|b20[a-z]?\d?|f20[a-z]?\d?|f22[a-z]?\d?|f23[a-z]?\d?|h22[a-z]?\d?)\b/i',
            fn ($matches) => strtoupper($matches[1]),
            $titleCased
        );

        // Engine series mentions (e.g., "B series" -> "B Series", "D series" -> "D Series")
        $titleCased = (string) preg_replace_callback(
            '/\b([bdfhjk])\s+series\b/i',
            fn ($matches) => strtoupper($matches[1]).' Series',
            $titleCased
        );

        // Bolt pattern mentions (e.g. 4X100 -> 4x100, 5X114.3 -> 5x114.3)
        $titleCased = (string) preg_replace_callback(
            '/\b(\d)[xX](\d+(?:\.\d+)?)\b/',
            fn ($matches) => $matches[1].'x'.$matches[2],
            $titleCased
        );

        // Honda OEM and alphanumeric part numbers (e.g. 39100-SD8-7700-M1, 37820-PNF-A05, TD-02U)
        $titleCased = (string) preg_replace_callback(
            '/\b([A-Za-z0-9]+-[A-Za-z0-9]+-[A-Za-z0-9]+(?:-[A-Za-z0-9]+)?|TD-\d{2}[A-Za-z]?)\b/i',
            fn ($matches) => strtoupper($matches[1]),
            $titleCased
        );

        // 5. Apply domain term dictionary replacements
        foreach (self::$replacements as $pattern => $replacement) {
            $titleCased = (string) preg_replace($pattern, $replacement, $titleCased);
        }

        // 6. Fix possessives and contractions (e.g. 17'S -> 17's, 80'S -> 80's)
        $titleCased = (string) preg_replace('/(\w)\'S\b/', '$1\'s', $titleCased);

        // 7. Clean up trailing/leading punctuation or duplicate spaces that may have resulted
        $titleCased = trim((string) preg_replace('/\s+/', ' ', $titleCased));

        return $titleCased;
    }

    /**
     * Converts a string to Title Case while respecting minor words (unless at start/after punctuation).
     */
    private static function toTitleCase(string $text): string
    {
        $minorWords = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'en', 'for', 'if', 'in', 'of', 'on', 'or', 'the', 'to', 'via', 'with', 'off'];

        // Split text by whitespace, preserving word boundaries
        $words = preg_split('/(\s+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($words === false) {
            return ucwords(strtolower($text));
        }

        $result = '';
        $wordIndex = 0;

        foreach ($words as $part) {
            if (trim($part) === '') {
                $result .= $part;

                continue;
            }

            $lower = strtolower($part);
            $isFirst = ($wordIndex === 0);

            // Clean punctuation to check root word
            $cleanWord = trim($lower, "(),.-/:;'\"[]{}");

            // Capitalize if first word, or if part starts with opening bracket/paren/quote
            $startsEnclosed = preg_match('/^[\(\[\{"\']/', $part) === 1;

            if (! $isFirst && ! $startsEnclosed && in_array($cleanWord, $minorWords, true)) {
                $result .= $lower;
            } else {
                // Split by internal hyphens, slashes, or opening parens
                $subParts = preg_split('/([-|\/\(\[\{"\'])/', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
                $casedSub = '';
                foreach ($subParts as $sp) {
                    if (preg_match('/^[-|\/\(\[\{"\']$/', $sp)) {
                        $casedSub .= $sp;
                    } else {
                        $casedSub .= ucfirst(strtolower($sp));
                    }
                }
                $result .= $casedSub;
            }

            $wordIndex++;
        }

        return $result;
    }
}
