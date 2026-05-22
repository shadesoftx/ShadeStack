<?php

namespace Database\Seeders;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\Bookshelf;
use BookStack\Entities\Models\Chapter;
use BookStack\Entities\Models\Page;
use BookStack\Entities\Repos\BaseRepo;
use BookStack\Entities\Tools\TrashCan;
use BookStack\Permissions\JointPermissionBuilder;
use BookStack\Permissions\Models\RolePermission;
use BookStack\Permissions\Permission;
use BookStack\Users\Models\Role;
use BookStack\Users\Models\User;
use Illuminate\Database\Seeder;

class ShadesOfTexasStructureSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = User::query()->where('email', '=', 'admin@admin.com')->value('id')
            ?? User::query()->value('id');

        if (!$ownerId) {
            return;
        }

        $byData = [
            'created_by' => $ownerId,
            'updated_by' => $ownerId,
            'owned_by'   => $ownerId,
        ];

        $this->applyBrandSettings();
        $this->grantTeamReadAccess();
        $this->resetExistingStructure();

        $shelf = $this->createShelf($byData);
        $createdBooks = [];
        $createdChapters = [];
        $createdPages = [];
        $createdProductPages = [];
        $createdVendorProductPages = [];
        $servicePageCounters = [];
        $startHerePriority = 0;

        foreach ($this->topLevelBooks() as $bookName => $config) {
            $createdBooks[$bookName] = $this->createBook($bookName, $config['description'], $byData);
            $shelf->appendBook($createdBooks[$bookName]);
        }

        $startHerePageCounters = 0;
        foreach (($this->bookConfigs()['Start Here']['pages'] ?? []) as $pageConfig) {
            $startHerePageCounters++;
            $pageName = $pageConfig['name'];
            $createdPages['Start Here'][null][$pageName] = $this->createPage(
                $createdBooks['Start Here'],
                null,
                $pageName,
                $pageConfig['summary'],
                $byData,
                $this->buildStartHerePageHtml($pageName, $pageConfig['summary'], $createdBooks),
                $startHerePageCounters
            );
        }

        $productPageCounters = 0;
        $brandProfiles = $this->brandProfiles();
        $vendorProductInventory = $this->parsedProductMarkdownBrands();
        foreach (($this->bookConfigs()['Vendors']['chapters'] ?? []) as $brandName => $brandConfig) {
            $brandChapter = $this->createChapter($createdBooks['Vendors'], $brandName, $brandConfig['description'], $byData);
            $createdChapters['Vendors'][$brandName] = $brandChapter;

            $vendorProductPages = [];
            $inventoryProducts = $vendorProductInventory[$brandName] ?? ($brandProfiles[$brandName]['products'] ?? []);
            $vendorProductPagePriority = 1;

            foreach ($inventoryProducts as $product) {
                $productName = $product['name'];
                $vendorProductPagePriority++;
                $vendorProductPages[$productName] = $this->createPage(
                    $createdBooks['Vendors'],
                    $brandChapter,
                    $productName,
                    $product['summary'] ?? '',
                    $byData,
                    $this->buildVendorProductPageHtml(
                        $brandName,
                        $productName,
                        $product['summary'] ?? '',
                        $product['url'] ?? null,
                        $inventoryProducts,
                        $brandProfiles[$brandName] ?? []
                    ),
                    $vendorProductPagePriority
                );
            }

            $createdVendorProductPages[$brandName] = $vendorProductPages;
            $createdProductPages[$brandName] = $this->createPage(
                $createdBooks['Vendors'],
                $brandChapter,
                $brandName,
                $brandConfig['description'],
                $byData,
                $this->buildBrandPageHtml(
                    $brandName,
                    $brandName,
                    $brandConfig['description'],
                    $brandProfiles[$brandName] ?? [],
                    $vendorProductPages
                ),
                1
            );

            foreach ($vendorProductPages as $productName => $vendorProductPage) {
                $productData = collect($vendorProductInventory[$brandName] ?? [])->firstWhere('name', $productName) ?? [];
                $productSummary = $productData['summary'] ?? ($vendorProductPage->name ?? '');
                $productUrl = $productData['url'] ?? null;
                $finalHtml = $this->buildVendorProductPageHtml(
                    $brandName,
                    $productName,
                    $productSummary,
                    $productUrl,
                    $vendorProductInventory[$brandName] ?? [],
                    $brandProfiles[$brandName] ?? [],
                    $vendorProductPages
                );

                $vendorProductPage->forceFill([
                    'html' => $finalHtml,
                    'text' => strip_tags($finalHtml),
                ])->save();
                $vendorProductPage->refresh();
            }
        }

        $serviceCatalog = $this->serviceCatalog();
        foreach ($serviceCatalog as $serviceName => $serviceConfig) {
            $book = $createdBooks[$serviceName];

            foreach ($serviceConfig['chapters'] as $chapterName => $chapterConfig) {
                $chapter = $this->createChapter($book, $chapterName, $chapterConfig['description'], $byData);
                $createdChapters[$serviceName][$chapterName] = $chapter;
            }
        }

        foreach ($serviceCatalog as $serviceName => $serviceConfig) {
            $book = $createdBooks[$serviceName];

            foreach ($serviceConfig['chapters'] as $chapterName => $chapterConfig) {
                $chapter = $createdChapters[$serviceName][$chapterName];

                foreach ($chapterConfig['pages'] as $pageConfig) {
                    $pageName = $pageConfig['name'];
                    $servicePageCounters[$serviceName][$chapterName] = ($servicePageCounters[$serviceName][$chapterName] ?? 0) + 1;
                    $customHtml = $this->buildServiceCategoryHtml(
                        $serviceName,
                        $chapterName,
                        $pageName,
                        $pageConfig['summary'],
                        $pageConfig['products'] ?? [],
                        $createdProductPages,
                        $createdBooks['Vendors']
                    );

                    $createdPages[$serviceName][$chapterName][$pageName] = $this->createPage(
                        $book,
                        $chapter,
                        $pageName,
                        $pageConfig['summary'],
                        $byData,
                        $customHtml,
                        $servicePageCounters[$serviceName][$chapterName]
                    );
                }
            }
        }

        $homePage = $this->createPage(
            $createdBooks['Start Here'],
            null,
            'Sales Hub Home',
            'Landing page for the sales team with quick links into the main resource areas.',
            $byData,
            $this->buildHomepageHtml($createdBooks, $createdChapters, $createdPages),
            ++$startHerePageCounters
        );

        setting()->put('app-homepage-type', 'page');
        setting()->put('app-homepage', (string) $homePage->id);
    }

    protected function applyBrandSettings(): void
    {
        $brandSettings = [
            'app-color'             => '#171B2A',
            'app-color-light'       => 'rgba(255,90,60,0.14)',
            'link-color'            => '#FF5A3C',
            'bookshelf-color'       => '#171B2A',
            'book-color'            => '#FF5A3C',
            'chapter-color'         => '#171B2A',
            'page-color'            => '#FF5A3C',
            'page-draft-color'      => '#9A9FB2',
            'app-color-dark'        => '#111523',
            'app-color-light-dark'  => 'rgba(255,90,60,0.14)',
            'link-color-dark'       => '#FF5A3C',
            'bookshelf-color-dark'  => '#171B2A',
            'book-color-dark'       => '#FF5A3C',
            'chapter-color-dark'    => '#171B2A',
            'page-color-dark'       => '#FF5A3C',
            'page-draft-color-dark' => '#B9BDD0',
        ];

        foreach ($brandSettings as $key => $value) {
            setting()->put($key, $value);
        }

        setting()->put('app-custom-head', $this->buildBrandHeadCss());
    }

    protected function buildBrandHeadCss(): string
    {
        return <<<'HTML'
<style>
:root {
    --sotx-navy: #171B2A;
    --sotx-orange: #FF5A3C;
    --sotx-bg: #f6f4ef;
    --sotx-surface: #ffffff;
    --sotx-surface-alt: #faf8f4;
    --sotx-border: rgba(23, 27, 42, 0.10);
    --sotx-text: #171B2A;
    --sotx-muted: #5f6474;
    --sotx-soft: rgba(255, 90, 60, 0.10);
}

html.dark-mode {
    --sotx-bg: #0f1320;
    --sotx-surface: #171B2A;
    --sotx-surface-alt: #1d2233;
    --sotx-border: rgba(255, 255, 255, 0.10);
    --sotx-text: #f5f7fb;
    --sotx-muted: #a7afc1;
    --sotx-soft: rgba(255, 90, 60, 0.14);
}

.sotx-homepage .tri-layout-middle-contents,
.sotx-homepage .content-wrap {
    width: 100%;
}

.sotx-home {
    max-width: 76rem;
    margin: 0 auto;
    padding: clamp(1rem, 2vw, 1.5rem);
    color: var(--sotx-text);
}

.sotx-panel,
.sotx-card,
.sotx-mini-card {
    background: var(--sotx-surface);
    border: 1px solid var(--sotx-border);
    border-radius: 1.25rem;
    box-shadow: 0 .75rem 1.6rem rgba(23, 27, 42, 0.05);
}

.sotx-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.6fr) minmax(16rem, 0.95fr);
    gap: 1rem;
    padding: clamp(1.2rem, 2.5vw, 2rem);
    background: linear-gradient(180deg, var(--sotx-surface) 0%, var(--sotx-surface-alt) 100%);
}

.sotx-kicker {
    margin: 0 0 .55rem;
    color: var(--sotx-orange);
    text-transform: uppercase;
    letter-spacing: .16em;
    font-size: .72rem;
    font-weight: 900;
}

.sotx-hero h1,
.sotx-section h2,
.sotx-card h3,
.sotx-card h4,
.sotx-mini-card h4 {
    color: var(--sotx-text);
    margin: 0;
}

.sotx-hero h1 {
    font-size: clamp(2rem, 4vw, 3.35rem);
    line-height: 1;
    letter-spacing: -0.03em;
}

.sotx-lede,
.sotx-card p,
.sotx-mini-card p,
.sotx-section p,
.sotx-note {
    color: var(--sotx-muted);
    line-height: 1.7;
}

.sotx-hero-copy {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 1rem;
}

.sotx-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .6rem;
}

.sotx-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .72rem 1rem;
    border-radius: 999px;
    background: var(--sotx-orange);
    color: #fff;
    text-decoration: none;
    font-weight: 900;
    box-shadow: 0 .7rem 1.3rem rgba(255, 90, 60, .22);
}

.sotx-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .75rem;
}

.sotx-metric {
    padding: .95rem 1rem;
    border-radius: 1rem;
    background: var(--sotx-soft);
    border: 1px solid rgba(255, 90, 60, .14);
}

.sotx-metric strong,
.sotx-metric span {
    display: block;
}

.sotx-metric strong {
    font-size: 1.12rem;
    color: var(--sotx-text);
}

.sotx-metric span {
    margin-top: .15rem;
    font-size: .92rem;
}

.sotx-section {
    margin-top: 1.15rem;
}

.sotx-section-head {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: .85rem;
}

.sotx-section h2 {
    font-size: clamp(1.35rem, 2vw, 1.7rem);
    letter-spacing: -0.02em;
}

.sotx-service-grid,
.sotx-resource-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));
    gap: .9rem;
}

.sotx-card,
.sotx-mini-card,
.sotx-service-card,
.sotx-link-card {
    display: block;
    padding: 1rem;
    text-decoration: none;
    color: var(--sotx-text);
    transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
}

.sotx-service-card:hover,
.sotx-link-card:hover,
.sotx-mini-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 1rem 1.9rem rgba(23, 27, 42, .08);
    border-color: rgba(255, 90, 60, .22);
}

.sotx-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
}

.sotx-flag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .28rem .5rem;
    border-radius: 999px;
    background: rgba(23, 27, 42, .95);
    color: #fff;
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.sotx-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-top: .8rem;
}

.sotx-chip {
    display: inline-flex;
    align-items: center;
    padding: .34rem .55rem;
    border-radius: 999px;
    background: var(--sotx-surface-alt);
    border: 1px solid var(--sotx-border);
    color: var(--sotx-text);
    font-size: .74rem;
    font-weight: 800;
    line-height: 1.2;
}

.sotx-chip-link {
    text-decoration: none;
}

.sotx-chip-link:hover {
    color: var(--sotx-orange);
    border-color: rgba(255, 90, 60, 0.35);
}

.sotx-section-card {
    padding: 1.15rem;
}

.sotx-guide {
    padding: 1.15rem;
}

.sotx-guide ol {
    margin: 0;
    padding-left: 1.2rem;
    color: var(--sotx-muted);
    line-height: 1.75;
}

.sotx-template-block {
    min-height: 100%;
}

.sotx-template-list {
    margin: .9rem 0 0;
    padding-left: 1.1rem;
    color: var(--sotx-muted);
    line-height: 1.7;
}

.sotx-template-list li + li {
    margin-top: .45rem;
}

@media (max-width: 860px) {
    .sotx-hero {
        grid-template-columns: 1fr;
    }

    .sotx-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .sotx-stats {
        grid-template-columns: 1fr;
    }
}
</style>
HTML;
    }

    protected function resetExistingStructure(): void
    {
        $trashCan = app(TrashCan::class);

        foreach (['Shades of Texas Sales Hub', 'High Level'] as $shelfName) {
            $existingShelf = Bookshelf::query()->where('name', '=', $shelfName)->first();
            if (!$existingShelf) {
                continue;
            }

            foreach ($existingShelf->books()->get() as $book) {
                $trashCan->destroyEntity($book);
            }

            $trashCan->destroyEntity($existingShelf);
        }
    }

    protected function createShelf(array $byData): Bookshelf
    {
        $description = 'A world class document management platform';

        /** @var Bookshelf $shelf */
        $shelf = new Bookshelf();
        $shelf->forceFill(array_merge($byData, [
            'name'            => 'High Level',
            'description'     => $description,
            'description_html' => '<p>' . e($description) . '</p>',
        ]))->save();
        $shelf->rebuildPermissions();

        return $shelf;
    }

    protected function createBook(string $name, string $description, array $byData): Book
    {
        /** @var Book $book */
        $book = new Book();
        $book->forceFill(array_merge($byData, [
            'name'             => $name,
            'description'      => $description,
            'description_html' => '<p>' . e($description) . '</p>',
        ]))->save();

        app(BaseRepo::class)->refreshSlug($book);
        $book->save();
        $book->refresh();
        $book->rebuildPermissions();

        return $book;
    }

    protected function createChapter(Book $book, string $name, string $description, array $byData): Chapter
    {
        /** @var Chapter $chapter */
        $chapter = new Chapter();
        $chapter->forceFill(array_merge($byData, [
            'book_id'          => $book->id,
            'name'             => $name,
            'description'      => $description,
            'description_html' => '<p>' . e($description) . '</p>',
            'priority'         => 1,
        ]))->save();

        app(BaseRepo::class)->refreshSlug($chapter);
        $chapter->save();
        $chapter->refresh();
        $chapter->rebuildPermissions();

        return $chapter;
    }

    protected function createPage(Book $book, ?Chapter $chapter, string $name, string $summary, array $byData, ?string $customHtml = null, int $priority = 1): Page
    {
        $html = $customHtml ?? $this->buildStandardPageHtml($name, $summary);

        /** @var Page $page */
        $page = new Page();
        $page->forceFill(array_merge($byData, [
            'book_id'         => $book->id,
            'chapter_id'      => $chapter?->id,
            'name'            => $name,
            'html'            => $html,
            'text'            => strip_tags($html),
            'revision_count'  => 1,
            'editor'          => 'wysiwyg',
            'priority'        => $priority,
        ]))->save();

        app(BaseRepo::class)->refreshSlug($page);
        $page->save();
        $page->refresh();
        $page->rebuildPermissions();

        return $page;
    }

    protected function buildStandardPageHtml(string $name, string $summary): string
    {
        return <<<HTML
<div class="sotx-home">
    <section class="sotx-panel sotx-card sotx-section-card">
        <p class="sotx-kicker">Shades of Texas Resource</p>
        <h1>{$name}</h1>
        <p class="sotx-lede">{$summary}</p>
    </section>
</div>
HTML;
    }

    protected function buildStartHerePageHtml(string $pageName, string $summary, array $books = []): string
    {
        if ($pageName === 'Source of Truth') {
            return $this->buildSourcePageHtml($summary, $books);
        }

        if ($pageName === 'How to Use This Hub') {
            $quickLinks = $this->buildStartHereBookLinks($books);
            $vendorLink = $this->buildBookLinkHtml($books, 'Vendors');
            $residentialLink = $this->buildBookLinkHtml($books, 'Residential');
            $commercialLink = $this->buildBookLinkHtml($books, 'Commercial');
            $sourceLink = $this->buildBookLinkHtml($books, 'Start Here', 'Source of Truth');

            return $this->buildScaffoldPageHtml(
                'Start Here',
                $pageName,
                $summary,
                [
                    [
                        'title' => 'Pick the Right Lane',
                        'summary' => 'Start with the book that matches the kind of answer you need.',
                        'points' => [
                            ['html' => $vendorLink . ' is where vendor overviews, product pages, warranty links, FAQ links, and official source links live.'],
                            ['html' => $residentialLink . ' is where homeowner-facing service categories live: tint and film, window treatments, outdoor living, and glass work.'],
                            ['html' => $commercialLink . ' is where business-facing service categories live: commercial film, patio systems, glazing, and commercial treatments.'],
                            ['html' => $sourceLink . ' is the audit trail for official vendor sources when a link or claim needs to be checked.'],
                        ],
                    ],
                    [
                        'title' => 'Common Lookup Paths',
                        'summary' => 'Use these paths when you need an answer quickly during sales or support work.',
                        'points' => [
                            ['html' => '<strong>Customer asks what we carry:</strong> open ' . $vendorLink . ', pick the vendor, then open the exact product page.'],
                            ['html' => '<strong>Customer asks about warranty:</strong> open the vendor page and use the <strong>Warranty Information</strong> section before quoting coverage.'],
                            ['html' => '<strong>Customer asks a practical product question:</strong> open the product page first, then use the vendor <strong>FAQs</strong> section if the product page does not answer it.'],
                            ['html' => '<strong>You only know the project type:</strong> start in ' . $residentialLink . ' or ' . $commercialLink . ', then use the linked vendor chips on that service page.'],
                        ],
                    ],
                    [
                        'title' => 'When to Slow Down',
                        'summary' => 'Some answers need verification before they go to a customer.',
                        'points' => [
                            'Do not promise warranty coverage without checking the exact product line, install conditions, and purchase date.',
                            'Do not use a vendor homepage as proof of warranty terms unless the vendor does not publish a public warranty page and the page says to confirm directly.',
                            'If a link looks stale, use Source of Truth to find the official vendor path and update the vendor page afterward.',
                        ],
                    ],
                ],
                $quickLinks
            );
        }

        if ($pageName === 'Where to Find Product Info and Pricing') {
            $quickLinks = $this->buildStartHereBookLinks($books);
            $vendorLink = $this->buildBookLinkHtml($books, 'Vendors');
            $residentialLink = $this->buildBookLinkHtml($books, 'Residential');
            $commercialLink = $this->buildBookLinkHtml($books, 'Commercial');

            return $this->buildScaffoldPageHtml(
                'Start Here',
                $pageName,
                $summary,
                [
                    [
                        'title' => 'Where Each Type of Info Lives',
                        'summary' => 'Keep the product and pricing details in the right place.',
                        'points' => [
                            ['html' => '<strong>Product lines:</strong> use ' . $vendorLink . ' and open the product page under the vendor.'],
                            ['html' => '<strong>Service fit:</strong> use ' . $residentialLink . ' or ' . $commercialLink . ' when you need to know which vendors apply to a project type.'],
                            ['html' => '<strong>Warranty:</strong> use the vendor page or product page <strong>Warranty Information</strong> section.'],
                            ['html' => '<strong>FAQs:</strong> use the vendor page or product page <strong>FAQs</strong> section for care, ordering, and common support questions.'],
                            ['html' => '<strong>Pricing:</strong> use the vendor page <strong>Quick Links</strong>, dealer portal links, or the listed rep/contact path. If a vendor requires login access, do not quote from memory.'],
                        ],
                    ],
                    [
                        'title' => 'What to Check Before Quoting',
                        'summary' => 'Use the current sources instead of old attachments or memory.',
                        'points' => [
                            'Confirm the exact product name and series.',
                            'Confirm whether the job is residential or commercial.',
                            'Confirm warranty coverage before using it as a selling point.',
                            'Confirm whether pricing is public, dealer-only, or rep-provided.',
                            'If the vendor page says access is pending or direct confirmation is required, ask for the missing detail before quoting.',
                        ],
                    ],
                ],
                $quickLinks
            );
        }

        if ($pageName === 'How to Request Missing Documents') {
            $quickLinks = $this->buildStartHereBookLinks($books);
            $vendorLink = $this->buildBookLinkHtml($books, 'Vendors');
            $residentialLink = $this->buildBookLinkHtml($books, 'Residential');
            $commercialLink = $this->buildBookLinkHtml($books, 'Commercial');
            $sourceLink = $this->buildBookLinkHtml($books, 'Start Here', 'Source of Truth');

            return $this->buildScaffoldPageHtml(
                'Start Here',
                $pageName,
                $summary,
                [
                    [
                        'title' => 'What to Include in the Request',
                        'summary' => 'Give enough detail so someone can find the right file on the first pass.',
                        'points' => [
                            'List the vendor, product line, and exact document type.',
                            'Include the job name or customer context if it matters.',
                            'Include whether this is for a residential or commercial project.',
                            'Add the link you already checked, even if it was wrong or incomplete.',
                            'Say what decision is blocked: pricing, warranty, install detail, product fit, or customer answer.',
                        ],
                    ],
                    [
                        'title' => 'Where to Check First',
                        'summary' => 'Most missing-document requests can be narrowed down before asking someone else.',
                        'points' => [
                            ['html' => 'Check ' . $vendorLink . ' for vendor-level product, warranty, FAQ, and quick links.'],
                            ['html' => 'Check ' . $residentialLink . ' or ' . $commercialLink . ' if you only know the service category.'],
                            ['html' => 'Check ' . $sourceLink . ' if the issue is a bad link, missing vendor source, or conflicting vendor information.'],
                            'If the source page says rep confirmation is required, collect the rep response and add it back to the vendor page later.',
                        ],
                    ],
                    [
                        'title' => 'Who to Ask',
                        'summary' => 'Use the right escalation path for the type of missing information.',
                        'points' => [
                            'For vendor access, pricing, samples, or dealer portal issues, ask the assigned vendor rep or account contact.',
                            'For website structure, navigation, or content placement, contact John Borg.',
                            'For customer-facing uncertainty, mark the answer as unconfirmed until the vendor source or rep confirms it.',
                        ],
                    ],
                ],
                $quickLinks
            );
        }

        return $this->buildScaffoldPageHtml('Start Here', $pageName, $summary, []);
    }

    protected function buildSourcePageHtml(string $summary, array $books = []): string
    {
        $quickLinks = $this->buildStartHereBookLinks($books);
        $brandPoints = [];
        foreach ($this->sourceRegistry()['brands'] as $brandName => $brandConfig) {
            $links = '';
            foreach ($brandConfig['links'] as $link) {
                $links .= '<a href="' . e($link['url']) . '" target="_blank" rel="noreferrer">' . e($link['label']) . '</a> ';
            }

            $brandPoints[] = [
                'html' => '<strong>' . e($brandName) . '</strong><br>' . e($brandConfig['summary']) . '<br>' . trim($links),
            ];
        }

        $servicePoints = [];
        foreach ($this->sourceRegistry()['services'] as $serviceName => $serviceConfig) {
            $servicePoints[] = [
                'html' => '<strong>' . e($serviceName) . '</strong><br>' . e($serviceConfig['summary']) . '<br>Brands: ' . e(implode(', ', $serviceConfig['sources'])),
            ];
        }

        return $this->buildScaffoldPageHtml(
            'Start Here',
            'Source of Truth',
            $summary,
            [
                [
                    'title' => 'Read This First',
                    'summary' => 'Use this page when you need to verify where vendor information came from.',
                    'points' => [
                        'Use the vendor page first during normal sales lookup.',
                        'Use this page when a vendor link breaks, a customer asks for proof, or two pages disagree.',
                        'After a source changes, update the vendor page and any affected service pages so the hub stays consistent.',
                    ],
                ],
                [
                    'title' => 'Verified Vendor Sources',
                    'summary' => 'Official vendor sites, portals, warranty pages, FAQs, and product references used throughout the hub.',
                    'points' => $brandPoints,
                ],
                [
                    'title' => 'Service Source Map',
                    'summary' => 'Which vendors support each residential and commercial service area.',
                    'points' => $servicePoints,
                ],
                [
                    'title' => 'How to Keep This Current',
                    'summary' => 'Use this order when a vendor source changes or a bad link is found.',
                    'points' => [
                        'Confirm the replacement link is official and relevant to what Shades of Texas sells.',
                        'Update the matching vendor page in Vendors.',
                        'Update product pages under that vendor if the change affects a specific product line.',
                        'Update Residential or Commercial pages if the change affects how we position a service category.',
                        'Leave a clear note when the vendor does not publish a public source and direct rep confirmation is required.',
                    ],
                ],
            ],
            <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Core Books</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Jump straight to the books that matter most during a sales lookup.</p>
        </div>
    </div>
    <div class="sotx-actions" style="margin-top:.55rem;">{$quickLinks}</div>
</section>
HTML
        );
    }

    protected function buildStartHereBookLinks(array $books): string
    {
        $links = [];
        foreach ([
            'Start Here',
            'Residential',
            'Commercial',
            'Vendors',
        ] as $bookName) {
            if (empty($books[$bookName])) {
                continue;
            }

            $links[] = '<a href="' . e($books[$bookName]->getUrl()) . '" class="sotx-pill">' . e($bookName) . '</a>';
        }

        return implode('', $links);
    }

    protected function buildBookLinkHtml(array $books, string $bookName, ?string $label = null): string
    {
        $label ??= $bookName;
        if (empty($books[$bookName])) {
            return e($label);
        }

        return '<a href="' . e($books[$bookName]->getUrl()) . '">' . e($label) . '</a>';
    }

    protected function sourceRegistry(): array
    {
        return [
            'brands' => [
                '3M' => [
                    'summary' => 'Window film and warranty sources.',
                    'links' => [
                        ['label' => 'Building Window Solutions', 'url' => 'https://www.3m.com/3M/en_US/building-window-solutions-us/'],
                        ['label' => 'Home Window Solutions', 'url' => 'https://www.3m.com/3M/en_US/home-window-solutions-us/'],
                        ['label' => 'Support / FAQs', 'url' => 'https://www.3m.com/3M/en_US/building-window-solutions-us/support/'],
                        ['label' => 'Find a Dealer', 'url' => 'https://www.3m.com/3M/en_US/building-window-solutions-us/support/find-a-dealer/'],
                        ['label' => 'Resources', 'url' => 'https://www.3m.com/3M/en_US/building-window-solutions-us/resources/'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://multimedia.3m.com/mws/media/943121O/3m-window-film-commercial-product-information-form.pdf?fn=Commercial+Product+Information+Form.pdf'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://www.3m.com/3M/en_US/building-window-solutions-us/support/'],
                    ],
                ],
                'Hunter Douglas' => [
                    'summary' => 'Residential and commercial window-treatment support sources.',
                    'links' => [
                        ['label' => 'Window Treatments', 'url' => 'https://www.hunterdouglas.com/window-treatments'],
                        ['label' => 'Support Center', 'url' => 'https://help.hunterdouglas.com/hc/en-us'],
                        ['label' => 'FAQs', 'url' => 'https://help.hunterdouglas.com/hc/en-us/categories/39191091693076-FAQs'],
                        ['label' => 'Warranty FAQs', 'url' => 'https://help.hunterdouglas.com/hc/en-us/sections/39307695386772-Warranty-FAQs'],
                        ['label' => 'Installation', 'url' => 'https://www.hunterdouglas.com/installation?Parts='],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://help.hunterdouglas.com/hc/en-us/articles/39534899498516'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://help.hunterdouglas.com/hc/en-us/categories/39191091693076-FAQs'],
                        ['label' => 'Warranty FAQs', 'url' => 'https://help.hunterdouglas.com/hc/en-us/sections/39307695386772-Warranty-FAQs'],
                    ],
                ],
                'Alta' => [
                    'summary' => 'Alta window treatments, resources, and warranty references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.altawindowfashions.com/'],
                        ['label' => 'Resources', 'url' => 'https://www.altawindowfashions.com/resources'],
                        ['label' => 'Maintenance & Warranty', 'url' => 'https://prod.altawindowfashions.com/en/maintenance-and-warranty'],
                        ['label' => 'FAQs', 'url' => 'https://www.altawindowfashions.com/faqs'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://prod.altawindowfashions.com/en/maintenance-and-warranty'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://www.altawindowfashions.com/faqs'],
                    ],
                ],
                'Norman' => [
                    'summary' => 'Norman product and warranty references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://normanusa.com/'],
                        ['label' => 'Window Treatments', 'url' => 'https://normanusa.com/window-treatments/'],
                        ['label' => 'FAQs', 'url' => 'https://normanusa.com/window-treatments/beach-house-coastal-window-treatments/'],
                        ['label' => 'Warranties', 'url' => 'https://normanusa.com/warranties/'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://normanusa.com/warranties/'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://normanusa.com/window-treatments/beach-house-coastal-window-treatments/'],
                    ],
                ],
                'Eclipse' => [
                    'summary' => 'Eclipse awning and shading references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.eclipseawning.com/'],
                        ['label' => 'Brochure', 'url' => 'https://www.eclipseawning.com/wp-content/uploads/Eclipse-Brochure-v.2.21-for-email.pdf'],
                        ['label' => 'Questions / Dealer Inquiries', 'url' => 'https://eclipseshading.com/dealers/search/'],
                        ['label' => 'Custom Brands Group', 'url' => 'https://www.custombrandsgroup.com/'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://eclipseshading.com/the-eclipse-shading-systems%C2%AE-warranty/'],
                        ['label' => 'Warranty PDF', 'url' => 'https://eclipseshading.com/wp-content/uploads/Warranty-Information.pdf'],
                    ],
                    'faq_links' => [
                        ['label' => 'Questions / Dealer Inquiries', 'url' => 'https://eclipseshading.com/dealers/search/'],
                    ],
                ],
                'SmartTint' => [
                    'summary' => 'SmartTint product, application, and technical references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.smarttint.com/'],
                        ['label' => 'Applications', 'url' => 'https://www.smarttint.com/applications/'],
                        ['label' => 'Why Us', 'url' => 'https://www.smarttint.com/whyus/'],
                        ['label' => 'FAQs', 'url' => 'https://www.smarttint.com/faq/'],
                        ['label' => 'Technical Data Sheet', 'url' => 'https://www.smarttint.com/wp-content/uploads/2025/03/SmartTint-SmartCling-Technical-Data-Sheet-10th-Gen-v3-1.pdf'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://www.smarttint.com/warranty/'],
                        ['label' => 'Warranty Claim Form', 'url' => 'https://www.smarttint.com/warranty-claim/'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://www.smarttint.com/faq/'],
                    ],
                ],
                'Andersen' => [
                    'summary' => 'Andersen support, warranty, and product identification sources.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.andersenwindows.com/'],
                        ['label' => 'Support', 'url' => 'https://www.andersenwindows.com/support/'],
                        ['label' => 'Warranty', 'url' => 'https://www.andersenwindows.com/support/warranty'],
                        ['label' => 'FAQs', 'url' => 'https://www.andersenwindows.com/support/faqs'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://www.andersenwindows.com/support/warranty'],
                        ['label' => 'Warranty Documents', 'url' => 'https://www.andersenwindows.com/for-professionals/documents/warranty'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://www.andersenwindows.com/support/faqs'],
                    ],
                ],
                'Pella' => [
                    'summary' => 'Pella product, support, and warranty sources.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.pella.com/'],
                        ['label' => 'Windows', 'url' => 'https://www.pella.com/ideas/windows/'],
                        ['label' => 'Warranties', 'url' => 'https://www.pella.com/support/warranties/'],
                        ['label' => 'Support', 'url' => 'https://www.pella.com/support/'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://www.pella.com/support/warranties/'],
                        ['label' => 'Historical Warranties', 'url' => 'https://www.pella.com/support/warranties/historical/'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://www.pella.com/support/faq/'],
                    ],
                ],
                'CRL' => [
                    'summary' => 'CRL architectural hardware and glazing sources.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.crlaurence.com/'],
                        ['label' => 'Shower Hardware', 'url' => 'https://www.crlaurence.com/productcategory/ShowerHardware'],
                        ['label' => 'Glass Entrance & Interior Systems', 'url' => 'https://www.crlaurence.com/productcategory/GlassEntranceInteriorSystems'],
                        ['label' => 'Railing & Windscreen Systems', 'url' => 'https://www.crlaurence.com/productcategory/RailingWindscreenSystems'],
                        ['label' => 'Door & Window Hardware', 'url' => 'https://www.crlaurence.com/productcategory/DoorWindowHardware'],
                        ['label' => 'Glazing Tools & Supplies', 'url' => 'https://www.crlaurence.com/productcategory/GlazingToolsSupplies'],
                        ['label' => 'FAQs', 'url' => 'https://www.crlaurence.com/faq'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://www.crlaurence.com/about-us/business-policies'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://www.crlaurence.com/faq'],
                    ],
                ],
                'Dallas Flat Glass' => [
                    'summary' => 'Dallas Flat Glass wholesale and fabrication source.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://dallasflatglass.com/'],
                        ['label' => 'Questions / Contact', 'url' => 'https://dallasflatglass.com/'],
                        ['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/dallas-flat-glass-distributors'],
                        ['label' => 'MapQuest Profile', 'url' => 'https://www.mapquest.com/us/texas/dallas-flat-glass-distributors-304386954'],
                    ],
                    'warranty_note' => 'No public warranty page was found. Confirm warranty terms directly with Dallas Flat Glass before quoting.',
                    'faq_links' => [
                        ['label' => 'Questions / Contact', 'url' => 'https://dallasflatglass.com/'],
                    ],
                ],
                'Ghost Glass' => [
                    'summary' => 'Ghost Glass smart film source and installation references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://ghostglassfilm.com/'],
                        ['label' => 'Smart Film DIY', 'url' => 'https://ghostglassfilm.com/smart-film-diy'],
                        ['label' => 'Installation Guide', 'url' => 'https://ghostglassfilm.com/smart-film-installation-guide'],
                        ['label' => 'FAQs', 'url' => 'https://ghostglassfilm.com/faqs-1'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://ghostglassfilm.com/warrantyanddisclaimers'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://ghostglassfilm.com/faqs-1'],
                    ],
                ],
                'JELD-WEN' => [
                    'summary' => 'JELD-WEN product, warranty, and document library sources.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.jeld-wen.com/en-us/'],
                        ['label' => 'Brands and Products', 'url' => 'https://www.corporate.jeld-wen.com/brands-and-products'],
                        ['label' => 'Warranty Guide', 'url' => 'https://www.jeld-wen.com/en-us/all-warranty-guide'],
                        ['label' => 'Documents', 'url' => 'https://www.jeld-wen.com/en-us/documents'],
                        ['label' => 'FAQ', 'url' => 'https://brandstore.jeld-wen.com/customer-service/faq/'],
                    ],
                    'warranty_links' => [
                        ['label' => 'Warranty Information', 'url' => 'https://www.jeld-wen.com/en-us/all-warranty-guide'],
                    ],
                    'faq_links' => [
                        ['label' => 'FAQs', 'url' => 'https://brandstore.jeld-wen.com/customer-service/faq/'],
                    ],
                ],
            ],
            'services' => [
                'Residential / Tint & Film' => [
                    'summary' => '3M and SmartTint drive the tint and film reference pages.',
                    'sources' => ['3M', 'SmartTint'],
                ],
                'Residential / Window Treatments' => [
                    'summary' => 'Hunter Douglas, Alta, and Norman drive the treatment pages.',
                    'sources' => ['Hunter Douglas', 'Alta', 'Norman'],
                ],
                'Residential / Outdoor Living' => [
                    'summary' => 'Eclipse drives awnings, shade structures, and patio screens.',
                    'sources' => ['Eclipse'],
                ],
                'Residential / Glass & Windows' => [
                    'summary' => 'Pella, Dallas Flat Glass, Andersen, JELD-WEN, CRL, and Ghost Glass inform the glass pages.',
                    'sources' => ['Pella', 'Dallas Flat Glass', 'Andersen', 'JELD-WEN', 'CRL', 'Ghost Glass'],
                ],
                'Commercial / Solar Control & Safety' => [
                    'summary' => '3M and SmartTint drive the commercial film pages.',
                    'sources' => ['3M', 'SmartTint'],
                ],
                'Commercial / Patio Screens & Awnings' => [
                    'summary' => 'Eclipse drives the commercial shade and awning pages.',
                    'sources' => ['Eclipse'],
                ],
                'Commercial / Glass & Windows' => [
                    'summary' => 'Pella, Dallas Flat Glass, Andersen, JELD-WEN, and CRL drive the commercial glazing page.',
                    'sources' => ['Pella', 'Dallas Flat Glass', 'Andersen', 'JELD-WEN', 'CRL'],
                ],
                'Commercial / Window Treatments' => [
                    'summary' => 'Hunter Douglas, Alta, and Norman drive the commercial shade pages.',
                    'sources' => ['Hunter Douglas', 'Alta', 'Norman'],
                ],
            ],
        ];
    }

    protected function topLevelBooks(): array
    {
        return [
            'Start Here' => [
                'description' => 'Entry point for the sales team. Start here when you need to find the right resource quickly.',
                'pages' => [
                    ['name' => 'Source of Truth', 'summary' => 'Master source map for brands, services, and reference documents.'],
                    ['name' => 'How to Use This Hub', 'summary' => 'Quick guide to navigating the knowledge base.'],
                    ['name' => 'Where to Find Product Info and Pricing', 'summary' => 'Explains where product details and pricing references live.'],
                    ['name' => 'How to Request Missing Documents', 'summary' => 'How to request a file or ask for a new reference page.'],
                ],
            ],
            'Residential' => [
                'description' => 'Residential sales resources organized by the categories the team actually sells every day.',
            ],
            'Commercial' => [
                'description' => 'Commercial sales resources organized by the categories the team actually sells every day.',
            ],
            'Vendors' => [
                'description' => 'All vendor and product references in one place, grouped so reps can find the right line fast.',
            ],
        ];
    }

    protected function resourceBooks(): array
    {
        return [
            'Vendors' => ['description' => 'All vendors and product lines in one place for fast reference.'],
        ];
    }

    protected function serviceCatalog(): array
    {
        return [
            'Residential' => [
                'chapters' => [
                    'Tint & Film' => [
                        'description' => 'Residential tint and film solutions.',
                        'pages' => [
                            ['name' => 'Safety & Security Film', 'summary' => 'What residential safety film solves and how to position it.', 'products' => ['3M']],
                            ['name' => 'Solar Film', 'summary' => 'Energy and glare control film for homes.', 'products' => ['3M', 'SmartTint']],
                            ['name' => 'Privacy Film', 'summary' => 'Film options that improve privacy without changing the room.', 'products' => ['3M', 'SmartTint']],
                        ],
                    ],
                    'Window Treatments' => [
                        'description' => 'Shades, blinds, shutters, and motorized treatment options.',
                        'pages' => [
                            ['name' => 'Window Shades', 'summary' => 'Shades that solve light control and privacy needs.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                            ['name' => 'Window Shutters', 'summary' => 'Shutter materials, finishes, and use cases.', 'products' => ['Hunter Douglas', 'Norman']],
                            ['name' => 'Window Blinds', 'summary' => 'Blind options for homeowners and designers.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                            ['name' => 'Safety / Storm Shutters', 'summary' => 'Protective shutter options for the home.', 'products' => ['Norman']],
                        ],
                    ],
                    'Outdoor Living' => [
                        'description' => 'Awnings, shade structures, and exterior coverage solutions.',
                        'pages' => [
                            ['name' => 'Shade Structures', 'summary' => 'What residential shade structures solve and how to position them.', 'products' => ['Eclipse']],
                            ['name' => 'Patio Awnings', 'summary' => 'Retractable and fixed awning options.', 'products' => ['Eclipse']],
                            ['name' => 'Patio Screens', 'summary' => 'Screens and exterior comfort options.', 'products' => ['Eclipse']],
                        ],
                    ],
                    'Glass & Windows' => [
                        'description' => 'Glass replacement, window replacement, and related service references.',
                        'pages' => [
                            ['name' => 'Window Glass', 'summary' => 'Glass replacement references and service notes.', 'products' => ['Pella', 'Dallas Flat Glass', 'Andersen', 'JELD-WEN']],
                            ['name' => 'Frameless Showers', 'summary' => 'Shower enclosure references.', 'products' => ['CRL']],
                            ['name' => 'Window Cleaning', 'summary' => 'Care and maintenance notes for finished work.'],
                        ],
                    ],
                ],
            ],
            'Commercial' => [
                'chapters' => [
                    'Solar Control & Safety' => [
                        'description' => 'Commercial tint and protective film solutions.',
                        'pages' => [
                            ['name' => 'Sun Control Film', 'summary' => 'Energy and glare control for commercial properties.', 'products' => ['3M']],
                            ['name' => 'Safety & Security Film', 'summary' => 'Protective film for businesses and storefronts.', 'products' => ['3M']],
                            ['name' => 'Privacy Film', 'summary' => 'Privacy and glare management for commercial spaces.', 'products' => ['3M', 'SmartTint']],
                            ['name' => 'SmartTint', 'summary' => 'Switchable privacy glass and film references.', 'products' => ['SmartTint']],
                        ],
                    ],
                    'Patio Screens & Awnings' => [
                        'description' => 'Commercial exterior shade and protection systems.',
                        'pages' => [
                            ['name' => 'Patio Awnings', 'summary' => 'Commercial awning and shade references.', 'products' => ['Eclipse']],
                            ['name' => 'Patio Screens', 'summary' => 'Screen and exterior comfort solutions.', 'products' => ['Eclipse']],
                        ],
                    ],
                    'Glass & Windows' => [
                        'description' => 'Commercial glass replacement, glazing, and storefront references.',
                        'pages' => [
                            ['name' => 'Commercial Glazing', 'summary' => 'Storefront and glazing references.', 'products' => ['Pella', 'Dallas Flat Glass', 'Andersen', 'JELD-WEN', 'CRL']],
                        ],
                    ],
                    'Window Treatments' => [
                        'description' => 'Commercial shades and motorized systems.',
                        'pages' => [
                            ['name' => 'Roller Shades', 'summary' => 'Commercial shade systems and project references.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildServiceCategoryHtml(string $serviceName, string $chapterName, string $categoryName, string $summary, array $productNames, array $productPages, ?Book $vendorsBook = null): string
    {
        $resourceLinks = '';
        if ($vendorsBook) {
            $resourceLinks .= '<a href="' . e($vendorsBook->getUrl()) . '" class="sotx-pill">Vendors</a>';
        }

        $productsHtml = $this->buildProductChipsHtml($productNames, $productPages);

        return <<<HTML
<div class="sotx-home">
    <section class="sotx-panel sotx-card sotx-section-card">
        <p class="sotx-kicker">{$serviceName} / {$chapterName}</p>
        <h1>{$categoryName}</h1>
        <p class="sotx-lede">{$summary}</p>
        {$productsHtml}
        <div class="sotx-actions" style="margin-top:1rem;">
            {$resourceLinks}
        </div>
    </section>
</div>
HTML;
    }

    protected function buildBrandPageHtml(string $brandName, string $pageName, string $summary, array $brandProfile = [], array $productPages = []): string
    {
        $sourceBrandProfile = $this->sourceRegistry()['brands'][$brandName] ?? [];
        $websiteUrl = $brandProfile['website'] ?? ($sourceBrandProfile['website'] ?? null);
        $quickLinks = $brandProfile['quick_links'] ?? ($brandProfile['links'] ?? ($sourceBrandProfile['links'] ?? null));
        $inventoryProducts = $this->parsedProductMarkdownBrands()[$brandName] ?? ($brandProfile['products'] ?? []);
        $productSection = [];
        foreach ($inventoryProducts as $product) {
            $productPage = $productPages[$product['name']] ?? null;
            $productSection[] = [
                'title' => $product['name'],
                'summary' => $product['summary'] ?? '',
                'points' => $product['points'] ?? [],
                'url' => $productPage?->getUrl() ?? ($product['url'] ?? null),
            ];
        }
        if (!empty($productSection)) {
            $renderSections = [[
                'title' => 'Products',
                'summary' => 'Direct links to the product lines we use from this vendor.',
                'points' => array_map(function (array $product): array {
                    $label = $product['title'];
                    if (!empty($product['summary'])) {
                        $label .= ' - ' . $product['summary'];
                    }

                    if (!empty($product['url'])) {
                        return [
                            'html' => '<a href="' . e($product['url']) . '" target="_blank" rel="noreferrer">' . e($label) . '</a>',
                        ];
                    }

                    return [
                        'text' => $label,
                    ];
                }, $productSection),
            ]];
        } else {
            $renderSections = [];
        }

        $sourceLinks = '';
        if (!empty($quickLinks)) {
            foreach ($quickLinks as $link) {
                $linkUrl = $link['url'] ?? null;
                if (empty($linkUrl)) {
                    continue;
                }

                $sourceLinks .= '<a href="' . e($linkUrl) . '" class="sotx-pill" target="_blank" rel="noreferrer">' . e($link['label'] ?? 'Website') . '</a>';
            }
        } else {
            if (!empty($websiteUrl)) {
                $sourceLinks .= '<a href="' . e($websiteUrl) . '" class="sotx-pill" target="_blank" rel="noreferrer">Website</a>';
            }

            foreach (($brandProfile['sources'] ?? []) as $label => $url) {
                $sourceLinks .= '<a href="' . e($url) . '" class="sotx-pill" target="_blank" rel="noreferrer">' . e($label) . '</a>';
            }
        }

        $leadContent = $this->renderScaffoldSections($renderSections);
        $renderSections = [];

        $warrantyLinks = $this->buildVendorWarrantyLinks($brandName, $brandProfile);
        $warrantyNote = $this->buildVendorWarrantyNote($brandName);
        if (!empty($warrantyLinks) || $warrantyNote !== '') {
            $warrantyLinksHtml = $this->renderLinkPills($warrantyLinks, 'Warranty Information');
            $warrantyLinksHtml .= '<p class="sotx-note" style="margin:.65rem 0 0;">' . e($this->buildWarrantyReminder($brandName)) . '</p>';

            if ($warrantyNote !== '') {
                $warrantyLinksHtml .= '<p class="sotx-note" style="margin:.65rem 0 0;">' . e($warrantyNote) . '</p>';
            }

            if ($warrantyLinksHtml !== '') {
                $leadContent .= <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Warranty Information</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Use these links for current warranty terms before quoting coverage.</p>
        </div>
    </div>
    {$warrantyLinksHtml}
</section>
HTML;
            }
        }

        $faqLinks = $this->buildVendorFaqLinks($brandName);
        if (!empty($faqLinks)) {
            $faqLinksHtml = $this->renderLinkPills($faqLinks, 'FAQs');
            if ($faqLinksHtml !== '') {
                $leadContent .= <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>FAQs</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Use these links for common product, care, ordering, and warranty questions.</p>
        </div>
    </div>
    {$faqLinksHtml}
</section>
HTML;
            }
        }

        if ($sourceLinks !== '') {
            $leadContent .= <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Quick Links</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Fast access to the live public pages and dealer resources for this brand.</p>
        </div>
    </div>
    <div class="sotx-actions" style="margin-top:.55rem;">{$sourceLinks}</div>
</section>
HTML;
        }

        $sourceInventoryHtml = $this->buildSourceInventoryHtml($brandName);
        if ($sourceInventoryHtml !== '') {
            $leadContent .= $sourceInventoryHtml;
        }

        return $this->buildScaffoldPageHtml($brandName, $pageName, $summary, $renderSections, $leadContent);
    }

    protected function buildVendorProductPageHtml(string $brandName, string $productName, string $summary, ?string $url, array $inventoryProducts = [], array $brandProfile = [], array $vendorProductPages = []): string
    {
        $relatedProducts = [];
        foreach ($inventoryProducts as $product) {
            if (($product['name'] ?? '') === $productName) {
                continue;
            }

            $relatedProducts[] = $product;
        }

        $quickLinks = [];
        if (!empty($url)) {
            $quickLinks[] = [
                'label' => 'Official Product Page',
                'url' => $url,
            ];
        }

        $leadContent = '';
        $links = '';
        foreach ($quickLinks as $link) {
            $links .= '<a href="' . e($link['url']) . '" class="sotx-pill" target="_blank" rel="noreferrer">' . e($link['label']) . '</a>';
        }

        if ($links === '') {
            $links = '<span class="sotx-note" style="display:block;">No public product URL is listed for this line yet. Use the vendor hub below for broader context.</span>';
        }

        $leadContent = <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Quick Links</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Use the official source when you need the live product page or brochure.</p>
        </div>
    </div>
    <div class="sotx-actions" style="margin-top:.55rem;">{$links}</div>
</section>
HTML;

        $productSummary = trim($summary);
        if ($productSummary === '') {
            $productSummary = 'Product page for ' . $productName . '.';
        }

        $fitBullets = $this->buildProductFitBullets($productName, $productSummary, $brandName);
        $relatedHtml = '';
        if (!empty($relatedProducts)) {
            $relatedHtml .= '<div class="sotx-chip-list">';
            foreach (array_slice($relatedProducts, 0, 8) as $relatedProduct) {
                $relatedLabel = $relatedProduct['name'];
                if (!empty($relatedProduct['summary'])) {
                    $relatedLabel .= ' - ' . $relatedProduct['summary'];
                }

                $relatedPage = $vendorProductPages[$relatedProduct['name']] ?? null;
                if ($relatedPage instanceof Page) {
                    $relatedHtml .= '<a class="sotx-chip" href="' . e($relatedPage->getUrl()) . '">' . e($relatedLabel) . '</a>';
                } else {
                    $relatedHtml .= '<span class="sotx-chip">' . e($relatedLabel) . '</span>';
                }
            }
            $relatedHtml .= '</div>';
        }

        $sections = [
            [
                'title' => 'Product Summary',
                'summary' => 'Use this page as the quick snapshot for the product line.',
                'points' => [
                    [
                        'text' => $productSummary,
                    ],
                    [
                        'text' => 'Vendor: ' . $brandName,
                    ],
                    [
                        'text' => 'Official product page is linked above for line-specific details.',
                    ],
                ],
            ],
            [
                'title' => 'Best Fit For',
                'summary' => 'Use these notes when deciding whether the line fits the project.',
                'points' => $fitBullets,
            ],
            [
                'title' => 'Related Lines',
                'summary' => 'Other lines we carry from the same vendor.',
                'points' => !empty($relatedProducts)
                    ? array_map(function (array $relatedProduct) use ($vendorProductPages): array {
                        $label = $relatedProduct['name'];
                        if (!empty($relatedProduct['summary'])) {
                            $label .= ' - ' . $relatedProduct['summary'];
                        }

                        $relatedPage = $vendorProductPages[$relatedProduct['name']] ?? null;
                        if ($relatedPage instanceof Page) {
                            return [
                                'html' => '<a href="' . e($relatedPage->getUrl()) . '" target="_self" rel="internal">' . e($label) . '</a>',
                            ];
                        }

                        return ['text' => $label];
                    }, array_slice($relatedProducts, 0, 8))
                    : [
                        ['text' => 'No additional related product lines are listed for this vendor yet.'],
                    ],
            ],
        ];

        $warrantyPoints = $this->buildLinkListPoints($this->buildVendorWarrantyLinks($brandName, $brandProfile));
        $warrantyPoints[] = ['text' => $this->buildWarrantyReminder($brandName)];
        $warrantyNote = $this->buildVendorWarrantyNote($brandName);
        if ($warrantyNote !== '') {
            $warrantyPoints[] = ['text' => $warrantyNote];
        }

        $sections[] = [
            'title' => 'Warranty Information',
            'summary' => 'Vendor warranty paths that apply before quoting coverage for this product line.',
            'points' => $warrantyPoints,
        ];

        $faqPoints = $this->buildLinkListPoints($this->buildVendorFaqLinks($brandName));
        if (empty($faqPoints)) {
            $faqPoints[] = ['text' => 'No public FAQ page is listed for this vendor yet.'];
        }

        $sections[] = [
            'title' => 'FAQs',
            'summary' => 'Vendor FAQ paths for common product, care, ordering, and warranty questions.',
            'points' => $faqPoints,
        ];

        $sourceBrandProfile = $this->sourceRegistry()['brands'][$brandName] ?? [];
        $vendorSource = $brandProfile['website'] ?? ($sourceBrandProfile['website'] ?? null);
        if (empty($vendorSource) && !empty($sourceBrandProfile['links'][0]['url'] ?? null)) {
            $vendorSource = $sourceBrandProfile['links'][0]['url'];
        }
        if (!empty($vendorSource)) {
            $leadContent .= <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Vendor Hub</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Reference the vendor hub when you need broader context for the product line.</p>
        </div>
    </div>
    <div class="sotx-actions" style="margin-top:.55rem;">
        <a href="{$vendorSource}" class="sotx-pill" target="_blank" rel="noreferrer">Vendor Website</a>
    </div>
</section>
HTML;
        }

        return $this->buildScaffoldPageHtml(
            $brandName,
            $productName,
            $summary,
            $sections,
            $leadContent
        );
    }

    protected function buildProductFitBullets(string $productName, string $summary, string $brandName): array
    {
        $haystack = strtolower($productName . ' ' . $summary . ' ' . $brandName);

        $bullets = [];

        if (str_contains($haystack, 'film')) {
            $bullets[] = 'Use for sun control, privacy, or safety depending on the series.';
            $bullets[] = 'Good choice when the project needs a film-based solution on existing glass.';
        }

        if (str_contains($haystack, 'shade') || str_contains($haystack, 'roman') || str_contains($haystack, 'woven')) {
            $bullets[] = 'Use for light control, privacy, and a softer interior finish.';
            $bullets[] = 'Good fit for rooms where the customer wants design plus function.';
        }

        if (str_contains($haystack, 'blind')) {
            $bullets[] = 'Use when the project needs straightforward light control with a harder treatment style.';
            $bullets[] = 'Good fit for everyday window coverage and easy quoting.';
        }

        if (str_contains($haystack, 'shutter')) {
            $bullets[] = 'Use when the customer wants a more architectural, fixed-window look.';
            $bullets[] = 'Good fit for moisture-prone or design-forward spaces depending on the line.';
        }

        if (str_contains($haystack, 'awning') || str_contains($haystack, 'screen') || str_contains($haystack, 'roof')) {
            $bullets[] = 'Use for exterior shade, patio comfort, or weather protection.';
            $bullets[] = 'Good fit for outdoor living and solar management projects.';
        }

        if (str_contains($haystack, 'glass') || str_contains($haystack, 'window') || str_contains($haystack, 'door')) {
            $bullets[] = 'Use when the project needs a replacement, fabrication, or new-build opening product.';
            $bullets[] = 'Good fit for residential or commercial openings depending on the line.';
        }

        if (str_contains($haystack, 'hardware') || str_contains($haystack, 'railing') || str_contains($haystack, 'shower')) {
            $bullets[] = 'Use for glass hardware, railing, or shower projects that need a component-based solution.';
            $bullets[] = 'Good fit when the installation needs supporting hardware and finishes.';
        }

        if (str_contains($haystack, 'smart') || str_contains($haystack, 'tint')) {
            $bullets[] = 'Use when the project needs switchable privacy or electronically controlled film or glass.';
            $bullets[] = 'Good fit for conference rooms, hospitality, and residential privacy upgrades.';
        }

        if (empty($bullets)) {
            $bullets[] = 'Use this line when it matches the job specification and the vendor summary above.';
            $bullets[] = 'Confirm the exact product family before quoting or ordering.';
        }

        $bullets[] = 'Compare this line against the other products listed below before choosing a final solution.';

        return array_values(array_unique($bullets));
    }

    protected function buildVendorWarrantyLinks(string $brandName, array $brandProfile = []): array
    {
        $sourceBrandProfile = $this->sourceRegistry()['brands'][$brandName] ?? [];
        if (!empty($sourceBrandProfile['warranty_links'] ?? [])) {
            return $this->uniqueLinksByUrl($sourceBrandProfile['warranty_links']);
        }

        $explicitLinks = [];
        foreach (($brandProfile['warranty_links'] ?? []) as $link) {
            $label = strtolower(trim((string) ($link['label'] ?? '')));
            $url = strtolower(trim((string) ($link['url'] ?? '')));

            if (!str_contains($label, 'warranty') && !str_contains($url, 'warranty')) {
                continue;
            }

            $explicitLinks[] = [
                'label' => 'Warranty Information',
                'url' => $link['url'],
            ];
        }

        return $this->uniqueLinksByUrl($explicitLinks);
    }

    protected function buildVendorWarrantyNote(string $brandName): string
    {
        return (string) ($this->sourceRegistry()['brands'][$brandName]['warranty_note'] ?? '');
    }

    protected function buildWarrantyReminder(string $brandName): string
    {
        if ($brandName === 'Dallas Flat Glass') {
            return 'Use project-specific or manufacturer-specific warranty language until direct warranty docs are provided.';
        }

        return 'Confirm the exact product line, install conditions, and purchase date before quoting coverage.';
    }

    protected function buildVendorFaqLinks(string $brandName): array
    {
        return $this->uniqueLinksByUrl($this->sourceRegistry()['brands'][$brandName]['faq_links'] ?? []);
    }

    protected function renderLinkPills(array $links, string $fallbackLabel): string
    {
        $html = '';
        foreach ($links as $link) {
            $linkUrl = $link['url'] ?? null;
            if (empty($linkUrl)) {
                continue;
            }

            $html .= '<a href="' . e($linkUrl) . '" class="sotx-pill" target="_blank" rel="noreferrer">' . e($link['label'] ?? $fallbackLabel) . '</a>';
        }

        return $html === '' ? '' : '<div class="sotx-actions" style="margin-top:.55rem;">' . $html . '</div>';
    }

    protected function buildLinkListPoints(array $links): array
    {
        $points = [];
        foreach ($links as $link) {
            $linkUrl = $link['url'] ?? null;
            if (empty($linkUrl)) {
                continue;
            }

            $label = $link['label'] ?? 'Link';
            $points[] = [
                'html' => '<a href="' . e($linkUrl) . '" target="_blank" rel="noreferrer">' . e($label) . '</a>',
            ];
        }

        return $points;
    }

    protected function uniqueLinksByUrl(array $links): array
    {
        $unique = [];
        foreach ($links as $link) {
            $url = $link['url'] ?? null;
            if (empty($url) || isset($unique[$url])) {
                continue;
            }

            $unique[$url] = $link;
        }

        return array_values($unique);
    }

    protected function buildSourceInventoryHtml(string $brandName): string
    {
        $brands = $this->parsedSourceMarkdownBrands();
        $sections = $brands[$brandName] ?? [];

        if (empty($sections)) {
            return '';
        }

        $sectionHtml = '';
        foreach ($sections as $sectionName => $items) {
            $sectionName = e($sectionName);
            $listItems = '';
            foreach ($items as $item) {
                $listItems .= '<li>' . $this->renderSourceMarkdownLine($item) . '</li>';
            }

            $sectionHtml .= <<<HTML
<section class="sotx-card sotx-mini-card sotx-template-block">
    <div class="sotx-card-top" style="align-items:center;">
        <div>
            <p class="sotx-kicker" style="margin-bottom:.35rem;">Verified Source</p>
            <h4>{$sectionName}</h4>
        </div>
        <span class="sotx-flag">Live</span>
    </div>
    <ul class="sotx-template-list">
        {$listItems}
    </ul>
</section>
HTML;
        }

        return <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Source Inventory</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Official vendor links and access notes used to keep this vendor page current.</p>
        </div>
    </div>
    <div class="sotx-service-grid">
        {$sectionHtml}
    </div>
</section>
HTML;
    }

    protected function parsedSourceMarkdownBrands(): array
    {
        $path = base_path('docs/source.md');
        if (!is_file($path)) {
            return [];
        }

        $brands = [];
        $currentBrand = null;
        $currentSection = null;

        foreach (preg_split('/\R/', file_get_contents($path) ?: '') as $line) {
            $trimmed = trim($line);

            if (preg_match('/^###\\s+(.+)$/', $trimmed, $matches)) {
                $currentBrand = $matches[1];
                $currentSection = null;
                continue;
            }

            if ($currentBrand === null) {
                continue;
            }

            if (preg_match('/^\\*\\*(.+)\\*\\*$/', $trimmed, $matches)) {
                $currentSection = $matches[1];
                $brands[$currentBrand][$currentSection] ??= [];
                continue;
            }

            if ($trimmed === '' || $trimmed === '---' || str_starts_with($trimmed, '## ')) {
                continue;
            }

            $sectionKey = $currentSection ?? 'Notes';
            if (str_starts_with($trimmed, '- ')) {
                $trimmed = substr($trimmed, 2);
            }

            $brands[$currentBrand][$sectionKey][] = $trimmed;
        }

        return $brands;
    }

    protected function renderSourceMarkdownLine(string $line): string
    {
        $pattern = '/\\[(.*?)\\]\\((https?:\\/\\/[^)]+)\\)/';
        $offset = 0;
        $html = '';

        while (preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $matchText = $matches[0][0];
            $matchStart = $matches[0][1];

            $html .= e(substr($line, $offset, $matchStart - $offset));
            $html .= '<a href="' . e($matches[2][0]) . '" target="_blank" rel="noreferrer">' . e($matches[1][0]) . '</a>';
            $offset = $matchStart + strlen($matchText);
        }

        $html .= e(substr($line, $offset));

        return $html;
    }

    protected function parsedProductMarkdownBrands(): array
    {
        $path = base_path('docs/products.md');
        if (!is_file($path)) {
            return [];
        }

        $brands = [];
        $currentBrand = null;

        foreach (preg_split('/\R/', file_get_contents($path) ?: '') as $line) {
            $trimmed = trim($line);

            if (preg_match('/^###\\s+(.+)$/', $trimmed, $matches)) {
                $currentBrand = $matches[1];
                $brands[$currentBrand] ??= [];
                continue;
            }

            if ($currentBrand === null || $trimmed === '' || str_starts_with($trimmed, '#') || $trimmed === '---') {
                continue;
            }

            if (!str_starts_with($trimmed, '- ')) {
                continue;
            }

            $item = substr($trimmed, 2);
            $name = $item;
            $url = null;
            $summary = '';

            if (preg_match('/^\\[(.*?)\\]\\((https?:\\/\\/[^)]+)\\)\\s*-\\s*(.+)$/', $item, $matches)) {
                $name = $matches[1];
                $url = $matches[2];
                $summary = $matches[3];
            } elseif (preg_match('/^\\[(.*?)\\]\\((https?:\\/\\/[^)]+)\\)$/', $item, $matches)) {
                $name = $matches[1];
                $url = $matches[2];
            } elseif (preg_match('/^(.+?)\\s*-\\s*(.+)$/', $item, $matches)) {
                $name = $matches[1];
                $summary = $matches[2];
            }

            $brands[$currentBrand][] = [
                'name' => trim($name),
                'url' => $url,
                'summary' => trim($summary),
            ];
        }

        return $brands;
    }

    protected function brandProfiles(): array
    {
        return [
            'Pella' => [
                'website' => 'https://www.pella.com/',
                'quick_links' => [
                    ['label' => 'Pella Home Page', 'url' => 'https://www.pella.com/'],
                    ['label' => 'Pella Windows', 'url' => 'https://www.pella.com/ideas/windows/'],
                    ['label' => 'Pella Warranties', 'url' => 'https://www.pella.com/support/warranties/'],
                    ['label' => 'Pella FAQ', 'url' => 'https://www.pella.com/support/faq/'],
                ],
                'warranty_links' => [
                    ['label' => 'Website', 'url' => 'https://www.pella.com/'],
                    ['label' => 'Warranty Information', 'url' => 'https://www.pella.com/support/warranties/'],
                ],
                'products' => [
                    [
                        'name' => 'Reserve',
                        'summary' => 'Wood and aluminum-clad wood windows and patio doors.',
                        'url' => 'https://www.pella.com/ideas/windows/reserve/',
                    ],
                    [
                        'name' => 'Lifestyle Series',
                        'summary' => 'Wood windows designed for everyday life.',
                        'url' => 'https://www.pella.com/ideas/windows/lifestyle-series/',
                    ],
                    [
                        'name' => 'Impervia',
                        'summary' => 'Fiberglass windows and patio doors built for strength and durability.',
                        'url' => 'https://www.pella.com/ideas/windows/pella-impervia/',
                    ],
                    [
                        'name' => '250 Series',
                        'summary' => 'Vinyl windows with enhanced security and privacy features.',
                        'url' => 'https://www.pella.com/ideas/windows/250-series/',
                    ],
                    [
                        'name' => 'Encompass',
                        'summary' => 'Budget-friendly vinyl windows backed by Pella.',
                        'url' => 'https://www.pella.com/ideas/windows/encompass/',
                    ],
                    [
                        'name' => 'Hurricane Shield Series',
                        'summary' => 'Impact-resistant vinyl windows for coastal and storm-prone markets.',
                        'url' => 'https://www.pella.com/ideas/windows/hurricaneshield/',
                    ],
                    [
                        'name' => 'Defender Series',
                        'summary' => 'Impact-resistant vinyl windows with storm protection.',
                        'url' => 'https://www.pella.com/ideas/windows/defender-series/',
                    ],
                ],
                'sources' => [
                    'Pella Professionals' => 'https://www.pella.com/professionals/windows/',
                ],
            ],
            'Andersen' => [
                'website' => 'https://www.andersenwindows.com/',
                'quick_links' => [
                    ['label' => 'Andersen Website', 'url' => 'https://www.andersenwindows.com/'],
                    ['label' => 'Andersen Support', 'url' => 'https://www.andersenwindows.com/support/'],
                    ['label' => 'Andersen Warranty', 'url' => 'https://www.andersenwindows.com/support/warranty'],
                    ['label' => 'Andersen FAQs', 'url' => 'https://www.andersenwindows.com/support/faqs'],
                    ['label' => 'Andersen Quality', 'url' => 'https://www.andersenwindows.com/about/quality'],
                ],
                'warranty_links' => [
                    ['label' => 'Website', 'url' => 'https://www.andersenwindows.com/'],
                    ['label' => 'Warranty Information', 'url' => 'https://www.andersenwindows.com/support/warranty'],
                ],
                'products' => [
                    [
                        'name' => 'E-Series',
                        'summary' => 'Ultimate flexibility and design freedom for windows and patio doors.',
                        'url' => 'https://www.andersenwindows.com/windows-and-doors/series/e-series',
                    ],
                    [
                        'name' => 'A-Series',
                        'summary' => 'Architectural collection with strong performance and energy efficiency.',
                        'url' => 'https://www.andersenwindows.com/windows-and-doors/series/a-series/',
                    ],
                    [
                        'name' => '400 Series',
                        'summary' => 'Clad wood windows and doors with broad everyday appeal.',
                        'url' => 'https://www.andersenwindows.com/windows-and-doors/series/400-series/',
                    ],
                    [
                        'name' => '200 Series',
                        'summary' => 'Balanced innovation, design, efficiency, and price.',
                        'url' => 'https://www.andersenwindows.com/windows-and-doors/series/200-series/',
                    ],
                    [
                        'name' => '100 Series',
                        'summary' => 'Fibrex composite windows and patio doors at an accessible price.',
                        'url' => 'https://www.andersenwindows.com/windows-and-doors/series/100-series',
                    ],
                    [
                        'name' => 'Andersen Aluminum',
                        'summary' => 'Contemporary all-aluminum windows and doors for the Southwest.',
                        'url' => 'https://www.andersenwindows.com/windows-and-doors/series/andersen-aluminum/',
                    ],
                ],
                'sources' => [],
            ],
            'JELD-WEN' => [
                'website' => 'https://www.jeld-wen.com/en-us/',
                'quick_links' => [
                    ['label' => 'Website', 'url' => 'https://www.jeld-wen.com/en-us/'],
                    ['label' => 'Dealer Locator', 'url' => 'https://locations.jeld-wen.com/'],
                    ['label' => 'JELD-WEN Warranty Guide', 'url' => 'https://www.jeld-wen.com/en-us/all-warranty-guide'],
                    ['label' => 'JELD-WEN Documents', 'url' => 'https://www.jeld-wen.com/en-us/documents'],
                ],
                'warranty_links' => [
                    ['label' => 'Website', 'url' => 'https://www.jeld-wen.com/en-us/'],
                    ['label' => 'Warranty Information', 'url' => 'https://www.jeld-wen.com/en-us/all-warranty-guide'],
                ],
                'products' => [
                    [
                        'name' => 'Windows',
                        'summary' => 'Windows and related building products across wood, vinyl, and composite materials.',
                        'url' => 'https://www.corporate.jeld-wen.com/brands-and-products',
                    ],
                    [
                        'name' => 'Exterior Doors',
                        'summary' => 'Exterior door options including fiberglass and steel styles.',
                        'url' => 'https://www.jeld-wen.com/en-us/products/exterior-doors/steel/8ft-full-view-18-light',
                    ],
                    [
                        'name' => 'Interior Doors',
                        'summary' => 'Interior door options and composite construction choices.',
                        'url' => 'https://www.jeld-wen.com/en-us/products/interior-doors/tria-composite/l1000-all-panel',
                    ],
                    [
                        'name' => 'Patio Doors',
                        'summary' => 'French and patio doors for indoor-outdoor living.',
                        'url' => 'https://brandstore.jeld-wen.com/store/20200807975/assets/pdfs/brochuresPDF/17-96725%20Patio%20Door%20071318.pdf',
                    ],
                    [
                        'name' => 'Auraline True Composite',
                        'summary' => 'Composite windows and patio doors for a wood-like look with durable maintenance-friendly performance.',
                        'url' => 'https://www.corporate.jeld-wen.com/newsroom/press-releases/2022/06-21-2022-145756008',
                    ],
                ],
                'sources' => [
                    'JELD-WEN Brands' => 'https://www.corporate.jeld-wen.com/brands-and-products',
                    'JELD-WEN Markets' => 'https://www.corporate.jeld-wen.com/about-us/our-markets-and-customers',
                ],
            ],
            'Dallas Flat Glass' => [
                'website' => 'https://dallasflatglass.com/',
                'quick_links' => [
                    ['label' => 'Dallas Flat Glass', 'url' => 'https://dallasflatglass.com/'],
                ],
                'warranty_links' => [
                    ['label' => 'Website', 'url' => 'https://dallasflatglass.com/'],
                ],
                'products' => [
                    [
                        'name' => 'Insulated Glass',
                        'summary' => 'Core glass wholesale and replacement offering.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Mirrors',
                        'summary' => 'Custom mirrors and mirrored glass products.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Pattern Glass',
                        'summary' => 'Decorative and patterned glass options.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Custom Beveled Glass',
                        'summary' => 'Specialty glass and decorative fabrication work.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Specialty Glass',
                        'summary' => 'Specialty and custom glass fabrication.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Low-E Glass',
                        'summary' => 'Energy-focused glass options for windows and replacement units.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Laminated Glass',
                        'summary' => 'Safety and performance glass for residential and commercial projects.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Fully Tempered Glass',
                        'summary' => 'Tempered glass for safety and durable installations.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Heavy Glass',
                        'summary' => 'Heavy glass and large-format applications.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'Monolithic Glass',
                        'summary' => 'Single-lite glass products and fabrication work.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                    [
                        'name' => 'All Glass Entrance Systems',
                        'summary' => 'Entrance systems and storefront-related glass products.',
                        'url' => 'https://dallasflatglass.com/',
                    ],
                ],
                'sources' => [
                    'LinkedIn Company Profile' => 'https://www.linkedin.com/company/dallas-flat-glass-distributors',
                    'MapQuest Profile' => 'https://www.mapquest.com/us/texas/dallas-flat-glass-distributors-304386954',
                ],
            ],
        ];
    }

    protected function buildScaffoldPageHtml(string $eyebrow, string $title, string $summary, array $sections, string $leadContent = ''): string
    {
        $eyebrow = e($eyebrow);
        $title = e($title);
        $summary = e($summary);
        $sectionHtml = $this->renderScaffoldSections($sections);

        return <<<HTML
<div class="sotx-home">
    <section class="sotx-panel sotx-card sotx-section-card">
        <p class="sotx-kicker">{$eyebrow}</p>
        <h1>{$title}</h1>
        <p class="sotx-lede">{$summary}</p>
    </section>
    {$leadContent}
    {$sectionHtml}
</div>
HTML;
    }

    protected function renderScaffoldSections(array $sections): string
    {
        if (empty($sections)) {
            return '';
        }

        $sectionHtml = '';
        foreach ($sections as $section) {
            $sectionTitle = e($section['title']);
            $sectionSummary = e($section['summary']);
            $points = '';
            foreach ($section['points'] as $point) {
                if (is_array($point) && array_key_exists('html', $point)) {
                    $points .= '<li>' . $point['html'] . '</li>';
                    continue;
                }

                $text = is_array($point) ? ($point['text'] ?? '') : $point;
                $points .= '<li>' . e($text) . '</li>';
            }

            $sectionHtml .= <<<HTML
<section class="sotx-card sotx-mini-card sotx-template-block">
    <div class="sotx-card-top" style="align-items:center;">
        <div>
            <h4>{$sectionTitle}</h4>
        </div>
    </div>
    <p class="sotx-note" style="margin-top:.65rem;">{$sectionSummary}</p>
    <ul class="sotx-template-list">
        {$points}
    </ul>
</section>
HTML;
        }

        return <<<HTML
<section class="sotx-section">
    <div class="sotx-service-grid">
        {$sectionHtml}
    </div>
</section>
HTML;
    }

    protected function buildProductChipsHtml(array $productNames, array $productPages): string
    {
        if (empty($productNames)) {
            return '';
        }

        $items = collect($productNames)->map(function (string $productName) use ($productPages): string {
            $page = $productPages[$productName] ?? null;

            if ($page) {
                return '<a href="' . e($page->getUrl()) . '" class="sotx-chip sotx-chip-link">' . e($productName) . '</a>';
            }

            return '<span class="sotx-chip">' . e($productName) . '</span>';
        })->implode('');

        return '<div class="sotx-chip-list">' . $items . '</div>';
    }

    protected function grantTeamReadAccess(): void
    {
        $viewPermissions = [
            Permission::BookshelfViewAll->value,
            Permission::BookViewAll->value,
            Permission::ChapterViewAll->value,
            Permission::PageViewAll->value,
        ];

        $permissionIds = RolePermission::query()
            ->whereIn('name', $viewPermissions)
            ->pluck('id')
            ->all();

        if (empty($permissionIds)) {
            return;
        }

        Role::query()
            ->where(function ($query) {
                $query->whereNull('system_name')
                    ->orWhereNotIn('system_name', ['admin', 'public']);
            })
            ->chunkById(50, function ($roles) use ($permissionIds) {
                foreach ($roles as $role) {
                    $role->permissions()->syncWithoutDetaching($permissionIds);
                }
            });

        app(JointPermissionBuilder::class)->rebuildForAll();
    }

    protected function buildHomepageHtml(array $books, array $chaptersByBook, array $pagesByBook): string
    {
        $homepageSections = [
            [
                'title' => 'Residential',
                'summary' => 'Residential window treatments, tinting, awnings and home services.',
                'book' => $books['Residential'],
                'chapters' => [
                    ['name' => 'Tint & Film', 'pages' => ['Safety & Security Film', 'Solar Film', 'Privacy Film']],
                    ['name' => 'Window Treatments', 'pages' => ['Window Shades', 'Window Shutters', 'Window Blinds', 'Safety / Storm Shutters']],
                    ['name' => 'Outdoor Living', 'pages' => ['Shade Structures', 'Patio Awnings', 'Patio Screens']],
                    ['name' => 'Glass & Windows', 'pages' => ['Window Glass', 'Frameless Showers', 'Window Cleaning']],
                ],
            ],
            [
                'title' => 'Commercial',
                'summary' => 'Commercial storefront glazing, commercial shades, film and protective solutions.',
                'book' => $books['Commercial'],
                'chapters' => [
                    ['name' => 'Solar Control & Safety', 'pages' => ['Sun Control Film', 'Safety & Security Film', 'Privacy Film', 'SmartTint']],
                    ['name' => 'Patio Screens & Awnings', 'pages' => ['Patio Awnings', 'Patio Screens']],
                    ['name' => 'Glass & Windows', 'pages' => ['Commercial Glazing']],
                    ['name' => 'Window Treatments', 'pages' => ['Roller Shades']],
                ],
            ],
        ];

        $serviceCards = '';
        foreach ($homepageSections as $service) {
            $chips = '';
            foreach ($service['chapters'] as $chapter) {
                $items = '';
                $chapterModel = $chaptersByBook[$service['title']][$chapter['name']] ?? null;
                if (!$chapterModel) {
                    continue;
                }

                foreach ($chapter['pages'] as $pageName) {
                    $page = $pagesByBook[$service['title']][$chapter['name']][$pageName] ?? null;
                    if (!$page) {
                        continue;
                    }

                    $items .= '<span class="sotx-chip">' . e($pageName) . '</span>';
                }

                $chapterUrl = $chapterModel->getUrl();

                $chips .= <<<HTML
<a href="{$chapterUrl}" class="sotx-card sotx-service-card">
    <div class="sotx-card-top">
        <div>
            <h4>{$chapter['name']}</h4>
        </div>
        <span class="sotx-flag">Open</span>
    </div>
    <div class="sotx-chip-list">{$items}</div>
</a>
HTML;
            }

        $serviceCards .= <<<HTML
<div class="sotx-panel sotx-section-card">
    <div class="sotx-card-top">
        <div style="max-width:38rem;">
            <p class="sotx-kicker">Service Area</p>
            <h3>{$service['title']}</h3>
            <p class="sotx-note">{$service['summary']}</p>
        </div>
        <a href="{$service['book']->getUrl()}" class="sotx-pill">Open {$service['title']}</a>
    </div>
    <div class="sotx-service-grid" style="margin-top:1rem;">{$chips}</div>
</div>
HTML;
        }

        $quickLinks = '';
        foreach ([
            ['book' => $books['Start Here'], 'label' => 'Start Here'],
            ['book' => $books['Vendors'], 'label' => 'Vendors'],
        ] as $link) {
            $quickLinks .= '<a href="' . e($link['book']->getUrl()) . '" class="sotx-pill">' . e($link['label']) . '</a>';
        }

        $startHereBook = $books['Start Here'];
        $vendorsBook = $books['Vendors'];

        $supportCards = '';
        foreach ([
            ['book' => $startHereBook, 'title' => 'Start Here', 'description' => 'Fast onboarding for new reps and a quick map of the hub.'],
            ['book' => $vendorsBook, 'title' => 'Vendors', 'description' => 'Brand-level vendor pages and what we carry.'],
        ] as $supportCard) {
            $supportCards .= <<<HTML
<a href="{$supportCard['book']->getUrl()}" class="sotx-card sotx-mini-card">
    <h4>{$supportCard['title']}</h4>
    <p>{$supportCard['description']}</p>
</a>
HTML;
        }

        return <<<HTML
<div class="sotx-home">
    <section class="sotx-panel sotx-hero">
        <div class="sotx-hero-copy">
            <div>
                <p class="sotx-kicker">High Level</p>
                <h1>Find the right answer fast.</h1>
                <p class="sotx-lede" style="max-width:38rem;">
                    Sales-ready access to the resources our team needs most: product lines, vendor context, and field notes.
                    Organized to match the Shades of Texas brand and the way our teams actually sell.
                </p>
            </div>
            <div class="sotx-actions">
                {$quickLinks}
            </div>
        </div>
        <div class="sotx-stats">
            <div class="sotx-metric">
                <strong>35+ Years</strong>
                <span>Serving Texas</span>
            </div>
            <div class="sotx-metric">
                <strong>Licensed</strong>
                <span>&amp; Insured</span>
            </div>
            <div class="sotx-metric">
                <strong>Vendors</strong>
                <span>Brands &amp; specs</span>
            </div>
            <div class="sotx-metric">
                <strong>Fast</strong>
                <span>Sales navigation</span>
            </div>
        </div>
    </section>

    <section class="sotx-section">
        <div class="sotx-section-head">
            <div>
                <h2>Service Areas</h2>
                <p class="sotx-note" style="margin:.35rem 0 0;">Choose the project type first, then jump into the category.</p>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:1rem;">
            {$serviceCards}
        </div>
    </section>

    <section class="sotx-section">
        <div class="sotx-resource-grid">
            {$supportCards}
        </div>
    </section>

    <section class="sotx-section sotx-panel sotx-guide">
        <h2>How to Use the Hub</h2>
        <ol>
            <li>Pick the service area first.</li>
            <li>Jump into the category that matches the customer’s need.</li>
            <li>Open the product page for the exact brand or line we carry.</li>
            <li>Use the vendor page and source map when a deal needs more detail.</li>
        </ol>
    </section>
</div>
HTML;
    }

    protected function bookConfigs(): array
    {
        return [
            'Start Here' => [
                'description' => 'Entry point for the sales team. Start here when you need to find the right resource quickly.',
                'pages' => [
                    ['name' => 'Source of Truth', 'summary' => 'Master source map for brands, services, and reference documents.'],
                    ['name' => 'How to Use This Hub', 'summary' => 'Quick guide to navigating the knowledge base.'],
                    ['name' => 'Where to Find Product Info and Pricing', 'summary' => 'Explains where product details and pricing references live.'],
                    ['name' => 'How to Request Missing Documents', 'summary' => 'How to request a file or ask for a new reference page.'],
                ],
            ],
            'Residential' => [
                'description' => 'Residential sales resources organized by the categories the team actually sells every day.',
                'chapters' => [
                    'Tint & Film' => [
                        'description' => 'Residential tint and film solutions.',
                        'pages' => [
                            ['name' => 'Safety & Security Film', 'summary' => 'What residential safety film solves and how to position it.', 'details' => 'Use the four sections below to keep the sales rep on track for this film type.', 'products' => ['3M']],
                            ['name' => 'Solar Film', 'summary' => 'Energy and glare control film for homes.', 'details' => 'Use the four sections below to compare solar-control film options.', 'products' => ['3M', 'SmartTint']],
                            ['name' => 'Privacy Film', 'summary' => 'Film options that improve privacy without changing the room.', 'details' => 'Use the four sections below for privacy film positioning and references.', 'products' => ['3M', 'SmartTint']],
                        ],
                    ],
                    'Window Treatments' => [
                        'description' => 'Shades, blinds, shutters, and motorized treatment options.',
                        'pages' => [
                            ['name' => 'Window Shades', 'summary' => 'Shades that solve light control and privacy needs.', 'details' => 'Use the four sections below for residential shade solutions.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                            ['name' => 'Window Shutters', 'summary' => 'Shutter materials, finishes, and use cases.', 'details' => 'Use the four sections below for shutter options and comparisons.', 'products' => ['Hunter Douglas', 'Norman']],
                            ['name' => 'Window Blinds', 'summary' => 'Blind options for homeowners and designers.', 'details' => 'Use the four sections below for blind products and selling points.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                            ['name' => 'Safety / Storm Shutters', 'summary' => 'Protective shutter options for the home.', 'details' => 'Use the four sections below for storm and safety shutter references.', 'products' => ['Norman']],
                        ],
                    ],
                    'Outdoor Living' => [
                        'description' => 'Awnings, shade structures, and exterior coverage solutions.',
                        'pages' => [
                            ['name' => 'Shade Structures', 'summary' => 'What residential shade structures solve and how to position them.', 'details' => 'Use the four sections below for outdoor shade structures.', 'products' => ['Eclipse']],
                            ['name' => 'Patio Awnings', 'summary' => 'Retractable and fixed awning options.', 'details' => 'Use the four sections below for awning options and sales notes.', 'products' => ['Eclipse']],
                            ['name' => 'Patio Screens', 'summary' => 'Screens and exterior comfort options.', 'details' => 'Use the four sections below for patio screen solutions.', 'products' => ['Eclipse']],
                        ],
                    ],
                    'Glass & Windows' => [
                        'description' => 'Glass replacement, window replacement, and related service references.',
                        'pages' => [
                            ['name' => 'Window Glass', 'summary' => 'Glass replacement references and service notes.', 'details' => 'Use the four sections below for glass replacement projects.', 'products' => ['Dallas Flat Glass', 'Ghost Glass']],
                            ['name' => 'Frameless Showers', 'summary' => 'Shower enclosure references.', 'details' => 'Use the four sections below for frameless shower projects.', 'products' => ['CRL']],
                            ['name' => 'Window Cleaning', 'summary' => 'Care and maintenance notes for finished work.', 'details' => 'Use the four sections below for cleaning and maintenance references.'],
                        ],
                    ],
                ],
            ],
            'Commercial' => [
                'description' => 'Commercial sales resources organized by the categories the team actually sells every day.',
                'chapters' => [
                    'Solar Control & Safety' => [
                        'description' => 'Commercial tint and protective film solutions.',
                        'pages' => [
                            ['name' => 'Sun Control Film', 'summary' => 'Energy and glare control for commercial properties.', 'details' => 'Use the four sections below for commercial sun-control film.', 'products' => ['3M']],
                            ['name' => 'Safety & Security Film', 'summary' => 'Protective film for businesses and storefronts.', 'details' => 'Use the four sections below for commercial safety film.', 'products' => ['3M']],
                            ['name' => 'Privacy Film', 'summary' => 'Privacy and glare management for commercial spaces.', 'details' => 'Use the four sections below for commercial privacy film.', 'products' => ['3M', 'SmartTint']],
                            ['name' => 'SmartTint', 'summary' => 'Switchable privacy glass and film references.', 'details' => 'Use the four sections below for switchable privacy solutions.', 'products' => ['SmartTint']],
                        ],
                    ],
                    'Patio Screens & Awnings' => [
                        'description' => 'Commercial exterior shade and protection systems.',
                        'pages' => [
                            ['name' => 'Patio Awnings', 'summary' => 'Commercial awning and shade references.', 'details' => 'Use the four sections below for commercial awning work.', 'products' => ['Eclipse']],
                            ['name' => 'Patio Screens', 'summary' => 'Screen and exterior comfort solutions.', 'details' => 'Use the four sections below for commercial patio screen work.', 'products' => ['Eclipse']],
                        ],
                    ],
                    'Glass & Windows' => [
                        'description' => 'Commercial glass replacement, glazing, and storefront references.',
                        'pages' => [
                            ['name' => 'Commercial Glazing', 'summary' => 'Storefront and glazing references.', 'details' => 'Use the four sections below for commercial glazing projects.', 'products' => ['Dallas Flat Glass', 'Ghost Glass', 'JELD-WEN', 'CRL']],
                        ],
                    ],
                    'Window Treatments' => [
                        'description' => 'Commercial shades and motorized systems.',
                        'pages' => [
                            ['name' => 'Roller Shades', 'summary' => 'Commercial shade systems and project references.', 'details' => 'Use the four sections below for commercial roller shades.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                        ],
                    ],
                ],
            ],
            'Vendors' => [
                'description' => 'Manufacturer and product-line reference pages for the products Shades of Texas carries.',
                'chapters' => [
                    '3M' => ['description' => '3M product reference and sales support.'],
                    'Hunter Douglas' => ['description' => 'Hunter Douglas product reference and sales support.'],
                    'Alta' => ['description' => 'Alta product reference and sales support.'],
                    'Norman' => ['description' => 'Norman product reference and sales support.'],
                    'Eclipse' => ['description' => 'Eclipse product reference and sales support.'],
                    'SmartTint' => ['description' => 'SmartTint product reference and sales support.'],
                    'Andersen' => ['description' => 'Andersen windows and doors product reference and sales support.'],
                    'Pella' => ['description' => 'Pella windows and doors product reference and sales support.'],
                    'CRL' => ['description' => 'CRL product reference and sales support.'],
                    'Dallas Flat Glass' => ['description' => 'Dallas Flat Glass product reference and sales support.'],
                    'Ghost Glass' => ['description' => 'Ghost Glass product reference and sales support.'],
                    'JELD-WEN' => ['description' => 'JELD-WEN product reference and sales support.'],
                ],
            ],
        ];
    }
}
