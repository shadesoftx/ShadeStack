# Link Audit

Last audited: 2026-05-27

This document tracks the external-link audit for the Shades of Texas sales hub. The audit covers links extracted from:

- `docs/source.md`
- `docs/products.md`
- `database/seeders/ShadesOfTexasStructureSeeder.php`

Current pass: automated reachability and redirect check, plus sales-use classification for login-gated, rep-confirmed, pending, and replacement-needed resources. Vendor websites with bot protection may return `429`, `405`, `999`, or HTTP/2 framing errors during command-line checks even when they open in a browser, so those links are marked for browser review instead of immediate deletion.

## Summary

| Metric | Count |
|---|---:|
| Unique external URLs checked | 273 |
| Login/dealer/portal URLs identified | 24 |
| URLs needing browser or replacement review | 83 |

## Status Tags

| Tag | Meaning |
|---|---|
| `Audited` | Link is in the current audit scope and should be treated as intentionally included. |
| `Login Required` | Link is a portal, dealer app, pricing page, or protected resource. Credentials belong in 1Password, not BookStack. |
| `Pending` | Access, source replacement, or vendor confirmation is still needed. |
| `Rep Confirm Needed` | Use the vendor rep/contact path before quoting or promising details. |
| `Broken / Replace` | Link failed clearly or points to a page that should be replaced. |

## Domain Results

| Domain | URLs | OK / Expected | Review | Login |
|---|---:|---:|---:|---:|
| `www.3m.com` | 27 | 0 | 27 | 1 |
| `multimedia.3m.com` | 5 | 5 | 0 | 0 |
| `www.hunterdouglas.com` | 31 | 0 | 31 | 1 |
| `help.hunterdouglas.com` | 4 | 4 | 0 | 0 |
| `dc.hunterdouglas.com` | 1 | 1 | 0 | 1 |
| `dealer.altawindowfashions.com` | 1 | 1 | 0 | 1 |
| `www.altawindowfashions.com` | 13 | 0 | 13 | 2 |
| `prod.altawindowfashions.com` | 5 | 0 | 5 | 0 |
| `normanusa.com` | 23 | 22 | 1 | 0 |
| `eclipseshading.com` | 13 | 13 | 0 | 1 |
| `www.eclipseawning.com` | 2 | 0 | 2 | 0 |
| `www.smarttint.com` | 17 | 17 | 0 | 1 |
| `smarttintdealer.com` | 2 | 2 | 0 | 2 |
| `www.andersenwindows.com` | 22 | 22 | 0 | 0 |
| `www.andersenaccess.com` | 1 | 0 | 1 | 1 |
| `www.pella.com` | 28 | 28 | 0 | 1 |
| `pellapro.pella.com` | 1 | 1 | 0 | 1 |
| `prodealers.pellacoop.com` | 1 | 1 | 0 | 1 |
| `www.crlaurence.com` | 16 | 16 | 0 | 2 |
| `azure.crlaurence.com` | 2 | 2 | 0 | 1 |
| `dallasflatglass.com` | 1 | 1 | 0 | 0 |
| `ghostglassfilm.com` | 16 | 15 | 1 | 0 |
| `www.jeld-wen.com` | 10 | 10 | 0 | 0 |
| `jeldwenorg3.my.site.com` | 1 | 1 | 0 | 1 |

## Priority Review Items

| Vendor | URL | Result | Action |
|---|---|---|---|
| Ghost Glass | `https://ghostglassfilm.com/installation` | `404` | Replace with `https://ghostglassfilm.com/smart-film-installation-guide` or another current install page. |
| Norman | `https://normanusa.com/product/portrait-honeycomb/` | `502` | Browser-check; replace if the product page is retired. | (This one is 200)
| Alta | `https://prod.altawindowfashions.com/en/maintenance-and-warranty` | `429` | Browser-check; likely bot protection. Keep marked `Audited` plus `Rep Confirm Needed` until verified. | (This one is 200)
| 3M | `https://www.3m.com/...` links | `CURL_FAIL` / HTTP2 errors | Browser-check official pages; do not remove based only on CLI failure. | home page is 200
| Hunter Douglas | `https://www.hunterdouglas.com/...` links | `429` | Browser-check; likely bot protection/rate limiting. | home page is 200
| Eclipse | `https://www.eclipseawning.com/` and brochure PDF | `405` | Browser-check; likely HEAD disallowed. | home page is 200
| Andersen Access | `https://www.andersenaccess.com/` | `405`, login redirect | Keep as `Login Required`; credentials must be referenced in 1Password only. | this is the dealer portal
| LinkedIn | Dallas Flat Glass profile | `999` | Browser-check; LinkedIn commonly blocks command-line audit. |

## Audit Command

Use this command from the repo root to regenerate the current automated audit files in `/tmp`:

```bash
rg -o 'https?://[^])"'"'"'<>[:space:]]+' docs/source.md docs/products.md database/seeders/ShadesOfTexasStructureSeeder.php \
  | sed 's/^[^:]*://' \
  | sed 's/[.,;]$//' \
  | sort -u > /tmp/sotx_urls.txt

while IFS= read -r url; do
  code=$(curl -L -I -sS --max-time 12 -o /dev/null -w '%{http_code}' "$url" || echo CURL_FAIL)
  final=$(curl -L -I -sS --max-time 12 -o /dev/null -w '%{url_effective}' "$url" || echo '')
  login=no
  case "$url" in
    *login*|*portal*|*dealer*|*dc.hunterdouglas.com*|*pellapro*|*andersenaccess*|*my.site.com*|*pricing.altawindowfashions.com*) login=yes;;
  esac
  printf '%s\t%s\t%s\t%s\n' "$code" "$login" "$final" "$url"
done < /tmp/sotx_urls.txt > /tmp/sotx_link_audit.tsv
```

## Maintenance Rules

- Replace clear `404` links before publishing customer-facing collateral.
- Treat `429`, `405`, `999`, and HTTP/2 command-line failures as browser-review items.
- Keep login-gated resources visible, but label them `Login Required`.
- Store only portal URLs and 1Password item names in BookStack.
- Warranty, pricing, install, and certification information should be rep-confirmed before being quoted to customers.
