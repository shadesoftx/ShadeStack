# Product Taxonomy

This is the canonical product taxonomy and seed inventory used by the `Products` book.

The approved navigation model is:

```text
Start Here -> SOT Product Category -> Vendor -> Product -> Outcome Resources
```

Keep product entries focused on product families, collection pages, or the closest official hub for each line. When a vendor exposes a more specific product page, link directly to it.

## Status Labels

Use these labels consistently:

| Status | Meaning |
|---|---|
| Complete | All expected resources are present. |
| Partial | Some resources exist, but at least one major outcome resource is missing. |
| Needed | Documentation is required but has not been sourced. |
| Pending Review | Documentation exists but needs validation. |
| Not Applicable | This outcome category does not apply. |

## Product Categories

Initial categories:

- Shades
- Shutters
- Screens
- Pergolas
- Patio Covers
- Tint & Film
- Windows
- Doors
- Glass & Windows
- Outdoor Living
- Smart & Automation

## BookStack Placement

```text
Shelf: Products
  Book: SOT Product Category
    Chapter: Vendor
      Page: Vendor - Overview
      Page: Vendor - Product
```

Outcome resources are headings inside product pages, not child pages. The locked outcome order is:

1. Install Guides
2. Product Specs
3. Sales Collateral
4. Warranty

## Category Book Template

```text
# SOT Product Category

## Purpose
Short description of what this category covers.

## Vendors
- Vendor A
- Vendor B

## Products
Grouped by vendor.

## Common Sales Notes
Category-level notes useful to sales.

## Common Install Notes
Category-level install considerations and vendor install guide categories.

## Open Documentation Gaps
Missing specs, collateral, install guides, or warranty docs.
```

## Vendor Overview Template

```text
# Vendor - Overview

Product link strip

## Install Guides
Brand-wide or category-level install resources.

## Product Specs
Official product hubs, spec libraries, dealer/pro portals, CAD/BIM/spec references.

## Sales Collateral
Brochures, catalogs, sell sheets, customer-facing pages, and approved collateral sources.

## Warranty
Brand-wide warranty page/PDF, claim/support path, and product-specific exception reminders.
```

Generated vendor overview pages should not include separate containers for Vendor Reference, Products in this line, Categories Supported, FAQs, Quick Links, or Source Inventory. Put those links inside one of the four outcome sections or in the compact product link strip.

## Product Page Template

```text
# Vendor - Product

## Install Guides
Links to install guides, field notes, checklists, or job prep docs.
Show status badge and scope note.

## Product Specs
Links or notes for product specifications.
Show status badge and scope note.

## Sales Collateral
Links to brochures, sell sheets, comparison docs, or customer-facing materials.
Show status badge and scope note.

## Warranty
Links or notes for warranty coverage, claim process, and exclusions.
If brand-wide, link up to Vendor - Overview -> Warranty.
Show status badge and scope note.
```

Generated product pages should not include filler containers for Overview, Category, Vendor, Use Cases, Internal Notes, or Source Status. If that information becomes useful, put it in one of the four outcome sections or on the vendor overview.

## Naming Conventions

Pages use plain hyphen names:

- `Vendor - Overview`
- `Vendor - Product`

Source/resource names include vendor, product, resource type, and date when known:

```text
3M - Prestige Series - Product Specs - 2026-06-03
Eclipse - Exterior Screens - Install Guide - 2026-06-03
Pella - Impervia Windows - Warranty - 2026-06-03
```

## Category Vendor Map

| Product category | Vendors |
|---|---|
| Shades | Hunter Douglas, Alta, Norman, Draper, Eclipse, Vantis |
| Shutters | Hunter Douglas, Alta, Norman, Rollock Security Shutters |
| Screens | Austin Screens, Draper, ShadePro Shade Systems, Eclipse |
| Pergolas | ShadePro Shade Systems, Four Seasons Patio Systems, Eclipse |
| Patio Covers | Old Castle / US Aluminum, ShadePro Shade Systems, Four Seasons Patio Systems, Eclipse |
| Tint & Film | 3M, Accent, Sunbelt, Avery Dennison, Decorative Films, SolX, Frost, SmartTint, Ghost Glass, Vantis |
| Windows | Andersen, Pella, JELD-WEN, Dallas Flat Glass, Old Castle / US Aluminum |
| Doors | Andersen, Pella, JELD-WEN, CRL |
| Glass & Windows | Old Castle / US Aluminum, Andersen, Pella, JELD-WEN, CRL, Dallas Flat Glass, Ghost Glass |
| Outdoor Living | Austin Screens, Draper, ShadePro Shade Systems, Four Seasons Patio Systems, Eclipse |
| Smart & Automation | Somfy, Vantis, SmartTint, Ghost Glass |

## Product Coverage Ledger

This ledger separates known product inventory from collection work. Use it to decide what needs to be sourced before writing fuller product pages.

| Vendor | Primary category | Current product inventory | Product source status | Still need to find/populate |
|---|---|---|---|---|
| 3M | Tint & Film | 28 known public product, family, simulator, resource, and PDF entries | Partial | Product-by-product install guides, current sales collateral, dealer resources, warranty mapping by film family |
| Accent | Tint & Film | 1 distributor/product path entry | Needed | Official distributor URL, supported 3M product list, source URLs, portal, warranty/support path |
| Sunbelt | Tint & Film | 1 distributor/product path entry | Needed | Official distributor URL, supported Avery Dennison product list, source URLs, portal, warranty/support path |
| Avery Dennison | Tint & Film | 1 architectural window film family entry | Needed | Official product hub, film family URLs, specs, install guides, brochures, warranty/support path |
| Somfy | Smart & Automation | 1 motorization/control platform entry | Needed | Official product hub, controls list, compatibility docs, install/programming guides, warranty/support path |
| Vantis | Smart & Automation | 2 product entries: Smart Film and Shade Systems | Partial | Official product pages captured; install/programming docs, collateral, and warranty/support path still need confirmation |
| Hunter Douglas | Shades | 27 known product, treatment, shutter, door-covering, and automation entries | Partial | Dealer resource library, product-specific install PDFs, current brochures, warranty mapping by line |
| Alta | Shades | 13 known product, blind, shade, shutter, and automation entries | Partial | Install guides, current brochures/spec sheets, dealer portal, warranty mapping by line |
| Norman | Shades | 16 known shade, blind, shutter, and vertical treatment entries | Partial | Install guides, current brochures/spec sheets, dealer portal, warranty mapping by line |
| Eclipse | Screens | 13 known awning, screen, shade, roof-system, brochure, and comparison entries | Partial | Product-specific install guides, dealer/training resources, warranty mapping by product |
| Austin Screens | Screens | 1 solar screen entry | Needed | Official product hub, product family list, specs, install guides, sales collateral, warranty/support path |
| Draper | Shades | 1 shade/solar-control entry | Needed | Official product hub, shade/screen product lines, specs, install guides, collateral, warranty/support path |
| Decorative Films | Tint & Film | 1 decorative/privacy film entry | Needed | Official product hub, film family URLs, specs, install guides, brochures, warranty/support path |
| SolX | Tint & Film | 1 decorative/privacy film entry | Needed | Official product hub, specs, install guides, brochures, warranty/support path |
| Frost | Tint & Film | 1 frosted privacy film entry | Needed | Official product hub, specs, install guides, brochures, warranty/support path |
| Old Castle / US Aluminum | Windows | 1 storefront/glazing systems entry | Needed | Official product hub, system catalog, specs, install guides, warranty/support path |
| ShadePro Shade Systems | Pergolas | 1 patio shade systems entry | Needed | Official product hub, product lines, specs, install guides, collateral, warranty/support path |
| Four Seasons Patio Systems | Pergolas | 1 patio rooms/enclosures entry | Needed | Official product hub, product lines, specs, install guides, collateral, warranty/support path |
| Rollock Security Shutters | Shutters | 1 rolling security shutters entry | Needed | Official product hub, specs, install guides, collateral, warranty/support path |
| SmartTint | Tint & Film | 17 known smart film, smart glass, shop, controller, and application entries | Partial | Product-specific install guides, sales collateral, dealer pricing/login path, warranty mapping by product |
| Andersen | Windows | 11 known window, door, series, comparison, and big-door entries | Partial | Install guides by series, spec sheets by series, brochures, dealer/pro portal resources, warranty mapping |
| Pella | Windows | 25 known window, door, patio door, series, and product-family entries | Partial | Install guides by series, spec sheets by series, brochures, dealer/pro portal resources, warranty mapping |
| CRL | Doors | 22 known hardware, glass entrance, railing, glazing, and system entries | Partial | Exact product-level spec sheets, installation instructions, login-gated catalog resources, warranty mapping |
| Dallas Flat Glass | Windows | 13 known public company/product/service entries | Partial | Product catalog, specs, warranty terms, install/handling notes, rep/contact path |
| Ghost Glass | Tint & Film | 11 known smart/privacy film, install, FAQ, gallery, and application entries | Partial | Current product specs, sales collateral, dealer/installer notes, warranty mapping by product |
| JELD-WEN | Windows | 11 known window, door, patio door, brochure, and product hub entries | Partial | Install guides by series, specs by series, pro/dealer resources, warranty mapping |

## Product Resource Backlog

These are the product-page fields that still need real content before pages can be considered complete.

| Backlog area | Needed content |
|---|---|
| Install Guides | Official install PDFs, field notes, pre-install checklists, electrical/programming guides, handling notes, and category-level install routing |
| Product Specs | Dimensions, materials, options, colors/fabrics/finishes, compatibility, performance data, CAD/BIM/spec PDFs, discontinued/current status |
| Sales Collateral | Customer brochures, sell sheets, comparison charts, approved talking points, photo/application examples |
| Warranty | Product-specific or brand-wide warranty terms, claim process, exclusions, labor/material coverage, registration requirements |

## Vendor Product Inventory

The vendor inventory below is retained as seed/import data for product pages and link audits.

## 3M

### 3M
- [Building Window Solutions](https://www.3m.com/3M/en_US/building-window-solutions-us/) - Commercial window film hub.
- [Home Window Solutions](https://www.3m.com/3M/en_US/home-window-solutions-us/) - Residential window film hub.
- [3M Window Films](https://www.3m.com/3M/en_US/p/c/films-sheeting/window/) - Full architectural window film category.
- [3M Window Films for Architectural Design](https://www.3m.com/3M/en_US/p/c/films-sheeting/window/i/design-construction/architectural-design/) - Product family overview.
- [Residential Window Film Simulator](https://www.3m.com/3M/en_US/home-window-solutions-us/window-film-simulator/) - Compare film families and find a match.
- [All Season Window Film](https://www.3m.com/3M/en_US/p/d/b5005059014/) - Seasonal comfort and energy-saving film.
- [Night Vision Series](https://www.3m.com/3M/en_US/p/dc/v000056974/) - Sun control film family with reduced glare.
- [Night Vision 15](https://www.3m.com/3M/en_US/p/dc/v000056974/) - Darker Night Vision option.
- [Night Vision 35](https://www.3m.com/3M/en_US/p/dc/v000090930/) - Lighter Night Vision option.
- [Traditional Series](https://www.3m.com/3M/en_US/p/dc/v000585796/) - Classic sun control film family.
- [Privacy Film](https://www.3m.com/3M/en_US/p/d/b00021090/) - Privacy and decorative film family.
- [Ceramic Architectural Series](https://www.3m.com/3M/en_US/p/d/b5005542004/) - Ceramic sun control film family.
- [Prestige Series](https://multimedia.3m.com/mws/media/1704917O/prestige-series.pdf) - Residential film data sheet.
- [Prestige Exterior Series 50](https://www.3m.com/3M/en_US/p/dc/v101788738/) - Sun control film family.
- [Neutral Series 20](https://www.3m.com/3M/en_US/p/dc/v000585880/) - Sun control film family.
- [Silver Exterior Series 15X](https://www.3m.com/3M/en_US/p/d/b00016675/) - Reflective exterior film family.
- [Ceramic Series](https://multimedia.3m.com/mws/media/1704841O/ceramic-series.pdf) - Sun control ceramic series.
- [Ultra Night Vision Series](https://www.3m.com/3M/en_US/p/d/v000226027/) - Safety and security film family.
- [Ultra Prestige Series](https://multimedia.3m.com/mws/media/1173223O/3m-scotchshield-safety-security-window-film-ultra-prestige-series-family-card.pdf?fn=Ultra_Prestige_Family_Card_98-01) - Safety and security film family card.
- [Safety and Security Window Film](https://www.3m.com/3M/en_US/building-window-solutions-us/solutions/security/) - Security-focused film hub.
- [Scotchshield Safety & Security Window Film Series](https://www.3m.com/3M/en_US/p/c/films-sheeting/window/i/design-construction/architectural-design/) - Security film family in the architectural catalog.
- [Safety Series](https://www.3m.com/3M/en_US/p/c/films-sheeting/window/i/design-construction/architectural-design/) - Safety-focused family in the architectural catalog.
- [Exterior Series](https://www.3m.com/3M/en_US/p/d/b00016675/) - Exterior solar-control film family.
- [Anti-Graffiti Films](https://www.3m.com/3M/en_US/p/c/films-sheeting/window/i/design-construction/architectural-design/) - Protective specialty film family.
- [All Season Low E 35](https://www.3m.com/3M/en_US/p/dc/v000524445/) - Low-e seasonal comfort film.
- [All Season Low E 70](https://www.3m.com/3M/en_US/p/dc/v100819416/) - Higher light transmission low-e film.
- [Safety & Security Window Film Technical Data](https://multimedia.3m.com/mws/media/1704931O/safety-s70.pdf) - Security film reference.
- [Envision Window Film](https://www.3m.com/3M/en_US/home-window-solutions-us/envision-window-film/) - Residential film line page.

## Accent

### Accent
- Accent / 3M Distribution - Distributor path for 3M climate control, privacy control, and security film resources. Needed: official distributor URL, supported product list, portal path, and warranty/support source.

## Sunbelt

### Sunbelt
- Sunbelt / Avery Dennison Distribution - Distributor path for Avery Dennison climate control, privacy control, and security film resources. Needed: official distributor URL, supported product list, portal path, and warranty/support source.

## Avery Dennison

### Avery Dennison
- Avery Dennison Architectural Window Films - Climate control, privacy control, and safety film product family. Needed: official product hub, film family URLs, specs, install guides, collateral, and warranty/support source.

## Somfy

### Somfy
- Somfy Motorization - Motorization and control platform for shades and exterior systems. Needed: official product hub, controls list, compatibility docs, install/programming guides, and warranty/support source.

## Vantis

### Vantis
- [Smart Film](https://vantisshades.com/) - Vantis smart film product line.
- [Shade Systems](https://vantisshades.com/shade-systems) - Vantis shade systems product line.

## Hunter Douglas

### Hunter Douglas
- [Window Treatments](https://www.hunterdouglas.com/window-treatments) - Main product hub.
- [Alustra Architectural Roller Shades](https://www.hunterdouglas.com/product/alustra-architectural) - Distinctive roller shade with fabric-covered battens.
- [Duette Honeycomb Shades](https://www.hunterdouglas.com/window-treatments/shades/cellular-shades/duette) - Energy-efficient honeycomb shades.
- [Applause Cellular Shades](https://www.hunterdouglas.com/window-treatments/shades/cellular-shades) - Value-focused cellular shade family.
- [Silhouette Sheer Shadings](https://www.hunterdouglas.com/window-treatments/shades/sheer-shades/silhouette) - Light-diffusing sheer shades.
- [Pirouette Sheer Shades](https://www.hunterdouglas.com/window-treatments/shades/sheer-shades/pirouette?vt-k=hunterdouglaspirouetteblinds) - Sculpted sheer shading.
- [Luminette Privacy Sheers](https://www.hunterdouglas.com/window-treatments/shades/sheer-shades/luminette) - Vertical sheer panels for large openings.
- [Designer Roller Shades](https://www.hunterdouglas.com/window-treatments/shades/roller-shades/designer-roller) - Clean, versatile roller shades.
- [Designer Solar Shades](https://www.hunterdouglas.com/window-treatments/shades/roller-shades) - Light and glare control shade family.
- [Designer Banded Shades](https://www.hunterdouglas.com/window-treatments/shades/roller-shades/designer-banded?pp=0) - Roller and sheer banded shade.
- [Alustra Woven Textures Roller Shades](https://www.hunterdouglas.com/window-treatments/shades/roller-shades/alustra-woven-textures-roller) - Designer roller collection.
- [Alustra Woven Textures Roman Shades](https://www.hunterdouglas.com/window-treatments/shades/roman-shades/alustra-woven-textures-roman) - Transitional roman collection.
- [Provenance Woven Wood Shades](https://www.hunterdouglas.com/window-treatments/shades/woven-shades/provenance?orientation=horizontal) - Natural woven shades.
- [Sonnette Cellular Roller Shades](https://www.hunterdouglas.com/window-treatments/shades/roller-shades) - Energy-efficient cellular roller shade family.
- [Skyline Gliding Window Panels](https://www.hunterdouglas.com/window-treatments/blinds/vertical-blinds/skyline?vt-k=skyline+panels) - Panel-track blinds for wide openings.
- [Somner Vertical Blinds](https://www.hunterdouglas.com/window-treatments/blinds/vertical-blinds/somner?vt-k=somner%2520coverings) - Textured vertical blinds.
- [Vertical Solutions Vertical Blinds](https://www.hunterdouglas.com/window-treatments/blinds/vertical-blinds) - Minimal vertical blind collection.
- [Modern Precious Metals](https://www.hunterdouglas.com/window-treatments/blinds/metal-blinds/modern-precious-metals?vt-k=modern+precious+metal+hunter+douglas) - Aluminum mini blinds.
- [EverWood Faux Wood Blinds](https://www.hunterdouglas.com/window-treatments/blinds/wood-blinds/everwood?vt-k=everwoodtrugrainshades) - Alternative wood blinds.
- [Parkland Wood Blinds](https://www.hunterdouglas.com/window-treatments/blinds/wood-blinds/parkland) - Hardwood blind collection.
- [Heritance Hardwood Shutters](https://www.hunterdouglas.com/window-treatments/shutters/heritance) - Hardwood shutter line.
- [NewStyle Hybrid Shutters](https://www.hunterdouglas.com/window-treatments/shutters/newstyle?cmp=os-pinterest) - Composite shutter line.
- [Palm Beach Polysatin Shutters](https://www.hunterdouglas.com/window-treatments/shutters/palm-beach?%253Fcmp=fb_dpa_retargeting) - Vinyl shutter line.
- [Door Coverings](https://www.hunterdouglas.com/window-treatments/door-coverings) - Door and large-opening treatments.
- [Vignette Roman Shades](https://www.hunterdouglas.com/window-treatments/shades/roman-shades/vignette) - Modern Roman shade family.
- [Carole Fabrics Roman Shades](https://www.hunterdouglas.com/window-treatments/shades/roman-shades/carole-roman) - Partner-company Roman shades.
- [PowerView Automation](https://www.hunterdouglas.com/innovation) - Motorization and smart control platform.

## Alta

### Alta
- [Product Hub](https://www.altawindowfashions.com/product) - Main product page.
- [Motorized Blinds and Shades](https://www.altawindowfashions.com/product) - Automation-ready window coverings.
- [Honeycomb Shades](https://prod.altawindowfashions.com/en/product/honeycomb-shades) - Energy-efficient cellular shades.
- [Roller Shades](https://prod.altawindowfashions.com/en/product/roller-shades) - Minimal, modern shade solution.
- [Banded Shades](https://prod.altawindowfashions.com/en/product/banded-shades) - Layered sheer and solid bands.
- [Sheer Shadings](https://www.altawindowfashions.com/product/sheer-shadings) - Soft light-filtering shades.
- [Wood Blinds](https://www.altawindowfashions.com/product/wood-blinds/) - Classic wood blind line.
- [Faux Wood Blinds](https://www.altawindowfashions.com/product/faux-wood-blinds/) - Durable, moisture-resistant blinds.
- [Natural Woven Shades](https://prod.altawindowfashions.com/en/product/natural-woven-shades) - Texture-forward woven shades.
- [Vertical Blinds](https://www.altawindowfashions.com/product/vertical-blinds) - Sliding-door friendly coverage.
- [Custom Shutters](https://www.altawindowfashions.com/product/custom-shutters/wood-shutters) - Tailored shutter solutions.
- [Eclipse Polyresin Window Shutters](https://www.altawindowfashions.com/product/custom-shutters/eclipse-shutters) - Moisture-resistant shutter line.
- [Aluminum Blinds](https://www.altawindowfashions.com/product/aluminum-blinds) - Lightweight, durable blind option.

## Norman

### Norman
- [Window Treatments](https://normanusa.com/window-treatments/) - Main product hub.
- [Portrait Honeycomb Shades](https://normanusa.com/product/portrait-honeycomb/) - Cellular shade line.
- [Soluna Roller Shades](https://normanusa.com/product/soluna-roller-shades/) - Modern roller shade collection.
- [Soluna Solar Shades](https://normanusa.com/window-treatments/shades/roller-shades/) - Solar shade option within the Soluna family.
- [Centerpiece Roman Shades](https://normanusa.com/product/centerpiece-roman/) - Clean Roman shade line.
- [SmartDrape Shades](https://normanusa.com/product/smartdrape-shades/) - Walk-through sheer shade.
- [SmartFold Shades](https://normanusa.com/product/smartfold-shades/) - Liftable fold shade collection.
- [PerfectSheer Shades](https://normanusa.com/product/perfectsheer-shades/) - Hybrid shade with strong light control.
- [Ultimate Faux Wood Blinds](https://normanusa.com/product/ultimate-faux-wood-blinds/) - Cordless faux wood blind.
- [Custom Ultimate Normandy Wood Blinds](https://normanusa.com/product/ultimate-normandy-wood-blinds) - Premium wood blind line.
- [Woodlore Shutters](https://normanusa.com/product/woodlore-shutters/) - Wood composite shutter line.
- [Woodlore Plus Shutters](https://normanusa.com/product/woodlore-plus-shutters/) - Composite shutter line.
- [Brightwood Shutters](https://normanusa.com/product/brightwood-shutters/) - Premium composite shutter line.
- [Normandy Shutters](https://normanusa.com/product/normandy-shutters/) - Hardwood shutter line.
- [AquaShield Shutters](https://normanusa.com/product/aquashield-shutters/) - Waterproof shutter line.
- [Synchrony Vertical Blinds](https://normanusa.com/product/synchrony-blinds/) - Modern vertical blind line.

## Eclipse

### Eclipse
- [Retractable Awnings](https://eclipseshading.com/) - Main product hub.
- [Shading Products](https://eclipseshading.com/shading-products/) - Overview of the full shading catalog.
- [The Eclipse](https://eclipseshading.com/products/the-eclipse/) - Core retractable awning.
- [The Eclipse Lite / E-Lite](https://eclipseshading.com/e-lite-motorized-retractable-awning/) - Economical retractable awning.
- [The Eclipse Premier](https://eclipseshading.com/products/eclipse-premier-retractable-awning/) - Upgraded retractable awning.
- [The Total Eclipse](https://eclipseshading.com/products/total-eclipse-retractable-awning/) - Commercial long-projection awning.
- [Solar Eclipse](https://eclipseshading.com/products/solar-eclipse/) - European-inspired cassette awning.
- [Drop Shade](https://eclipseshading.com/products/eclipse-drop-shade/) - Retractable drop shade.
- [Retractable Exterior Screens](https://eclipseshading.com/shading-products/) - Exterior screen family.
- [Interior Roller Shades](https://eclipseshading.com/shading-products/) - Interior shade family.
- [Roof Systems](https://eclipseshading.com/shading-products/) - Louvered roof system family.
- [Eclipse Product Comparison](https://eclipseshading.com/wp-content/uploads/Eclipse-Product-Comparison-v8.24.pdf) - Product comparison guide.
- [Exterior Screens](https://eclipseshading.com/wp-content/uploads/EAS_ExteriorScreens.pdf) - Exterior screen reference.

## Austin Screens

### Austin Screens
- Austin Screens Solar Screens - Solar screen product reference for climate and privacy control. Needed: official product hub, product family list, specs, install guides, collateral, and warranty/support source.

## Draper

### Draper
- Draper Shades and Solar Control - Shade, screen, and solar-control product reference. Needed: official product hub, product family list, specs, install guides, collateral, and warranty/support source.

## Decorative Films

### Decorative Films
- Decorative Films Privacy Films - Decorative and privacy film product family. Needed: official product hub, film family URLs, specs, install guides, collateral, and warranty/support source.

## SolX

### SolX
- SolX Decorative Film - Decorative and privacy film reference. Needed: official product hub, specs, install guides, collateral, and warranty/support source.

## Frost

### Frost
- Frost Privacy Film - Frosted privacy film reference. Needed: official product hub, specs, install guides, collateral, and warranty/support source.

## Old Castle / US Aluminum

### Old Castle / US Aluminum
- Old Castle / US Aluminum Storefront Systems - Commercial storefront and glazing system reference. Needed: official product hub, system catalog, specs, install guides, and warranty/support source.

## ShadePro Shade Systems

### ShadePro Shade Systems
- ShadePro Patio Shade Systems - Patio shade and extension system reference. Needed: official product hub, product lines, specs, install guides, collateral, and warranty/support source.

## Four Seasons Patio Systems

### Four Seasons Patio Systems
- Four Seasons Patio Rooms and Enclosures - Patio room, enclosure, and extension system reference. Needed: official product hub, product lines, specs, install guides, collateral, and warranty/support source.

## Rollock Security Shutters

### Rollock Security Shutters
- Rollock Rolling Security Shutters - Security shutter product reference for safety and security projects. Needed: official product hub, specs, install guides, collateral, and warranty/support source.

## SmartTint

### SmartTint
- [Home](https://www.smarttint.com/) - Main product hub.
- [Smart Tint Smart Film](https://www.smarttint.com/) - Core switchable film system.
- [Smart Cling Smart Tint Grey](https://www.smarttint.com/product/smart-tint-grey/) - Self-adhesive grey film.
- [Smart Cling Smart Tint Black](https://www.smarttint.com/product/smart-tint-black/) - Self-adhesive black film.
- [Smart Cling Smart Tint White](https://www.smarttint.com/product/smart-tint-white/) - Self-adhesive white film.
- [Smart Tint Technology COLORS](https://shop.smarttint.com/Smart-Tint%C2%AE-Technology-COLORS-custom-cut-pre-wired-and-ready-to-install-_b_6.html) - Color and custom-cut film family.
- [Smart Tint Shop](https://shop.smarttint.com/) - Dealer shop and product catalog.
- [Smart Plexi Glass](https://www.smarttint.com/smart-plexi-glass/) - Pre-built smart glass panel.
- [Smart Plexi Create Smart Glass](https://www.smarttint.com/smart-plexi-create-smart-glass/) - Plexi-based smart glass solution.
- [Smart Plexi Glass Interactive Plexi](https://shop.smarttint.com/Interactive-Plexi-Glass_b_52.html) - Interactive plexi product family.
- [Stealth Tint Windshield](https://www.smarttint.com/product/stealth-tint-windshield/) - Automotive windshield product.
- [Stealth Tint](https://www.smarttint.com/product/stealth-tint-windshield/) - Reverse-operating smart film family.
- [Riot Tint](https://www.smarttint.com/) - Security film family.
- [Smart Blinds](https://www.smarttint.com/) - Smart blind solution family.
- [Flip Tint](https://www.smarttint.com/wp-content/uploads/2025/06/Flip-Tint-by-Smart-Tint-Technical-Data-Sheet-1.pdf) - Reverse-operating smart film.
- [Smart Tint Glass Controller](https://www.smarttint.com/smart-tint-glass-controller/) - App and control interface.
- [Applications](https://www.smarttint.com/applications/) - Application examples and use cases.

## Andersen

### Andersen
- [E-Series](https://www.andersenwindows.com/windows-and-doors/series/e-series) - Ultimate flexibility and design freedom.
- [A-Series](https://www.andersenwindows.com/windows-and-doors/series/a-series/) - Architectural collection.
- [400 Series](https://www.andersenwindows.com/windows-and-doors/series/400-series/) - Clad wood windows and doors.
- [200 Series](https://www.andersenwindows.com/windows-and-doors/series/200-series/) - Balanced innovation and price.
- [100 Series](https://www.andersenwindows.com/windows-and-doors/series/100-series) - Fibrex composite window line.
- [Andersen Aluminum](https://www.andersenwindows.com/windows-and-doors/series/andersen-aluminum/) - All-aluminum line.
- [Compare Windows](https://www.andersenwindows.com/windows-and-doors/windows/compare/) - Full windows-by-series comparison.
- [Compare Patio Doors](https://www.andersenwindows.com/windows-and-doors/doors/compare-doors/) - Full patio doors-by-series comparison.
- [Big Doors](https://www.andersenwindows.com/windows-and-doors/doors/big-doors/) - Oversized patio doors and glass wall systems.
- [Entry Doors](https://www.andersenwindows.com/windows-and-doors/doors/entry-doors) - Residential entry door hub.
- [Sliding Glass Patio Doors](https://www.andersenwindows.com/windows-and-doors/doors/sliding-patio-doors) - Sliding patio door family.

## Pella

### Pella
- [Windows](https://www.pella.com/ideas/windows/) - Main window hub.
- [Doors](https://www.pella.com/ideas/doors/) - Main door hub.
- [Patio Doors](https://www.pella.com/ideas/doors/patio-doors/) - Patio door category.
- [Reserve Traditional Windows](https://www.pella.com/shop/windows/reserve/traditional/) - Historic wood and aluminum-clad wood window line.
- [Reserve Contemporary Windows](https://www.pella.com/ideas/windows/reserve/) - Contemporary wood and aluminum-clad wood window line.
- [Lifestyle Series](https://www.pella.com/ideas/windows/lifestyle-series/) - Everyday wood window line.
- [Impervia Windows](https://www.pella.com/ideas/windows/pella-impervia/) - Fiberglass windows and patio doors.
- [250 Series Windows](https://www.pella.com/ideas/windows/250-series/) - Vinyl window line.
- [Encompass by Pella Windows](https://www.pella.com/ideas/windows/encompass/) - Budget-friendly vinyl windows.
- [Hurricane Shield Series](https://www.pella.com/ideas/windows/hurricaneshield/) - Impact-resistant vinyl windows.
- [Defender Series](https://www.pella.com/ideas/windows/defender-series/) - Storm-protection vinyl windows.
- [Reserve Casement Windows](https://www.pella.com/shop/windows/reserve/traditional/casement-windows/) - Traditional casement line.
- [Double-Hung Windows](https://www.pella.com/ideas/windows/double-hung/) - Double-hung window family.
- [Single-Hung Windows](https://www.pella.com/ideas/windows/single-hung/) - Single-hung window family.
- [Bay Windows](https://www.pella.com/ideas/windows/bay-windows/) - Bay window family.
- [Bow Windows](https://www.pella.com/shop/windows/bow/) - Bow window family.
- [Custom Windows](https://www.pella.com/ideas/windows/) - Made-to-order specialty windows.
- [Reserve Patio Doors](https://www.pella.com/ideas/doors/patio-doors/reserve/) - Custom wood patio doors.
- [Lifestyle Series Patio Doors](https://www.pella.com/ideas/doors/patio-doors/lifestyle-series/) - Wood patio doors.
- [Impervia Patio Doors](https://www.pella.com/ideas/doors/patio-doors/pella-impervia/) - Fiberglass patio doors.
- [250 Series Patio Doors](https://www.pella.com/ideas/doors/patio-doors/250-series/) - Vinyl patio doors.
- [Encompass Patio Doors](https://www.pella.com/ideas/doors/patio-doors/) - Budget-friendly vinyl patio doors.
- [Hurricane Shield Patio Doors](https://www.pella.com/ideas/doors/patio-doors/) - Coastal patio doors.
- [Front Doors](https://www.pella.com/ideas/doors/) - Front entry door hub.
- [Auraline True Composite](https://www.corporate.jeld-wen.com/newsroom/press-releases/2022/04-26-2022-145756555) - Composite window and patio door line.

## CRL

### CRL
- [Shower Hardware](https://www.crlaurence.com/productcategory/ShowerHardware) - Frameless shower hardware systems.
- [Trento Hinges](https://www.crlaurence.com/productcategory/ShowerHardware) - Featured shower hinge collection.
- [Zero Collection](https://www.crlaurence.com/productcategory/ShowerHardware) - Patented hinge and clamp system.
- [Premium Shower Sliders](https://www.crlaurence.com/productcategory/ShowerHardware) - Premium slider line.
- [Shower Sliders](https://www.crlaurence.com/productcategory/ShowerHardware) - Core slider line.
- [Premium Handles & Towel Bars](https://www.crlaurence.com/productcategory/ShowerHardware) - Shower accessory line.
- [Showers Online](https://www.crlaurence.com/productcategory/ShowerHardware) - Online estimating software.
- [690/695 Sliding Door System](https://www.crlaurence.com/productcategory/GlassEntranceInteriorSystems) - Sliding glass door system.
- [Fallbrook Interior Partition Systems](https://www.crlaurence.com/productcategory/GlassEntranceInteriorSystems) - Interior partition systems.
- [Palisades Sliding & Bi-Folding Doors](https://www.crlaurence.com/productcategory/GlassEntranceInteriorSystems) - Folding and sliding door systems.
- [Blumcraft Entice Series Entrance System](https://www.crlaurence.com/productcategory/GlassEntranceInteriorSystems) - Entrance system family.
- [Blumcraft Panic Devices](https://www.crlaurence.com/productcategory/GlassEntranceInteriorSystems) - Panic hardware line.
- [Unitized Glass Railing System](https://www.crlaurence.com/productcategory/RailingWindscreenSystems) - Glass railing system.
- [Taper-Loc Glass Railing Installation System](https://www.crlaurence.com/productcategory/RailingWindscreenSystems) - Railing installation system.
- [Glass Entrance & Interior Systems](https://www.crlaurence.com/productcategory/GlassEntranceInteriorSystems) - Interior glass hardware and entrance systems.
- [Railing & Windscreen Systems](https://www.crlaurence.com/productcategory/RailingWindscreenSystems) - Glass railing and windscreen systems.
- [Door & Window Hardware](https://www.crlaurence.com/productcategory/DoorWindowHardware) - Door and window hardware.
- [Glazing Tools & Supplies](https://www.crlaurence.com/productcategory/GlazingToolsSupplies) - Professional glazing tools and consumables.
- [US Aluminum](https://www.crlaurence.com/productcategory/USAluminum) - U.S. Aluminum systems.
- [Hospitality & Display Systems](https://www.crlaurence.com/productcategory/HospitalityDisplaySystems) - Retail and display systems.
- [Service & Security Systems](https://www.crlaurence.com/productcategory/ServiceSecuritySystems) - Service windows and security systems.
- [Automotive Windows & Supplies](https://www.crlaurence.com/productcategory/AutomotiveWindowsSupplies) - Auto glass and supply products.

## Dallas Flat Glass

### Dallas Flat Glass
- [Home](https://dallasflatglass.com/) - Public company home page.
- [Insulated Glass](https://dallasflatglass.com/) - Core wholesale and replacement offering.
- [Mirrors](https://dallasflatglass.com/) - Custom mirrors and mirrored glass.
- [Pattern Glass](https://dallasflatglass.com/) - Decorative and patterned glass.
- [Custom Beveled Glass](https://dallasflatglass.com/) - Specialty decorative fabrication.
- [Specialty Glass](https://dallasflatglass.com/) - Custom specialty glass work.
- [Low-E Glass](https://dallasflatglass.com/) - Energy-focused glass options.
- [Laminated Glass](https://dallasflatglass.com/) - Safety and performance glass.
- [Fully Tempered Glass](https://dallasflatglass.com/) - Tempered safety glass.
- [Heavy Glass](https://dallasflatglass.com/) - Large-format glass applications.
- [Monolithic Glass](https://dallasflatglass.com/) - Single-lite glass products.
- [All Glass Entrance Systems](https://dallasflatglass.com/) - Entrance and storefront glass systems.
- Needed: product catalog access, specs, warranty terms, install/handling notes, and rep/contact path.

## Ghost Glass

### Ghost Glass
- [PDLC Privacy Film](https://ghostglassfilm.com/pdlc-privacy-film) - Core smart film offering.
- [Privacy Film](https://ghostglassfilm.com/privacy-film) - Privacy-focused smart film page.
- [Privacy Window Lite](https://ghostglassfilm.com/privacy-window-lite) - Privacy film variant.
- [Privacy and Protection](https://ghostglassfilm.com/privacy-and-protection) - Security-oriented film page.
- [Benefits](https://ghostglassfilm.com/benefits) - Product benefits overview.
- [Smart Film DIY](https://ghostglassfilm.com/smart-film-diy) - DIY installation entry point.
- [Smart Film Installation Guide](https://ghostglassfilm.com/smart-film-installation-guide) - Install reference.
- [Installation](https://ghostglassfilm.com/smart-film-installation-guide) - How-to install page.
- [About Us](https://ghostglassfilm.com/about-us) - Company and product context.
- [Installations](https://ghostglassfilm.com/installations) - Completed installations and gallery.
- [FAQ](https://ghostglassfilm.com/faqs-1) - Product and ordering FAQ.

## JELD-WEN

### JELD-WEN
- [Windows](https://www.jeld-wen.com/en-us/products/windows) - Window product hub.
- [Exterior Doors](https://www.jeld-wen.com/en-us/products/exterior-doors/steel/8ft-flush) - Exterior door family.
- [Interior Doors](https://www.jeld-wen.com/en-us/products/interior-doors/tria-composite/l1000-all-panel) - Interior door family.
- [Patio Doors](https://brandstore.jeld-wen.com/store/20200807975/assets/pdfs/brochuresPDF/17-96725%20Patio%20Door%20071318.pdf) - Patio door brochure.
- [Auraline True Composite](https://www.corporate.jeld-wen.com/newsroom/press-releases/2022/06-21-2022-145756008) - Composite window and patio door line.
- [IWP Aurora Fiberglass Doors](https://www.corporate.jeld-wen.com/newsroom/press-releases/2021/12-02-2021-145757322) - Luxury fiberglass exterior door line.
- [Design Pro Fiberglass Doors](https://www.jeld-wen.com/en-us/products/exterior-doors/design-pro-fiberglass/fir-half-view-2-panel-glass-panel) - Fiberglass exterior door family.
- [Simply Modern Interior Doors](https://www.jeld-wen.com/en-us/style/collection/interior-doors/simply-modern) - Contemporary interior door collection.
- [Windows & Doors Product Hub](https://www.jeld-wen.com/en-us/products/windows) - Current product hub for the full window/door lineup.
- [Patio Doors Product Hub](https://www.jeld-wen.com/en-us/products/patio-doors) - Patio door product hub.
- [Brands and Products](https://www.corporate.jeld-wen.com/brands-and-products) - Corporate product overview.
