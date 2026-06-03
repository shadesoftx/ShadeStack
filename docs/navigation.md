# Navigation & Structure

This document defines the ShadeStack BookStack navigation model.

Scope right now: navigation and flow only. SOPs and partnerships are reserved parallel areas, not part of the core product build.

## Container Model

**Category-canonical.** One book per SOT product category in the Products shelf. Each vendor belongs to exactly one primary category; that is where its `Vendor - Overview` page and all product pages live. Vendors that also apply in other categories are cross-linked through secondary category routing pages — no duplicate chapters or pages are created in secondary categories.

| Vendor | Primary category |
|---|---|
| Hunter Douglas | Shades |
| Alta | Shades |
| Norman | Shades |
| Draper | Shades |
| Eclipse | Screens |
| Rollock Security Shutters | Shutters |
| Austin Screens | Screens |
| ShadePro Shade Systems | Pergolas |
| Four Seasons Patio Systems | Pergolas |
| Old Castle / US Aluminum | Windows |
| 3M | Tint & Film |
| Accent | Tint & Film |
| Sunbelt | Tint & Film |
| Avery Dennison | Tint & Film |
| Decorative Films | Tint & Film |
| SolX | Tint & Film |
| Frost | Tint & Film |
| SmartTint | Tint & Film |
| Ghost Glass | Tint & Film |
| Andersen | Windows |
| Pella | Windows |
| JELD-WEN | Windows |
| Dallas Flat Glass | Windows |
| CRL | Doors |
| Somfy | Smart & Automation |
| Vantis | Smart & Automation |

This mapping is enforced by `vendorPrimaryCategories()` in the seeder. Adding a new vendor requires: (1) add it to `productCategoryMap()` for every category it covers, and (2) add its primary category to `vendorPrimaryCategories()`.

For every secondary category a vendor appears in, the seeder creates a **cross-link chapter** — one page named `Vendor - See Primary Category` that routes directly to the canonical vendor overview. Users browsing any category book can still find every relevant vendor; clicking the cross-link takes them to the single source of truth.

Start Here exposes three first-click paths:

- `I know the product type` -> Products shelf -> SOT product category.
- `I know the vendor` -> Vendor Index -> canonical `Vendor - Overview`.
- `I know the customer outcome` -> System Categories -> Products.

The Vendor Index lists every vendor with primary category, supported categories, and a direct link to the canonical overview page so employees do not have to memorize the primary category map.

## Core Constraint

The intended path is five conceptual levels deep:

```text
Start Here -> SOT Product Category -> Vendor -> Product -> Outcome Resources
```

BookStack only gives four nesting layers:

```text
Shelf -> Book -> Chapter -> Page
```

The fifth level, outcome resources, is not a navigation container. It is the fixed set of headings inside each product page. BookStack builds a page table of contents from those headings, so outcome resources read like a fifth level without becoming separate pages.

## Primary Structure

Use one book per product category so Category, Vendor, and Product are all clickable levels:

```text
Shelf: Workflow
  Book: Start Here
    Page: Find Your Path
    Page: Vendor Index

Shelf: Products
  Book: Shades
    Chapter: Draper
      Page: Draper - Overview
      Page: Draper - FlexShade
  Book: Tint & Film
    Chapter: 3M
      Page: 3M - Overview
      Page: 3M - Prestige Series
  Book: Windows
  Book: Screens
```

Reserved for later:

```text
Shelf: SOPs
Shelf: Partnerships
```

## No Redundancy Rule

Put each resource at the lowest level where it is still 100% true. Anything more specific links up to it. It is never copied down.

| Resource | Vendor Overview page | Product page |
|---|---|---|
| Vendor website | Canonical | Link up if needed |
| Dealer portal URL + 1Password ref | Canonical | Link up |
| Sales training, brand-wide | Canonical | Link up |
| Warranty, brand-wide | Canonical | Link up |
| Install guides, brand-wide | Canonical | Link up |
| Rep / contacts / notes | Canonical | Not applicable |
| Product page URL on vendor site | Not applicable | Canonical |
| Product specs / spec sheets | Not applicable | Canonical |
| Product sales guides / collateral | Brand-level only | Canonical when product-specific |
| Product-specific training | Not applicable | Canonical |

Quick test: is this true for the whole brand, or just this product line? Whole brand goes on the vendor overview. One product goes on the product page.

## Outcome Heading Order

Every product page must use this exact outcome heading order and show a visible status badge/scope note for each outcome resource:

1. Install Guides
2. Product Specs
3. Sales Collateral
4. Warranty

Supported resource status labels are `Complete`, `Partial`, `Needed`, `Pending Review`, and `Not Applicable`. Scope notes should distinguish product-specific resources from brand-wide resources that link back to the vendor overview.

## Page Templates

### Vendor Overview

```text
# Vendor - Overview

Product link strip

## Install Guides
## Product Specs
## Sales Collateral
## Warranty
```

### Cross-link Page (secondary categories)

```text
# Vendor - See Primary Category

Routing page only. Routes to the canonical vendor overview in the primary category book.
Contains: clear routing explanation, primary-category button to Vendor - Overview, product name chips.
No specs, warranty, install guides, or source status — those live on the canonical page.
```

### Product

```text
# Vendor - Product

## Install Guides
## Product Specs
## Sales Collateral
## Warranty
```

## Linking And Credential Rules

- Always link to the internal vendor/product page before an external vendor URL.
- External vendor URLs live on the vendor or product page only.
- Product pages link up to vendor pages for brand-wide resources.
- System Category pages link into Products for outcome routing and hold no canonical specs, warranty text, or credentials.
- Never store usernames, passwords, recovery codes, or shared credentials in BookStack.
- Use this credential reference pattern: `1Password -> Vendor Portals / [Vendor]`.

## Category Note

The current category list has overlap, especially Pergolas / Patio Covers / Outdoor Living and Windows / Glass & Windows. Consolidating those categories is a content decision, not a model change. Decide consolidation before a production rebuild if the team wants fewer category books.

## Reserved Future Areas

SOPs are role/process-built and should live in their own shelf later.

Partnerships are outcome-built and should live in their own shelf later, using the same four resource buckets while staying out of the core product library.
