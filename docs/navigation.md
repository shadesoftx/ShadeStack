# Navigation & Structure

This document defines how the Shades of Texas internal knowledge base is organized, how people move through it, and how every page should be built. It is the blueprint for anyone adding or editing content.

---

## The goal

A salesperson in front of a customer should be able to open this hub on their phone and get to the right spec sheet, warranty summary, or sales answer in two taps. No guessing. No learning a folder structure. No dead ends.

Everything in this document serves that goal.

---

## The hierarchy

BookStack has four layers:

| Layer | Our name | Purpose |
|---|---|---|
| Bookshelf | `High Level` | The single top-level container for everything |
| Book | `Residential`, `Commercial`, `Products`, `Start Here` | Major business areas — the main nav |
| Chapter | `Tint & Film`, `Window Treatments`, etc. | Service families within each book |
| Page | `Home Window Tint`, `3M`, `Awnings`, etc. | The actual content a salesperson reads |

---

## The structure

Four books. That is all that appears in the main sidebar. Everything lives inside one of these.

```
High Level (Shelf)
│
├── Start Here
│   ├── How to use this hub
│   ├── Source of Truth
│   ├── Sales workflow overview
│   └── Request a missing resource
│
├── Residential
│   ├── Tint & Film
│   │   ├── Home Window Tint
│   │   ├── Safety & Security Film
│   │   └── Privacy Film
│   ├── Window Treatments
│   │   ├── Window Shades
│   │   ├── Shutters
│   │   └── Blinds
│   ├── Outdoor Living
│   │   ├── Awnings
│   │   └── Patio Screens
│   └── Glass & Windows
│       ├── Glass Replacement
│       └── Window Replacement
│
├── Commercial
│   ├── Solar Control & Safety
│   ├── Window Treatments
│   ├── Patio Screens & Awnings
│   └── Glass & Windows
│
└── Products
    ├── 3M
    ├── Hunter Douglas
    ├── Alta
    ├── Norman
    ├── Eclipse
    ├── SmartTint
    ├── Andersen
    ├── Pella
    ├── CRL
    ├── Dallas Flat Glass
    ├── Ghost Glass
    ├── JELD-WEN
    │
    ├── [Chapter] Specs & Drawings
    ├── [Chapter] Warranty / Compliance
    └── [Chapter] Reference / FAQs
```

### Why only four books in the sidebar

Seven top-level books is too many for someone navigating on a phone mid-appointment. The previous structure had `Specs & Drawings`, `Warranty / Compliance`, and `Reference / FAQs` as top-level books, which forced reps to decide between seven options every time they opened the hub.

Those three are now chapters inside `Products`. They are reference material — not primary navigation destinations. Reps reach them from a category page or by searching, not by browsing.

---

## How navigation works

### The expected click path

```
Home page
  → tap a service tile (Residential / Commercial)
    → open the relevant chapter (Tint & Film, Window Treatments, etc.)
      → open the category page (Home Window Tint, Awnings, etc.)
        → follow a brand card to a product page (3M, Eclipse, etc.)
          → open the spec sheet, warranty doc, or FAQ from there
```

### The home page

The home page is not a final destination. It is a launcher.

It should contain:
- A search bar, front and center
- Four large tiles: `Residential` · `Commercial` · `Products` · `Start Here`
- Nothing else

A salesperson who opens the hub in front of a customer should be able to tap one tile and be one level away from what they need. No scrolling, no reading, no learning a structure.

### Search is always available

Full-text search in BookStack indexes everything — page titles, body text, and attachment names. Reps who know what they are looking for (a brand name, a product line, a warranty term) should use search first. The navigation structure exists for browsing, not as a replacement for search.

---

## Service-to-brand mapping

Every category page links directly to the relevant brand pages in `Products`. This is the bridge between service navigation and product detail. Reps should never have to open a separate book to find which brands apply to a given service.

| Service category | Brands |
|---|---|
| Home Window Tint | 3M, SmartTint |
| Safety & Security Film | 3M |
| Privacy Film | SmartTint, Ghost Glass |
| Window Shades | Hunter Douglas, Alta, Norman |
| Shutters | Hunter Douglas, Norman |
| Blinds | Hunter Douglas, Alta, Norman |
| Awnings | Eclipse |
| Patio Screens | Eclipse |
| Glass Replacement | CRL, Dallas Flat Glass |
| Window Replacement | Andersen, Pella, JELD-WEN |
| Solar Control & Safety (Commercial) | 3M, SmartTint |
| Window Treatments (Commercial) | Hunter Douglas, Alta, Norman |
| Patio Screens & Awnings (Commercial) | Eclipse |
| Glass & Windows (Commercial) | Pella, Andersen, JELD-WEN, CRL, Dallas Flat Glass |

---

## Standard page templates

Every page in this hub follows one of two templates depending on whether it is a **category page** or a **brand page**. Using the same layout every time means reps always know where to look, regardless of which page they land on.

---

### Template A — Category page

Use this for every page inside `Residential` and `Commercial` (e.g. `Home Window Tint`, `Awnings`, `Window Shades`).

```
# [Category Name]

## Overview
1–2 sentences. What is this product or service, and who is it for?
Residential or commercial context. Key benefit in plain language.

## Brands for this category
Inline brand cards linking directly to the brand page in Products.
Example: [3M] [SmartTint]

## Spec Sheets
Direct links to the 2–3 most relevant spec PDFs for this category.
Pull these from the brand pages in Products — do not duplicate files.

## Warranty Summary
- Coverage: [X years / lifetime / limited]
- Key exclusions: [brief note]
- Full warranty: [link to brand warranty page]

## Common Sales Questions
Q: [Question a customer actually asks]
A: [Direct answer a rep can say out loud]

Q: [Second question]
A: [Answer]

Q: [Third question]
A: [Answer]

## Related Categories
See also: [linked page] · [linked page]
```

---

### Template B — Brand page

Use this for every page inside `Products` (e.g. `3M`, `Hunter Douglas`, `Eclipse`).

```
# [Brand Name]

## Overview
1–2 sentences. What does this brand make, and where does it fit in our
service offering? Which service categories use this brand?

## Products we carry
- [Product line 1] — [one-line description]
- [Product line 2] — [one-line description]
- [Product line 3] — [one-line description]

## Spec Sheets & Tech Data
- [Spec sheet name] — [direct link or attached PDF]
- [Tech data sheet] — [direct link or attached PDF]

## Architect & BIM Resources
- [CAD / Revit / BIM link if applicable]
- [Architectural drawing link if applicable]

## Warranties
- [Warranty name]: [coverage summary]
- [Full warranty link]

## Installation Resources
- [Install guide link]
- [How-to video link]

## Pricing
- [Portal name]: [link] (login required)
- [Notes on how to get a quote]

## Dealer Portal
- [Portal name]: [link]
- [Notes on access / login]

## Samples & Swatches
- [How to order samples]
- [Rep contact for sample requests]

## Training & Certification
- [Certification name]: [what it means, how to get it]
- [Selling point: what this certification means for the customer]

## Sales Notes
Key selling points, differentiators, and things that come up in the field.
This is the section reps actually read before a call or appointment.

## Rep Contact
- Name:
- Phone:
- Email:

## External Links
- [Home]
- [Support]
- [Warranty]
- [Dealer Portal]
```

---

## Internal linking rules

These rules keep the navigation consistent as content grows.

### Always link to the right level

| If you want to send someone to... | Link to... |
|---|---|
| A service overview | The chapter (e.g. `/books/residential/chapter/tint-film`) |
| A specific product or category | The page (e.g. `/books/residential/page/home-window-tint`) |
| A brand reference | The brand page in Products (e.g. `/books/products/page/3m`) |
| A document or spec sheet | The attached file on the brand page, not a raw URL |

### Brand cards on category pages

Every category page must have inline brand cards in the `Brands for this category` section. These are links to brand pages in `Products`, not to external websites. External links live on the brand page itself.

```
Correct:   [3M] → /books/products/page/3m
Incorrect: [3M] → https://www.3m.com/...
```

### Spec sheets and documents

Attach PDFs directly to the relevant brand page in BookStack using the attachment feature. Link to the BookStack attachment, not to an external URL. This way documents stay available even if a vendor's website changes.

Exception: for documents that update frequently (warranty pages, pricing portals), link to the live vendor URL and note "check for current version."

### Cross-links between Residential and Commercial

Where the same brand or product appears in both Residential and Commercial, link to the shared brand page in Products rather than duplicating content. Category pages summarize; brand pages hold the detail.

---

## Canonical URL patterns

These are the URL formats BookStack generates. Use them when sharing links with the team.

```
Home page:          https://resources.shadesoftx.com/
Residential book:   https://resources.shadesoftx.com/books/residential
Chapter:            https://resources.shadesoftx.com/books/residential/chapter/tint-film
Category page:      https://resources.shadesoftx.com/books/residential/page/home-window-tint
Products book:      https://resources.shadesoftx.com/books/products
Brand page:         https://resources.shadesoftx.com/books/products/page/3m
Specs chapter:      https://resources.shadesoftx.com/books/products/chapter/specs-and-drawings
```

Local development URLs follow the same pattern with `https://bookstack-sotx.test/` as the base.

---

## Content priority order

Build content in this order. Start with the skeleton, then fill in detail incrementally.

**Phase 1 — Skeleton (do this first)**
- [ ] Create all four books
- [ ] Create all chapters inside Residential and Commercial
- [ ] Create all brand pages inside Products (empty is fine)
- [ ] Create Specs & Drawings, Warranty / Compliance, Reference / FAQs as chapters inside Products

**Phase 2 — Category pages**
- [ ] Write Overview and Brands sections for every category page
- [ ] Add brand card links (internal links to Products)
- [ ] Add 3 common sales questions per page

**Phase 3 — Brand pages**
- [ ] Fill in Overview, Products we carry, and Rep Contact for every brand
- [ ] Attach spec sheet PDFs to each brand page
- [ ] Add warranty summaries and links
- [ ] Add dealer portal and pricing links

**Phase 4 — Polish**
- [ ] Add architect / BIM resources to applicable brands
- [ ] Fill in Training & Certification sections
- [ ] Add Sales Notes to every brand page
- [ ] Fill in all Rep Contact fields

---

## Common mistakes to avoid

- **Linking directly to an external URL from a category page.** External links belong on brand pages only. Category pages link internally.
- **Creating a new book instead of a chapter.** If content belongs under a service family or under Products, it is a chapter or page, not a new book. Keep the sidebar to four books.
- **Duplicating content across Residential and Commercial.** If 3M is the brand for both home window tint and commercial solar film, there is one 3M brand page. Both category pages link to it.
- **Adding a page without following the template.** Templates exist so reps know where to look. A page that skips sections breaks that expectation.
- **Attaching a file to the wrong page.** Spec sheets and PDFs attach to the brand page, not to category pages. Category pages link to brand pages.
- **Leaving Rep Contact fields blank long-term.** These are the most important fields in the hub. Make filling them in a priority as you onboard each brand.

---

## What this doc is not

- It is not a content-writing guide
- It is not a full sitemap of every future page
- It is not a replacement for the README setup instructions

It is the single source of structural truth for how this hub is organized and how pages should be built.

For the master vendor source map, see [docs/source.md](source.md).
For local setup and deployment, see [docs/readme.md](readme.md).