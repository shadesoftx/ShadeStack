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
        $resourcePages = [];
        $resourcePageCounters = [];
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
                $this->buildStartHerePageHtml($pageName, $pageConfig['summary']),
                $startHerePageCounters
            );
        }

        $productPageCounters = 0;
        $brandProfiles = $this->brandProfiles();
        foreach (($this->bookConfigs()['Products']['chapters'] ?? []) as $brandName => $brandConfig) {
            $createdProductPages[$brandName] = $this->createPage(
                $createdBooks['Products'],
                null,
                $brandName,
                $brandConfig['description'],
                $byData,
                $this->buildBrandPageHtml($brandName, $brandName, $brandConfig['description'], $brandProfiles[$brandName] ?? []),
                ++$productPageCounters
            );
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
            foreach ($serviceConfig['chapters'] as $chapterName => $chapterConfig) {
                foreach ($chapterConfig['pages'] as $pageConfig) {
                    $pageName = $pageConfig['name'];
                    foreach ($this->resourceBooks() as $resourceBookName => $resourceBookConfig) {
                        $resourcePageCounters[$resourceBookName] = ($resourcePageCounters[$resourceBookName] ?? 0) + 1;
                        $resourcePages[$resourceBookName][$serviceName][$chapterName][$pageName] = $this->createPage(
                            $createdBooks[$resourceBookName],
                            null,
                            $this->resourcePageTitle($chapterName, $pageName),
                            $pageConfig['summary'],
                            $byData,
                            $this->buildResourcePageHtml(
                                $resourceBookName,
                                $serviceName,
                                $chapterName,
                                $pageName,
                                $pageConfig['summary'],
                                $pageConfig['products'] ?? [],
                                $createdProductPages
                            ),
                            $resourcePageCounters[$resourceBookName]
                        );
                    }
                }
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
                        [
                            'Products' => $resourcePages['Products'][$serviceName][$chapterName][$pageName],
                            'Specs & Drawings' => $resourcePages['Specs & Drawings'][$serviceName][$chapterName][$pageName],
                            'Warranty / Compliance' => $resourcePages['Warranty / Compliance'][$serviceName][$chapterName][$pageName],
                            'Reference / FAQs' => $resourcePages['Reference / FAQs'][$serviceName][$chapterName][$pageName],
                        ]
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
        <div class="sotx-note" style="margin-top:.8rem;">Populate this page with the approved content for this topic.</div>
    </section>
</div>
HTML;
    }

    protected function buildStartHerePageHtml(string $pageName, string $summary): string
    {
        if ($pageName === 'Source of Truth') {
            return $this->buildSourcePageHtml($summary);
        }

        return $this->buildScaffoldPageHtml(
            'Start Here',
            $pageName,
            $summary,
            [
                [
                    'title' => 'How This Page Should Work',
                    'summary' => 'Describe the purpose of this page and what the rep should do with it.',
                    'points' => [
                        'Add a plain-English explanation of the page topic.',
                        'Call out the team member or workflow owner.',
                        'Link to the next step in the sales process.',
                    ],
                ],
                [
                    'title' => 'Populate This Next',
                    'summary' => 'Use this space to finish the first draft quickly.',
                    'points' => [
                        'Add links to the source documents or source systems.',
                        'Add any required login or access notes.',
                        'Add examples, screenshots, or project references.',
                    ],
                ],
            ]
        );
    }

    protected function buildSourcePageHtml(string $summary): string
    {
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
                    'title' => 'How to Use This Page',
                    'summary' => 'Treat this as the master source map before changing any brand, service, or reference page.',
                    'points' => [
                        'Update this page first whenever a source changes.',
                        'Use the listed links to verify product, warranty, and support details.',
                        'Then update the downstream BookStack docs and the repo markdown docs together.',
                    ],
                ],
                [
                    'title' => 'Brand Sources',
                    'summary' => 'Official vendor sites and support pages used to write the brand pages.',
                    'points' => $brandPoints,
                ],
                [
                    'title' => 'Service Source Map',
                    'summary' => 'Which brands inform each service family and category set.',
                    'points' => $servicePoints,
                ],
                [
                    'title' => 'Update Order',
                    'summary' => 'Keep every documentation update in the same order so the hub stays consistent.',
                    'points' => [
                        'Start with source.md in the repo.',
                        'Update the BookStack Source of Truth page.',
                        'Update brand pages in Products.',
                        'Update service pages in Residential and Commercial.',
                        'Finish with readme.md and navigation.md.',
                    ],
                ],
            ]
        );
    }

    protected function sourceRegistry(): array
    {
        return [
            'brands' => [
                '3M' => [
                    'summary' => 'Window film and warranty sources.',
                    'links' => [
                        ['label' => 'Building Window Solutions', 'url' => 'https://www.3m.com/3M/en_US/building-window-solutions-us/'],
                        ['label' => 'Window Films', 'url' => 'https://www.3m.com/3M/en_US/graphics-signage-us/applications/windows-and-glass/window-films/'],
                        ['label' => 'Warranties', 'url' => 'https://www.3m.com/3M/en_US/post-factory-installation-us/resources/warranties/'],
                    ],
                ],
                'Hunter Douglas' => [
                    'summary' => 'Residential and commercial window-treatment support sources.',
                    'links' => [
                        ['label' => 'Window Treatments', 'url' => 'https://www.hunterdouglas.com/window-treatments'],
                        ['label' => 'Support Center', 'url' => 'https://help.hunterdouglas.com/hc/en-us'],
                        ['label' => 'Warranty FAQs', 'url' => 'https://help.hunterdouglas.com/hc/en-us/sections/39307695386772-Warranty-FAQs'],
                        ['label' => 'Installation', 'url' => 'https://www.hunterdouglas.com/installation?Parts='],
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
                ],
                'Norman' => [
                    'summary' => 'Norman product and warranty references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://normanusa.com/'],
                        ['label' => 'Window Treatments', 'url' => 'https://normanusa.com/window-treatments/'],
                        ['label' => 'Warranties', 'url' => 'https://normanusa.com/warranties/'],
                    ],
                ],
                'Eclipse' => [
                    'summary' => 'Eclipse awning and shading references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.eclipseawning.com/'],
                        ['label' => 'Brochure', 'url' => 'https://www.eclipseawning.com/wp-content/uploads/Eclipse-Brochure-v.2.21-for-email.pdf'],
                        ['label' => 'Custom Brands Group', 'url' => 'https://www.custombrandsgroup.com/'],
                    ],
                ],
                'SmartTint' => [
                    'summary' => 'SmartTint product, application, and technical references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.smarttint.com/'],
                        ['label' => 'Applications', 'url' => 'https://www.smarttint.com/applications/'],
                        ['label' => 'Why Us', 'url' => 'https://www.smarttint.com/whyus/'],
                        ['label' => 'Technical Data Sheet', 'url' => 'https://www.smarttint.com/wp-content/uploads/2025/03/SmartTint-SmartCling-Technical-Data-Sheet-10th-Gen-v3-1.pdf'],
                    ],
                ],
                'Andersen' => [
                    'summary' => 'Andersen support, warranty, and product identification sources.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.andersenwindows.com/'],
                        ['label' => 'Support', 'url' => 'https://www.andersenwindows.com/support/'],
                        ['label' => 'Warranty', 'url' => 'https://www.andersenwindows.com/support/warranty'],
                        ['label' => 'Help Center', 'url' => 'https://helpcenter.andersenwindows.com/aw/articles/Knowledge/Andersen-Limited-Warranties'],
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
                ],
                'CRL' => [
                    'summary' => 'CRL architectural hardware and glazing sources.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.crlaurence.com/'],
                        ['label' => 'About Us', 'url' => 'https://www.crlaurence.com/about-us'],
                        ['label' => 'Automotive Windows Supplies', 'url' => 'https://www.crlaurence.com/productcategory/AutomotiveWindowsSupplies'],
                    ],
                ],
                'Dallas Flat Glass' => [
                    'summary' => 'Dallas Flat Glass wholesale and fabrication source.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://dallasflatglass.com/'],
                        ['label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/company/dallas-flat-glass-distributors'],
                        ['label' => 'MapQuest Profile', 'url' => 'https://www.mapquest.com/us/texas/dallas-flat-glass-distributors-304386954'],
                    ],
                ],
                'Ghost Glass' => [
                    'summary' => 'Ghost Glass smart film source and installation references.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://ghostglassfilm.com/'],
                        ['label' => 'About Us', 'url' => 'https://ghostglassfilm.com/about-us'],
                        ['label' => 'Installations', 'url' => 'https://ghostglassfilm.com/installations'],
                    ],
                ],
                'Jeld-Wen' => [
                    'summary' => 'JELD-WEN product, warranty, and document library sources.',
                    'links' => [
                        ['label' => 'Home', 'url' => 'https://www.jeld-wen.com/en-us/'],
                        ['label' => 'Brands and Products', 'url' => 'https://www.corporate.jeld-wen.com/brands-and-products'],
                        ['label' => 'Warranty Guide', 'url' => 'https://www.jeld-wen.com/en-us/all-warranty-guide'],
                        ['label' => 'Documents', 'url' => 'https://www.jeld-wen.com/en-us/documents'],
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
                    'summary' => 'Pella, Dallas Flat Glass, Andersen, Jeld-Wen, CRL, and Ghost Glass inform the glass pages.',
                    'sources' => ['Pella', 'Dallas Flat Glass', 'Andersen', 'Jeld-Wen', 'CRL', 'Ghost Glass'],
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
                    'summary' => 'Pella, Dallas Flat Glass, Andersen, Jeld-Wen, and CRL drive the commercial glazing page.',
                    'sources' => ['Pella', 'Dallas Flat Glass', 'Andersen', 'Jeld-Wen', 'CRL'],
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
                    ['name' => 'Where to Find Specs, Drawings, and Pricing', 'summary' => 'Explains where the technical documents and pricing references live.'],
                    ['name' => 'How to Request Missing Documents', 'summary' => 'How to request a file or ask for a new reference page.'],
                ],
            ],
            'Residential' => [
                'description' => 'Residential sales resources organized by the categories the team actually sells every day.',
            ],
            'Commercial' => [
                'description' => 'Commercial sales resources organized by the categories the team actually sells every day.',
            ],
            'Products' => [
                'description' => 'All product and category references in one place, grouped so reps can find the right line fast.',
            ],
            'Specs & Drawings' => [
                'description' => 'All technical docs, cut sheets, and architect files in one place.',
            ],
            'Warranty / Compliance' => [
                'description' => 'All warranty notes, code concerns, and exceptions in one place.',
            ],
            'Reference / FAQs' => [
                'description' => 'All objections, measurements, and comparison notes in one place.',
            ],
        ];
    }

    protected function resourceBooks(): array
    {
        return [
            'Products' => ['description' => 'All products and categories in one place for fast reference.'],
            'Specs & Drawings' => ['description' => 'All spec sheets, drawings, and technical files in one place.'],
            'Warranty / Compliance' => ['description' => 'All warranty and compliance references in one place.'],
            'Reference / FAQs' => ['description' => 'All FAQs, comparison notes, and sales references in one place.'],
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
                            ['name' => 'Window Glass', 'summary' => 'Glass replacement references and service notes.', 'products' => ['Pella', 'Dallas Flat Glass', 'Andersen', 'Jeld-Wen']],
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
                            ['name' => 'Commercial Glazing', 'summary' => 'Storefront and glazing references.', 'products' => ['Pella', 'Dallas Flat Glass', 'Andersen', 'Jeld-Wen', 'CRL']],
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

    protected function resourcePageTitle(string $chapterName, string $categoryName): string
    {
        return $chapterName . ' - ' . $categoryName;
    }

    protected function buildServiceCategoryHtml(string $serviceName, string $chapterName, string $categoryName, string $summary, array $productNames, array $productPages, array $resourcePages): string
    {
        $resourceLinks = '';
        foreach ($resourcePages as $label => $page) {
            $resourceLinks .= '<a href="' . e($page->getUrl()) . '" class="sotx-pill">' . e($label) . '</a>';
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

    protected function buildResourcePageHtml(string $resourceBookName, string $serviceName, string $chapterName, string $categoryName, string $summary, array $productNames, array $productPages): string
    {
        $sections = match ($resourceBookName) {
            'Products' => [
                [
                    'title' => 'Product Overview',
                    'summary' => 'Describe the product direction and where this category fits.',
                    'points' => [
                        'List the product lines or brands that belong here.',
                        'Add the quick sales story for this category.',
                        'Keep this section short and easy to update.',
                    ],
                ],
                [
                    'title' => 'What We Carry',
                    'summary' => 'Capture the lines, collections, or brands we sell.',
                    'points' => [
                        'List the actual products carried by this category.',
                        'Call out the brands most often quoted.',
                        'Link out to deeper brand references later.',
                    ],
                ],
                [
                    'title' => 'Selling Points',
                    'summary' => 'Show why this category matters to the rep.',
                    'points' => [
                        'Add the core benefits.',
                        'Add a quick comparison note.',
                        'Add the best objection response.',
                    ],
                ],
            ],
            'Specs & Drawings' => [
                [
                    'title' => 'Spec Sheet Index',
                    'summary' => 'Add the approved spec sheets for this category.',
                    'points' => [
                        'List the current PDFs or cut sheets.',
                        'Note revision dates when needed.',
                        'Call out where the master file lives.',
                    ],
                ],
                [
                    'title' => 'Architect Drawings',
                    'summary' => 'Collect drawings and install details.',
                    'points' => [
                        'Add architect files or detail drawings.',
                        'Call out file format or source links.',
                        'Note whether the drawing is current.',
                    ],
                ],
                [
                    'title' => 'Install Diagrams',
                    'summary' => 'Collect install diagrams and setup references.',
                    'points' => [
                        'Add install sheets or diagrams.',
                        'Call out any special install considerations.',
                        'Link to project-specific guidance later.',
                    ],
                ],
            ],
            'Warranty / Compliance' => [
                [
                    'title' => 'Warranty Summary',
                    'summary' => 'Capture the main warranty terms for this category.',
                    'points' => [
                        'List the active warranty language.',
                        'Add coverage windows and exclusions.',
                        'Link to manufacturer docs when ready.',
                    ],
                ],
                [
                    'title' => 'Compliance Notes',
                    'summary' => 'Note code, field, or project constraints.',
                    'points' => [
                        'Add code or compliance notes.',
                        'Call out any pre-approval needs.',
                        'Describe known project exceptions.',
                    ],
                ],
                [
                    'title' => 'Warranty Caveats',
                    'summary' => 'Document the things reps should not promise.',
                    'points' => [
                        'List common exclusions.',
                        'Add wording for edge cases.',
                        'Keep this section short and practical.',
                    ],
                ],
            ],
            'Reference / FAQs' => [
                [
                    'title' => 'Sales Answers',
                    'summary' => 'Use this for the most common customer questions.',
                    'points' => [
                        'Add quick answers the team can reuse.',
                        'Add objection handling language.',
                        'Keep the tone customer-friendly.',
                    ],
                ],
                [
                    'title' => 'Measurement Notes',
                    'summary' => 'Capture sizing and field-measurement guidance.',
                    'points' => [
                        'Add measurement reminders.',
                        'Note site-check details or caveats.',
                        'Link to field notes later.',
                    ],
                ],
                [
                    'title' => 'Comparisons',
                    'summary' => 'Use this for side-by-side product comparisons.',
                    'points' => [
                        'Compare the category to alternatives.',
                        'Add when to choose one option over another.',
                        'Include the simplest field-friendly language.',
                    ],
                ],
            ],
        };

        $chips = $this->buildProductChipsHtml($productNames, $productPages);

        $html = $this->buildScaffoldPageHtml(
            $resourceBookName . ' / ' . $serviceName,
            $chapterName . ' - ' . $categoryName,
            $summary,
            $sections
        );

        if ($resourceBookName === 'Products' && $chips !== '') {
            $html = str_replace(
                '<div class="sotx-note" style="margin-top:.8rem;">This page is fully scaffolded. Replace the draft guidance with approved content when you are ready.</div>',
                '<div class="sotx-note" style="margin-top:.8rem;">This page is fully scaffolded. Replace the draft guidance with approved content when you are ready.</div>' . $chips,
                $html
            );
        }

        return $html;
    }

    protected function buildBrandPageHtml(string $brandName, string $pageName, string $summary, array $brandProfile = []): string
    {
        $websiteUrl = $brandProfile['website'] ?? null;
        $quickLinks = $brandProfile['quick_links'] ?? null;
        $warrantyLinks = $brandProfile['warranty_links'] ?? null;
        $productSection = [];
        foreach (($brandProfile['products'] ?? []) as $product) {
            $productSection[] = [
                'title' => $product['name'],
                'summary' => $product['summary'] ?? '',
                'points' => $product['points'] ?? [],
                'url' => $product['url'] ?? null,
            ];
        }

        $sections = $brandProfile['sections'] ?? [
            [
                'title' => 'Product Overview',
                'summary' => 'Describe what we carry from this brand and where it fits.',
                'points' => [
                    'List the exact product lines we sell.',
                    'Note the customers or project types that use them.',
                    'Add any sales notes that help reps position the brand.',
                ],
            ],
            [
                'title' => 'Selling Points',
                'summary' => 'Capture the reasons a rep should lead with this brand.',
                'points' => [
                    'Add the main benefits and differentiators.',
                    'Add comparison notes against common alternatives.',
                    'Include the most useful talking points for the field team.',
                ],
            ],
            [
                'title' => 'Specs & Drawings',
                'summary' => 'Store cut sheets, technical documents, and any line drawings here.',
                'points' => [
                    'Link to approved spec sheets.',
                    'Link to install drawings or architect files.',
                    'Add document version or revision notes.',
                ],
            ],
            [
                'title' => 'Warranty & Compliance',
                'summary' => 'Summarize warranties, code concerns, and limitations.',
                'points' => [
                    'Add warranty terms or exclusions.',
                    'Add project limitations or compliance notes.',
                    'Call out anything that needs pre-approval.',
                ],
            ],
            [
                'title' => 'Reference / FAQs',
                'summary' => 'Capture objections, measurement notes, and comparison language.',
                'points' => [
                    'Add common customer questions and responses.',
                    'Add field notes from installers or project managers.',
                    'Add side-by-side comparisons when helpful.',
                ],
            ],
        ];

        if (!empty($productSection)) {
            array_unshift($sections, [
                'title' => 'Products',
                'summary' => 'Direct links to the product lines we use from this brand.',
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
            ]);
        }

        if (!empty($websiteUrl)) {
            foreach ($sections as &$section) {
                if (($section['title'] ?? '') === 'Warranty & Compliance') {
                    $linksToInsert = $warrantyLinks ?? [
                        [
                            'label' => 'Website',
                            'url' => $websiteUrl,
                        ],
                    ];

                    foreach (array_reverse($linksToInsert) as $link) {
                        $linkUrl = $link['url'] ?? null;
                        if (empty($linkUrl)) {
                            continue;
                        }

                        $linkLabel = $link['label'] ?? 'Website';
                        array_unshift($section['points'], [
                            'html' => '<a href="' . e($linkUrl) . '" target="_blank" rel="noreferrer">' . e($linkLabel) . '</a>',
                        ]);
                    }
                    break;
                }
            }
            unset($section);
        }

        $html = $this->buildScaffoldPageHtml($brandName, $pageName, $summary, $sections);

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

        if ($sourceLinks !== '') {
            $html = str_replace(
                '<div class="sotx-note" style="margin-top:.8rem;">This page is fully scaffolded. Replace the draft guidance with approved content when you are ready.</div>',
                '<div class="sotx-note" style="margin-top:.8rem;">This page is fully scaffolded. Replace the draft guidance with approved content when you are ready.</div><div class="sotx-section-head" style="margin-top:.8rem;"><div><h2>Quick Links</h2></div></div><div class="sotx-actions" style="margin-top:.55rem;">' . $sourceLinks . '</div>',
                $html
            );
        }

        return $html;
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
                'sections' => [
                    [
                        'title' => 'Product Overview',
                        'summary' => 'Pella covers windows and patio doors across wood, fiberglass, vinyl, coastal vinyl, and aluminum product lines.',
                        'points' => [
                            'Primary lines include Reserve, Lifestyle Series, Impervia, 250 Series, Encompass, Hurricane Shield Series, Defender Series, and Vista Series.',
                            'Use Pella when the project needs a broad mix of style, performance, and material options.',
                            'The brand spans both windows and doors, with shopping, education, and professional support on the site.',
                        ],
                    ],
                    [
                        'title' => 'Selling Points',
                        'summary' => 'Pella leans into design flexibility, strong performance, and broad product selection.',
                        'points' => [
                            'Lifestyle Series is positioned for everyday life with energy efficiency and noise reduction.',
                            'Impervia emphasizes strength, durability, and fiberglass performance.',
                            'Defender and Hurricane Shield series address coastal and storm-resistant needs.',
                        ],
                    ],
                    [
                        'title' => 'Specs & Drawings',
                        'summary' => 'Pella provides professional resources, brochures, installation instructions, and technical downloads.',
                        'points' => [
                            'Use Pella’s professional windows page for technical downloads.',
                            'Use installation manuals and product brochures for project support.',
                            'Keep the exact product line and series noted before quoting.',
                        ],
                    ],
                    [
                        'title' => 'Warranty & Compliance',
                        'summary' => 'Pella publishes warranties by product line and includes support content for replacement and installation customers.',
                        'points' => [
                            'Warranty coverage varies by series and material.',
                            'Use the warranties page for current and historical coverage documents.',
                            'Confirm the exact line before promising coverage details on a quote.',
                        ],
                    ],
                    [
                        'title' => 'Reference / FAQs',
                        'summary' => 'Pella’s site supports shopping, education, inspiration, and warranty lookup in one place.',
                        'points' => [
                            'Start at the main Pella home page for shopping and education.',
                            'Use the support and warranty pages for service questions.',
                            'Use the product-line pages when reps need quick positioning language.',
                        ],
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
                    ['label' => 'Warranty Information', 'url' => 'https://www.renewalbyandersen.com/resources/warranty'],
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
                'sections' => [
                    [
                        'title' => 'Product Overview',
                        'summary' => 'Andersen covers windows, doors, hardware, accessories, and multiple product series for residential and professional use.',
                        'points' => [
                            'Core series include E-Series, A-Series, 400 Series, 200 Series, 100 Series, and Andersen Aluminum.',
                            'Door offerings include entry doors, patio doors, MultiGlide, folding outswing, liftslide, pivot, bifold, and swing doors.',
                            'Andersen also maintains support for accessories, parts, and service tools.',
                        ],
                    ],
                    [
                        'title' => 'Selling Points',
                        'summary' => 'Andersen emphasizes quality, transferable limited warranties, and a deep support ecosystem.',
                        'points' => [
                            'Owner-to-Owner limited warranties are a major sales point and can transfer to the next homeowner.',
                            'Professional resources include product guides, CAD/BIM/CSI tools, and installation support.',
                            'The support site is built around replacement, cleaning, service, and warranty help.',
                        ],
                    ],
                    [
                        'title' => 'Specs & Drawings',
                        'summary' => 'Andersen provides product guides and technical resources for professionals.',
                        'points' => [
                            'Use the support center for product guides and installation resources.',
                            'Architectural tools include CAD/BIM/CSI resources and other technical references.',
                            'Keep the exact series and product ID handy when pulling documentation.',
                        ],
                    ],
                    [
                        'title' => 'Warranty & Compliance',
                        'summary' => 'Andersen warranty documents vary by product line and purchase date, with support information published online.',
                        'points' => [
                            'Warranties are product-line specific and may vary by options or accessories.',
                            'Support pages identify limited warranties, claim steps, and product identification help.',
                            'Non-coastal products have published glass and non-glass coverage in the support FAQs.',
                        ],
                    ],
                    [
                        'title' => 'Reference / FAQs',
                        'summary' => 'Andersen’s support center is the main path for FAQs, service, care, and replacement help.',
                        'points' => [
                            'Use the FAQ and support pages for ordering, sizing, and warranty questions.',
                            'Use the quality page for brand positioning and trust language.',
                            'Use the help center when a rep needs to identify an installed product.',
                        ],
                    ],
                ],
                'sources' => [],
            ],
            'Jeld-Wen' => [
                'website' => 'https://www.jeld-wen.com/en-us/',
                'quick_links' => [
                    ['label' => 'Website', 'url' => 'https://www.jeld-wen.com/en-us/'],
                    ['label' => 'JELD-WEN About', 'url' => 'https://www.corporate.jeld-wen.com/about-us'],
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
                'sections' => [
                    [
                        'title' => 'Product Overview',
                        'summary' => 'JELD-WEN offers windows, patio doors, interior and exterior doors, wall systems, and related building products.',
                        'points' => [
                            'The brand markets a broad selection of wood, vinyl, and composite product options.',
                            'Auraline True Composite is one of the newer composite window and patio door lines.',
                            'Use JELD-WEN for a broad, builder-friendly windows and doors reference.',
                        ],
                    ],
                    [
                        'title' => 'Selling Points',
                        'summary' => 'JELD-WEN leans on selection, energy efficiency, durability, and support.',
                        'points' => [
                            'The company positions itself as one of the world’s largest window and door manufacturers.',
                            'Dealer and homeowner support is part of the brand story.',
                            'The products are marketed as beautiful, built to last, and energy efficient.',
                        ],
                    ],
                    [
                        'title' => 'Specs & Drawings',
                        'summary' => 'Technical documents, installation instructions, care and maintenance, and performance ratings live in the document library.',
                        'points' => [
                            'Use the documents library for installation and performance references.',
                            'Keep the exact window or door line noted before pulling cut sheets.',
                            'Use product literature for line-specific comparison work.',
                        ],
                    ],
                    [
                        'title' => 'Warranty & Compliance',
                        'summary' => 'JELD-WEN publishes a current all-warranty guide plus product literature and historical support documents.',
                        'points' => [
                            'Use the all-warranty guide for current warranty language.',
                            'Use the documents page for installation instructions, care and maintenance, and performance ratings.',
                            'Confirm the exact line before stating warranty terms to a customer.',
                        ],
                    ],
                    [
                        'title' => 'Reference / FAQs',
                        'summary' => 'The brand’s corporate pages provide product highlights, market support, and customer/service content.',
                        'points' => [
                            'Use the brands and products page for portfolio positioning.',
                            'Use the about and markets pages for sales language and support context.',
                            'Use the warranty guide and documents library when the rep needs claim or technical details.',
                        ],
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
                'sections' => [
                    [
                        'title' => 'Product Overview',
                        'summary' => 'Dallas Flat Glass is a Carrollton-based glass wholesaler serving the DFW market with a broad range of custom and replacement glass products.',
                        'points' => [
                            'Publicly indexed descriptions highlight insulated glass, mirrors, pattern glass, specialty glass, low-E glass, laminated glass, fully tempered glass, heavy glass, monolithic glass, and custom beveled glass.',
                            'The company is also associated with all-glass entrance systems and handrail-related glass products.',
                            'Use this page as the internal reference for window glass, replacement glass, decorative glass, and custom fabrication sourcing.',
                        ],
                    ],
                    [
                        'title' => 'Selling Points',
                        'summary' => 'The public descriptions emphasize breadth, fast turnaround, transparent ordering, and customer service.',
                        'points' => [
                            'Known for a wide selection of glass and glazing products.',
                            'Frequently described as a wholesaler with competitive pricing and transparent ordering.',
                            'Useful when reps need a Dallas-area glass sourcing reference for replacement and custom fabrication work.',
                        ],
                    ],
                    [
                        'title' => 'Specs & Drawings',
                        'summary' => 'Use this section for glass specs, fabrication notes, and project drawings once the exact source files are added.',
                        'points' => [
                            'Track insulated glass, tempered, laminated, monolithic, low-E, pattern, and specialty product documentation here.',
                            'Add fabrication drawings or project notes when available.',
                            'Confirm the exact glass build and finish before quoting.',
                        ],
                    ],
                    [
                        'title' => 'Warranty & Compliance',
                        'summary' => 'Dallas Flat Glass warranty information should be confirmed directly with the company before quoting.',
                        'points' => [
                            'Use project-specific or manufacturer-specific warranty language until direct warranty docs are added here.',
                            'Confirm compliance details for tempered, laminated, or installed glass assemblies.',
                            'Treat this as a working reference until the official docs are loaded.',
                        ],
                    ],
                    [
                        'title' => 'Reference / FAQs',
                        'summary' => 'This section should capture ordering, lead time, and glass-type questions for the DFW team.',
                        'points' => [
                            'Add sourcing notes for common window, replacement-glass, and custom-fabrication jobs.',
                            'Add any ordering or turnaround expectations after confirmation.',
                            'Use this space for common glass-identification questions.',
                        ],
                    ],
                ],
                'sources' => [
                    'LinkedIn Company Profile' => 'https://www.linkedin.com/company/dallas-flat-glass-distributors',
                    'MapQuest Profile' => 'https://www.mapquest.com/us/texas/dallas-flat-glass-distributors-304386954',
                ],
            ],
        ];
    }

    protected function buildIndexPageHtml(string $bookName, string $pageName, string $summary): string
    {
        return $this->buildScaffoldPageHtml(
            $bookName,
            $pageName,
            $summary,
            [
                [
                    'title' => 'What Belongs Here',
                    'summary' => 'Use this page to collect the documents that are easiest to lose.',
                    'points' => [
                        'Add the source files this page should point to.',
                        'Add the file naming convention used by the team.',
                        'Add the owner responsible for keeping it current.',
                    ],
                ],
                [
                    'title' => 'How to Organize It',
                    'summary' => 'Keep the index scannable so reps can get to the right file quickly.',
                    'points' => [
                        'Group files by product family or manufacturer.',
                        'Separate current files from archived files.',
                        'Note any job-specific or project-specific exceptions.',
                    ],
                ],
                [
                    'title' => 'Still Missing',
                    'summary' => 'Leave a clear place for gaps so the team knows what still needs to be added.',
                    'points' => [
                        'List the missing documents.',
                        'Add a request owner or next action.',
                        'Add the last confirmed source for the document.',
                    ],
                ],
            ]
        );
    }

    protected function buildScaffoldPageHtml(string $eyebrow, string $title, string $summary, array $sections): string
    {
        $eyebrow = e($eyebrow);
        $title = e($title);
        $summary = e($summary);

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
            <p class="sotx-kicker" style="margin-bottom:.35rem;">Populate This Section</p>
            <h4>{$sectionTitle}</h4>
        </div>
        <span class="sotx-flag">Draft</span>
    </div>
    <p class="sotx-note" style="margin-top:.65rem;">{$sectionSummary}</p>
    <ul class="sotx-template-list">
        {$points}
    </ul>
</section>
HTML;
        }

        return <<<HTML
<div class="sotx-home">
    <section class="sotx-panel sotx-card sotx-section-card">
        <p class="sotx-kicker">{$eyebrow}</p>
        <h1>{$title}</h1>
        <p class="sotx-lede">{$summary}</p>
        <div class="sotx-note" style="margin-top:.8rem;">This page is fully scaffolded. Replace the draft guidance with approved content when you are ready.</div>
    </section>
    <section class="sotx-section">
        <div class="sotx-service-grid">
            {$sectionHtml}
        </div>
    </section>
</div>
HTML;
    }

    protected function buildServiceSectionHtml(string $serviceName, string $sectionName, string $summary, string $details, array $productNames): string
    {
        $productsHtml = collect($productNames)->isNotEmpty()
            ? '<div class="sotx-chip-list">' . collect($productNames)->map(fn (string $productName): string => '<span class="sotx-chip">' . e($productName) . '</span>')->implode('') . '</div>'
            : '';

        return $this->buildScaffoldPageHtml(
            $serviceName,
            $sectionName,
            $summary,
            [
                [
                    'title' => 'Products',
                    'summary' => 'Brand-level product pages and what we carry.',
                    'points' => [
                        'List the approved brands for this category.',
                        'Add the exact product lines or model names.',
                        'Use the chips above as the first-pass product list.',
                    ],
                ],
                [
                    'title' => 'Specs & Drawings',
                    'summary' => 'Technical docs, cut sheets, and architect files.',
                    'points' => [
                        'Link to the active spec sheets.',
                        'Link to drawings or install details.',
                        'Call out revision dates or source files.',
                    ],
                ],
                [
                    'title' => 'Warranty / Compliance',
                    'summary' => 'Warranty notes, code concerns, and exceptions.',
                    'points' => [
                        'List warranty details and exclusions.',
                        'Add any code or compliance cautions.',
                        'Note whether approvals are required before quoting.',
                    ],
                ],
                [
                    'title' => 'Reference / FAQs',
                    'summary' => 'Objection handling, measurements, and comparisons.',
                    'points' => [
                        'Capture common objections and the best responses.',
                        'Add measurement and site-visit reminders.',
                        'Add comparison notes for similar products.',
                    ],
                ],
            ]
        );
    }

    protected function buildProductsHtml(array $productNames): string
    {
        $items = collect($productNames)->map(function (string $productName): string {
            return '<span class="sotx-chip">' . e($productName) . '</span>';
        })->implode('');

        return <<<HTML
<div class="sotx-home">
    <section class="sotx-panel sotx-card sotx-section-card">
        <p class="sotx-kicker">What We Carry</p>
        <p class="sotx-note">Use the linked product pages below to find the brand-level information for this category.</p>
        <div class="sotx-chip-list">{$items}</div>
    </section>
</div>
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
            ['book' => $books['Products'], 'label' => 'Products'],
            ['book' => $books['Specs & Drawings'], 'label' => 'Specs & Drawings'],
            ['book' => $books['Warranty / Compliance'], 'label' => 'Warranty / Compliance'],
            ['book' => $books['Reference / FAQs'], 'label' => 'Reference / FAQs'],
        ] as $link) {
            $quickLinks .= '<a href="' . e($link['book']->getUrl()) . '" class="sotx-pill">' . e($link['label']) . '</a>';
        }

        $startHereBook = $books['Start Here'];
        $productsBook = $books['Products'];
        $specsBook = $books['Specs & Drawings'];
        $warrantyBook = $books['Warranty / Compliance'];
        $faqsBook = $books['Reference / FAQs'];

        $supportCards = '';
        foreach ([
            ['book' => $startHereBook, 'title' => 'Start Here', 'description' => 'Fast onboarding for new reps and a quick map of the hub.'],
            ['book' => $productsBook, 'title' => 'Products', 'description' => 'Brand-level product pages and what we carry.'],
            ['book' => $specsBook, 'title' => 'Specs & Drawings', 'description' => 'Technical docs, cut sheets, and architect files.'],
            ['book' => $warrantyBook, 'title' => 'Warranty / Compliance', 'description' => 'Warranty notes, code concerns, and exceptions.'],
            ['book' => $faqsBook, 'title' => 'Reference / FAQs', 'description' => 'Objection handling, measurements, and comparisons.'],
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
                    Sales-ready access to the resources our team needs most: product lines, spec sheets, drawings, warranties, and field notes.
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
                <strong>Products</strong>
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
            <li>Use specs, drawings, warranty, or FAQs when a deal needs more detail.</li>
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
                    ['name' => 'Where to Find Specs, Drawings, and Pricing', 'summary' => 'Explains where the technical documents and pricing references live.'],
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
                            ['name' => 'Commercial Glazing', 'summary' => 'Storefront and glazing references.', 'details' => 'Use the four sections below for commercial glazing projects.', 'products' => ['Dallas Flat Glass', 'Ghost Glass', 'Jeld-Wen', 'CRL']],
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
            'Products' => [
                'description' => 'Manufacturer and product-line reference pages for the products Shades of Texas carries.',
                'chapters' => [
                    '3M' => [
                        'description' => '3M product reference and sales support.',
                        'pages' => [
                            ['name' => '3M Product Overview', 'summary' => 'What we carry from 3M and where it fits.'],
                            ['name' => '3M Selling Points', 'summary' => 'Primary reasons we sell 3M products.'],
                            ['name' => '3M Specs & Drawings', 'summary' => 'Cut sheets and technical references for 3M items.'],
                            ['name' => '3M Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Hunter Douglas' => [
                        'description' => 'Hunter Douglas product reference and sales support.',
                        'pages' => [
                            ['name' => 'Hunter Douglas Product Overview', 'summary' => 'What we carry from Hunter Douglas and where it fits.'],
                            ['name' => 'Hunter Douglas Selling Points', 'summary' => 'Primary reasons we sell Hunter Douglas products.'],
                            ['name' => 'Hunter Douglas Specs & Drawings', 'summary' => 'Cut sheets and technical references for Hunter Douglas items.'],
                            ['name' => 'Hunter Douglas Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Alta' => [
                        'description' => 'Alta product reference and sales support.',
                        'pages' => [
                            ['name' => 'Alta Product Overview', 'summary' => 'What we carry from Alta and where it fits.'],
                            ['name' => 'Alta Selling Points', 'summary' => 'Primary reasons we sell Alta products.'],
                            ['name' => 'Alta Specs & Drawings', 'summary' => 'Cut sheets and technical references for Alta items.'],
                            ['name' => 'Alta Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Norman' => [
                        'description' => 'Norman product reference and sales support.',
                        'pages' => [
                            ['name' => 'Norman Product Overview', 'summary' => 'What we carry from Norman and where it fits.'],
                            ['name' => 'Norman Selling Points', 'summary' => 'Primary reasons we sell Norman products.'],
                            ['name' => 'Norman Specs & Drawings', 'summary' => 'Cut sheets and technical references for Norman items.'],
                            ['name' => 'Norman Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Eclipse' => [
                        'description' => 'Eclipse product reference and sales support.',
                        'pages' => [
                            ['name' => 'Eclipse Product Overview', 'summary' => 'What we carry from Eclipse and where it fits.'],
                            ['name' => 'Eclipse Selling Points', 'summary' => 'Primary reasons we sell Eclipse products.'],
                            ['name' => 'Eclipse Specs & Drawings', 'summary' => 'Cut sheets and technical references for Eclipse items.'],
                            ['name' => 'Eclipse Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'SmartTint' => [
                        'description' => 'SmartTint product reference and sales support.',
                        'pages' => [
                            ['name' => 'SmartTint Product Overview', 'summary' => 'What we carry from SmartTint and where it fits.'],
                            ['name' => 'SmartTint Selling Points', 'summary' => 'Primary reasons we sell SmartTint products.'],
                            ['name' => 'SmartTint Specs & Drawings', 'summary' => 'Cut sheets and technical references for SmartTint items.'],
                            ['name' => 'SmartTint Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Andersen' => [
                        'description' => 'Andersen windows and doors product reference and sales support.',
                        'pages' => [
                            ['name' => 'Andersen Product Overview', 'summary' => 'What we carry from Andersen and where it fits.'],
                            ['name' => 'Andersen Selling Points', 'summary' => 'Primary reasons we sell Andersen products.'],
                            ['name' => 'Andersen Specs & Drawings', 'summary' => 'Cut sheets and technical references for Andersen items.'],
                            ['name' => 'Andersen Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Pella' => [
                        'description' => 'Pella windows and doors product reference and sales support.',
                        'pages' => [
                            ['name' => 'Pella Product Overview', 'summary' => 'What we carry from Pella and where it fits.'],
                            ['name' => 'Pella Selling Points', 'summary' => 'Primary reasons we sell Pella products.'],
                            ['name' => 'Pella Specs & Drawings', 'summary' => 'Cut sheets and technical references for Pella items.'],
                            ['name' => 'Pella Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'CRL' => [
                        'description' => 'CRL product reference and sales support.',
                        'pages' => [
                            ['name' => 'CRL Product Overview', 'summary' => 'What we carry from CRL and where it fits.'],
                            ['name' => 'CRL Selling Points', 'summary' => 'Primary reasons we sell CRL products.'],
                            ['name' => 'CRL Specs & Drawings', 'summary' => 'Cut sheets and technical references for CRL items.'],
                            ['name' => 'CRL Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Dallas Flat Glass' => [
                        'description' => 'Dallas Flat Glass product reference and sales support.',
                        'pages' => [
                            ['name' => 'Dallas Flat Glass Product Overview', 'summary' => 'What we carry from Dallas Flat Glass and where it fits.'],
                            ['name' => 'Dallas Flat Glass Selling Points', 'summary' => 'Primary reasons we sell Dallas Flat Glass products.'],
                            ['name' => 'Dallas Flat Glass Specs & Drawings', 'summary' => 'Cut sheets and technical references for Dallas Flat Glass items.'],
                            ['name' => 'Dallas Flat Glass Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Ghost Glass' => [
                        'description' => 'Ghost Glass product reference and sales support.',
                        'pages' => [
                            ['name' => 'Ghost Glass Product Overview', 'summary' => 'What we carry from Ghost Glass and where it fits.'],
                            ['name' => 'Ghost Glass Selling Points', 'summary' => 'Primary reasons we sell Ghost Glass products.'],
                            ['name' => 'Ghost Glass Specs & Drawings', 'summary' => 'Cut sheets and technical references for Ghost Glass items.'],
                            ['name' => 'Ghost Glass Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                    'Jeld-Wen' => [
                        'description' => 'Jeld-Wen product reference and sales support.',
                        'pages' => [
                            ['name' => 'Jeld-Wen Product Overview', 'summary' => 'What we carry from Jeld-Wen and where it fits.'],
                            ['name' => 'Jeld-Wen Selling Points', 'summary' => 'Primary reasons we sell Jeld-Wen products.'],
                            ['name' => 'Jeld-Wen Specs & Drawings', 'summary' => 'Cut sheets and technical references for Jeld-Wen items.'],
                            ['name' => 'Jeld-Wen Warranty & Compliance', 'summary' => 'Warranty notes and compliance considerations.'],
                        ],
                    ],
                ],
            ],
            'Specs & Drawings' => [
                'description' => 'Document-first library for technical files, grouped so reps can get to the right file quickly.',
                'pages' => [
                    ['name' => 'Spec Sheet Index', 'summary' => 'Landing page for product spec sheets.'],
                    ['name' => 'Architect Drawing Index', 'summary' => 'Landing page for architect drawings and install references.'],
                    ['name' => 'Install Diagram Index', 'summary' => 'Landing page for installation diagrams and reference sheets.'],
                ],
            ],
            'Warranty / Compliance' => [
                'description' => 'Warranty summaries, code notes, and the key limitations salespeople need to know.',
                'pages' => [
                    ['name' => 'Warranty Summary Index', 'summary' => 'Landing page for manufacturer warranty references.'],
                    ['name' => 'Compliance Notes', 'summary' => 'Landing page for code, material, and project compliance notes.'],
                    ['name' => 'Warranty Caveats', 'summary' => 'Landing page for common exclusions and edge cases.'],
                ],
            ],
            'Reference / FAQs' => [
                'description' => 'Sales support pages for common questions, objections, and field notes.',
                'pages' => [
                    ['name' => 'Sales Answers', 'summary' => 'Answers to common customer and prospect questions.'],
                    ['name' => 'Measurement Notes', 'summary' => 'General notes about sizing and on-site measurement.'],
                    ['name' => 'Installation Caveats', 'summary' => 'Important install constraints and reminders.'],
                    ['name' => 'Product Comparisons', 'summary' => 'A place for side-by-side comparison notes.'],
                    ['name' => 'Objection Handling', 'summary' => 'Helpful responses to common objections.'],
                ],
            ],
        ];
    }
}
