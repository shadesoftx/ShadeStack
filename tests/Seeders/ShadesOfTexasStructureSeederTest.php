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
        $this->assertContains('Vendors', $bookNames);
        $this->assertNotContains('Specs & Drawings', $bookNames);
        $this->assertNotContains('Warranty / Compliance', $bookNames);
        $this->assertNotContains('Reference / FAQs', $bookNames);

        $startHere = $shelf->books()->where('name', '=', 'Start Here')->firstOrFail();
        $this->assertEquals(
            ['Source of Truth', 'How to Use This Hub', 'Where to Find Product Info and Pricing', 'How to Request Missing Documents', 'Sales Hub Home'],
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
        $this->assertStringContainsString('Core Books', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Start Here', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Residential', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Commercial', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Vendors', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Read This First', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Verified Vendor Sources', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Service Source Map', $sourceOfTruthPage->html);
        $this->assertStringContainsString('How to Keep This Current', $sourceOfTruthPage->html);
        $this->assertStringContainsString('official and relevant to what Shades of Texas sells', $sourceOfTruthPage->html);
        $this->assertStringNotContainsString('source.md', $sourceOfTruthPage->html);
        $this->assertStringNotContainsString('repo markdown', strtolower($sourceOfTruthPage->html));
        $this->assertStringNotContainsString('automotive', strtolower($sourceOfTruthPage->html));
        $this->assertStringNotContainsString('post-factory', strtolower($sourceOfTruthPage->html));

        $howToUsePage = Page::query()
            ->where('book_id', '=', $startHere->id)
            ->where('name', '=', 'How to Use This Hub')
            ->firstOrFail();
        $this->assertStringContainsString('Pick the Right Lane', $howToUsePage->html);
        $this->assertStringContainsString('Common Lookup Paths', $howToUsePage->html);
        $this->assertStringContainsString('When to Slow Down', $howToUsePage->html);
        $this->assertStringContainsString('Customer asks about warranty', $howToUsePage->html);
        $this->assertStringContainsString('Vendors', $howToUsePage->html);
        $this->assertStringContainsString('Residential', $howToUsePage->html);
        $this->assertStringContainsString('Commercial', $howToUsePage->html);
        $this->assertStringNotContainsString('placeholder', strtolower($howToUsePage->html));

        $specsPricingPage = Page::query()
            ->where('book_id', '=', $startHere->id)
            ->where('name', '=', 'Where to Find Product Info and Pricing')
            ->firstOrFail();
        $this->assertStringContainsString('Where Each Type of Info Lives', $specsPricingPage->html);
        $this->assertStringContainsString('What to Check Before Quoting', $specsPricingPage->html);
        $this->assertStringContainsString('Pricing', $specsPricingPage->html);
        $this->assertStringContainsString('dealer-only', $specsPricingPage->html);
        $this->assertStringContainsString('Vendors', $specsPricingPage->html);

        $missingDocsPage = Page::query()
            ->where('book_id', '=', $startHere->id)
            ->where('name', '=', 'How to Request Missing Documents')
            ->firstOrFail();
        $this->assertStringContainsString('What to Include in the Request', $missingDocsPage->html);
        $this->assertStringContainsString('Where to Check First', $missingDocsPage->html);
        $this->assertStringContainsString('Who to Ask', $missingDocsPage->html);
        $this->assertStringContainsString('John Borg', $missingDocsPage->html);
        $this->assertStringContainsString('Source of Truth', $missingDocsPage->html);
        $this->assertStringContainsString('pricing, warranty, install detail, product fit, or customer answer', $missingDocsPage->html);

        $residential = $shelf->books()->where('name', '=', 'Residential')->firstOrFail();
        $this->assertEquals(
            ['Tint & Film', 'Window Treatments', 'Outdoor Living', 'Glass & Windows'],
            $residential->chapters()->pluck('name')->all()
        );

        $tintFilm = Chapter::query()
            ->where('book_id', '=', $residential->id)
            ->where('name', '=', 'Tint & Film')
            ->firstOrFail();

        $vendors = $shelf->books()->where('name', '=', 'Vendors')->firstOrFail();
        $expectedVendorNames = [
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
            'JELD-WEN',
        ];

        $this->assertEquals($expectedVendorNames, $vendors->chapters()->pluck('name')->all());

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
        $this->assertStringContainsString('Vendors', $safetyFilmPage->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $vendors->id)->where('name', '=', '3M')->firstOrFail()->getUrl(), $safetyFilmPage->html);

        $vendorOverviewPages = [];
        foreach ($expectedVendorNames as $vendorName) {
            $vendorChapter = Chapter::query()
                ->where('book_id', '=', $vendors->id)
                ->where('name', '=', $vendorName)
                ->firstOrFail();

            $vendorOverviewPage = Page::query()
                ->where('book_id', '=', $vendors->id)
                ->where('chapter_id', '=', $vendorChapter->id)
                ->where('name', '=', $vendorName)
                ->firstOrFail();

            $vendorOverviewPages[$vendorName] = $vendorOverviewPage;

            $this->assertStringContainsString($vendorName, $vendorOverviewPage->html);
            $this->assertStringContainsString('Products', $vendorOverviewPage->html);
            $this->assertStringContainsString('Quick Links', $vendorOverviewPage->html);
            $this->assertStringContainsString('Warranty Information', $vendorOverviewPage->html);
            $this->assertStringContainsString('FAQs', $vendorOverviewPage->html);
            $this->assertStringContainsString('Source Inventory', $vendorOverviewPage->html);
            $this->assertLessThan(strpos($vendorOverviewPage->html, 'Warranty Information'), strpos($vendorOverviewPage->html, 'Products'));
            $this->assertLessThan(strpos($vendorOverviewPage->html, 'FAQs'), strpos($vendorOverviewPage->html, 'Warranty Information'));
            $this->assertLessThan(strpos($vendorOverviewPage->html, 'Quick Links'), strpos($vendorOverviewPage->html, 'FAQs'));
            $this->assertLessThan(strpos($vendorOverviewPage->html, 'Source Inventory'), strpos($vendorOverviewPage->html, 'Quick Links'));
            $this->assertStringNotContainsString('Product Overview', $vendorOverviewPage->html);
            $this->assertStringNotContainsString('Selling Points', $vendorOverviewPage->html);
            $this->assertStringNotContainsString('Specs &amp; Drawings', $vendorOverviewPage->html);
            $this->assertStringNotContainsString('Warranty &amp; Compliance', $vendorOverviewPage->html);
            $this->assertStringNotContainsString('Reference / FAQs', $vendorOverviewPage->html);

            $vendorProductNames = Page::query()
                ->where('book_id', '=', $vendors->id)
                ->where('chapter_id', '=', $vendorChapter->id)
                ->where('name', '!=', $vendorName)
                ->orderBy('priority')
                ->pluck('name')
                ->all();

            $this->assertNotEmpty($vendorProductNames, $vendorName . ' should have product pages.');

            foreach ($vendorProductNames as $vendorProductName) {
                $vendorProductPage = Page::query()
                    ->where('book_id', '=', $vendors->id)
                    ->where('chapter_id', '=', $vendorChapter->id)
                    ->where('name', '=', $vendorProductName)
                    ->firstOrFail();

                $relatedProductName = collect($vendorProductNames)
                    ->first(fn (string $name): bool => $name !== $vendorProductName);
                if ($relatedProductName !== null) {
                    $relatedProductPage = Page::query()
                        ->where('book_id', '=', $vendors->id)
                        ->where('chapter_id', '=', $vendorChapter->id)
                        ->where('name', '=', $relatedProductName)
                        ->firstOrFail();

                    $this->assertStringContainsString($relatedProductPage->getUrl(), $vendorProductPage->html);
                }

                $this->assertStringContainsString('Product Summary', $vendorProductPage->html);
                $this->assertStringContainsString('Best Fit For', $vendorProductPage->html);
                $this->assertStringContainsString('Related Lines', $vendorProductPage->html);
                $this->assertStringContainsString('Warranty Information', $vendorProductPage->html);
                $this->assertStringContainsString('FAQs', $vendorProductPage->html);
                $this->assertStringContainsString('Quick Links', $vendorProductPage->html);
                $this->assertStringContainsString('Vendor Hub', $vendorProductPage->html);
                $this->assertStringNotContainsString('Product Notes', $vendorProductPage->html);
                $this->assertStringNotContainsString('Sales Notes', $vendorProductPage->html);
            }
        }

        $vendor3mChapter = Chapter::query()
            ->where('book_id', '=', $vendors->id)
            ->where('name', '=', '3M')
            ->firstOrFail();
        $this->assertContains(
            '3M',
            Page::query()
                ->where('book_id', '=', $vendors->id)
                ->where('chapter_id', '=', $vendor3mChapter->id)
                ->pluck('name')
                ->all()
        );

        $pellaChapter = Chapter::query()
            ->where('book_id', '=', $vendors->id)
            ->where('name', '=', 'Pella')
            ->firstOrFail();
        $pellaReservePage = Page::query()
            ->where('book_id', '=', $vendors->id)
            ->where('chapter_id', '=', $pellaChapter->id)
            ->where('name', '=', 'Reserve Traditional Windows')
            ->firstOrFail();
        $pellaLifestylePage = Page::query()
            ->where('book_id', '=', $vendors->id)
            ->where('chapter_id', '=', $pellaChapter->id)
            ->where('name', '=', 'Lifestyle Series')
            ->firstOrFail();
        $pellaImperviaPage = Page::query()
            ->where('book_id', '=', $vendors->id)
            ->where('chapter_id', '=', $pellaChapter->id)
            ->where('name', '=', 'Impervia Windows')
            ->firstOrFail();
        $this->assertStringContainsString('Reserve Traditional Windows', $vendorOverviewPages['Pella']->html);
        $this->assertStringContainsString('Lifestyle Series', $vendorOverviewPages['Pella']->html);
        $this->assertStringContainsString('Impervia Windows', $vendorOverviewPages['Pella']->html);
        $this->assertStringContainsString($pellaReservePage->getUrl(), $vendorOverviewPages['Pella']->html);
        $this->assertStringContainsString($pellaLifestylePage->getUrl(), $vendorOverviewPages['Pella']->html);
        $this->assertStringContainsString($pellaImperviaPage->getUrl(), $vendorOverviewPages['Pella']->html);
        $this->assertStringContainsString('https://www.pella.com/shop/windows/reserve/traditional/', $pellaReservePage->html);
        $this->assertStringContainsString('https://www.pella.com/ideas/windows/lifestyle-series/', $pellaLifestylePage->html);
        $this->assertStringContainsString('https://www.pella.com/ideas/windows/pella-impervia/', $pellaImperviaPage->html);
        $this->assertStringContainsString('https://www.pella.com/support/warranties/', $vendorOverviewPages['Pella']->html);
        $this->assertStringContainsString('https://www.pella.com/support/faq/', $vendorOverviewPages['Pella']->html);

        $andersenChapter = Chapter::query()
            ->where('book_id', '=', $vendors->id)
            ->where('name', '=', 'Andersen')
            ->firstOrFail();
        $andersenSeriesPage = Page::query()
            ->where('book_id', '=', $vendors->id)
            ->where('chapter_id', '=', $andersenChapter->id)
            ->where('name', '=', 'E-Series')
            ->firstOrFail();
        $this->assertStringContainsString($andersenSeriesPage->getUrl(), $vendorOverviewPages['Andersen']->html);
        $this->assertStringContainsString('E-Series', $andersenSeriesPage->html);
        $this->assertStringContainsString('https://www.andersenwindows.com/windows-and-doors/series/e-series', $andersenSeriesPage->html);
        $this->assertStringContainsString('https://www.andersenwindows.com/support/warranty', $vendorOverviewPages['Andersen']->html);
        $this->assertStringContainsString('https://www.andersenwindows.com/support/faqs', $vendorOverviewPages['Andersen']->html);

        $jeldChapter = Chapter::query()
            ->where('book_id', '=', $vendors->id)
            ->where('name', '=', 'JELD-WEN')
            ->firstOrFail();
        $jeldAuralinePage = Page::query()
            ->where('book_id', '=', $vendors->id)
            ->where('chapter_id', '=', $jeldChapter->id)
            ->where('name', '=', 'Auraline True Composite')
            ->firstOrFail();
        $this->assertStringContainsString($jeldAuralinePage->getUrl(), $vendorOverviewPages['JELD-WEN']->html);
        $this->assertStringContainsString('Auraline True Composite', $jeldAuralinePage->html);
        $this->assertStringContainsString('https://www.corporate.jeld-wen.com/newsroom/press-releases/2022/06-21-2022-145756008', $jeldAuralinePage->html);
        $this->assertStringContainsString('https://www.jeld-wen.com/en-us/all-warranty-guide', $vendorOverviewPages['JELD-WEN']->html);
        $this->assertStringContainsString('https://www.jeld-wen.com/en-us/documents', $vendorOverviewPages['JELD-WEN']->html);

        $vendor3mOverview = Page::query()
            ->where('book_id', '=', $vendors->id)
            ->where('chapter_id', '=', $vendor3mChapter->id)
            ->where('name', '=', '3M')
            ->firstOrFail();
        $this->assertStringContainsString('Source Inventory', $vendor3mOverview->html);
        $this->assertStringContainsString('Find a Dealer', $vendor3mOverview->html);
        $this->assertStringContainsString('Resources', $vendor3mOverview->html);
        $this->assertStringContainsString('Support', $vendor3mOverview->html);
        $this->assertStringContainsString('Safety Data Sheets', $vendor3mOverview->html);
        $this->assertStringNotContainsString('Draft', $vendor3mOverview->html);
        $this->assertStringNotContainsString('Populate This Section', $vendor3mOverview->html);
        $this->assertStringNotContainsString('automotive', strtolower($vendor3mOverview->html));

        $dallasChapter = Chapter::query()
            ->where('book_id', '=', $vendors->id)
            ->where('name', '=', 'Dallas Flat Glass')
            ->firstOrFail();
        $dallasPage = Page::query()
            ->where('book_id', '=', $vendors->id)
            ->where('chapter_id', '=', $dallasChapter->id)
            ->where('name', '=', 'Dallas Flat Glass')
            ->firstOrFail();
        $this->assertStringContainsString('Insulated Glass', $dallasPage->html);
        $this->assertStringContainsString('Mirrors', $dallasPage->html);
        $this->assertStringContainsString('Pattern Glass', $dallasPage->html);
        $this->assertStringContainsString('Low-E Glass', $dallasPage->html);
        $this->assertStringContainsString('No public warranty page was found', $dallasPage->html);
        $this->assertStringNotContainsString('Shower Glass', $dallasPage->html);

        $brandTemplate = [
            'Pella' => [
                'quick_links' => ['Pella Home Page', 'Pella Windows', 'Pella Warranties', 'Pella FAQ'],
                'website' => 'https://www.pella.com/',
                'warranty_url' => 'https://www.pella.com/support/warranties/',
            ],
            'Andersen' => [
                'quick_links' => ['Andersen Website', 'Andersen Support', 'Andersen Warranty', 'Andersen FAQs', 'Andersen Quality'],
                'website' => 'https://www.andersenwindows.com/',
                'warranty_url' => 'https://www.andersenwindows.com/support/warranty',
            ],
            'JELD-WEN' => [
                'quick_links' => ['Website', 'Dealer Locator', 'JELD-WEN Warranty Guide', 'JELD-WEN Documents', 'FAQ'],
                'website' => 'https://www.jeld-wen.com/en-us/',
                'warranty_url' => 'https://www.jeld-wen.com/en-us/all-warranty-guide',
            ],
            'Dallas Flat Glass' => [
                'quick_links' => ['Dallas Flat Glass', 'Questions / Contact'],
                'website' => 'https://dallasflatglass.com/',
                'warranty_url' => null,
            ],
        ];

        foreach ($brandTemplate as $brandName => $template) {
            foreach ($template['quick_links'] as $label) {
                $this->assertStringContainsString($label, $vendorOverviewPages[$brandName]->html);
            }

            $this->assertStringNotContainsString('Populate This Section', $vendorOverviewPages[$brandName]->html);
            $this->assertStringNotContainsString('Draft', $vendorOverviewPages[$brandName]->html);
            $this->assertStringNotContainsString('Product Overview', $vendorOverviewPages[$brandName]->html);
            $this->assertStringNotContainsString('Selling Points', $vendorOverviewPages[$brandName]->html);
            $this->assertStringNotContainsString('Specs &amp; Drawings', $vendorOverviewPages[$brandName]->html);
            $this->assertStringNotContainsString('Warranty &amp; Compliance', $vendorOverviewPages[$brandName]->html);
            $this->assertStringNotContainsString('Reference / FAQs', $vendorOverviewPages[$brandName]->html);
        }

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
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $vendors->id)->where('name', '=', 'JELD-WEN')->firstOrFail()->getUrl(), $commercialGlazing->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $vendors->id)->where('name', '=', 'CRL')->firstOrFail()->getUrl(), $commercialGlazing->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $vendors->id)->where('name', '=', 'Andersen')->firstOrFail()->getUrl(), $commercialGlazing->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $vendors->id)->where('name', '=', 'Pella')->firstOrFail()->getUrl(), $commercialGlazing->html);

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
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $vendors->id)->where('name', '=', 'Andersen')->firstOrFail()->getUrl(), $windowGlassPage->html);
        $this->assertStringContainsString(Page::query()->where('book_id', '=', $vendors->id)->where('name', '=', 'Pella')->firstOrFail()->getUrl(), $windowGlassPage->html);

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

        $admin = $this->users->admin();
        $this->actingAs($admin)->get($residential->getUrl())->assertOk();
        $this->actingAs($admin)->get($tintFilm->getUrl())->assertOk();
        $this->actingAs($admin)->get($safetyFilmPage->getUrl())->assertOk();
        $this->actingAs($admin)->get($glassAndWindows->getUrl())->assertOk();
        $this->actingAs($admin)->get($vendors->getUrl())->assertOk();
        $this->actingAs($admin)->get($vendorOverviewPages['Pella']->getUrl())->assertOk();
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
