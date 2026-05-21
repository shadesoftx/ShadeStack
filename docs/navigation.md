# High-Level Navigation & Structure

This document explains how the Shades of Texas internal knowledge base is organized and how people should move through it.

The goal is simple: make it obvious to salespeople where to start, where to drill down, and where to find the exact resource they need without guessing at routes or folder names.

## The hierarchy

BookStack has four layers in this project:

- `Bookshelf` is the top-level container. Ours is named `High Level`.
- `Books` are the major business areas.
- `Chapters` are the service families or top-level service groupings.
- `Pages` are the category resources and the actual reference content.

In practice, the structure is:

```text
High Level
  Start Here
    Source of Truth
  Residential
  Commercial
  Products
  Specs & Drawings
  Warranty / Compliance
  Reference / FAQs
```

## How navigation works

The home page is a branded landing page, not the final destination.

The expected click path is:

1. Start on the home page.
2. Click into a major service area such as `Residential` or `Commercial`.
3. Open the relevant chapter for the service family.
4. Choose the category page for the exact product or solution.
5. Use `Source of Truth` when you need the master source map before updating content.
6. Jump from that category page into the aggregate resource books:
   - `Products`
   - `Specs & Drawings`
   - `Warranty / Compliance`
   - `Reference / FAQs`

Those four books are the canonical “everything in one place” indexes. Each book collects all categories from all services so a rep does not have to remember where a file lives.

This keeps the sales flow consistent and prevents people from having to remember where documents live.

## Service families

The current service organization mirrors the public Shades of Texas site where it helps recognition:

- `Tint & Film`
- `Window Treatments`
- `Outdoor Living`
- `Glass & Windows`

Residential and commercial areas both reuse that logic, with the category names tailored to the audience and the service line.

## Product pages

Product brands live in the `Products` book instead of inside a service chapter.

That is where the team should look for brand-level pages such as:

- `3M`
- `Hunter Douglas`
- `Alta`
- `Norman`
- `Eclipse`
- `SmartTint`
- `Andersen`
- `Pella`
- `CRL`
- `Dallas Flat Glass`
- `Ghost Glass`
- `Jeld-Wen`

This keeps service navigation focused on the job type while the product book stays clean and searchable.

## Standard category pattern

Inside a service family, the pages are grouped around the exact category the team sells.

For example, `Tint & Film` contains:

- `Safety & Security Film`
- `Solar Film`
- `Privacy Film`

Each of those category pages then follows the same four-section layout:

- links into the four resource books:
  - `Products`
  - `Specs & Drawings`
  - `Warranty / Compliance`
  - `Reference / FAQs`

That repeated pattern gives salespeople a predictable place to look no matter which category they open.

## Canonical examples

Use chapter URLs when you want someone to land on the service area overview.

Use page URLs when you want someone to land directly on a specific category page.

Examples:

- Home page: `https://bookstack-sotx.test/`
- Residential book: `https://bookstack-sotx.test/books/residential`
- Residential chapter: `https://bookstack-sotx.test/books/residential/chapter/tint-film`
- Products book: `https://bookstack-sotx.test/books/products`
- Category page: `https://bookstack-sotx.test/books/residential/page/window-shades`

## Link rules

When adding or updating links:

- Link to a chapter if the destination is a service overview or category landing area.
- Link to a page only when the user needs a specific product or category page.
- Avoid pointing cards or menu items at a child page unless that page is the actual destination.

This is the main reason the team saw a `Page not found` message earlier. The navigation was pointing at the wrong level of the hierarchy.

## Common mistakes

- Linking to a child page when the user really needs the chapter landing page.
- Treating the home page as the final destination instead of the starting point.
- Mixing product pages and category pages together in the same navigation layer.
- Creating a new ad hoc folder structure instead of using the existing `High Level` hierarchy.

## What this doc is not

- It is not a content-writing guide.
- It is not a full sitemap of every future page.
- It is not a replacement for the README setup instructions.

It is a map of how the current site is organized so the team can move through it consistently.

For the master source list that should be updated first, see [docs/source.md](source.md).
