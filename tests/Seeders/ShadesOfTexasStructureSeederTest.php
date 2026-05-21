<?php

namespace Tests\Seeders;

use BookStack\Entities\Models\Bookshelf;
use BookStack\Entities\Models\Chapter;
use BookStack\Entities\Models\Page;
use Database\Seeders\ShadesOfTexasStructureSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShadesOfTexasStructureSeederTest extends TestCase
{
    public function test_structure_seeder_creates_the_sales_hub_tree(): void
    {
        app(ShadesOfTexasStructureSeeder::class)->run();

        $shelf = Bookshelf::query()->where('name', '=', 'High Level')->firstOrFail();

        $bookNames = $shelf->books()->pluck('name')->all();
        $this->assertContains('Start Here', $bookNames);
        $this->assertContains('Residential', $bookNames);
        $this->assertContains('Commercial', $bookNames);
        $this->assertContains('Products', $bookNames);
        $this->assertContains('Specs & Drawings', $bookNames);
        $this->assertContains('Warranty / Compliance', $bookNames);
        $this->assertContains('Reference / FAQs', $bookNames);

        $startHere = $shelf->books()->where('name', '=', 'Start Here')->firstOrFail();
        $this->assertEquals(
            ['Source of Truth', 'How to Use This Hub', 'Where to Find Specs, Drawings, and Pricing', 'How to Request Missing Documents'],
            Page::query()
                ->where('book_id', '=', $startHere->id)
                ->orderBy('priority')
                ->pluck('name')
                ->all()
        );

        $sourceOfTruthPage = Page::query()
            ->where('book_id', '=', $startHere->id)
            ->where('name', '=', 'Source of Truth')
            ->firstOrFail();
        $this->assertStringContainsString('Brand Sources', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Service Source Map', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Update Order', $sourceOfTruthPage->html);
        $this->assertStringContainsString('source.md', $sourceOfTruthPage->html);

        $residential = $shelf->books()->where('name', '=', 'Residential')->firstOrFail();
        $this->assertEquals(
            ['Tint & Film', 'Window Treatments', 'Outdoor Living', 'Glass & Windows'],
            $residential->chapters()->pluck('name')->all()
        );

        $tintFilm = Chapter::query()
            ->where('book_id', '=', $residential->id)
            ->where('name', '=', 'Tint & Film')
            ->firstOrFail();

        $products = $shelf->books()->where('name', '=', 'Products')->firstOrFail();

        $this->assertEquals(
            ['Safety & Security Film', 'Solar Film', 'Privacy Film'],
            Page::query()
                ->where('book_id', '=', $residential->id)
                ->where('chapter_id', '=', $tintFilm->id)
                ->orderBy('priority')
                ->pluck('name')
                ->all()
        );

        $safetyFilmPage = Page::query()
            ->where('book_id', '=', $residential->id)
            ->where('chapter_id', '=', $tintFilm->id)
            ->where('name', '=', 'Safety & Security Film')
            ->firstOrFail();
        $this->assertStringContainsString('Products', $safetyFilmPage->html);
        $this->assertStringContainsString('Specs & Drawings', $safetyFilmPage->html);
        $this->assertStringContainsString('Warranty / Compliance', $safetyFilmPage->html);
        $this->assertStringContainsString('Reference / FAQs', $safetyFilmPage->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $products->id)->where('name', '=', '3M')->firstOrFail()->getUrl(), $safetyFilmPage->html);
        $productAggregatePage = Page::query()
            ->where('book_id', '=', $products->id)
            ->where('name', '=', 'Tint & Film - Safety & Security Film')
            ->firstOrFail();
        $this->assertStringContainsString('Product Overview', $productAggregatePage->html);
        $this->assertStringContainsString('What We Carry', $productAggregatePage->html);
        $this->assertStringContainsString('Selling Points', $productAggregatePage->html);
        $this->assertStringContainsString('3M', $productAggregatePage->html);

        $expectedProductPages = [
            '3M',
            'Hunter Douglas',
            'Alta',
            'Norman',
            'Eclipse',
            'SmartTint',
            'Andersen',
            'Pella',
            'CRL',
            'Dallas Flat Glass',
            'Ghost Glass',
            'Jeld-Wen',
        ];

        $productPages = [];
        foreach ($expectedProductPages as $productName) {
            $productPage = Page::query()
                ->where('book_id', '=', $products->id)
                ->where('name', '=', $productName)
                ->firstOrFail();

            $productPages[$productName] = $productPage;
            $this->assertStringContainsString($productName, $productPage->html);
            $this->assertStringContainsString('Product Overview', $productPage->html);
            $this->assertStringContainsString('Selling Points', $productPage->html);
        }

        $this->assertStringContainsString('Lifestyle Series', $productPages['Pella']->html);
        $this->assertStringContainsString('Owner-to-Owner', $productPages['Andersen']->html);
        $this->assertStringContainsString('Auraline', $productPages['Jeld-Wen']->html);
        $this->assertStringContainsString('insulated glass', $productPages['Dallas Flat Glass']->html);
        $this->assertStringContainsString('https://www.pella.com/ideas/windows/reserve/', $productPages['Pella']->html);
        $this->assertStringContainsString('https://www.pella.com/ideas/windows/lifestyle-series/', $productPages['Pella']->html);
        $this->assertStringContainsString('https://www.pella.com/ideas/windows/pella-impervia/', $productPages['Pella']->html);
        $this->assertStringContainsString('E-Series', $productPages['Andersen']->html);
        $this->assertStringContainsString('https://www.andersenwindows.com/windows-and-doors/series/e-series', $productPages['Andersen']->html);
        $this->assertStringContainsString('Auraline True Composite', $productPages['Jeld-Wen']->html);
        $this->assertStringContainsString('https://www.corporate.jeld-wen.com/newsroom/press-releases/2022/06-21-2022-145756008', $productPages['Jeld-Wen']->html);
        $this->assertStringContainsString('https://www.jeld-wen.com/en-us/all-warranty-guide', $productPages['Jeld-Wen']->html);
        $this->assertStringContainsString('https://www.jeld-wen.com/en-us/documents', $productPages['Jeld-Wen']->html);
        $this->assertStringContainsString('Insulated Glass', $productPages['Dallas Flat Glass']->html);
        $this->assertStringContainsString('Mirrors', $productPages['Dallas Flat Glass']->html);
        $this->assertStringContainsString('Pattern Glass', $productPages['Dallas Flat Glass']->html);
        $this->assertStringContainsString('Low-E Glass', $productPages['Dallas Flat Glass']->html);
        $this->assertStringNotContainsString('Shower Glass', $productPages['Dallas Flat Glass']->html);

        $brandTemplate = [
            'Pella' => [
                'quick_links' => ['Pella Home Page', 'Pella Windows', 'Pella Warranties'],
                'website' => 'https://www.pella.com/',
                'warranty_url' => 'https://www.pella.com/support/warranties/',
            ],
            'Andersen' => [
                'quick_links' => ['Andersen Website', 'Andersen Support', 'Andersen Warranty', 'Andersen FAQs', 'Andersen Quality'],
                'website' => 'https://www.andersenwindows.com/',
                'warranty_url' => 'https://www.renewalbyandersen.com/resources/warranty',
            ],
            'Jeld-Wen' => [
                'quick_links' => ['Website', 'JELD-WEN About', 'JELD-WEN Warranty Guide', 'JELD-WEN Documents'],
                'website' => 'https://www.jeld-wen.com/en-us/',
                'warranty_url' => 'https://www.jeld-wen.com/en-us/all-warranty-guide',
            ],
            'Dallas Flat Glass' => [
                'quick_links' => ['Dallas Flat Glass'],
                'website' => 'https://dallasflatglass.com/',
                'warranty_url' => null,
            ],
        ];

        foreach ($brandTemplate as $brandName => $template) {
            foreach ($template['quick_links'] as $label) {
                $this->assertStringContainsString($label, $productPages[$brandName]->html);
            }

            $warrantySection = $this->extractTemplateSection($productPages[$brandName]->html, 'Warranty &amp; Compliance');
            $this->assertNotEmpty($warrantySection, $brandName . ' warranty section should be present.');
            $this->assertStringContainsString('<a href="' . $template['website'] . '" target="_blank" rel="noopener noreferrer">Website</a>', $warrantySection);

            if (!empty($template['warranty_url'])) {
                $this->assertStringContainsString($template['warranty_url'], $warrantySection);
                $this->assertStringContainsString('Warranty Information', $warrantySection);
            }
        }

        $specsBook = $shelf->books()->where('name', '=', 'Specs & Drawings')->firstOrFail();
        $specSheetIndex = Page::query()
            ->where('book_id', '=', $specsBook->id)
            ->where('name', '=', 'Tint & Film - Safety & Security Film')
            ->firstOrFail();
        $this->assertStringContainsString('Spec Sheet Index', $specSheetIndex->html);
        $this->assertStringContainsString('Architect Drawings', $specSheetIndex->html);
        $this->assertStringContainsString('Install Diagrams', $specSheetIndex->html);

        $commercial = $shelf->books()->where('name', '=', 'Commercial')->firstOrFail();
        $this->assertEquals(
            ['Solar Control & Safety', 'Patio Screens & Awnings', 'Glass & Windows', 'Window Treatments'],
            $commercial->chapters()->pluck('name')->all()
        );

        $commercialFilm = Chapter::query()
            ->where('book_id', '=', $commercial->id)
            ->where('name', '=', 'Solar Control & Safety')
            ->firstOrFail();

        $this->assertEquals(
            ['Sun Control Film', 'Safety & Security Film', 'Privacy Film', 'SmartTint'],
            Page::query()
                ->where('book_id', '=', $commercial->id)
                ->where('chapter_id', '=', $commercialFilm->id)
                ->orderBy('priority')
                ->pluck('name')
                ->all()
        );

        $commercialGlassChapter = Chapter::query()
            ->where('book_id', '=', $commercial->id)
            ->where('name', '=', 'Glass & Windows')
            ->firstOrFail();
        $commercialGlazing = Page::query()
            ->where('book_id', '=', $commercial->id)
            ->where('chapter_id', '=', $commercialGlassChapter->id)
            ->where('name', '=', 'Commercial Glazing')
            ->firstOrFail();
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $products->id)->where('name', '=', 'Jeld-Wen')->firstOrFail()->getUrl(), $commercialGlazing->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $products->id)->where('name', '=', 'CRL')->firstOrFail()->getUrl(), $commercialGlazing->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $products->id)->where('name', '=', 'Andersen')->firstOrFail()->getUrl(), $commercialGlazing->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $products->id)->where('name', '=', 'Pella')->firstOrFail()->getUrl(), $commercialGlazing->html);

        $glassAndWindows = Chapter::query()
            ->where('book_id', '=', $residential->id)
            ->where('name', '=', 'Glass & Windows')
            ->firstOrFail();
        $this->assertEquals(
            ['Window Glass', 'Frameless Showers', 'Window Cleaning'],
            Page::query()
                ->where('book_id', '=', $residential->id)
                ->where('chapter_id', '=', $glassAndWindows->id)
                ->orderBy('priority')
                ->pluck('name')
                ->all()
        );
        $windowGlassPage = Page::query()
            ->where('book_id', '=', $residential->id)
            ->where('chapter_id', '=', $glassAndWindows->id)
            ->where('name', '=', 'Window Glass')
            ->firstOrFail();
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $products->id)->where('name', '=', 'Andersen')->firstOrFail()->getUrl(), $windowGlassPage->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $products->id)->where('name', '=', 'Pella')->firstOrFail()->getUrl(), $windowGlassPage->html);

        $this->assertDatabaseHas('settings', [
            'setting_key' => 'app-homepage-type',
            'value'       => 'page',
        ]);
        $this->assertDatabaseHas('settings', [
            'setting_key' => 'app-color',
            'value'       => '#171B2A',
        ]);
        $this->assertDatabaseHas('settings', [
            'setting_key' => 'link-color',
            'value'       => '#FF5A3C',
        ]);

        $homeSetting = DB::table('settings')->where('setting_key', '=', 'app-homepage')->value('value');
        $this->assertNotEmpty($homeSetting);

        $homePage = Page::query()->findOrFail((int) $homeSetting);
        $homeVisit = $this->actingAs($this->users->admin())->get('/');
        $homeVisit->assertSee('High Level');
        $homeVisit->assertSee('Find the right answer fast.');
        $homeVisit->assertSee('Safety & Security Film');
        $homeVisit->assertSee('Solar Film');
        $homeVisit->assertSee('Privacy Film');
        $homeVisit->assertSee('35+ Years');
        $homeVisit->assertSee($homePage->name);

        $viewer = $this->users->viewer();
        $this->actingAs($viewer)->get($residential->getUrl())->assertOk();
        $this->actingAs($viewer)->get($tintFilm->getUrl())->assertOk();
        $this->actingAs($viewer)->get($safetyFilmPage->getUrl())->assertOk();
        $this->actingAs($viewer)->get($glassAndWindows->getUrl())->assertOk();
        $this->actingAs($viewer)->get($products->getUrl())->assertOk();
        $this->actingAs($viewer)->get($productAggregatePage->getUrl())->assertOk();
        $this->actingAs($viewer)->get($pellaProductPage->getUrl())->assertOk();
    }

    private function extractTemplateSection(string $html, string $heading): string
    {
        $pattern = '#<section class="sotx-card sotx-mini-card sotx-template-block">.*?<h4[^>]*>' . preg_quote($heading, '#') . '</h4>(.*?)</section>#s';

        if (preg_match($pattern, $html, $matches)) {
            return $matches[0];
        }

        return '';
    }
}
