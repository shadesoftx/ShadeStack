ShadeStack Internal Product Documentation
=========================================

ShadeStack is the internal Shades of Texas product documentation system for employees in sales, install, support, and warranty workflows.

The first click should match what the employee already knows:

- `I know the product type` -> Products shelf -> SOT product category -> Vendor -> Product.
- `I know the vendor` -> Start Here -> Vendor Index -> canonical vendor overview.
- `I know the customer outcome` -> Start Here -> System Categories -> Products.

The primary product path is:

```text
Start Here -> SOT Product Category -> Vendor -> Product -> Outcome Resources
```

BookStack structure:

```text
Shelf: Workflow
  Book: Start Here
    Page: Find Your Path
    Page: Vendor Index

Shelf: Products
  Book: one SOT product category per book
    Chapter: vendor
      Page: Vendor - Overview
      Page: Vendor - Product
```

Outcome resources are headings inside product pages, not extra navigation containers. The section order is locked and must not be changed:

1. Install Guides
2. Product Specs
3. Sales Collateral
4. Warranty

Core Rules
----------

- Put each resource at the lowest level where it is still 100% true.
- Brand-wide resources live on `Vendor - Overview`.
- Product-specific resources live on `Vendor - Product`.
- Product pages show per-resource status badges and link up to vendor overview pages for brand-wide install guides, warranty, training, portals, and contacts.
- Workflow and System Category pages link into Products and hold no canonical product data.
- Credentials live in 1Password only.

Content Contracts
-----------------

Each page type has one job:

| Page Type | Job | Expected Information |
|---|---|---|
| Start Here | Route the employee to the right first click. | Product type path, vendor path, customer outcome path, protected SOP link. |
| Vendor Index | Help employees find a vendor without knowing the primary category. | Vendor name, primary category, supported categories, canonical overview link. |
| System Category | Translate customer outcomes into product/vendor routes. | Customer need, recognition cues, relevant vendors, links into Products. |
| Product Category Book | Browse by SOT product type. | Category definition, primary vendors, cross-linked vendors, related categories, overlap notes. |
| Vendor - Overview | Hold brand-wide outcome resources. | Product link strip, Install Guides, Product Specs, Sales Collateral, Warranty. |
| Vendor - Product | Hold product-specific outcome resources. | Install Guides, Product Specs, Sales Collateral, Warranty. |
| Vendor - See Primary Category | Route from secondary category to canonical vendor page. | Routing explanation, primary-category link, product chips only. |
| Source of Truth | Govern sources and confidence. | Source rules, status definitions, credential rules, vendor source inventory, link-health expectations. |
| SOP Link | Route to process documentation. | Protected SOP link and login-required note only. |

Status Labels
-------------

| Status | Meaning |
|---|---|
| Complete | All expected resources are present. |
| Partial | Some resources are present, but at least one major outcome category is missing. |
| Needed | Documentation is required but has not been sourced. |
| Pending Review | Documentation exists but needs validation. |
| Not Applicable | This outcome category does not apply. |

Source Files
------------

- `docs/navigation.md` defines the BookStack shelf/book/chapter/page model.
- `docs/products.md` defines product categories, templates, naming, and seed inventory.
- `docs/source.md` defines sourcing rules, ownership, freshness, and credential handling.
- `docs/source-collection.md` is the working collection tracker for official links, portal references, and rep-confirmed resources.
- `docs/link-audit.md` tracks gaps, broken links, duplicate docs, outdated PDFs, login-gated URLs, and incomplete outcome categories.

Reserved Future Areas
---------------------

SOPs and Partnerships should be separate shelves later. SOPs are role/process-built. Partnerships are outcome-built and should reuse the four resource buckets without being mixed into the core Products shelf.
