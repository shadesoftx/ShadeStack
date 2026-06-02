# Navigation & Structure

This document defines how the Shades of Texas internal knowledge base is organized, how people move through it, and how every page should be built. It is the structural source of truth for the sales hub.

The navigation model is:

```text
Customer Need -> System Category -> Residential/Commercial Category -> Source Reference
```

The hub should feel like a practical sales map, not a file cabinet.

---

## Core hierarchy

BookStack has four layers:

| Layer | Our name | Purpose |
|---|---|---|
| Bookshelf | `High Level` | Single top-level container for the hub |
| Book | `Start Here`, `Residential`, `Commercial`, `Vendors` | Main navigation areas |
| Chapter | `Tint & Film`, `Window Treatments`, vendor names, etc. | Grouping layer inside a book |
| Page | `Climate Control`, `Window Glass`, `Andersen`, etc. | Actual content and routing pages |

Only these four books should appear as operational top-level areas:

```text
High Level
├── Start Here
├── Residential
├── Commercial
└── Vendors
```

Do not create separate top-level books for specs, warranties, FAQs, training, or collateral. Those resources belong on the relevant vendor or product page and are reached through internal links.

---

## Page jobs

Every page has one job.

| Page type | Job | Should contain | Should not contain |
|---|---|---|---|
| System Category page | Route by customer outcome | Relevant service lanes and supporting source references | Full specs, warranty text, portal credentials |
| Residential/Commercial page | Route by sales workflow | Sales context and secondary source-reference chips | Duplicated vendor documentation |
| Vendor page | Vendor content source of truth in BookStack | Contacts, official links, warranties, FAQs, source inventory, dealer portal references, collateral | Repeated service-category explanations |
| Product page | Product-line content source of truth in BookStack | Summary, fit, related lines, quick links, warranty/FAQ paths | Unrelated vendor catalog content |
| Start Here page | Usage and governance | How to navigate, request changes, and verify sources | Product detail |

---

## System categories

The five System Categories live under `Start Here`. They are the first customer-need layer and should route users into the existing Residential and Commercial sales categories first, with vendor/product pages shown as secondary source references.

| System category | Description | Examples |
|---|---|---|
| Climate Control | Reduce heat, glare, and energy load. | Somfy, Vantis, Accent/3M, Sunbelt/Avery Dennison, Alta, Hunter Douglas, Austin Screens, Draper |
| Privacy Control | Create privacy without sacrificing design. | Decorative Films, SolX, Frost, Accent/3M, Sunbelt/Avery Dennison, Alta, Hunter Douglas, Austin Screens, Draper |
| Patio Extension | Extend indoor comfort into outdoor living. | Old Castle / US Aluminum, Andersen, JELD-WEN, ShadePro Shade Systems, Four Seasons Patio Systems, Eclipse |
| Security and Safety | Protect people, property, and peace of mind. | Accent/3M, Sunbelt/Avery Dennison, Rollock Security Shutters |
| Home Automation & Control | Automate comfort, light, shade, and privacy. | Somfy, Vantis |

System pages are navigation pages. They should summarize the need, list common examples, link to matching sales categories, and show relevant vendor/product pages as source references rather than the primary next step.

---

## Expected click paths

When the customer describes a problem:

```text
Home
  -> System Category
    -> Residential or Commercial category
      -> Source reference if specs, warranty, install guide, collateral, or dealer portal info is needed
```

When the rep already knows the vendor:

```text
Home
  -> Vendors
    -> Vendor page
      -> Product line
        -> Official source
```

When something looks stale:

```text
Page with bad/missing info
  -> Vendor/product page in BookStack
    -> Verify against official source or rep
      -> Update affected routing pages only if positioning changed
```

---

## Book structure

### Start Here

- Start Here
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

The `Start Here` page should be a task-button landing page, not the default BookStack book list. Its primary buttons are Find Product, Check Warranty, Get Specs, Install Guide, Brochure / Collateral, Pricing / Portal, Rep Contact, and Request Update.

### Residential

- Tint & Film
  - Safety & Security Film
  - Solar Film
  - Privacy Film
- Window Treatments
  - Window Shades
  - Window Shutters
  - Window Blinds
  - Safety / Storm Shutters
- Outdoor Living
  - Shade Structures
  - Patio Awnings
  - Patio Screens
- Glass & Windows
  - Window Glass
  - Frameless Showers
  - Window Cleaning

### Commercial

- Solar Control & Safety
  - Sun Control Film
  - Safety & Security Film
  - Privacy Film
  - SmartTint
- Patio Screens & Awnings
  - Patio Awnings
  - Patio Screens
- Glass & Windows
  - Commercial Glazing
- Window Treatments
  - Roller Shades

### Vendors

Vendor chapters contain a vendor overview page plus product-line pages.

Current vendor chapters:

- 3M
- Accent
- Sunbelt
- Avery Dennison
- Somfy
- Vantis
- Hunter Douglas
- Alta
- Norman
- Eclipse
- Austin Screens
- Draper
- Decorative Films
- SolX
- Frost
- Old Castle / US Aluminum
- ShadePro Shade Systems
- Four Seasons Patio Systems
- Rollock Security Shutters
- SmartTint
- Andersen
- Pella
- CRL
- Dallas Flat Glass
- Ghost Glass
- JELD-WEN

---

## Internal linking rules

### Link to the right level

| If you want to send someone to... | Link to... |
|---|---|
| A customer outcome | A System Category page under `Start Here` |
| A service family | A Residential or Commercial chapter |
| A specific sales category | A Residential or Commercial page |
| A vendor reference | The vendor overview page in `Vendors` |
| A product line | The product page under that vendor chapter |
| A warranty, FAQ, portal, install guide, or collateral source | The relevant vendor/product page section |

### Keep external links canonical

Residential, Commercial, and System Category pages should use internal links first. External vendor links belong on vendor and product pages so updates happen in one place.

Correct:

```text
Climate Control -> Solar Film -> 3M product page -> official 3M source
```

Incorrect:

```text
Climate Control -> official 3M warranty PDF
```

### Do not duplicate vendor facts

Do not repeat these across routing pages:

- Vendor contacts
- Dealer portal URLs
- Dealer credentials
- Warranty details
- Technical specifications
- Installation guides
- Training links
- Sales and marketing collateral
- FAQ/support links

Routing pages can mention that these resources exist, but they should link to the vendor or product page that owns them.

### Dealer portal credentials

BookStack may list the dealer portal URL and the internal credential reference. Actual usernames, passwords, recovery codes, and shared credentials must live in 1Password.

Use this pattern:

```text
Dealer Portal: https://vendor.example.com/login
Credentials: 1Password -> Vendor Portals / Vendor Name
```

---

## Service-to-vendor map

| Service category | Vendors |
|---|---|
| Safety & Security Film | Accent, 3M, Sunbelt, Avery Dennison |
| Solar Film | Accent, 3M, Sunbelt, Avery Dennison |
| Privacy Film | Decorative Films, SolX, Frost, Accent, 3M, Sunbelt, Avery Dennison |
| Window Shades | Somfy, Vantis, Hunter Douglas, Alta, Draper, Norman |
| Window Shutters | Hunter Douglas, Alta, Norman |
| Window Blinds | Hunter Douglas, Alta, Norman |
| Safety / Storm Shutters | Rollock Security Shutters, Norman |
| Shade Structures | ShadePro Shade Systems, Four Seasons Patio Systems, Eclipse |
| Patio Awnings | ShadePro Shade Systems, Four Seasons Patio Systems, Eclipse |
| Patio Screens | Austin Screens, Draper, ShadePro Shade Systems, Eclipse |
| Window Glass | Old Castle / US Aluminum, Andersen, JELD-WEN, Pella, Dallas Flat Glass |
| Frameless Showers | CRL |
| Sun Control Film | Accent, 3M, Sunbelt, Avery Dennison |
| SmartTint | SmartTint |
| Commercial Glazing | Old Castle / US Aluminum, Andersen, JELD-WEN, CRL, Pella, Dallas Flat Glass |
| Roller Shades | Somfy, Vantis, Hunter Douglas, Alta, Draper, Norman |

---

## Content priority

Build and maintain content in this order:

1. Confirm the vendor/product source page exists in `Vendors`.
2. Add or update official links, warranty paths, FAQ links, install/training links, collateral, and portal references there.
3. Link Residential or Commercial category pages to the vendor/product page only when the sales route changes.
4. Link System Category pages to the relevant Residential or Commercial category pages first.
5. Keep audit findings open until the canonical BookStack page has been corrected.

---

## Common mistakes to avoid

- Creating a new top-level book for a resource type.
- Linking from a routing page straight to an external vendor PDF when an internal vendor/product page should own that link.
- Repeating warranty or spec details across multiple service pages.
- Putting credentials in BookStack instead of 1Password.
- Using `Products` as the main area name. The canonical name is `Vendors`.
- Treating System Categories as content dumps instead of routing pages.

For the master vendor source map, see [docs/source.md](source.md).
For the project overview, see [docs/readme.md](readme.md).
