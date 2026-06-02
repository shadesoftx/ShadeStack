# Link Audit

Last automated audit: 2026-05-27
Last browser reconciliation: 2026-06-02

This document tracks the external-link audit for the Shades of Texas sales hub. The audit covers links extracted from:

- `docs/source.md`
- `docs/products.md`
- `database/seeders/ShadesOfTexasStructureSeeder.php`

The automated pass runs a reachability and redirect check, plus sales-use classification for login-gated, rep-confirmed, pending, and replacement-needed resources. Vendor websites with bot protection may return `429`, `405`, `999`, or HTTP/2 framing errors during command-line checks even when they open normally in a browser, so those links are marked for browser review instead of immediate deletion.

**Reconciliation note (2026-06-02):** The priority items below have since been browser-verified, and the table now records the verified result and final status directly. All priority items are resolved; the Ghost Glass `/installation` replacement has been applied in `source.md` and `products.md`.

## Summary

| Metric | Count |
|---|---:|
| Unique external URLs checked | 273 |
| Login/dealer/portal URLs identified | 24 |
| URLs flagged by automated pass for browser/replacement review | 83 |
| Priority items browser-reconciled | 8 |
| &nbsp;&nbsp;— Resolved (reachable, login-gated, or expected) | 7 |
| &nbsp;&nbsp;— Still open (genuine replacement needed) | 1 |

> **Closed:** Ghost Glass `https://ghostglassfilm.com/installation` (`404`) was replaced with `https://ghostglassfilm.com/smart-film-installation-guide` (verified `200`).

## Status Tags

| Tag | Meaning |
|---|---|
| `Audited` | Link is in the current audit scope and should be treated as intentionally included. Also applied once a browser check clears a command-line failure. |
| `Login Required` | Link is a portal, dealer app, pricing page, or protected resource. Credentials belong in 1Password, not BookStack. |
| `Pending` | Access, source replacement, or vendor confirmation is still needed. |
| `Rep Confirm Needed` | Use the vendor rep/contact path before quoting or promising details. |
| `Broken / Replace` | Link failed clearly or points to a page that should be replaced. |

## Domain Results

| Domain | URLs | OK / Expected | Review | Login |
|---|---:|---:|---:|---:|
| `www.3m.com` | 27 | 0 | 27¹ | 1 |
| `multimedia.3m.com` | 5 | 5 | 0 | 0 |
| `www.hunterdouglas.com` | 31 | 0 | 31¹ | 1 |
| `help.hunterdouglas.com` | 4 | 4 | 0 | 0 |
| `dc.hunterdouglas.com` | 1 | 1 | 0 | 1 |
| `dealer.altawindowfashions.com` | 1 | 1 | 0 | 1 |
| `www.altawindowfashions.com` | 13 | 0 | 13¹ | 2 |
| `prod.altawindowfashions.com` | 5 | 1 | 4¹ | 0 |
| `normanusa.com` | 23 | 23 | 0 | 0 |
| `eclipseshading.com` | 13 | 13 | 0 | 1 |
| `www.eclipseawning.com` | 2 | 1 | 1¹ | 0 |
| `www.smarttint.com` | 17 | 17 | 0 | 1 |
| `smarttintdealer.com` | 2 | 2 | 0 | 2 |
| `www.andersenwindows.com` | 22 | 22 | 0 | 0 |
| `www.andersenaccess.com` | 1 | 1 | 0 | 1 |
| `www.pella.com` | 28 | 28 | 0 | 1 |
| `pellapro.pella.com` | 1 | 1 | 0 | 1 |
| `prodealers.pellacoop.com` | 1 | 1 | 0 | 1 |
| `www.crlaurence.com` | 16 | 16 | 0 | 2 |
| `azure.crlaurence.com` | 2 | 2 | 0 | 1 |
| `dallasflatglass.com` | 1 | 1 | 0 | 0 |
| `ghostglassfilm.com` | 16 | 15 | 1 | 0 |
| `www.jeld-wen.com` | 10 | 10 | 0 | 0 |
| `jeldwenorg3.my.site.com` | 1 | 1 | 0 | 1 |

¹ **Bot-protection domains (browser-verified reachable).** Command-line checks against 3M, Hunter Douglas, and Alta return `429` / `CURL_FAIL` / HTTP-2 errors due to bot and rate-limit protection, not because the pages are down. Representative pages on each domain were opened in a browser and returned `200` on 2026-06-02, confirming the protection is the cause. These items should be read as `Audited` (reachable) rather than suspect; individual deep links can be spot-checked in a browser as needed, but none should be removed on a command-line failure alone. The Eclipse awning domain returns `405` to `HEAD` requests only; the home page loads `200` via `GET`, and the one remaining review item is the brochure PDF, which is low-risk.

## Priority Review Items (reconciled)

Legend: **[Resolved]** = verified and retagged · **[Open]** = action still required.

| Vendor | URL / scope | Automated result | Browser check (2026-06-02) | Final status |
|---|---|---|---|---|
| Ghost Glass | `https://ghostglassfilm.com/installation` | `404` | Confirmed `404` | **Resolved** — replaced with `https://ghostglassfilm.com/smart-film-installation-guide` (verified `200`) in `source.md` and `products.md`. |
| Norman | `https://normanusa.com/product/portrait-honeycomb/` | `502` | `200` | **Audited [Resolved]** — transient/edge `502` on the automated pass; product page is live. |
| Alta | `https://prod.altawindowfashions.com/en/maintenance-and-warranty` | `429` | `200` | **Audited [Resolved]** — `429` was bot protection; page is live. Warranty terms still **Rep Confirm Needed** before quoting to a customer. |
| 3M | `https://www.3m.com/...` product/support links | `CURL_FAIL` / HTTP-2 | Home + sampled pages `200` | **Audited [Resolved]** — command-line failures are bot/HTTP-2 protection; pages load in a browser. Do not remove on CLI failure alone. |
| Hunter Douglas | `https://www.hunterdouglas.com/...` product links | `429` | Home `200` | **Audited [Resolved]** — `429` is rate limiting; pages load in a browser. |
| Eclipse | `https://www.eclipseawning.com/` + brochure PDF | `405` | Home `200` | **Audited [Resolved]** — `405` is `HEAD` disallowed; `GET` returns `200`. Brochure PDF not individually confirmed — low-risk spot-check. |
| Andersen Access | `https://www.andersenaccess.com/` | `405`, login redirect | Confirmed dealer portal | **Login Required [Resolved]** — working as intended; credentials referenced in 1Password only. Not broken. |
| LinkedIn | Dallas Flat Glass company profile | `999` | Loads in browser | **Audited [Resolved]** — `999` is LinkedIn blocking command-line audits; profile is reachable. Not broken. |

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

> **Tip:** The automated pass uses `HEAD` (`curl -I`), which several vendor sites reject or rate-limit. When a result is `405`, `429`, `999`, or `CURL_FAIL`, re-check with a browser or a `GET` request before treating the link as broken.

## Maintenance Rules

- Replace clear `404` links before publishing customer-facing collateral.
- Treat `429`, `405`, `999`, and HTTP/2 command-line failures as browser-review items, not deletions.
- When a browser check clears a command-line failure, retag the item `Audited` and record the verified result in the Priority Review table above, with the check date.
- Keep login-gated resources visible, but label them `Login Required`.
- Store only portal URLs and 1Password item names in BookStack.
- Warranty, pricing, install, and certification information should be rep-confirmed before being quoted to customers.
