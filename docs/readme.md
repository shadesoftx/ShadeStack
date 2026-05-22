Shades of Texas Knowledge Base
==============================

This repository powers the internal Shades of Texas sales hub. It is a BookStack-based knowledge base for sales teams who need fast access to products, specs, drawings, warranties, and service context without digging through the public website.

The goal of this first draft is not to migrate everything at once. It is to establish a clean, sales-first structure that is easy to scan, easy to expand, and familiar to anyone who already knows the public Shades of Texas site.

Design principles
-----------------

- Keep the most common sales questions at the top
- Mirror the public site where that helps recognition
- Group resources by service family and by product line
- Make specs, drawings, and warranty documents easy to find
- Leave room for future products and new service lines without reorganizing the whole hub

Proposed information architecture
---------------------------------

The first visible structure should look like this:

```text
Start Here
  Source of Truth
Residential
Commercial
Vendors
Specs & Drawings
Warranty / Compliance
Reference / FAQs
```

Top-level purpose of each area:

- `Start Here`
  - `Source of Truth`
  - How to use this hub
  - Where to find specs, drawings, and pricing
  - How to request missing documents
- `Residential`
  - Residential versions of the core service families
- `Commercial`
  - Commercial versions of the core service families
- `Vendors`
  - Manufacturer and vendor-level reference pages
- `Specs & Drawings`
  - Organized document library for technical files
- `Warranty / Compliance`
  - Warranty summaries, code notes, and compliance references
- `Reference / FAQs`
  - Sales answers, measurement notes, and common objections

Service families to mirror
--------------------------

The current website’s service taxonomy should be reflected in the knowledge base so the internal structure feels familiar.

```text
Tint & Film
Window Treatments
Outdoor Living
Glass & Windows
```

These service families should appear under both `Residential` and `Commercial`, with pages tailored to the audience where needed.

Suggested book structure
-----------------------

### Start Here

- How to use this hub
- Sales workflow overview
- Request a missing resource
- Where pricing lives

### Residential

- Tint & Film
  - Home window tint
  - Safety / security film
  - Privacy and glare control
- Window Treatments
  - Window shades
  - Shutters
  - Blinds
- Outdoor Living
  - Awnings
  - Shade structures
  - Patio screens
- Glass & Windows
  - Glass replacement
  - Window replacement
  - Door and glazing references

### Commercial

- Tint & Film
  - Commercial solar control film
  - Security film
  - Privacy film
- Window Treatments
  - Commercial shades
  - Motorization
  - Project / spec references
- Outdoor Living
  - Commercial awnings
  - Shade structures
  - Exterior coverage systems
- Glass & Windows
  - Replacement glass
  - Glazing references
  - Door and storefront references

### Vendors

- 3M
- Hunter Douglas
- Alta
- Norman
- Eclipse
- SmartTint
- Andersen
- Pella
- CRL
- Dallas Flat Glass
- Ghost Glass
- JELD-WEN
- Other vendor / manufacturer pages as needed

### Specs & Drawings

- By product family
- By manufacturer
- By document type
  - spec sheets
  - cut sheets
  - architect drawings
  - install diagrams

### Warranty / Compliance

- Manufacturer warranties
- Warranty exclusions and caveats
- Code and compliance notes
- Material and finish limitations

### Reference / FAQs

- Sales answers
- Measurement notes
- Installation caveats
- Common product comparisons
- Objection handling

Placeholder pages for the first draft
------------------------------------

The first pass should include placeholder pages for the highest-value sales resources so the skeleton is obvious before all the content is written.

Recommended placeholder page types:

- Product overview
- Selling points
- Spec sheets
- Architect drawings
- Warranty docs
- Common objections / FAQs

Suggested content relationships
-------------------------------

The hub should make it easy to move from a service to the exact resource a salesperson needs:

1. Start with the service family
2. Drill into the relevant product or brand
3. Open the spec sheet or drawing
4. Check warranty or compliance notes if needed
5. Use FAQ or objection pages for sales support

This keeps the structure simple while still supporting the deeper documentation the team needs.

Local setup and maintenance
---------------------------

This repository is intended to be worked on locally with Laravel Herd and DBngin.

When you need to run the app locally, use the standard Laravel setup:

```env
APP_URL=http://bookstack.test
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bookstack_sotx
DB_USERNAME=root
DB_PASSWORD=
```

Typical first-time setup:

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run dev
```

The seed step creates the initial Shades of Texas BookStack tree so you can inspect the structure immediately after setup.

If the database does not already exist, create it in MySQL first:

```bash
mysql -h 127.0.0.1 -P 3306 -u root -p -e "CREATE DATABASE bookstack_sotx CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Default admin login:

- Email: `admin@admin.com`
- Password: `password`

Deployment and support
----------------------

Production hosting and operations are handled separately from this local draft. Before pushing changes to the live instance, make sure the environment variables, database details, and file permissions match the deployment setup in use.

For structure questions, content requests, or maintenance help, contact John Borg.

For the canonical source map used to update the docs in one pass, see [docs/source.md](source.md).
For the vendor product inventory that powers the brand pages, see [docs/products.md](products.md).

For the current navigation map and page organization model, see [docs/navigation.md](navigation.md).

BookStack references
--------------------

- Official docs: https://www.bookstackapp.com/docs/
- Project source: https://codeberg.org/bookstack/bookstack
