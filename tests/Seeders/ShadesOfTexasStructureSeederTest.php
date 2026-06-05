<?php

namespace Tests\Seeders;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\Bookshelf;
use BookStack\Entities\Models\Chapter;
use BookStack\Entities\Models\Page;
use BookStack\Search\SearchTerm;
use BookStack\Users\Models\User;
use Database\Seeders\ShadesOfTexasStructureSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShadesOfTexasStructureSeederTest extends TestCase
{
    public function test_structure_seeder_creates_the_product_first_documentation_tree(): void
    {
        config(['app.url' => 'https://bookstack-sotx.test']);
        $ownerId = User::query()->where('email', '=', 'admin@admin.com')->value('id')
            ?? User::query()->value('id');
        $legacyByData = [
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
            'owned_by'   => $ownerId,
        ];
        $legacyBook = Book::factory()->create(array_merge($legacyByData, [
            'name'             => 'Vendors',
            'description'      => 'Vendor reference pages for manufacturers and distributors.',
            'description_html' => '<p>Vendor reference pages for manufacturers and distributors.</p>',
        ]));
        $legacyChapter = Chapter::factory()->create(array_merge($legacyByData, [
            'book_id'          => $legacyBook->id,
            'name'             => '3M',
            'description'      => '3M product reference and sales support.',
            'description_html' => '<p>3M product reference and sales support.</p>',
        ]));
        $legacyPage = Page::factory()->create(array_merge($legacyByData, [
            'book_id'    => $legacyBook->id,
            'chapter_id' => $legacyChapter->id,
            'name'       => '3M Window Films for Architectural Design',
            'html'       => '<div class="sotx-home"><p>Legacy generated 3M product reference and sales support.</p></div>',
            'text'       => 'Legacy generated 3M product reference and sales support.',
        ]));
        $legacyPage->indexForSearch();

        app(ShadesOfTexasStructureSeeder::class)->run();

        $this->assertSame('HighLevel', setting('app-name'));
        $this->assertSame('highlevel-logo.png', setting('app-logo'));
        $this->assertTrue(setting('app-name-header'));

        $this->assertFalse(Book::withTrashed()->whereKey($legacyBook->id)->exists());
        $this->assertFalse(Chapter::withTrashed()->whereKey($legacyChapter->id)->exists());
        $this->assertFalse(Page::withTrashed()->whereKey($legacyPage->id)->exists());
        $this->assertFalse(
            SearchTerm::query()
                ->where('entity_type', '=', 'page')
                ->where('entity_id', '=', $legacyPage->id)
                ->exists()
        );

        $productsShelf = Bookshelf::query()->where('name', '=', 'Products')->firstOrFail();
        $workflowShelf = Bookshelf::query()->where('name', '=', 'Workflow')->firstOrFail();

        $this->assertSame('products', $productsShelf->slug);
        $this->assertSame('workflow', $workflowShelf->slug);
        $this->assertStringEndsWith('/shelves/products', $productsShelf->getUrl());
        $this->assertStringEndsWith('/shelves/workflow', $workflowShelf->getUrl());

        $this->assertFalse(
            Bookshelf::query()->where('name', '=', 'Start Here')->exists(),
            'Start Here must not be a separate shelf — it belongs in Workflow'
        );
        $this->assertEquals(['Start Here'], $workflowShelf->books()->pluck('name')->all());

        $this->assertEquals(
            [
                'Shades',
                'Shutters',
                'Screens',
                'Pergolas',
                'Patio Covers',
                'Tint & Film',
                'Windows',
                'Doors',
                'Glass & Windows',
                'Outdoor Living',
                'Smart & Automation',
            ],
            $productsShelf->books()->pluck('name')->all()
        );

        $this->assertNotContains('Vendors', $productsShelf->books()->pluck('name')->all());
        $this->assertNotContains('Products', $productsShelf->books()->pluck('name')->all());

        $startHere = $workflowShelf->books()->where('name', '=', 'Start Here')->firstOrFail();
        $tintFilmBook = $productsShelf->books()->where('name', '=', 'Tint & Film')->firstOrFail();

        $startHereTaskPage = Page::query()
            ->where('book_id', '=', $startHere->id)
            ->where('name', '=', 'Find Your Path')
            ->firstOrFail();
        $this->assertFalse(
            Page::query()
                ->where('book_id', '=', $startHere->id)
                ->whereNull('chapter_id')
                ->where('name', '=', 'Start Here')
                ->exists(),
            'The Start Here book should not contain a same-named Start Here page.'
        );

        foreach ([
            'I know the product type',
            'I know the vendor',
            'I know the customer outcome',
            'Check Warranty',
            'Get Product Specs',
            'Install Guide',
            'Brochure / Collateral',
            'Pricing / Portal',
            'Request Update',
            'Products',
            'SOPs',
            'You must have a login to access our SOPs.',
            'Open SOPs',
            '1Password',
        ] as $text) {
            $this->assertStringContainsString($text, $startHereTaskPage->html);
        }
        $this->assertStringContainsString('https://sotwt.sharepoint.com/:u:/s/FieldOperations/IQDYxnSFVm-wQ4JDMkGEN_mBAaJWR2xzRLQlbWB6VNf51IY?e=RbxrhP', $startHereTaskPage->html);
        $this->assertStringContainsString($productsShelf->getUrl(), $startHereTaskPage->html);
        $this->assertStringNotContainsString('Residential', $startHereTaskPage->html);
        $this->assertStringNotContainsString('Commercial', $startHereTaskPage->html);
        $this->assertStringNotContainsString('http://localhost', $startHereTaskPage->html);

        $startInstallPosition = strpos($startHereTaskPage->html, 'Install Guide');
        $startSpecsPosition = strpos($startHereTaskPage->html, 'Get Product Specs');
        $startCollateralPosition = strpos($startHereTaskPage->html, 'Brochure / Collateral');
        $startWarrantyPosition = strpos($startHereTaskPage->html, 'Check Warranty');
        $this->assertNotFalse($startInstallPosition);
        $this->assertNotFalse($startSpecsPosition);
        $this->assertNotFalse($startCollateralPosition);
        $this->assertNotFalse($startWarrantyPosition);
        $this->assertTrue($startInstallPosition < $startSpecsPosition);
        $this->assertTrue($startSpecsPosition < $startCollateralPosition);
        $this->assertTrue($startCollateralPosition < $startWarrantyPosition);

        $vendorIndexPage = Page::query()
            ->where('book_id', '=', $startHere->id)
            ->where('name', '=', 'Vendor Index')
            ->firstOrFail();
        $this->assertStringContainsString('Vendor Directory', $vendorIndexPage->html);
        $this->assertStringContainsString('Hunter Douglas - Overview', $vendorIndexPage->html);
        $this->assertStringContainsString('CRL - Overview', $vendorIndexPage->html);
        $this->assertStringContainsString('3M - Overview', $vendorIndexPage->html);
        $this->assertStringContainsString('Primary category', $vendorIndexPage->html);
        $this->assertStringContainsString('Supported categories', $vendorIndexPage->html);
        $this->assertStringContainsString($vendorIndexPage->getUrl(), $startHereTaskPage->html);

        $sourceOfTruthPage = Page::query()
            ->where('book_id', '=', $startHere->id)
            ->where('name', '=', 'Source of Truth')
            ->firstOrFail();
        $this->assertStringContainsString('Start Here -&gt; SOT Product Category -&gt; Vendor -&gt; Product -&gt; Outcome Resources', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Complete', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Partial', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Needed', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Pending Review', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Not Applicable', $sourceOfTruthPage->html);
        $this->assertStringContainsString('Source Collection Pass', $sourceOfTruthPage->html);
        $this->assertStringContainsString('104 outcome resource rows', $sourceOfTruthPage->html);
        $this->assertStringContainsString('104 rows now have a source location', $sourceOfTruthPage->html);
        $this->assertStringContainsString('96 rows now have official public, portal, or official-support links', $sourceOfTruthPage->html);
        $this->assertStringContainsString('83 Partial, 13 Needed, and 8 Pending Review', $sourceOfTruthPage->html);

        $howToUsePage = Page::query()
            ->where('book_id', '=', $startHere->id)
            ->where('name', '=', 'How to Use This Hub')
            ->firstOrFail();
        $this->assertStringContainsString('Products', $howToUsePage->html);
        $this->assertStringContainsString('product category', strtolower($howToUsePage->html));
        $this->assertStringContainsString('Sales Collateral', $howToUsePage->html);
        $this->assertStringNotContainsString('Vendors is the canonical', $howToUsePage->html);

        $this->assertTrue(
            Chapter::query()
                ->where('book_id', '=', $tintFilmBook->id)
                ->where('name', '=', 'Tint & Film')
                ->doesntExist()
        );
        $this->assertTrue(
            Chapter::query()
                ->where('book_id', '=', $tintFilmBook->id)
                ->where('name', '=', '3M')
                ->exists()
        );

        $vendorOverview = Page::query()
            ->where('book_id', '=', $tintFilmBook->id)
            ->where('name', '=', '3M - Overview')
            ->firstOrFail();
        foreach ([
            'Install Guides',
            'Product Specs',
            'Sales Collateral',
            'Warranty',
            'Outcome Resources',
            'Prestige Series',
        ] as $text) {
            $this->assertStringContainsString($text, $vendorOverview->html);
        }
        foreach ([
            '<h2>Vendor Reference</h2>',
            '<h2>Products in this line</h2>',
            '<h2>Categories Supported</h2>',
            '<h2>Install Guides By Category</h2>',
            '<h2>Brand-wide Warranty</h2>',
            '<h2>FAQs</h2>',
            '<h2>Quick Links</h2>',
            '<h2>Source Inventory',
        ] as $text) {
            $this->assertStringNotContainsString($text, $vendorOverview->html);
        }

        $productPage = Page::query()
            ->where('book_id', '=', $tintFilmBook->id)
            ->where('name', '=', '3M - Prestige Series')
            ->firstOrFail();
        $this->assertTrue(
            SearchTerm::query()
                ->where('entity_type', '=', 'page')
                ->where('entity_id', '=', $productPage->id)
                ->where('term', '=', 'prestige')
                ->exists(),
            'Seeded product pages must be indexed so global search returns results immediately after reseeding.'
        );
        $this->asAdmin()
            ->get('/search?term=' . urlencode('Prestige'))
            ->assertOk()
            ->assertSeeText('3M - Prestige Series');

        foreach ([
            'Install Guides',
            'Product Specs',
            'Sales Collateral',
            'Warranty',
            'Partial',
            'Needed',
            'Pending Review',
            'Official product source linked',
            'Brand-wide warranty',
        ] as $text) {
            $this->assertStringContainsString($text, $productPage->html);
        }
        foreach ([
            'Use Cases',
            'Internal Notes',
            'Source Status',
            'Common customer or job scenarios',
            'Employee-only notes',
            'Allowed labels',
        ] as $text) {
            $this->assertStringNotContainsString($text, $productPage->html);
        }

        $installPosition = strpos($productPage->html, 'Install Guides');
        $specsPosition = strpos($productPage->html, 'Product Specs');
        $collateralPosition = strpos($productPage->html, 'Sales Collateral');
        $warrantyPosition = strpos($productPage->html, 'Warranty');
        $this->assertNotFalse($installPosition);
        $this->assertNotFalse($specsPosition);
        $this->assertNotFalse($collateralPosition);
        $this->assertNotFalse($warrantyPosition);
        $this->assertTrue($installPosition < $specsPosition);
        $this->assertTrue($specsPosition < $collateralPosition);
        $this->assertTrue($collateralPosition < $warrantyPosition);
        $this->assertStringContainsString('https://multimedia.3m.com/mws/media/1704917O/prestige-series.pdf', $productPage->html);
        $this->assertStringNotContainsString('http://localhost', $productPage->html);

        $this->assertStringContainsString($vendorOverview->getUrl(), $productPage->html);

        $windowsBook = $productsShelf->books()->where('name', '=', 'Windows')->firstOrFail();
        $pellaPage = Page::query()
            ->where('book_id', '=', $windowsBook->id)
            ->where('name', '=', 'Pella - Overview')
            ->firstOrFail();
        $this->assertStringContainsString('Reserve Traditional Windows', $pellaPage->html);

        // Dimension B: primary category has full content; secondary categories have thin cross-link chapters.
        $doorsBook = $productsShelf->books()->where('name', '=', 'Doors')->firstOrFail();
        $glassWindowsBook = $productsShelf->books()->where('name', '=', 'Glass & Windows')->firstOrFail();
        $screensBook = $productsShelf->books()->where('name', '=', 'Screens')->firstOrFail();
        $shadesBook = $productsShelf->books()->where('name', '=', 'Shades')->firstOrFail();
        $smartAutomationBook = $productsShelf->books()->where('name', '=', 'Smart & Automation')->firstOrFail();

        // Andersen primary: Windows (full chapter). Secondary: Doors and Glass & Windows (cross-link chapters).
        $this->assertTrue(
            Chapter::query()->where('book_id', '=', $windowsBook->id)->where('name', '=', 'Andersen')->exists(),
            'Andersen must have a full chapter in primary category: Windows'
        );
        $this->assertTrue(
            Chapter::query()->where('book_id', '=', $doorsBook->id)->where('name', '=', 'Andersen')->exists(),
            'Andersen must have a cross-link chapter in secondary category: Doors'
        );
        $this->assertTrue(
            Chapter::query()->where('book_id', '=', $glassWindowsBook->id)->where('name', '=', 'Andersen')->exists(),
            'Andersen must have a cross-link chapter in secondary category: Glass & Windows'
        );
        $andersenDoorsChapter = Chapter::query()
            ->where('book_id', '=', $doorsBook->id)->where('name', '=', 'Andersen')->firstOrFail();
        $andersenPrimaryPage = Page::query()
            ->where('book_id', '=', $windowsBook->id)
            ->where('name', '=', 'Andersen - Overview')
            ->firstOrFail();
        $andersenCrossLink = Page::query()
            ->where('book_id', '=', $doorsBook->id)
            ->where('chapter_id', '=', $andersenDoorsChapter->id)
            ->where('name', '=', 'Andersen - See Windows')
            ->firstOrFail();
        $this->assertStringContainsString('Windows', $andersenCrossLink->html);
        $this->assertStringContainsString('Routing page only', $andersenCrossLink->html);
        $this->assertStringContainsString($andersenPrimaryPage->getUrl(), $andersenCrossLink->html);
        $this->assertStringNotContainsString('Source Status', $andersenCrossLink->html);

        // Eclipse primary: Screens (full chapter). Secondary: Shades (cross-link chapter).
        $this->assertTrue(
            Chapter::query()->where('book_id', '=', $screensBook->id)->where('name', '=', 'Eclipse')->exists(),
            'Eclipse must have a full chapter in primary category: Screens'
        );
        $this->assertTrue(
            Chapter::query()->where('book_id', '=', $shadesBook->id)->where('name', '=', 'Eclipse')->exists(),
            'Eclipse must have a cross-link chapter in secondary category: Shades'
        );
        $eclipseShadesChapter = Chapter::query()
            ->where('book_id', '=', $shadesBook->id)->where('name', '=', 'Eclipse')->firstOrFail();
        $eclipseCrossLink = Page::query()
            ->where('book_id', '=', $shadesBook->id)
            ->where('chapter_id', '=', $eclipseShadesChapter->id)
            ->where('name', '=', 'Eclipse - See Screens')
            ->firstOrFail();
        $this->assertStringContainsString('Screens', $eclipseCrossLink->html);
        $this->assertStringContainsString('Routing page only', $eclipseCrossLink->html);
        $this->assertStringNotContainsString('Source Status', $eclipseCrossLink->html);

        // Vantis primary: Smart & Automation (full chapter). Secondary: Shades and Tint & Film (cross-link chapters).
        $this->assertTrue(
            Chapter::query()->where('book_id', '=', $smartAutomationBook->id)->where('name', '=', 'Vantis')->exists(),
            'Vantis must have a full chapter in primary category: Smart & Automation'
        );
        $vantisOverview = Page::query()
            ->where('book_id', '=', $smartAutomationBook->id)
            ->where('name', '=', 'Vantis - Overview')
            ->firstOrFail();
        $vantisSmartFilm = Page::query()
            ->where('book_id', '=', $smartAutomationBook->id)
            ->where('name', '=', 'Vantis - Smart Film')
            ->firstOrFail();
        $vantisShadeSystems = Page::query()
            ->where('book_id', '=', $smartAutomationBook->id)
            ->where('name', '=', 'Vantis - Shade Systems')
            ->firstOrFail();

        foreach ([
            'Smart Film',
            'Shade Systems',
            'https://vantisshades.com/',
            'https://vantisshades.com/shade-systems',
        ] as $text) {
            $this->assertStringContainsString($text, $vantisOverview->html);
        }

        foreach ([$vantisSmartFilm, $vantisShadeSystems] as $vantisProductPage) {
            $this->assertStringContainsString('Install Guides', $vantisProductPage->html);
            $this->assertStringContainsString('Product Specs', $vantisProductPage->html);
            $this->assertStringContainsString('Sales Collateral', $vantisProductPage->html);
            $this->assertStringContainsString('Warranty', $vantisProductPage->html);
            $this->assertStringContainsString($vantisOverview->getUrl(), $vantisProductPage->html);
        }

        $this->assertFalse(
            Page::query()
                ->where('book_id', '=', $smartAutomationBook->id)
                ->where('name', '=', 'Vantis - Vantis Control Systems')
                ->exists(),
            'The old generated Vantis Control Systems placeholder should be removed.'
        );

        foreach ([$shadesBook, $tintFilmBook] as $secondaryBook) {
            $vantisSecondaryChapter = Chapter::query()
                ->where('book_id', '=', $secondaryBook->id)
                ->where('name', '=', 'Vantis')
                ->firstOrFail();
            $vantisCrossLink = Page::query()
                ->where('book_id', '=', $secondaryBook->id)
                ->where('chapter_id', '=', $vantisSecondaryChapter->id)
                ->where('name', '=', 'Vantis - See Smart & Automation')
                ->firstOrFail();

            $this->assertStringContainsString('Smart &amp; Automation', $vantisCrossLink->html);
            $this->assertStringContainsString('Routing page only', $vantisCrossLink->html);
            $this->assertStringContainsString($vantisOverview->getUrl(), $vantisCrossLink->html);
            $this->assertStringNotContainsString('Source Status', $vantisCrossLink->html);
        }

        $this->assertDatabaseHas('settings', [
            'setting_key' => 'app-homepage-type',
            'value'       => 'page',
        ]);

        $homeSetting = DB::table('settings')->where('setting_key', '=', 'app-homepage')->value('value');
        $this->assertNotEmpty($homeSetting);

        $homePage = Page::query()->findOrFail((int) $homeSetting);
        $this->assertSame($startHereTaskPage->id, $homePage->id);
        $this->assertFalse(
            Page::query()
                ->where('book_id', '=', $startHere->id)
                ->whereNull('chapter_id')
                ->whereIn('name', ['Sales Hub Home', 'ShadeStack Home'])
                ->exists(),
            'Generated legacy home pages should be removed once Find Your Path is the homepage.'
        );
        $this->assertStringContainsString($productsShelf->getUrl(), $homePage->html);
        $this->assertStringContainsString($vendorIndexPage->getUrl(), $homePage->html);
        $this->assertStringContainsString('I know the product type', $homePage->html);
        $this->assertStringContainsString('I know the vendor', $homePage->html);
        $this->assertStringContainsString('I know the customer outcome', $homePage->html);
        $this->assertStringContainsString('Products', $homePage->html);
        $this->assertStringNotContainsString('>Start Here</a>', $homePage->html);
        $this->assertStringNotContainsString('Open Task Buttons', $homePage->html);
        $this->assertStringNotContainsString('Workflow supports the product library', $homePage->html);
        $this->assertStringNotContainsString('Residential', $homePage->html);
        $this->assertStringNotContainsString('Commercial', $homePage->html);
        $this->assertStringNotContainsString('http://localhost', $homePage->html);

        $admin = $this->users->admin();
        $this->actingAs($admin)->get($startHereTaskPage->getUrl())->assertOk();
        $this->actingAs($admin)->get($productsShelf->getUrl())->assertOk();
        $this->actingAs($admin)->get($tintFilmBook->getUrl())->assertOk();
        $this->actingAs($admin)->get($productPage->getUrl())->assertOk();

        $orderMarkers = [
            'docs/readme.md'     => 'The section order is locked',
            'docs/navigation.md' => 'Every product page must use this exact outcome heading order',
            'docs/products.md'   => 'The locked outcome order is',
            'docs/structure.md'  => 'The locked outcome section order is',
        ];

        foreach ($orderMarkers as $docPath => $marker) {
            $content = file_get_contents(base_path($docPath));
            $orderSection = substr($content, strpos($content, $marker) ?: 0);
            $docInstallPosition = strpos($orderSection, 'Install Guides');
            $docSpecsPosition = strpos($orderSection, 'Product Specs');
            $docCollateralPosition = strpos($orderSection, 'Sales Collateral');
            $docWarrantyPosition = strpos($orderSection, 'Warranty');
            $this->assertNotFalse($docInstallPosition, $docPath . ' must mention Install Guides.');
            $this->assertNotFalse($docSpecsPosition, $docPath . ' must mention Product Specs.');
            $this->assertNotFalse($docCollateralPosition, $docPath . ' must mention Sales Collateral.');
            $this->assertNotFalse($docWarrantyPosition, $docPath . ' must mention Warranty.');
            $this->assertTrue($docInstallPosition < $docSpecsPosition, $docPath . ' must list Install Guides before Product Specs.');
            $this->assertTrue($docSpecsPosition < $docCollateralPosition, $docPath . ' must list Product Specs before Sales Collateral.');
            $this->assertTrue($docCollateralPosition < $docWarrantyPosition, $docPath . ' must list Sales Collateral before Warranty.');
        }
    }
}
