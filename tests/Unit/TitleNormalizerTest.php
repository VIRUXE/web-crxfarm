<?php

namespace Tests\Unit;

use App\Support\TitleNormalizer;
use PHPUnit\Framework\TestCase;

class TitleNormalizerTest extends TestCase
{
    public function test_normalizes_basic_title_case_and_whitespace(): void
    {
        $this->assertSame(
            '1992 Honda Civic VX Hatchback 2D',
            TitleNormalizer::normalize('1992 honda civic vx hatchback 2d')
        );

        $this->assertSame(
            'CRX Sunroof Panels',
            TitleNormalizer::normalize("  CRX   sunroof   panels \n\t ")
        );
    }

    public function test_removes_facebook_scrape_hidden_information_artifacts(): void
    {
        $this->assertSame(
            'Skunk2 Racing Pro Series Cam Gear Set',
            TitleNormalizer::normalize('Skunk2 Racing [hidden information] Pro Series Cam Gear Set')
        );
    }

    public function test_normalizes_chassis_and_model_codes(): void
    {
        $this->assertSame(
            '05-06 Honda S2000 AP2 Adapter Harness for EG',
            TitleNormalizer::normalize('05-06 honda s2000 ap2 adapter harness for eg')
        );

        $this->assertSame(
            'EM1 B16 Transmission 99,00 Civic Si',
            TitleNormalizer::normalize('em1 b16 transmission 99,00 civic si')
        );

        $this->assertSame(
            'DC2 Integra Type R Strut Tower Bar',
            TitleNormalizer::normalize('dc2 integra type r strut tower bar')
        );

        $this->assertSame(
            '1995 Honda Del Sol Si Coupe 2D',
            TitleNormalizer::normalize('1995 honda del sol si coupe 2d')
        );

        $this->assertSame(
            '1998 Honda CR-V LX Sport Utility 4D',
            TitleNormalizer::normalize('1998 honda cr-v lx sport utility 4d')
        );
    }

    public function test_normalizes_engine_codes_and_acronyms(): void
    {
        $this->assertSame(
            'B18C1 Bare Block GSR',
            TitleNormalizer::normalize('b18c1 bare block gsr')
        );

        $this->assertSame(
            'D16Z6 VTEC Honda Refreshed Engine',
            TitleNormalizer::normalize('d16z6 vtec honda refreshed engine')
        );

        $this->assertSame(
            'F20B HMO (Full Swap) LSD Transmission',
            TitleNormalizer::normalize('f20b hmo (full swap) lsd transmission')
        );

        $this->assertSame(
            'Del Sol Targa Seals OEM Used Good Condition Honda UKDM',
            TitleNormalizer::normalize('del sol targa seals oem used good condition honda ukdm')
        );

        $this->assertSame(
            'EG Subframe PS Honda',
            TitleNormalizer::normalize('eg subframe ps honda')
        );
    }

    public function test_normalizes_multiline_and_slashed_strings(): void
    {
        $this->assertSame(
            '1992-1995 Civic Hatch Coupe Sedan Front OEM Impact Bar',
            TitleNormalizer::normalize("1992-1995 civic hatch coupe sedan front oem\nImpact bar")
        );

        $this->assertSame(
            'Buddy Club 02-06 RSX Base/Type S Shift Box',
            TitleNormalizer::normalize("Buddy club\n02-06 rsx base/type s shift box")
        );

        $this->assertSame(
            'DC Sports Stainless B18B/B20 Header Brand New',
            TitleNormalizer::normalize("DC sports stainless b18b/b20 \nheader brand new")
        );
    }

    public function test_handles_common_typos_and_possessives(): void
    {
        $this->assertSame("Enkei 17's", TitleNormalizer::normalize("Enkie 17's"));
        $this->assertSame('Tri-Y Honda CRX Header ZC DOHC Motor', TitleNormalizer::normalize('Try Y Honda CRX header ZC dohc motor'));
        $this->assertSame('1989 Honda CRX', TitleNormalizer::normalize('1989 Honda cr-z Crx'));
    }
}
