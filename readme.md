# Shades of Texas Sales Hub

The internal knowledge base for the Shades of Texas sales, operations, and supplier teams.

- Last updated: 2026-06-02
- Platform: BookStack, self-hosted; structure seeded via Laravel
- Status: Active build-out; core structure complete, vendor content and rep contacts in progress

This repository customizes BookStack for the Shades of Texas Sales Hub. For upstream BookStack documentation, see [bookstackapp.com/docs](https://www.bookstackapp.com/docs).

## What This Is

This repository powers a BookStack-based knowledge base that gives the sales team fast, reliable access to product lines, vendor contacts, specs, warranties, dealer portals, training resources, and sales context without hunting across vendor websites or relying on word of mouth.

It exists to solve three recurring problems:

- **Finding the truth fast:** a rep on a call should reach the right product page, spec sheet, or rep contact in a few clicks.
- **Correcting the truth easily:** anyone can flag or request a change when they spot stale or missing information.
- **Maintaining the truth over time:** vendor and product facts live in exactly one canonical place, so an update happens once and propagates everywhere through internal links.

The goal is not to duplicate every vendor website. It is to build a clean internal map that always points to the right answer and keeps the authoritative copy in one place.

## Who This Is For

| Audience | What they get from the hub |
| --- | --- |
| Sales reps | Fast routing from a customer's need to the right product, spec, warranty, or rep contact. |
| Operations | A single reference for vendor relationships, portals, and pending gaps. |
| Supplier / partner owners | A structured place to add new vendors, rep contacts, and collateral as partnerships mature. |
| Admins / maintainers | A documented governance model for editing, link health, and source-of-truth updates. |
| New hires | A guided Start Here path and a clear mental model of how everything is organized. |

## Core Navigation Model

Everything in the hub follows one path, from how a customer describes a need to the official vendor source:

```text
Customer Need
  -> System Category            (the need, in the customer's words)
    -> Residential / Commercial (the sales workflow)
      -> Product / Vendor Page  (the source of truth)
        -> Source Reference     (vendor link, warranty, install guide, portal)
```

The hub should feel like a practical sales map, not a file cabinet.

Design principles:

- Start with the way customers describe the need, not the way vendors organize catalogs.
- Keep top-level navigation simple: four books, no more.
- Prefer internal links before external links.
- Keep vendor and product pages in BookStack as the single source of truth for day-to-day content.
- Never duplicate specs, warranties, contacts, portal links, install guides, or collateral across pages.
- Make gaps visible so sales, ops, and supplier owners can fill them over time.

## Repository Structure

The `docs/` folder defines structure, routing, and seed/import data. The live BookStack site owns day-to-day vendor and product content.

```text
docs/
+-- readme.md        # Project overview, model, and governance
+-- navigation.md    # Structural source of truth: IA, page jobs, click paths, linking rules
+-- source.md        # Vendor source seed/import map and audit reference
+-- products.md      # Product inventory seed/import data feeding the Vendors book
+-- link-audit.md    # External-link health: status, login gates, broken/replace, review items

database/
+-- seeders/
    +-- ShadesOfTexasStructureSeeder.php   # Programmatically builds the BookStack hierarchy
```

| File | Role | Authority |
| --- | --- | --- |
| `docs/navigation.md` | Defines how the hub is organized and how people move through it. | Structural source of truth. |
| `docs/source.md` | Seed/import map of where each vendor's official info started. | Reference data for bulk seeding and audits. |
| `docs/products.md` | Product-line inventory used to seed vendor product pages. | Seed/import data, not the day-to-day editing target. |
| `docs/link-audit.md` | Tracks external-link health and flags what needs replacing, login, or rep confirmation. | Link-health source of truth. |
| `database/seeders/ShadesOfTexasStructureSeeder.php` | Builds the BookStack books, chapters, and pages from the structure above. | Deployment mechanism. |

Ownership rule: structure and routing live in code; vendor and product content lives in BookStack. Routine content edits happen directly on the relevant BookStack vendor or product page. Update `readme.md`, `docs/navigation.md`, or the seeder only when the structure or routing model changes.

## BookStack Architecture

BookStack has four nesting layers. The hub maps onto them like this:

| BookStack layer | Hub name | Purpose |
| --- | --- | --- |
| Bookshelf | High Level | Single top-level container for the whole hub. |
| Book | Start Here, Residential, Commercial, Vendors | Main navigation areas. |
| Chapter | Tint & Film, Window Treatments, vendor names, etc. | Grouping layer inside a book. |
| Page | Climate Control, Window Glass, Andersen, etc. | Actual content and routing pages. |

Only four books appear as operational top-level areas:

```text
High Level
+-- Start Here     # Usage, governance, and customer-need routing
+-- Residential    # Sales-workflow pages for homeowner projects
+-- Commercial     # Sales-workflow pages for business / storefront / facility projects
+-- Vendors        # Canonical vendor and product source of truth
```

Do not create separate top-level books for specs, warranties, FAQs, training, or collateral. Those resources live on the relevant vendor or product page and are reached through internal links.

## System Categories

The five System Categories live under Start Here and are the first routing layer. They are navigation pages, not content dumps. They summarize the need, list common vendor examples, and link into Residential, Commercial, and Vendor pages.

| System Category | What the customer wants | Example vendors |
| --- | --- | --- |
| Climate Control | Reduce heat, glare, and energy load. | Somfy, Vantis, Accent/3M, Sunbelt/Avery Dennison, Alta, Hunter Douglas, Austin Screens, Draper |
| Privacy Control | Privacy without sacrificing design. | Decorative Films, SolX, Frost, Accent/3M, Sunbelt/Avery Dennison, Alta, Hunter Douglas, Austin Screens, Draper |
| Patio Extension | Extend indoor comfort outdoors. | Old Castle / US Aluminum, Andersen, JELD-WEN, ShadePro, Four Seasons, Eclipse |
| Security and Safety | Protect people, property, and peace of mind. | Accent/3M, Sunbelt/Avery Dennison, Rollock Security Shutters |
| Home Automation & Control | Automate comfort, light, shade, and privacy. | Somfy, Vantis |

## Page Jobs

Every page has exactly one job. This keeps the hub from sprawling.

| Page type | Its job | Should contain | Should not contain |
| --- | --- | --- | --- |
| System Category | Route by customer outcome. | Relevant services, vendors, products. | Full specs, warranty text, portal credentials. |
| Residential / Commercial | Route by sales workflow. | Sales context and vendor/product chips. | Duplicated vendor documentation. |
| Vendor | Vendor source of truth. | Contacts, official links, warranties, FAQs, source inventory, portal references, collateral. | Repeated service-category explanations. |
| Product | Product-line source of truth. | Summary, fit, related lines, quick links, warranty/FAQ paths. | Unrelated vendor catalog content. |
| Start Here | Usage and governance. | How to navigate, request changes, and verify sources. | Product detail. |

## Vendors

The Vendors book is canonical. Every vendor chapter contains:

- A vendor overview page owning contacts, official links, dealer-portal references, warranty paths, FAQs, source inventory, training links, and collateral.
- Product-line pages owning product summaries, best-fit notes, related lines, warranty/FAQ links, and quick links.

Routing pages link into Vendors rather than repeating vendor detail.

In `docs/source.md`, each brand is organized into seven resource categories plus General, FAQ, Warranty Information, and Rep Contact blocks:

| Category | What it holds |
| --- | --- |
| Install Guide | Installation instructions and technical documentation. |
| Products | Product catalog, lines, and specs. |
| Dealer Portal | Trade/dealer login or partner-program access. |
| Spec Sheets & BIM | Data sheets, cut sheets, CAD/Revit/BIM files for architect jobs. |
| Samples & Swatches | How to order physical samples for consultations. |
| Pricing | Pricing guides, quote tools, or pricing portals. |
| Training & Certification | Certification programs and what they mean for selling. |

Rep Contact is the most valuable field in the hub. When a deal needs support, the assigned regional rep's name, phone, and email is what unblocks it. These fields are intentionally blank until collected.

Some vendors are distributors who carry a manufacturer's products. Model the relationship rather than flattening it:

- Accent distributes 3M film.
- Sunbelt distributes Avery Dennison film.
- Alta carries Hunter Douglas lines.

Until distributor-specific collateral exists, distributor pages point to the manufacturer's official resources and are marked `Rep Confirm Needed`.

Current vendor roster:

```text
3M, Accent, Sunbelt, Avery Dennison, Somfy, Vantis, Hunter Douglas, Alta,
Norman, Eclipse, Austin Screens, Draper, Decorative Films, SolX, Frost,
Old Castle / US Aluminum, ShadePro Shade Systems, Four Seasons Patio Systems,
Rollock Security Shutters, SmartTint, Andersen, Pella, CRL, Dallas Flat Glass,
Ghost Glass, JELD-WEN
```

## Rep Workflows

When the customer describes a problem:

```text
Home -> System Category -> Residential/Commercial category -> Vendor/Product page -> Official source
```

When the rep already knows the vendor:

```text
Home -> Vendors -> Vendor page -> Product line -> Official source
```

When something looks stale or wrong:

```text
Bad/missing info -> Vendor/product page in BookStack
  -> Verify against official source or rep
  -> Update routing pages only if positioning changed
```

The wiki model means anyone can request a change even without edit rights. Use the Request Update button on the page, or the How to Request Missing Documents page under Start Here.

## Maintenance

Routine content update order:

1. Edit the relevant vendor or product page directly in BookStack.
2. Bump the page's last-verified note when the source or rep confirmation is checked.
3. Update Residential or Commercial routing pages only if the change affects positioning.
4. Post to Recent Changes if the update is a discontinuation, major vendor change, or urgent team-facing correction.

Structural update order:

1. Update `docs/navigation.md`.
2. Update the seeder.
3. Deploy or reseed; the seeder manages structure and routing pages.
4. Update `readme.md` if the model changed.

Content priority:

1. Confirm the vendor/product page exists in Vendors.
2. Add or update official links, warranty paths, FAQ links, install/training links, collateral, and portal references there.
3. Link Residential and Commercial category pages to the vendor/product page only when the sales route changes.
4. Link System Category pages to the relevant category pages first, with source references secondary.
5. Keep audit findings open until the canonical BookStack page has been corrected.

Internal linking rules:

| To send someone to... | Link to... |
| --- | --- |
| A customer outcome | A System Category page under Start Here |
| A service family | A Residential or Commercial chapter |
| A specific sales category | A Residential or Commercial page |
| A vendor reference | The vendor overview page in Vendors |
| A product line | The product page under that vendor chapter |
| A warranty, FAQ, portal, install guide, or collateral source | The relevant vendor/product page section |

Keep external links canonical. Routing pages use internal links first; external vendor links live on vendor/product pages so updates happen in one place.

```text
Correct:   Climate Control -> Solar Film -> 3M product page -> official 3M source
Incorrect: Climate Control -> official 3M warranty PDF
```

## Link And Source Governance

External links are audited for reachability, redirects, and sales-use classification in `docs/link-audit.md`. Vendor sites with bot protection may return `429`, `405`, `999`, or HTTP/2 errors from command-line checks even when they open fine in a browser. Treat those as browser-review items, not automatic deletions.

| Tag | Meaning |
| --- | --- |
| `Audited` | In current audit scope; treat as intentionally included. |
| `Login Required` | Portal, dealer app, pricing, or protected resource. Credentials go in 1Password, not BookStack. |
| `Pending` | Access, source replacement, or vendor confirmation still needed. |
| `Rep Confirm Needed` | Use the vendor rep/contact path before quoting or promising details. |
| `Broken / Replace` | Link failed clearly or points to a page that should be replaced. |

Maintenance rules:

- Replace clear 404 links before publishing customer-facing collateral.
- Treat `429`, `405`, `999`, and HTTP/2 CLI failures as browser-review items, not deletions.
- Keep login-gated resources visible but labeled `Login Required`.
- Store only portal URLs and 1Password item names in BookStack.
- Warranty, pricing, install, and certification details must be rep-confirmed before being quoted to a customer.

Regenerate the automated audit with the command documented in `docs/link-audit.md`.

## Credentials And Security

Credentials never live in BookStack. A vendor page may list the dealer-portal URL and a reference to where the credential is stored. Actual usernames, passwords, recovery codes, and shared credentials live in 1Password.

```text
Dealer Portal: https://vendor.example.com/login
Credentials:   1Password -> Vendor Portals / Vendor Name
```

## Common Mistakes To Avoid

- Creating a new top-level book for a resource type such as specs, warranties, training, or collateral.
- Linking from a routing page straight to an external vendor PDF when a vendor/product page should own that link.
- Repeating warranty or spec details across multiple service pages.
- Putting credentials in BookStack instead of 1Password.
- Using Products as the main area name; the canonical name is Vendors.
- Treating System Categories as content dumps instead of routing pages.

## Current Gaps And Roadmap

Gaps are intentionally visible so owners can fill them.

In progress:

- Rep contacts: most vendor Rep Contact blocks are blank; collecting these is the highest-value task.
- Distributor collateral: Accent, Sunbelt, and several manufacturers are `Rep Confirm Needed` pending vendor-specific resources.
- Pending access: Dallas Flat Glass dealer portal requested 2026-05-21; follow up if no response within a week.
- Link replacements: items tagged `Broken / Replace` in `docs/link-audit.md`, such as the Ghost Glass install page.

Planned:

- SOP / Playbook pillar: a parallel area for standard operating procedures such as over-the-phone pricing and lead intake.
- Change / announcement feed: surface critical updates, such as discontinued products, to the whole team.
- Verification dates: add a last verified by/on stamp per vendor page, with staleness flags for pages not re-verified within a set window.

## Glossary

| Term | Meaning |
| --- | --- |
| System Category | One of five customer-need routing pages under Start Here. |
| Routing page | Any System, Residential, or Commercial page whose job is to direct, not store facts. |
| Source of Truth | The canonical place a fact lives; for content, this is usually a vendor or product page in BookStack. |
| Rep Confirm Needed | Detail must be verified with the vendor rep before quoting to a customer. |
| Vendor overview page | The top page in a vendor chapter that owns contacts, links, warranties, and portal references. |

## Related Docs

- [Structure and navigation](docs/navigation.md)
- [Vendor source map](docs/source.md)
- [Product inventory](docs/products.md)
- [Link health](docs/link-audit.md)
