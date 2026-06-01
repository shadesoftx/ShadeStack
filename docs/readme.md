Shades of Texas Sales Hub
=========================

This repository powers the internal Shades of Texas sales hub. It is a BookStack-based knowledge base for sales teams who need fast access to product lines, vendor contacts, specs, warranties, training resources, and sales context without hunting through vendor websites or relying on word of mouth.

The structure is built around one path:

```text
Customer Need -> System Category -> Residential/Commercial Category -> Product/Vendor Page -> Official Source
```

The goal is not to duplicate every vendor website. The goal is to create a clean internal map that points the team to the right answer quickly and keeps official vendor information in one canonical place.

Design principles
-----------------

- Start with the way customers describe the need.
- Keep the top-level navigation simple.
- Use internal links before external links.
- Keep vendor and product pages as the source of truth.
- Avoid repeating specs, warranties, contacts, portal links, install guides, and collateral across pages.
- Make gaps visible so sales, operations, and supplier owners can fill them over time.

Core structure
--------------

Only four books should appear in the main hub structure:

```text
High Level
  Start Here
  Residential
  Commercial
  Vendors
```

### Start Here

Usage, governance, and top-level routing.

- Sales Hub Home
- System Categories
- Climate Control
- Privacy Control
- Patio Extension
- Security and Safety
- Home Automation & Control
- Source of Truth
- How to Use This Hub
- Where to Find Product Info and Pricing
- How to Request Missing Documents

### Residential

Sales workflow pages for homeowner projects.

- Tint & Film
- Window Treatments
- Outdoor Living
- Glass & Windows

### Commercial

Sales workflow pages for business, storefront, facility, and commercial property projects.

- Solar Control & Safety
- Patio Screens & Awnings
- Glass & Windows
- Window Treatments

### Vendors

The canonical vendor and product source of truth.

- Vendor overview pages own contacts, official links, dealer portal references, warranty paths, FAQs, source inventory, training links, and collateral.
- Vendor product pages own product summaries, best-fit notes, related lines, warranty/FAQ links, quick links, and vendor hub links.
- Residential, Commercial, and System Category pages link into Vendors instead of repeating vendor detail.

System categories
-----------------

The five system categories are the customer-need layer. They should route people toward the right service categories and vendor/product pages.

| System category | Purpose | Examples |
|---|---|---|
| Climate Control | Reduce heat, glare, and energy load. | Somfy, Vantis, Accent/3M, Sunbelt/Avery Dennison, Alta, Hunter Douglas, Austin Screens, Draper |
| Privacy Control | Create privacy without sacrificing design. | Decorative Films, SolX, Frost, Accent/3M, Sunbelt/Avery Dennison, Alta, Hunter Douglas, Austin Screens, Draper |
| Patio Extension | Extend indoor comfort into outdoor living. | Old Castle / US Aluminum, Andersen, JELD-WEN, ShadePro Shade Systems, Four Seasons Patio Systems, Eclipse |
| Security and Safety | Protect people, property, and peace of mind. | Accent/3M, Sunbelt/Avery Dennison, Rollock Security Shutters |
| Home Automation & Control | Automate comfort, light, shade, and privacy. | Somfy, Vantis |

Ownership rule
--------------

Every page has one job:

- System Category pages route by customer outcome.
- Residential and Commercial pages route by sales workflow.
- Vendor pages hold vendor-level facts.
- Product pages hold product-line facts.
- Start Here pages explain how to use and maintain the hub.

Dealer portals and credentials
------------------------------

Dealer portal URLs may be stored in BookStack. Actual usernames, passwords, recovery codes, and shared credentials should live in 1Password. BookStack should only reference the credential location, such as "1Password: Vendor Portals / Eclipse".

Source files
------------

- `docs/navigation.md` is the structural source of truth.
- `docs/source.md` is the vendor source map.
- `docs/products.md` is the canonical vendor product inventory feeding the Vendors book.
- `docs/link-audit.md` tracks external link status, login gates, pending sources, and replacement needs.
