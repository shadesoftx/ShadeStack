# Link Audit

Last automated audit: 2026-05-27
Last browser reconciliation: 2026-06-02
Product-first tracker updated: 2026-06-03

This document tracks link health and product documentation gaps for ShadeStack.

The audit covers links and resource gaps from:

- `docs/source.md`
- `docs/source-collection.md`
- `docs/products.md`
- `database/seeders/ShadesOfTexasStructureSeeder.php`
- BookStack product pages after seeding

## Status Tags

| Tag | Meaning |
|---|---|
| Complete | All expected product resources are present. |
| Partial | Some resources exist, but at least one major outcome category is missing. |
| Needed | Documentation is known to be required but has not been sourced. |
| Pending Review | Documentation exists but needs validation. |
| Not Applicable | This outcome category does not apply. |
| Login Required | Resource is protected by a portal, dealer app, pricing page, or gated vendor site. Credentials belong in 1Password. |
| Broken / Replace | Link failed clearly or points to a source that should be replaced. |
| Duplicate | Same document appears in more than one place and should be consolidated. |
| Outdated | PDF or source appears stale and needs a fresher version. |

## Working Tracker

| Product category | Vendor | Product/resource | Issue type | Status | Notes |
|---|---|---|---|---|---|
| Tint & Film | Accent | Distributor-specific public product URLs | Missing product docs | Needed | Confirm Accent-specific resources with rep. |
| Tint & Film | Sunbelt | Distributor-specific product and pricing paths | Login/source confirmation | Pending Review | Confirm dealer account flow and Avery Dennison source split. |
| Screens | Austin Screens | Official product docs | Missing specs/install/warranty | Needed | Confirm exact vendor/legal name, official product hub, specs, install guides, and warranty/support source. |
| Shades | Draper | Product-specific ShadeStack pages | Incomplete resource categories | Partial | Capture full shade/screen product family docs, swatch process, training path, and warranty mapping. |
| Windows | Dallas Flat Glass | Warranty information | Missing warranty | Needed | No public warranty page found; confirm directly before quoting. |
| Glass & Windows | Ghost Glass | Historical `/installation` URL | Broken / Replace | Complete | Replaced with `https://ghostglassfilm.com/smart-film-installation-guide`. |
| All categories | Dealer portals | Portal URLs | Login Required | Partial | Keep portal URL and 1Password reference only. |
| All categories | Source collection | Official links, portal references, and rep-provided files | Collection tracker | Partial | Use `docs/source-collection.md` as the active gathering checklist before updating source summaries. |

## Audit Rules

- Replace clear `404` links before relying on a source for customer-facing answers.
- Treat `429`, `405`, `999`, and command-line HTTP/2 failures as browser-review items before deletion.
- Keep login-gated resources visible, but label them `Login Required`.
- Track missing install guides, product specs, sales collateral, and warranty information separately.
- Track duplicate documents so the product page remains the canonical resource owner.
- Warranty, pricing, install, and certification information should be rep-confirmed before being quoted.

## Audit Command

Use this command from the repo root to regenerate the external URL list:

```bash
rg -o 'https?://[^])"'"'"'<>[:space:]]+' docs/source.md docs/source-collection.md docs/products.md database/seeders/ShadesOfTexasStructureSeeder.php \
  | sed 's/^[^:]*://' \
  | sed 's/[.,;]$//' \
  | sort -u > /tmp/sotx_urls.txt
```
