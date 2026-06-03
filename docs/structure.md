# ShadeStack — App Structure

This is the single reference for how ShadeStack is organized in BookStack. It covers every
shelf, book, chapter, and page type, and explains exactly how a user navigates from the
homepage to the answer they need.

---

## Two shelves. That's it.

| Shelf | What it contains | Job |
|---|---|---|
| **Workflow** | Start Here | Navigation, routing, and protected SOP access |
| **Products** | 11 category books | All vendor and product documentation |

---

## Workflow shelf

### Start Here book

No chapters. Pages sit directly under the book. This is the task-based entry point.

| Page | Job |
|---|---|
| Find Your Path | The BookStack homepage — three first-click cards plus protected SOP access |
| Vendor Index | Vendor-first lookup with primary category, supported categories, and canonical overview link |
| System Categories | Secondary outcome-routing layer (Climate Control, Privacy Control, etc.) |
| Climate Control | Lists vendors for heat / glare / energy jobs |
| Privacy Control | Lists vendors for privacy jobs |
| Patio Extension | Lists vendors for outdoor extension jobs |
| Security and Safety | Lists vendors for security jobs |
| Home Automation & Control | Lists vendors for automation jobs |
| Source of Truth | Master vendor source map, status tags, credential rules |
| How to Use This Hub | Navigation guide — which path to use and when |
| Where to Find Product Info and Pricing | Where each type of resource lives |
| How to Request Missing Documents | Escalation path for gaps |
| Recent Changes | Team announcements for discontinuations and urgent corrections |

## Products shelf

Eleven category books. Each book has vendor chapters. Each vendor chapter has pages.

### How chapters work: primary vs cross-link

Every vendor belongs to exactly one **primary category** — that is where its
`Vendor - Overview` page and all `Vendor - Product` pages live.

Vendors that also apply to other categories appear in those books too, but as a **cross-link
chapter** containing a single routing page (`Vendor - See Primary Category`). That page
links straight to the canonical chapter. No content is duplicated.

```
Products shelf
  Book: Shades
    Chapter: Hunter Douglas     ← PRIMARY — full content here
      Page: Hunter Douglas - Overview
      Page: Hunter Douglas - Duette Honeycomb Shades
      Page: Hunter Douglas - Silhouette Sheer Shadings
      ... (all Hunter Douglas products)
    Chapter: Eclipse            ← CROSS-LINK — routes to Screens
      Page: Eclipse - See Screens

  Book: Screens
    Chapter: Eclipse            ← PRIMARY — full content here
      Page: Eclipse - Overview
      Page: Eclipse - The Eclipse
      Page: Eclipse - Drop Shade
      ...
```

### Primary category map

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

### Category books and their chapters

**Shades** — Hunter Douglas (primary), Alta (primary), Norman (primary), Draper (primary),
Eclipse (cross-link → Screens), Vantis (cross-link → Smart & Automation)

**Shutters** — Rollock Security Shutters (primary), Hunter Douglas (cross-link → Shades),
Alta (cross-link → Shades), Norman (cross-link → Shades)

**Screens** — Austin Screens (primary), Eclipse (primary), Draper (cross-link → Shades),
ShadePro Shade Systems (cross-link → Pergolas)

**Pergolas** — ShadePro Shade Systems (primary), Four Seasons Patio Systems (primary),
Eclipse (cross-link → Screens)

**Patio Covers** — Old Castle / US Aluminum (cross-link → Windows),
ShadePro Shade Systems (cross-link → Pergolas),
Four Seasons Patio Systems (cross-link → Pergolas), Eclipse (cross-link → Screens)

**Tint & Film** — 3M (primary), Accent (primary), Sunbelt (primary), Avery Dennison (primary),
Decorative Films (primary), SolX (primary), Frost (primary), SmartTint (primary),
Ghost Glass (primary), Vantis (cross-link → Smart & Automation)

**Windows** — Andersen (primary), Pella (primary), JELD-WEN (primary),
Dallas Flat Glass (primary), Old Castle / US Aluminum (primary)

**Doors** — CRL (primary), Andersen (cross-link → Windows), Pella (cross-link → Windows),
JELD-WEN (cross-link → Windows)

**Glass & Windows** — Old Castle / US Aluminum (cross-link → Windows),
Andersen (cross-link → Windows), Pella (cross-link → Windows),
JELD-WEN (cross-link → Windows), CRL (cross-link → Doors),
Dallas Flat Glass (cross-link → Windows), Ghost Glass (cross-link → Tint & Film)

**Outdoor Living** — Austin Screens (cross-link → Screens), Draper (cross-link → Shades),
ShadePro Shade Systems (cross-link → Pergolas),
Four Seasons Patio Systems (cross-link → Pergolas), Eclipse (cross-link → Screens)

**Smart & Automation** — Somfy (primary), Vantis (primary),
SmartTint (cross-link → Tint & Film), Ghost Glass (cross-link → Tint & Film)

---

## Page types and what they contain

Every page type has a content contract. If a detail does not fit the page's contract,
link to the page that owns it instead of duplicating it.

### Start Here

The employee's first-click router.

- Product type path
- Vendor path
- Customer outcome path
- Protected SOP link and login-required note

No product facts, vendor details, specs, warranty language, pricing notes, or long
navigation explanations belong here.

### Vendor Index

Vendor-first lookup for employees who know the brand but not the primary category.

- Vendor name
- Primary category
- Supported categories
- Direct link to canonical `Vendor - Overview`

No specs, warranty, install guides, collateral, pricing, or product descriptions belong here.

### System Category pages

Outcome-routing pages. They explain customer needs and link directly to vendor pages in
Products.

- Plain-language customer need
- Recognition cues / customer phrases
- Relevant vendors
- Links into Products
- Short internal routing guidance

No canonical specs, warranty text, install documents, pricing, or duplicated product
writeups belong here.

### Product Category books

Category browsing by SOT product type.

- Category definition
- Primary vendors
- Cross-linked vendors
- Related categories
- Overlap/confusion notes

No full product docs, brand-wide warranty, install PDFs, or sales collateral files belong
at the category-book level.

### Vendor - Overview (primary chapter only)

The single home for brand-wide outcome resources. Generated vendor overview pages use a compact product link strip followed by four containers:

- Install Guides
- Product Specs
- Sales Collateral
- Warranty

Do not add separate Vendor Reference, Products in this line, Categories Supported, FAQs, Quick Links, or Source Inventory containers to generated vendor pages. Put those links inside one of the four outcome sections or in `docs/source-collection.md`.
  - What is login-gated
  - What needs review
  - What is missing

Product-specific specs, product-specific sales collateral, detailed install steps,
credentials, and unverified sales claims do not belong on the vendor overview.

### Vendor - Product (primary chapter only)

One page per product line. The only place product-specific facts live.

- Outcome resources in locked order
  - Install Guides
  - Product Specs
  - Sales Collateral
  - Warranty
- Do not add separate Overview, Category, Vendor, Use Cases, Internal Notes, or Source Status containers to generated product pages.

The locked outcome section order is:

1. Install Guides
2. Product Specs
3. Sales Collateral
4. Warranty

Each outcome resource must show a visible status badge plus a scope note:

- Product-specific resource present
- Brand-wide, see Vendor Overview
- Needed
- Pending Review
- Not Applicable

Product pages should eventually include product fit, when to recommend it, when not to
recommend it, install documents, spec sheets, brochures/sell sheets, product-specific
warranty notes, internal gotchas, and last-verified/source status. Credentials, generic
vendor marketing copy, duplicated brand-wide warranty text, and long copied PDF procedures
do not belong here.

### Vendor - See Primary Category (cross-link chapters only)

One page per cross-link chapter. Contains nothing except:

- A plain-language description of why the vendor is relevant to this category
- A button linking to `Vendor - Overview` in the primary category
- Product name chips so the user can confirm they have the right vendor

No specs, no warranty, no source status. Click through to the canonical page.

### Source of Truth

Governance and audit, not normal product lookup.

- Source rules
- Status definitions
- Credential rules
- Vendor source inventory
- Link-health expectations
- Update ownership and escalation rules

### SOP Link

Process documentation route only.

- SharePoint SOP link
- Login-required note
- No SOP content inside the product documentation structure

---

## How a user navigates

The app is built around three entry paths. All three converge on the same vendor and
product pages in the Products shelf.

### Path 1 — I know the product type

> "I need an awning for this customer."

Homepage → **Products** shelf → open the matching category book (e.g., Outdoor Living) →
find the vendor chapter → open `Vendor - Overview` or the specific product page.

If the vendor chapter in that book is a cross-link, click through to the primary category.
One extra click, no dead end.

### Path 2 — I know the customer outcome but not the product

> "Customer wants to reduce heat in their office."

Homepage → **System Categories** → open the matching outcome (e.g., Climate Control) →
use the vendor chips to open the vendor overview in Products.

### Path 3 — I know the vendor

> "Customer has Hunter Douglas and needs warranty info."

Homepage → **Vendor Index** → open `Hunter Douglas - Overview` in the primary category →
open the vendor overview or product page.

---

## Rules that keep the structure clean

1. **One canonical home per vendor.** Specs, warranty, install guides, and source status
   live on the primary category's pages only. Cross-link chapters route, never duplicate.

2. **One job per page.** Routing pages route. Vendor pages hold vendor-wide facts.
   Product pages hold product-specific facts. System Category pages route customer outcomes.

3. **Outcome resources are headings, not pages.** Install Guides, Product Specs, Sales
   Collateral, and Warranty are sections inside a product page — not separate BookStack
   pages. They appear in the page table of contents automatically.

4. **Workflow and System Category pages link into Products. They never own product data.**
   If a warranty figure or spec sheet lives outside a vendor/product page, it is in the wrong place.

5. **Adding a vendor:** add it to `productCategoryMap()` in the seeder for every category
   it covers, and add its primary category to `vendorPrimaryCategories()`. Reseed.

6. **SOPs and Partnerships** are reserved for separate shelves when that work begins.
   Start Here may link to protected SOP access, but SOP content does not belong inside
   the current product documentation structure.
