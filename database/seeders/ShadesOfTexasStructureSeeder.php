<?php

namespace Database\Seeders;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\Bookshelf;
use BookStack\Entities\Models\Chapter;
use BookStack\Entities\Models\Page;
use BookStack\Entities\Repos\BaseRepo;
use BookStack\Permissions\JointPermissionBuilder;
use BookStack\Permissions\Models\RolePermission;
use BookStack\Permissions\Permission;
use BookStack\Users\Models\Role;
use BookStack\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\URL;

class ShadesOfTexasStructureSeeder extends Seeder
{
    /**
     * Tracks pages created during the current seed so follow-up rendering can
     * enrich new seed content without clobbering live BookStack edits.
     *
     * @var array<int, bool>
     */
    protected array $createdPageIds = [];

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

        URL::forceRootUrl(config('app.url'));
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
                    $vendorProductPagePriority,
                    false
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
                1,
                false
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

                if (!empty($this->createdPageIds[$vendorProductPage->id])) {
                    $vendorProductPage->forceFill([
                        'html' => $finalHtml,
                        'text' => strip_tags($finalHtml),
                    ])->save();
                    $vendorProductPage->refresh();
                }
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

        $systemIndexPriority = ++$startHerePageCounters;
        $systemCategoryPages = [];
        foreach ($this->systemCategories() as $systemCategory) {
            $startHerePageCounters++;
            $systemCategoryPages[$systemCategory['name']] = $this->createPage(
                $createdBooks['Start Here'],
                null,
                $systemCategory['name'],
                $systemCategory['summary'],
                $byData,
                $this->buildSystemCategoryPageHtml(
                    $systemCategory,
                    $createdPages,
                    $createdProductPages
                ),
                $startHerePageCounters
            );
        }

        $createdPages['Start Here'][null]['System Categories'] = $this->createPage(
            $createdBooks['Start Here'],
            null,
            'System Categories',
            'Customer-need navigation layer that routes into service categories and vendor/product pages.',
            $byData,
            $this->buildSystemCategoriesPageHtml($systemCategoryPages),
            $systemIndexPriority
        );

        foreach ($systemCategoryPages as $pageName => $page) {
            $createdPages['Start Here'][null][$pageName] = $page;
        }

        $createdPages['Start Here'][null]['Start Here'] = $this->createPage(
            $createdBooks['Start Here'],
            null,
            'Start Here',
            'Task-based landing page for sales consultants and internal users.',
            $byData,
            $this->buildStartHereTaskPageHtml($createdBooks, $createdPages, $createdProductPages),
            0
        );

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
    --sotx-orange: #D94D2B;
    --sotx-blue: #0A6E9F;
    --sotx-bg: #f7f8fa;
    --sotx-surface: #ffffff;
    --sotx-surface-alt: #f1f4f7;
    --sotx-border: rgba(23, 27, 42, 0.12);
    --sotx-border-strong: rgba(23, 27, 42, 0.22);
    --sotx-text: #171B2A;
    --sotx-muted: #5c6575;
    --sotx-soft: rgba(10, 110, 159, 0.08);
    --sotx-focus: rgba(10, 110, 159, 0.28);
    --sotx-radius: 8px;
}

html.dark-mode {
    --sotx-bg: #0f1320;
    --sotx-surface: #171B2A;
    --sotx-surface-alt: #1d2233;
    --sotx-border: rgba(255, 255, 255, 0.10);
    --sotx-border-strong: rgba(255, 255, 255, 0.22);
    --sotx-text: #f5f7fb;
    --sotx-muted: #a7afc1;
    --sotx-soft: rgba(255, 90, 60, 0.14);
    --sotx-focus: rgba(255, 90, 60, 0.30);
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
    background: var(--sotx-bg);
}

.sotx-home * {
    box-sizing: border-box;
}

.sotx-panel,
.sotx-card,
.sotx-mini-card {
    background: var(--sotx-surface);
    border: 1px solid var(--sotx-border);
    border-radius: var(--sotx-radius);
}

.sotx-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(17rem, .9fr);
    gap: 1.1rem;
    padding: clamp(1rem, 2vw, 1.45rem);
    background: var(--sotx-surface);
    border-left: 4px solid var(--sotx-blue);
}

.sotx-hero .sotx-lede {
    font-size: 1rem;
}

.sotx-kicker {
    margin: 0 0 .45rem;
    color: var(--sotx-blue);
    text-transform: uppercase;
    letter-spacing: 0;
    font-size: .76rem;
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
    font-size: clamp(1.85rem, 3vw, 2.75rem);
    line-height: 1.06;
    letter-spacing: 0;
}

.sotx-lede,
.sotx-card p,
.sotx-mini-card p,
.sotx-section p,
.sotx-note {
    color: var(--sotx-muted);
    line-height: 1.55;
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
    gap: .5rem;
}

.sotx-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.5rem;
    padding: .62rem .9rem;
    border-radius: var(--sotx-radius);
    background: var(--sotx-navy);
    border: 1px solid var(--sotx-navy);
    color: #fff !important;
    text-decoration: none;
    font-weight: 900;
    line-height: 1.15;
    text-align: center;
    transition: background-color .15s ease, border-color .15s ease, color .15s ease;
}

.sotx-pill:hover {
    background: var(--sotx-orange);
    border-color: var(--sotx-orange);
    text-decoration: none;
}

.sotx-pill:focus-visible,
.sotx-card:focus-visible,
.sotx-chip-link:focus-visible {
    outline: 3px solid var(--sotx-focus);
    outline-offset: 2px;
}

.sotx-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .6rem;
}

.sotx-metric {
    padding: .82rem .9rem;
    border-radius: var(--sotx-radius);
    background: var(--sotx-soft);
    border: 1px solid rgba(10, 110, 159, .13);
    min-height: 4.15rem;
}

.sotx-metric strong,
.sotx-metric span {
    display: block;
}

.sotx-metric strong {
    font-size: 1rem;
    color: var(--sotx-text);
}

.sotx-metric span {
    margin-top: .15rem;
    font-size: .88rem;
}

.sotx-section {
    margin-top: 1.1rem;
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
    font-size: clamp(1.18rem, 1.6vw, 1.45rem);
    letter-spacing: 0;
}

.sotx-service-grid,
.sotx-resource-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
    gap: .7rem;
}

.sotx-card,
.sotx-mini-card,
.sotx-service-card,
.sotx-link-card {
    display: block;
    padding: .9rem;
    text-decoration: none;
    color: var(--sotx-text);
    min-width: 0;
    transition: border-color .15s ease, background-color .15s ease, color .15s ease;
}

.sotx-service-card:hover,
.sotx-link-card:hover,
.sotx-mini-card:hover {
    background: linear-gradient(180deg, var(--sotx-surface) 0%, var(--sotx-surface-alt) 100%);
    border-color: rgba(10, 110, 159, .35);
    text-decoration: none;
}

.sotx-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
}

.sotx-card h3,
.sotx-card h4,
.sotx-mini-card h4 {
    line-height: 1.2;
}

.sotx-card p,
.sotx-mini-card p {
    margin-top: .45rem;
}

.sotx-flag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    padding: .22rem .42rem;
    border-radius: 6px;
    background: rgba(23, 27, 42, .08);
    color: var(--sotx-text);
    font-size: .66rem;
    font-weight: 900;
    letter-spacing: 0;
    text-transform: uppercase;
}

.sotx-status-row {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
    margin-top: .65rem;
}

.sotx-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: .22rem .44rem;
    border-radius: 6px;
    background: rgba(23, 27, 42, .07);
    color: var(--sotx-text);
    font-size: .72rem;
    font-weight: 900;
    border: 1px solid transparent;
}

.sotx-status-audited {
    background: rgba(28, 132, 78, .13);
    border-color: rgba(28, 132, 78, .18);
    color: #16633d;
}

.sotx-status-login {
    background: rgba(18, 96, 136, .13);
    border-color: rgba(18, 96, 136, .18);
    color: #135979;
}

.sotx-status-pending,
.sotx-status-rep {
    background: rgba(180, 113, 16, .16);
    border-color: rgba(180, 113, 16, .20);
    color: #7a4c05;
}

.sotx-status-broken {
    background: rgba(185, 43, 39, .14);
    border-color: rgba(185, 43, 39, .18);
    color: #8a1f1d;
}

.sotx-task-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .7rem;
}

.sotx-task-card {
    position: relative;
    min-height: 8.25rem;
    border-left: 3px solid var(--sotx-blue);
    padding-right: 2rem;
}

.sotx-task-card::after {
    content: ">";
    position: absolute;
    right: .85rem;
    bottom: .72rem;
    color: var(--sotx-blue);
    font-weight: 900;
    line-height: 1;
}

.sotx-task-card:hover {
    border-left-color: var(--sotx-orange);
}

.sotx-task-card:hover::after {
    color: var(--sotx-orange);
}

.sotx-task-card strong {
    display: block;
    color: var(--sotx-text);
    font-size: 1rem;
    line-height: 1.25;
}

.sotx-task-card span {
    display: block;
    margin-top: .4rem;
    color: var(--sotx-muted);
    line-height: 1.38;
    font-size: .9rem;
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
    padding: .3rem .5rem;
    border-radius: 6px;
    background: var(--sotx-surface-alt);
    border: 1px solid var(--sotx-border);
    color: var(--sotx-text);
    font-size: .74rem;
    font-weight: 800;
    line-height: 1.2;
}

.sotx-chip-link {
    border-color: var(--sotx-border-strong);
}

.sotx-chip-link {
    text-decoration: none;
}

.sotx-chip-link:hover {
    color: var(--sotx-orange);
    border-color: rgba(255, 90, 60, 0.35);
}

.sotx-section-card {
    padding: 1rem;
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
    .sotx-home {
        padding: .85rem;
    }

    .sotx-hero {
        grid-template-columns: 1fr;
    }

    .sotx-task-grid,
    .sotx-service-grid,
    .sotx-resource-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .sotx-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .sotx-home {
        padding: .6rem;
    }

    .sotx-hero {
        padding: .9rem;
        border-left-width: 3px;
    }

    .sotx-hero h1 {
        font-size: 1.75rem;
    }

    .sotx-section-head,
    .sotx-card-top {
        align-items: flex-start;
    }

    .sotx-actions,
    .sotx-actions .sotx-pill {
        width: 100%;
    }

    .sotx-pill {
        justify-content: flex-start;
    }

    .sotx-task-grid,
    .sotx-service-grid,
    .sotx-resource-grid {
        grid-template-columns: 1fr;
    }

    .sotx-task-card {
        min-height: auto;
    }

    .sotx-stats {
        grid-template-columns: 1fr;
    }
}
</style>
HTML;
    }

    protected function resetExistingStructure(): void
    {
        // Preserve existing BookStack content. The seeder now upserts structure
        // and managed routing pages instead of deleting the hub on each run.
    }

    protected function createShelf(array $byData): Bookshelf
    {
        $description = 'A world class document management platform';

        /** @var Bookshelf $shelf */
        $shelf = Bookshelf::query()->where('name', '=', 'High Level')->first() ?? new Bookshelf();
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
        $book = Book::query()->where('name', '=', $name)->first() ?? new Book();
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
        $chapter = Chapter::query()
            ->where('book_id', '=', $book->id)
            ->where('name', '=', $name)
            ->first() ?? new Chapter();
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

    protected function createPage(Book $book, ?Chapter $chapter, string $name, string $summary, array $byData, ?string $customHtml = null, int $priority = 1, bool $overwriteHtml = true): Page
    {
        $html = $customHtml ?? $this->buildStandardPageHtml($name, $summary);

        /** @var Page $page */
        $page = Page::query()
            ->where('book_id', '=', $book->id)
            ->where('chapter_id', '=', $chapter?->id)
            ->where('name', '=', $name)
            ->first();

        $isNewPage = !$page;
        $page ??= new Page();
        $hasExistingContent = trim(strip_tags((string) $page->html)) !== '';
        $pageData = array_merge($byData, [
            'book_id'        => $book->id,
            'chapter_id'     => $chapter?->id,
            'name'           => $name,
            'revision_count' => $isNewPage ? 1 : max(1, (int) $page->revision_count),
            'editor'         => 'wysiwyg',
            'priority'       => $priority,
        ]);

        if ($overwriteHtml || !$hasExistingContent) {
            $pageData['html'] = $html;
            $pageData['text'] = strip_tags($html);
        }

        $page->forceFill($pageData)->save();

        if ($isNewPage) {
            $this->createdPageIds[$page->id] = true;
        }

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

    protected function buildStartHereTaskPageHtml(array $books, array $pagesByBook, array $vendorPages): string
    {
        $systemCategoriesUrl = $this->findPageUrl($pagesByBook, 'Start Here', null, 'System Categories') ?? ($books['Start Here']?->getUrl() ?? '#');
        $warrantyUrl = $this->findPageUrl($pagesByBook, 'Start Here', null, 'Where to Find Product Info and Pricing') ?? ($books['Vendors']?->getUrl() ?? '#');
        $requestUpdateUrl = $this->findPageUrl($pagesByBook, 'Start Here', null, 'How to Request Missing Documents') ?? ($books['Start Here']?->getUrl() ?? '#');
        $howToUseUrl = $this->findPageUrl($pagesByBook, 'Start Here', null, 'How to Use This Hub') ?? ($books['Start Here']?->getUrl() ?? '#');
        $vendorsUrl = $books['Vendors']?->getUrl() ?? '#';
        $residentialUrl = $books['Residential']?->getUrl() ?? '#';
        $commercialUrl = $books['Commercial']?->getUrl() ?? '#';

        $tasks = [
            ['title' => 'Find Product', 'summary' => 'Start with the customer need, then choose Residential or Commercial context.', 'url' => $systemCategoriesUrl, 'flag' => 'Start'],
            ['title' => 'Residential Job', 'summary' => 'Open homeowner categories before narrowing to sources or product details.', 'url' => $residentialUrl, 'flag' => 'Home'],
            ['title' => 'Commercial Job', 'summary' => 'Open business and facility categories before narrowing to sources.', 'url' => $commercialUrl, 'flag' => 'Biz'],
            ['title' => 'Check Warranty', 'summary' => 'Use the pricing and product-info guide before quoting coverage.', 'url' => $warrantyUrl, 'flag' => 'Verify'],
            ['title' => 'Get Specs', 'summary' => 'Use the guide to find specs, drawings, tech data, and BIM from the right source page.', 'url' => $warrantyUrl, 'flag' => 'Source'],
            ['title' => 'Install Guide', 'summary' => 'Use the guide to find install and training resources without skipping sales context.', 'url' => $warrantyUrl, 'flag' => 'Install'],
            ['title' => 'Pricing / Portal', 'summary' => 'Use dealer portals or rep-confirmed paths. Credentials stay in 1Password.', 'url' => $warrantyUrl, 'flag' => 'Login'],
            ['title' => 'Rep Contact', 'summary' => 'Use the hub guide when a deal needs source or rep confirmation.', 'url' => $howToUseUrl, 'flag' => 'Help'],
            ['title' => 'Request Update', 'summary' => 'Report stale links, missing files, unclear warranty, or bad product fit.', 'url' => $requestUpdateUrl, 'flag' => 'Fix'],
        ];

        $taskCards = '';
        foreach ($tasks as $task) {
            $taskCards .= <<<HTML
<a href="{$task['url']}" class="sotx-card sotx-link-card sotx-task-card">
    <div class="sotx-card-top">
        <strong>{$task['title']}</strong>
        <span class="sotx-flag">{$task['flag']}</span>
    </div>
    <span>{$task['summary']}</span>
</a>
HTML;
        }

        $laneCards = <<<HTML
<a href="{$residentialUrl}" class="sotx-card sotx-mini-card">
    <h4>Residential</h4>
    <p>Homeowner categories, sales context, and product/vendor routes.</p>
</a>
<a href="{$commercialUrl}" class="sotx-card sotx-mini-card">
    <h4>Commercial</h4>
    <p>Business, storefront, facility, and commercial project categories.</p>
</a>
<a href="{$vendorsUrl}" class="sotx-card sotx-mini-card">
    <h4>Source Library</h4>
    <p>Use after choosing a service path, when you need official vendor facts.</p>
</a>
HTML;

        return <<<HTML
<div class="sotx-home">
    <section class="sotx-panel sotx-hero">
        <div class="sotx-hero-copy">
            <div>
                <p class="sotx-kicker">Start Here</p>
                <h1>What do you need right now?</h1>
                <p class="sotx-lede" style="max-width:42rem;">
                    Pick the task that matches the sales conversation. The first path should be customer need and project type; source pages come in when you need proof, specs, warranty, pricing, or contacts.
                </p>
            </div>
        </div>
        <div class="sotx-stats">
            <div class="sotx-metric">
                <strong>Fast</strong>
                <span>Task-first lookup</span>
            </div>
            <div class="sotx-metric">
                <strong>Trusted</strong>
                <span>Vendor source pages</span>
            </div>
            <div class="sotx-metric">
                <strong>Current</strong>
                <span>Status-tagged resources</span>
            </div>
            <div class="sotx-metric">
                <strong>Clear</strong>
                <span>One owner per fact</span>
            </div>
        </div>
    </section>

    <section class="sotx-section">
        <div class="sotx-task-grid">{$taskCards}</div>
    </section>

    <section class="sotx-section">
        <div class="sotx-section-head">
            <div>
                <h2>Browse By Lane</h2>
                <p class="sotx-note" style="margin:.35rem 0 0;">Use these if you already know the project type, or need the source library after choosing a lane.</p>
            </div>
        </div>
        <div class="sotx-resource-grid">{$laneCards}</div>
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
                            ['html' => '<strong>System Categories</strong> under Start Here are the customer-need layer: Climate Control, Privacy Control, Patio Extension, Security and Safety, and Home Automation &amp; Control.'],
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
                            ['html' => '<strong>Customer describes an outcome:</strong> open <strong>System Categories</strong>, choose the need, then follow the internal links to a sales category or vendor/product page.'],
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
                            'Do not duplicate specs, warranties, contacts, dealer portals, install guides, or collateral on routing pages.',
                            'Do not promise warranty coverage without checking the exact product line, install conditions, and purchase date.',
                            'Do not use a vendor homepage as proof of warranty terms unless the vendor does not publish a public warranty page and the page says to confirm directly.',
                            'If a link looks stale, correct the vendor/product page in BookStack after verifying the official source or rep response.',
                        ],
                    ],
                    [
                        'title' => 'Page Jobs',
                        'summary' => 'Every page has one job so the hub stays easy to maintain.',
                        'points' => [
                            'System Category pages route by customer outcome; they do not hold full specs, warranty text, or portal credentials.',
                            'Residential and Commercial pages route by sales workflow and should link into vendor/product pages instead of repeating vendor documentation.',
                            'Vendor pages own contacts, official links, warranties, FAQs, dealer-portal references, training links, and collateral.',
                            'Product pages own product-line summaries, best-fit notes, related lines, quick links, and warranty or FAQ paths.',
                            'Start Here pages explain navigation, requests, source checks, and governance rather than storing product detail.',
                        ],
                    ],
                    [
                        'title' => 'Common Mistakes to Avoid',
                        'summary' => 'These are the patterns that make the hub harder to trust over time.',
                        'points' => [
                            'Do not create top-level books for specs, warranties, FAQs, training, or collateral.',
                            'Do not link a routing page straight to an external vendor PDF when a vendor/product page should own that link.',
                            'Do not repeat warranty, pricing, or spec details across multiple service pages.',
                            'Do not put usernames, passwords, recovery codes, or shared credentials in BookStack.',
                            'Do not rename the Vendors area to Products; Vendors is the canonical source-of-truth book.',
                            'Do not treat System Categories as content dumps; they are routing pages.',
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
                    [
                        'title' => 'Status Tags',
                        'summary' => 'Use tags to show what is reliable, gated, pending, or needs replacement.',
                        'points' => [
                            'Audited means the source is in the current audit scope and intentionally included.',
                            'Login Required means the resource is a portal, dealer app, pricing page, or protected source. Credentials stay in 1Password.',
                            'Pending means access, source replacement, or vendor confirmation is still needed.',
                            'Rep Confirm Needed means a vendor rep/contact path must be used before quoting or promising details.',
                            'Broken / Replace means the link failed clearly or points to a source that should be replaced.',
                        ],
                    ],
                    [
                        'title' => 'Credential Rule',
                        'summary' => 'BookStack can point to a protected resource, but it must not contain secrets.',
                        'points' => [
                            'Vendor pages may list dealer-portal URLs and the matching 1Password item name.',
                            'Actual usernames, passwords, recovery codes, and shared credentials live in 1Password only.',
                            'Keep login-gated resources visible but labeled Login Required so reps know the path exists.',
                            'Warranty, pricing, install, and certification details must be rep-confirmed before being quoted to a customer.',
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
                        'title' => 'Update Order',
                        'summary' => 'Use this order so rep updates land where the team can maintain them.',
                        'points' => [
                            'Edit the matching vendor or product page directly in BookStack.',
                            'Bump the last-verified note after checking the official source or rep response.',
                            'Update Residential and Commercial service pages only when positioning or routing changed.',
                            'Post major vendor changes, discontinuations, or urgent corrections to Recent Changes.',
                            'Update readme.md, docs/navigation.md, or the seeder only when the structure itself changed.',
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

        if ($pageName === 'Recent Changes') {
            $quickLinks = $this->buildStartHereBookLinks($books);

            return $this->buildScaffoldPageHtml(
                'Start Here',
                $pageName,
                $summary,
                [
                    [
                        'title' => 'What Belongs Here',
                        'summary' => 'Use this page for changes the whole team should notice, not routine source cleanup.',
                        'points' => [
                            'Product discontinuations or substitutions.',
                            'Major warranty, pricing, install, or certification changes.',
                            'New or changed vendor rep contact paths.',
                            'Urgent broken-link replacements when a customer-facing answer depends on the source.',
                        ],
                    ],
                    [
                        'title' => 'Entry Format',
                        'summary' => 'Keep updates short, dated, and tied back to the canonical page.',
                        'points' => [
                            'Date of change.',
                            'Vendor or product affected.',
                            'What changed and why reps should care.',
                            'Link to the vendor/product page that owns the full detail.',
                            'Owner or verifier for follow-up.',
                        ],
                    ],
                    [
                        'title' => 'Do Not Use This For',
                        'summary' => 'Routine edits should stay on the canonical page without creating announcement noise.',
                        'points' => [
                            'Small typo fixes.',
                            'Adding a normal product link that does not change sales positioning.',
                            'Private credentials or passwords.',
                            'Unverified rumors from a vendor or customer.',
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
        $parsedSourceBrands = $this->parsedSourceMarkdownBrands();
        $sourceBrands = array_unique(array_merge(
            array_keys($this->sourceRegistry()['brands']),
            array_keys($parsedSourceBrands)
        ));
        foreach ($sourceBrands as $brandName) {
            $brandConfig = $this->sourceRegistry()['brands'][$brandName] ?? [
                'summary' => 'Vendor source inventory is pending official link confirmation.',
                'links' => [],
            ];
            $links = '';
            foreach (($brandConfig['links'] ?? []) as $link) {
                $links .= '<a href="' . e($link['url']) . '" target="_blank" rel="noreferrer">' . e($link['label']) . '</a> ';
            }
            if ($links === '') {
                foreach ($this->extractSourceMarkdownLinks($parsedSourceBrands[$brandName] ?? []) as $link) {
                    $links .= '<a href="' . e($link['url']) . '" target="_blank" rel="noreferrer">' . e($link['label']) . '</a> ';
                }
            }
            if ($links === '') {
                $links = '<span>Pending official source link confirmation.</span>';
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
                        'Use System Categories when the customer describes a need or outcome instead of naming a product.',
                        'Use the vendor page first during normal sales lookup.',
                        'Use this page when a vendor link breaks, a customer asks for proof, or two pages disagree.',
                        'For day-to-day content, update the matching vendor/product page in BookStack after verifying the official source or rep response.',
                    ],
                ],
                [
                    'title' => 'Navigation Model',
                    'summary' => 'This is the path the hub is built around.',
                    'points' => [
                        'Customer Need -> System Category -> Residential/Commercial Category -> Source Reference',
                        'System and service pages route the team to the right place.',
                        'Vendor and product pages in BookStack own the facts that change over time.',
                    ],
                ],
                [
                    'title' => 'Status Tags',
                    'summary' => 'Use these labels to make link health and source confidence visible.',
                    'points' => [
                        'Audited: in current audit scope; treat as intentionally included.',
                        'Login Required: portal, dealer app, pricing, or protected resource. Credentials go in 1Password, not BookStack.',
                        'Pending: access, source replacement, or vendor confirmation still needed.',
                        'Rep Confirm Needed: use the vendor rep/contact path before quoting or promising details.',
                        'Broken / Replace: link failed clearly or points to a page that should be replaced.',
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
                        'Update the matching vendor/product page in BookStack first; that is the day-to-day content source of truth.',
                        'Bump the last-verified note after checking the official source or rep response.',
                        'Use docs/source.md and docs/products.md as seed/import references, audit inputs, or recovery data, not routine editing targets.',
                        'Update Residential or Commercial pages only if the change affects how we position or route a service category.',
                        'Leave a clear note when the vendor does not publish a public source and direct rep confirmation is required.',
                    ],
                ],
                [
                    'title' => 'Current Gaps and Roadmap',
                    'summary' => 'Gaps stay visible so the right owner can close them.',
                    'points' => [
                        'Rep contacts are the highest-value missing field; add regional rep name, phone, and email as they are collected.',
                        'Distributor collateral for Accent, Sunbelt, and related manufacturers remains Rep Confirm Needed until vendor-specific resources are confirmed.',
                        'Dallas Flat Glass dealer portal access is pending and should be followed up if no response is received.',
                        'Broken / Replace items in docs/link-audit.md should be replaced before customer-facing collateral depends on them.',
                        'Future additions may include an SOP / Playbook pillar, a change announcement feed, and last-verified stamps per vendor page.',
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

    protected function findPageUrl(array $pagesByBook, string $bookName, ?string $chapterName, string $pageName): ?string
    {
        $page = $pagesByBook[$bookName][$chapterName][$pageName] ?? null;

        return $page instanceof Page ? $page->getUrl() : null;
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
                    'summary' => 'Film vendors and distributors drive solar, privacy, safety, and decorative film pages.',
                    'sources' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison', 'Decorative Films', 'SolX', 'Frost', 'SmartTint'],
                ],
                'Residential / Window Treatments' => [
                    'summary' => 'Shade, blind, shutter, motorization, and safety shutter vendors drive treatment pages.',
                    'sources' => ['Somfy', 'Vantis', 'Hunter Douglas', 'Alta', 'Draper', 'Norman', 'Rollock Security Shutters'],
                ],
                'Residential / Outdoor Living' => [
                    'summary' => 'Patio extension, awning, shade, and solar screen vendors drive outdoor living pages.',
                    'sources' => ['Austin Screens', 'Draper', 'ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse'],
                ],
                'Residential / Glass & Windows' => [
                    'summary' => 'Window, door, storefront, and glass vendors inform the glass pages.',
                    'sources' => ['Old Castle / US Aluminum', 'Andersen', 'JELD-WEN', 'Pella', 'Dallas Flat Glass', 'CRL', 'Ghost Glass'],
                ],
                'Commercial / Solar Control & Safety' => [
                    'summary' => 'Commercial solar, privacy, safety, and decorative film vendors drive film pages.',
                    'sources' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison', 'Decorative Films', 'SolX', 'Frost', 'SmartTint'],
                ],
                'Commercial / Patio Screens & Awnings' => [
                    'summary' => 'Commercial patio extension, awning, shade, and screen vendors drive these pages.',
                    'sources' => ['Austin Screens', 'Draper', 'ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse'],
                ],
                'Commercial / Glass & Windows' => [
                    'summary' => 'Storefront, glazing, window, door, and glass vendors drive commercial glazing.',
                    'sources' => ['Old Castle / US Aluminum', 'Andersen', 'JELD-WEN', 'Pella', 'Dallas Flat Glass', 'CRL'],
                ],
                'Commercial / Window Treatments' => [
                    'summary' => 'Shade, motorization, and window treatment vendors drive commercial shade pages.',
                    'sources' => ['Somfy', 'Vantis', 'Hunter Douglas', 'Alta', 'Draper', 'Norman'],
                ],
                'CFO / Climate Control' => [
                    'summary' => 'CFO-defined vendor map for climate control.',
                    'sources' => ['Somfy', 'Vantis', 'Accent', '3M', 'Sunbelt', 'Avery Dennison', 'Alta', 'Hunter Douglas', 'Austin Screens', 'Draper'],
                ],
                'CFO / Privacy Control' => [
                    'summary' => 'CFO-defined vendor map for privacy control.',
                    'sources' => ['Decorative Films', 'SolX', 'Frost', 'Accent', '3M', 'Sunbelt', 'Avery Dennison', 'Alta', 'Hunter Douglas', 'Austin Screens', 'Draper'],
                ],
                'CFO / Patio Extension' => [
                    'summary' => 'CFO-defined vendor map for patio extension.',
                    'sources' => ['Old Castle / US Aluminum', 'Andersen', 'JELD-WEN', 'ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse'],
                ],
                'CFO / Security and Safety' => [
                    'summary' => 'CFO-defined vendor map for security and safety.',
                    'sources' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison', 'Rollock Security Shutters'],
                ],
                'CFO / Home Automation & Control' => [
                    'summary' => 'CFO-defined vendor map for automation and control.',
                    'sources' => ['Somfy', 'Vantis'],
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
                    ['name' => 'Recent Changes', 'summary' => 'Team-facing announcements for discontinuations, major source updates, and urgent corrections.'],
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

    protected function systemCategories(): array
    {
        return [
            [
                'name' => 'Climate Control',
                'summary' => 'Reduce heat, glare, and energy load.',
                'examples' => ['window film', 'motorized shades', 'solar screens', 'exterior shades', 'awning shade', 'energy load reduction'],
                'services' => [
                    ['book' => 'Residential', 'chapter' => 'Tint & Film', 'page' => 'Solar Film'],
                    ['book' => 'Residential', 'chapter' => 'Window Treatments', 'page' => 'Window Shades'],
                    ['book' => 'Residential', 'chapter' => 'Outdoor Living', 'page' => 'Patio Awnings'],
                    ['book' => 'Residential', 'chapter' => 'Outdoor Living', 'page' => 'Patio Screens'],
                    ['book' => 'Residential', 'chapter' => 'Glass & Windows', 'page' => 'Window Glass'],
                    ['book' => 'Commercial', 'chapter' => 'Solar Control & Safety', 'page' => 'Sun Control Film'],
                    ['book' => 'Commercial', 'chapter' => 'Patio Screens & Awnings', 'page' => 'Patio Screens'],
                    ['book' => 'Commercial', 'chapter' => 'Window Treatments', 'page' => 'Roller Shades'],
                ],
                'vendors' => ['Somfy', 'Vantis', 'Accent', '3M', 'Sunbelt', 'Avery Dennison', 'Alta', 'Hunter Douglas', 'Austin Screens', 'Draper'],
            ],
            [
                'name' => 'Privacy Control',
                'summary' => 'Create privacy without sacrificing design.',
                'examples' => ['decorative film', 'frosted film', 'solar screens', 'exterior shades', 'shutters', 'privacy shades'],
                'services' => [
                    ['book' => 'Residential', 'chapter' => 'Tint & Film', 'page' => 'Privacy Film'],
                    ['book' => 'Residential', 'chapter' => 'Window Treatments', 'page' => 'Window Shades'],
                    ['book' => 'Residential', 'chapter' => 'Window Treatments', 'page' => 'Window Shutters'],
                    ['book' => 'Residential', 'chapter' => 'Outdoor Living', 'page' => 'Patio Screens'],
                    ['book' => 'Commercial', 'chapter' => 'Solar Control & Safety', 'page' => 'Privacy Film'],
                    ['book' => 'Commercial', 'chapter' => 'Window Treatments', 'page' => 'Roller Shades'],
                    ['book' => 'Commercial', 'chapter' => 'Patio Screens & Awnings', 'page' => 'Patio Screens'],
                ],
                'vendors' => ['Decorative Films', 'SolX', 'Frost', 'Accent', '3M', 'Sunbelt', 'Avery Dennison', 'Alta', 'Hunter Douglas', 'Austin Screens', 'Draper'],
            ],
            [
                'name' => 'Patio Extension',
                'summary' => 'Extend indoor comfort into outdoor living.',
                'examples' => ['commercial storefront', 'patio doors', 'patio systems', 'shade systems', 'awnings', 'sunrooms'],
                'services' => [
                    ['book' => 'Residential', 'chapter' => 'Outdoor Living', 'page' => 'Shade Structures'],
                    ['book' => 'Residential', 'chapter' => 'Outdoor Living', 'page' => 'Patio Awnings'],
                    ['book' => 'Residential', 'chapter' => 'Outdoor Living', 'page' => 'Patio Screens'],
                    ['book' => 'Residential', 'chapter' => 'Glass & Windows', 'page' => 'Window Glass'],
                    ['book' => 'Commercial', 'chapter' => 'Patio Screens & Awnings', 'page' => 'Patio Awnings'],
                    ['book' => 'Commercial', 'chapter' => 'Patio Screens & Awnings', 'page' => 'Patio Screens'],
                    ['book' => 'Commercial', 'chapter' => 'Glass & Windows', 'page' => 'Commercial Glazing'],
                ],
                'vendors' => ['Old Castle / US Aluminum', 'Andersen', 'JELD-WEN', 'ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse'],
            ],
            [
                'name' => 'Security and Safety',
                'summary' => 'Protect people, property, and peace of mind.',
                'examples' => ['security film', 'safety film', 'security shutters', 'forced-entry delay', 'storm protection'],
                'services' => [
                    ['book' => 'Residential', 'chapter' => 'Tint & Film', 'page' => 'Safety & Security Film'],
                    ['book' => 'Residential', 'chapter' => 'Window Treatments', 'page' => 'Safety / Storm Shutters'],
                    ['book' => 'Commercial', 'chapter' => 'Solar Control & Safety', 'page' => 'Safety & Security Film'],
                    ['book' => 'Commercial', 'chapter' => 'Glass & Windows', 'page' => 'Commercial Glazing'],
                ],
                'vendors' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison', 'Rollock Security Shutters'],
            ],
            [
                'name' => 'Home Automation & Control',
                'summary' => 'Automate comfort, light, shade, and privacy.',
                'examples' => ['motorized shades', 'smart controls', 'shade automation', 'remote control', 'scheduled scenes'],
                'services' => [
                    ['book' => 'Residential', 'chapter' => 'Window Treatments', 'page' => 'Window Shades'],
                    ['book' => 'Residential', 'chapter' => 'Outdoor Living', 'page' => 'Patio Awnings'],
                    ['book' => 'Commercial', 'chapter' => 'Window Treatments', 'page' => 'Roller Shades'],
                    ['book' => 'Commercial', 'chapter' => 'Patio Screens & Awnings', 'page' => 'Patio Awnings'],
                ],
                'vendors' => ['Somfy', 'Vantis'],
            ],
        ];
    }

    protected function buildSystemCategoriesPageHtml(array $systemCategoryPages): string
    {
        $cards = '';
        foreach ($this->systemCategories() as $systemCategory) {
            $page = $systemCategoryPages[$systemCategory['name']] ?? null;
            $examples = '';
            foreach (array_slice($systemCategory['examples'], 0, 6) as $example) {
                $examples .= '<span class="sotx-chip">' . e($example) . '</span>';
            }

            $href = $page instanceof Page ? $page->getUrl() : '#';
            $cards .= <<<HTML
<a href="{$href}" class="sotx-card sotx-service-card">
    <div class="sotx-card-top">
        <div>
            <h4>{$systemCategory['name']}</h4>
            <p>{$systemCategory['summary']}</p>
        </div>
        <span class="sotx-flag">Open</span>
    </div>
    <div class="sotx-chip-list">{$examples}</div>
</a>
HTML;
        }

        return $this->buildScaffoldPageHtml(
            'Start Here',
            'System Categories',
            'Start with the customer need, then choose the Residential or Commercial sales category that matches the project.',
            [
                [
                    'title' => 'How This Layer Works',
                    'summary' => 'System categories are routing pages, not document libraries.',
                    'points' => [
                        'Use them when a customer describes the outcome they want rather than a specific product.',
                        'Open the matching Residential or Commercial category for sales context before jumping to source documents.',
                        'Use vendor and product pages only when the service page sends you there for specs, warranties, install guides, training, collateral, contacts, or portal references.',
                    ],
                ],
                [
                    'title' => 'Canonical Path',
                    'summary' => 'This keeps the hub easy to maintain as vendors, products, and sources change.',
                    'points' => [
                        'Customer Need -> System Category -> Residential/Commercial Category -> Source Reference',
                        'Do not duplicate vendor facts on routing pages.',
                        'When a vendor source changes, update the vendor/product page first.',
                    ],
                ],
            ],
            <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Choose the Customer Need</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Each card opens a routing page with Residential and Commercial next steps.</p>
        </div>
    </div>
    <div class="sotx-service-grid" style="margin-top:1rem;">{$cards}</div>
</section>
HTML
        );
    }

    protected function buildSystemCategoryPageHtml(array $systemCategory, array $pagesByBook, array $vendorPages): string
    {
        $examples = '';
        foreach ($systemCategory['examples'] as $example) {
            $examples .= '<span class="sotx-chip">' . e($example) . '</span>';
        }

        $serviceLinks = $this->buildSystemServiceLinksHtml($systemCategory['services'], $pagesByBook);
        $vendorLinks = $this->buildSystemVendorChipsHtml($systemCategory['vendors'], $vendorPages);

        $leadContent = <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Common Examples</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Use these as recognition cues when a customer describes the need in plain language.</p>
        </div>
    </div>
    <div class="sotx-chip-list" style="margin-top:.65rem;">{$examples}</div>
</section>

<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Sales Categories</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Choose one of these first. They carry the sales positioning, job context, and next-step guidance.</p>
        </div>
    </div>
    <div class="sotx-actions" style="margin-top:.55rem;">{$serviceLinks}</div>
</section>

<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Source References</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">These are supporting sources, not the primary route. Use them after a service page tells you which line matters.</p>
        </div>
    </div>
    <div class="sotx-chip-list" style="margin-top:.55rem;">{$vendorLinks}</div>
</section>
HTML;

        return $this->buildScaffoldPageHtml(
            'System Category',
            $systemCategory['name'],
            $systemCategory['summary'],
            [
                [
                    'title' => 'How to Use This Page',
                    'summary' => 'Start here when the customer need is clear but the product path is not.',
                    'points' => [
                        'Choose a sales category first for positioning, job fit, and the correct Residential or Commercial workflow.',
                        'Use source references only when you need specs, warranty, installation, training, collateral, pricing, or rep confirmation.',
                        'Keep new details on the vendor or product page, then link here only when the route changes.',
                    ],
                ],
            ],
            $leadContent
        );
    }

    protected function buildSystemServiceLinksHtml(array $serviceLinks, array $pagesByBook): string
    {
        $html = '';
        foreach ($serviceLinks as $serviceLink) {
            $page = $pagesByBook[$serviceLink['book']][$serviceLink['chapter']][$serviceLink['page']] ?? null;
            if (!$page instanceof Page) {
                continue;
            }

            $label = $serviceLink['book'] . ' / ' . $serviceLink['chapter'] . ' / ' . $serviceLink['page'];
            $html .= '<a href="' . e($page->getUrl()) . '" class="sotx-pill">' . e($label) . '</a>';
        }

        return $html;
    }

    protected function buildSystemVendorLinksHtml(array $vendorNames, array $vendorPages): string
    {
        $html = '';
        foreach ($vendorNames as $vendorName) {
            $page = $vendorPages[$vendorName] ?? null;
            if (!$page instanceof Page) {
                continue;
            }

            $html .= '<a href="' . e($page->getUrl()) . '" class="sotx-pill">' . e($vendorName) . '</a>';
        }

        return $html;
    }

    protected function buildSystemVendorChipsHtml(array $vendorNames, array $vendorPages): string
    {
        $html = '';
        foreach ($vendorNames as $vendorName) {
            $page = $vendorPages[$vendorName] ?? null;
            if (!$page instanceof Page) {
                $html .= '<span class="sotx-chip">' . e($vendorName) . '</span>';
                continue;
            }

            $html .= '<a href="' . e($page->getUrl()) . '" class="sotx-chip sotx-chip-link">' . e($vendorName) . '</a>';
        }

        return $html;
    }

    protected function serviceCatalog(): array
    {
        return [
            'Residential' => [
                'chapters' => [
                    'Tint & Film' => [
                        'description' => 'Residential tint and film solutions.',
                        'pages' => [
                            ['name' => 'Safety & Security Film', 'summary' => 'What residential safety film solves and how to position it.', 'products' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'Solar Film', 'summary' => 'Energy and glare control film for homes.', 'products' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'Privacy Film', 'summary' => 'Film options that improve privacy without changing the room.', 'products' => ['Decorative Films', 'SolX', 'Frost', 'Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                        ],
                    ],
                    'Window Treatments' => [
                        'description' => 'Shades, blinds, shutters, and motorized treatment options.',
                        'pages' => [
                            ['name' => 'Window Shades', 'summary' => 'Shades that solve light control and privacy needs.', 'products' => ['Somfy', 'Vantis', 'Hunter Douglas', 'Alta', 'Draper', 'Norman']],
                            ['name' => 'Window Shutters', 'summary' => 'Shutter materials, finishes, and use cases.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                            ['name' => 'Window Blinds', 'summary' => 'Blind options for homeowners and designers.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                            ['name' => 'Safety / Storm Shutters', 'summary' => 'Protective shutter options for the home.', 'products' => ['Rollock Security Shutters', 'Norman']],
                        ],
                    ],
                    'Outdoor Living' => [
                        'description' => 'Awnings, shade structures, and exterior coverage solutions.',
                        'pages' => [
                            ['name' => 'Shade Structures', 'summary' => 'What residential shade structures solve and how to position them.', 'products' => ['ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse']],
                            ['name' => 'Patio Awnings', 'summary' => 'Retractable and fixed awning options.', 'products' => ['ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse']],
                            ['name' => 'Patio Screens', 'summary' => 'Screens and exterior comfort options.', 'products' => ['Austin Screens', 'Draper', 'ShadePro Shade Systems', 'Eclipse']],
                        ],
                    ],
                    'Glass & Windows' => [
                        'description' => 'Glass replacement, window replacement, and related service references.',
                        'pages' => [
                            ['name' => 'Window Glass', 'summary' => 'Glass replacement references and service notes.', 'products' => ['Old Castle / US Aluminum', 'Andersen', 'JELD-WEN', 'Pella', 'Dallas Flat Glass']],
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
                            ['name' => 'Sun Control Film', 'summary' => 'Energy and glare control for commercial properties.', 'products' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'Safety & Security Film', 'summary' => 'Protective film for businesses and storefronts.', 'products' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'Privacy Film', 'summary' => 'Privacy and glare management for commercial spaces.', 'products' => ['Decorative Films', 'SolX', 'Frost', 'Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'SmartTint', 'summary' => 'Switchable privacy glass and film references.', 'products' => ['SmartTint']],
                        ],
                    ],
                    'Patio Screens & Awnings' => [
                        'description' => 'Commercial exterior shade and protection systems.',
                        'pages' => [
                            ['name' => 'Patio Awnings', 'summary' => 'Commercial awning and shade references.', 'products' => ['ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse']],
                            ['name' => 'Patio Screens', 'summary' => 'Screen and exterior comfort solutions.', 'products' => ['Austin Screens', 'Draper', 'ShadePro Shade Systems', 'Eclipse']],
                        ],
                    ],
                    'Glass & Windows' => [
                        'description' => 'Commercial glass replacement, glazing, and storefront references.',
                        'pages' => [
                            ['name' => 'Commercial Glazing', 'summary' => 'Storefront and glazing references.', 'products' => ['Old Castle / US Aluminum', 'Andersen', 'JELD-WEN', 'CRL', 'Pella', 'Dallas Flat Glass']],
                        ],
                    ],
                    'Window Treatments' => [
                        'description' => 'Commercial shades and motorized systems.',
                        'pages' => [
                            ['name' => 'Roller Shades', 'summary' => 'Commercial shade systems and project references.', 'products' => ['Somfy', 'Vantis', 'Hunter Douglas', 'Alta', 'Draper', 'Norman']],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function buildServiceCategoryHtml(string $serviceName, string $chapterName, string $categoryName, string $summary, array $productNames, array $productPages, ?Book $vendorsBook = null): string
    {
        $productsHtml = $this->buildProductChipsHtml($productNames, $productPages);
        $sourceLibraryLink = $vendorsBook
            ? '<a href="' . e($vendorsBook->getUrl()) . '" class="sotx-pill">Open Source Library</a>'
            : '';

        $sourceSection = '';
        if ($productsHtml !== '') {
            $sourceSection = <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Source References</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Use these after the project fit is clear. They hold specs, warranty paths, install guides, collateral, pricing references, and rep contacts.</p>
        </div>
        {$sourceLibraryLink}
    </div>
    {$productsHtml}
</section>
HTML;
        }

        return <<<HTML
<div class="sotx-home">
    <section class="sotx-panel sotx-card sotx-section-card">
        <p class="sotx-kicker">{$serviceName} / {$chapterName}</p>
        <h1>{$categoryName}</h1>
        <p class="sotx-lede">{$summary}</p>
    </section>

    <section class="sotx-section">
        <div class="sotx-service-grid">
            <section class="sotx-card sotx-mini-card sotx-template-block">
                <h4>Use This Page For</h4>
                <p class="sotx-note" style="margin-top:.65rem;">Project-fit and sales-context decisions before choosing a vendor source.</p>
                <ul class="sotx-template-list">
                    <li>Confirm whether this category matches the customer's stated need.</li>
                    <li>Use the linked source references only after this sales path fits the job.</li>
                    <li>Keep warranty, pricing, spec, install, and portal details on source pages.</li>
                </ul>
            </section>
            <section class="sotx-card sotx-mini-card sotx-template-block">
                <h4>Rep Flow</h4>
                <p class="sotx-note" style="margin-top:.65rem;">Stay in the sales lane until a specific source is needed.</p>
                <ul class="sotx-template-list">
                    <li>Need/problem first.</li>
                    <li>{$serviceName} context second.</li>
                    <li>{$categoryName} positioning third.</li>
                    <li>Source reference last.</li>
                </ul>
            </section>
        </div>
    </section>

    {$sourceSection}
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
        $warrantyLinksHtml = $this->renderLinkPills($warrantyLinks, 'Warranty Information');
        $warrantyLinksHtml .= '<p class="sotx-note" style="margin:.65rem 0 0;">' . e($this->buildWarrantyReminder($brandName)) . '</p>';

        if ($warrantyNote !== '') {
            $warrantyLinksHtml .= '<p class="sotx-note" style="margin:.65rem 0 0;">' . e($warrantyNote) . '</p>';
        }

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

        $faqLinks = $this->buildVendorFaqLinks($brandName);
        $faqLinksHtml = $this->renderLinkPills($faqLinks, 'FAQs');
        if ($faqLinksHtml === '') {
            $faqLinksHtml = '<p class="sotx-note" style="margin:.65rem 0 0;">No public FAQ page is listed for this vendor yet. Confirm product questions directly with the rep before quoting.</p>';
        }

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

        if ($sourceLinks === '') {
            $sourceLinks = '<span class="sotx-note">No public quick link is listed yet. Use the Source Inventory and rep contact path below until an official source is confirmed.</span>';
        }

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
    <div class="sotx-status-row">
        <span class="sotx-status sotx-status-audited">Audited</span>
        <span class="sotx-status sotx-status-rep">Rep Confirm Needed</span>
    </div>
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
        $vendorHubHtml = '';
        if (!empty($vendorSource)) {
            $vendorHubHtml = '<a href="' . e($vendorSource) . '" class="sotx-pill" target="_blank" rel="noreferrer">Vendor Website</a>';
        } else {
            $vendorHubHtml = '<span class="sotx-note">No public vendor website is listed for this line yet. Use the vendor overview Source Inventory and rep contact path.</span>';
        }

        $leadContent .= <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Vendor Hub</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Reference the vendor hub when you need broader context for the product line.</p>
        </div>
    </div>
    <div class="sotx-actions" style="margin-top:.55rem;">
        {$vendorHubHtml}
    </div>
</section>
HTML;

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
        return (string) ($this->sourceRegistry()['brands'][$brandName]['warranty_note'] ?? 'No public warranty page is listed for this vendor yet. Confirm warranty terms directly with the vendor or distributor rep before quoting coverage.');
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
            $rawSectionName = $sectionName;
            $sectionName = e($sectionName);
            $statusHtml = $this->renderResourceStatusTags($rawSectionName, $items);
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
            {$statusHtml}
        </div>
        <span class="sotx-flag">Resource</span>
    </div>
    <ul class="sotx-template-list">
        {$listItems}
    </ul>
</section>
HTML;
        }

        $legend = $this->renderAuditStatusLegend();

        return <<<HTML
<section class="sotx-section">
    <div class="sotx-section-head">
        <div>
            <h2>Source Inventory / Sales Resource Inventory</h2>
            <p class="sotx-note" style="margin:.35rem 0 0;">Use these sections for install guides, portals, specs, samples, pricing, training, collateral, warranty, FAQs, and rep contact paths.</p>
        </div>
    </div>
    {$legend}
    <div class="sotx-service-grid">
        {$sectionHtml}
    </div>
</section>
HTML;
    }

    protected function renderAuditStatusLegend(): string
    {
        return <<<HTML
<div class="sotx-status-row" style="margin:.25rem 0 1rem;">
    <span class="sotx-status sotx-status-audited">Audited</span>
    <span class="sotx-status sotx-status-login">Login Required</span>
    <span class="sotx-status sotx-status-pending">Pending</span>
    <span class="sotx-status sotx-status-rep">Rep Confirm Needed</span>
    <span class="sotx-status sotx-status-broken">Broken / Replace</span>
</div>
HTML;
    }

    protected function renderResourceStatusTags(string $sectionName, array $items): string
    {
        $haystack = strtolower($sectionName . ' ' . implode(' ', $items));
        $statuses = ['Audited' => 'audited'];

        if (str_contains($haystack, 'login') || str_contains($haystack, 'portal') || str_contains($haystack, 'dealer') || str_contains($haystack, 'pricing')) {
            $statuses['Login Required'] = 'login';
        }

        if (str_contains($haystack, 'pending') || str_contains($haystack, 'awaiting') || str_contains($haystack, 'requested')) {
            $statuses['Pending'] = 'pending';
        }

        if (str_contains($haystack, 'add rep') || str_contains($haystack, 'contact your rep') || str_contains($haystack, 'confirm') || str_contains($haystack, 'directly')) {
            $statuses['Rep Confirm Needed'] = 'rep';
        }

        if (str_contains($haystack, 'broken') || str_contains($haystack, 'replace')) {
            $statuses['Broken / Replace'] = 'broken';
        }

        $html = '<div class="sotx-status-row">';
        foreach ($statuses as $label => $className) {
            $html .= '<span class="sotx-status sotx-status-' . e($className) . '">' . e($label) . '</span>';
        }
        $html .= '</div>';

        return $html;
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

    protected function extractSourceMarkdownLinks(array $sections): array
    {
        $links = [];
        $seen = [];

        foreach ($sections as $items) {
            foreach ($items as $item) {
                if (!preg_match_all('/\[(.*?)\]\((https?:\/\/[^)]+)\)/', $item, $matches, PREG_SET_ORDER)) {
                    continue;
                }

                foreach ($matches as $match) {
                    $url = $match[2];
                    if (isset($seen[$url])) {
                        continue;
                    }

                    $seen[$url] = true;
                    $links[] = [
                        'label' => $match[1],
                        'url'   => $url,
                    ];

                    if (count($links) >= 5) {
                        return $links;
                    }
                }
            }
        }

        return $links;
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
        $systemCards = '';
        foreach ($this->systemCategories() as $systemCategory) {
            $page = $pagesByBook['Start Here'][null][$systemCategory['name']] ?? null;
            if (!$page instanceof Page) {
                continue;
            }

            $items = '';
            foreach (array_slice($systemCategory['examples'], 0, 5) as $example) {
                $items .= '<span class="sotx-chip">' . e($example) . '</span>';
            }

            $systemCards .= <<<HTML
<a href="{$page->getUrl()}" class="sotx-card sotx-service-card">
    <div class="sotx-card-top">
        <div>
            <h4>{$systemCategory['name']}</h4>
            <p>{$systemCategory['summary']}</p>
        </div>
        <span class="sotx-flag">Open</span>
    </div>
    <div class="sotx-chip-list">{$items}</div>
</a>
HTML;
        }

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

        $startHerePage = $pagesByBook['Start Here'][null]['Start Here'] ?? null;
        $startHereUrl = $startHerePage instanceof Page ? $startHerePage->getUrl() : $books['Start Here']->getUrl();

        $quickLinks = '';
        foreach ([
            ['url' => $startHereUrl, 'label' => 'Start Here'],
            ['url' => $books['Residential']->getUrl(), 'label' => 'Residential'],
            ['url' => $books['Commercial']->getUrl(), 'label' => 'Commercial'],
        ] as $link) {
            $quickLinks .= '<a href="' . e($link['url']) . '" class="sotx-pill">' . e($link['label']) . '</a>';
        }

        $vendorsBook = $books['Vendors'];
        $sourceTruthUrl = $this->findPageUrl($pagesByBook, 'Start Here', null, 'Source of Truth') ?? $startHereUrl;

        $supportCards = '';
        foreach ([
            ['url' => $startHereUrl, 'title' => 'Start Here', 'description' => 'Task buttons for sales-call lookup, updates, portals, and source checks.'],
            ['url' => $sourceTruthUrl, 'title' => 'Source of Truth', 'description' => 'Use when a link, claim, warranty, or vendor source needs verification.'],
            ['url' => $vendorsBook->getUrl(), 'title' => 'Source Library', 'description' => 'Brand-level pages for official specs, warranties, portals, contacts, and collateral.'],
        ] as $supportCard) {
            $supportCards .= <<<HTML
<a href="{$supportCard['url']}" class="sotx-card sotx-mini-card">
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
                    Start with the customer need, choose Residential or Commercial context, then open source references only when the answer needs proof.
                    Built so the team can stay in the sales workflow instead of falling straight into vendor pages.
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
                <strong>5 Systems</strong>
                <span>Customer needs</span>
            </div>
            <div class="sotx-metric">
                <strong>Sources</strong>
                <span>When needed</span>
            </div>
        </div>
    </section>

    <section class="sotx-section">
        <div class="sotx-section-head">
            <div>
                <h2>System Categories</h2>
                <p class="sotx-note" style="margin:.35rem 0 0;">Start here when the customer describes the outcome they want.</p>
            </div>
        </div>
        <div class="sotx-service-grid" style="margin-top:1rem;">
            {$systemCards}
        </div>
    </section>

    <section class="sotx-section">
        <div class="sotx-section-head">
            <div>
                <h2>Service Areas</h2>
                <p class="sotx-note" style="margin:.35rem 0 0;">Choose Residential or Commercial when the project type is already clear.</p>
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

    <section class="sotx-section sotx-panel sotx-section-card">
        <div class="sotx-card-top">
            <div>
                <p class="sotx-kicker">Workflow</p>
                <h2>Need first. Vendor facts last.</h2>
                <p class="sotx-note" style="margin:.35rem 0 0;">Use categories to route the conversation. Open source references only for specs, warranties, install guides, collateral, contacts, and official links.</p>
            </div>
            <a href="{$startHereUrl}" class="sotx-pill">Open Task Buttons</a>
        </div>
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
                    ['name' => 'Recent Changes', 'summary' => 'Team-facing announcements for discontinuations, major source updates, and urgent corrections.'],
                ],
            ],
            'Residential' => [
                'description' => 'Residential sales resources organized by the categories the team actually sells every day.',
                'chapters' => [
                    'Tint & Film' => [
                        'description' => 'Residential tint and film solutions.',
                        'pages' => [
                            ['name' => 'Safety & Security Film', 'summary' => 'What residential safety film solves and how to position it.', 'details' => 'Use the four sections below to keep the sales rep on track for this film type.', 'products' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'Solar Film', 'summary' => 'Energy and glare control film for homes.', 'details' => 'Use the four sections below to compare solar-control film options.', 'products' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'Privacy Film', 'summary' => 'Film options that improve privacy without changing the room.', 'details' => 'Use the four sections below for privacy film positioning and references.', 'products' => ['Decorative Films', 'SolX', 'Frost', 'Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                        ],
                    ],
                    'Window Treatments' => [
                        'description' => 'Shades, blinds, shutters, and motorized treatment options.',
                        'pages' => [
                            ['name' => 'Window Shades', 'summary' => 'Shades that solve light control and privacy needs.', 'details' => 'Use the four sections below for residential shade solutions.', 'products' => ['Somfy', 'Vantis', 'Hunter Douglas', 'Alta', 'Draper', 'Norman']],
                            ['name' => 'Window Shutters', 'summary' => 'Shutter materials, finishes, and use cases.', 'details' => 'Use the four sections below for shutter options and comparisons.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                            ['name' => 'Window Blinds', 'summary' => 'Blind options for homeowners and designers.', 'details' => 'Use the four sections below for blind products and selling points.', 'products' => ['Hunter Douglas', 'Alta', 'Norman']],
                            ['name' => 'Safety / Storm Shutters', 'summary' => 'Protective shutter options for the home.', 'details' => 'Use the four sections below for storm and safety shutter references.', 'products' => ['Rollock Security Shutters', 'Norman']],
                        ],
                    ],
                    'Outdoor Living' => [
                        'description' => 'Awnings, shade structures, and exterior coverage solutions.',
                        'pages' => [
                            ['name' => 'Shade Structures', 'summary' => 'What residential shade structures solve and how to position them.', 'details' => 'Use the four sections below for outdoor shade structures.', 'products' => ['ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse']],
                            ['name' => 'Patio Awnings', 'summary' => 'Retractable and fixed awning options.', 'details' => 'Use the four sections below for awning options and sales notes.', 'products' => ['ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse']],
                            ['name' => 'Patio Screens', 'summary' => 'Screens and exterior comfort options.', 'details' => 'Use the four sections below for patio screen solutions.', 'products' => ['Austin Screens', 'Draper', 'ShadePro Shade Systems', 'Eclipse']],
                        ],
                    ],
                    'Glass & Windows' => [
                        'description' => 'Glass replacement, window replacement, and related service references.',
                        'pages' => [
                            ['name' => 'Window Glass', 'summary' => 'Glass replacement references and service notes.', 'details' => 'Use the four sections below for glass replacement projects.', 'products' => ['Old Castle / US Aluminum', 'Andersen', 'JELD-WEN', 'Dallas Flat Glass', 'Ghost Glass']],
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
                            ['name' => 'Sun Control Film', 'summary' => 'Energy and glare control for commercial properties.', 'details' => 'Use the four sections below for commercial sun-control film.', 'products' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'Safety & Security Film', 'summary' => 'Protective film for businesses and storefronts.', 'details' => 'Use the four sections below for commercial safety film.', 'products' => ['Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'Privacy Film', 'summary' => 'Privacy and glare management for commercial spaces.', 'details' => 'Use the four sections below for commercial privacy film.', 'products' => ['Decorative Films', 'SolX', 'Frost', 'Accent', '3M', 'Sunbelt', 'Avery Dennison']],
                            ['name' => 'SmartTint', 'summary' => 'Switchable privacy glass and film references.', 'details' => 'Use the four sections below for switchable privacy solutions.', 'products' => ['SmartTint']],
                        ],
                    ],
                    'Patio Screens & Awnings' => [
                        'description' => 'Commercial exterior shade and protection systems.',
                        'pages' => [
                            ['name' => 'Patio Awnings', 'summary' => 'Commercial awning and shade references.', 'details' => 'Use the four sections below for commercial awning work.', 'products' => ['ShadePro Shade Systems', 'Four Seasons Patio Systems', 'Eclipse']],
                            ['name' => 'Patio Screens', 'summary' => 'Screen and exterior comfort solutions.', 'details' => 'Use the four sections below for commercial patio screen work.', 'products' => ['Austin Screens', 'Draper', 'ShadePro Shade Systems', 'Eclipse']],
                        ],
                    ],
                    'Glass & Windows' => [
                        'description' => 'Commercial glass replacement, glazing, and storefront references.',
                        'pages' => [
                            ['name' => 'Commercial Glazing', 'summary' => 'Storefront and glazing references.', 'details' => 'Use the four sections below for commercial glazing projects.', 'products' => ['Old Castle / US Aluminum', 'Andersen', 'JELD-WEN', 'CRL', 'Dallas Flat Glass', 'Ghost Glass']],
                        ],
                    ],
                    'Window Treatments' => [
                        'description' => 'Commercial shades and motorized systems.',
                        'pages' => [
                            ['name' => 'Roller Shades', 'summary' => 'Commercial shade systems and project references.', 'details' => 'Use the four sections below for commercial roller shades.', 'products' => ['Somfy', 'Vantis', 'Hunter Douglas', 'Alta', 'Draper', 'Norman']],
                        ],
                    ],
                ],
            ],
            'Vendors' => [
                'description' => 'Manufacturer and product-line reference pages for the products Shades of Texas carries.',
                'chapters' => [
                    '3M' => ['description' => '3M product reference and sales support.'],
                    'Accent' => ['description' => 'Accent distributor reference for 3M film resources.'],
                    'Sunbelt' => ['description' => 'Sunbelt distributor reference for Avery Dennison film resources.'],
                    'Avery Dennison' => ['description' => 'Avery Dennison architectural film product reference and sales support.'],
                    'Somfy' => ['description' => 'Somfy motorization and automation product reference and sales support.'],
                    'Vantis' => ['description' => 'Vantis automation and control product reference and sales support.'],
                    'Hunter Douglas' => ['description' => 'Hunter Douglas product reference and sales support.'],
                    'Alta' => ['description' => 'Alta product reference and sales support.'],
                    'Norman' => ['description' => 'Norman product reference and sales support.'],
                    'Eclipse' => ['description' => 'Eclipse product reference and sales support.'],
                    'Austin Screens' => ['description' => 'Austin Screens solar screen product reference and sales support.'],
                    'Draper' => ['description' => 'Draper shade, screen, and solar control product reference and sales support.'],
                    'Decorative Films' => ['description' => 'Decorative Films privacy and decorative film product reference and sales support.'],
                    'SolX' => ['description' => 'SolX decorative and privacy film product reference and sales support.'],
                    'Frost' => ['description' => 'Frost privacy film product reference and sales support.'],
                    'Old Castle / US Aluminum' => ['description' => 'Old Castle / US Aluminum storefront and commercial glazing product reference.'],
                    'ShadePro Shade Systems' => ['description' => 'ShadePro shade system product reference and sales support.'],
                    'Four Seasons Patio Systems' => ['description' => 'Four Seasons patio and sunroom system product reference and sales support.'],
                    'Rollock Security Shutters' => ['description' => 'Rollock security shutter product reference and sales support.'],
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
