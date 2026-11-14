# Agriculture Information System (AP3 Midterm Project)

PHP 8 + PDO + Bootstrap 5 web app on a **Supabase PostgreSQL** database.
Modules: Dashboard · Farmers · Farms · Crops · Plantings · Harvests · Reports · Login (Admin / Staff).

---

## STEP-BY-STEP SETUP

### Step 1 – Install what you need (once)
* **PHP 8.1+** with the `pdo_pgsql` extension (XAMPP already includes PHP).
  In XAMPP open `php.ini` (XAMPP Control Panel → Apache → Config → PHP) and make sure these lines have **no `;` in front**:
  ```
  extension=pdo_pgsql
  extension=pgsql
  ```
  Save, then restart Apache (or open a new terminal if you only use `php -S`).
* Check it: open a terminal and run `php -m` – you should see `pdo_pgsql` in the list.

### Step 2 – Create the Supabase database
1. Go to <https://supabase.com> → sign in → **New project**. Pick a name, set a **database password** (save it!), choose the region nearest you (e.g. Singapore).
2. Wait until the project finishes setting up.
3. Open **SQL Editor → New query**.
4. Open `database/schema.sql` from this project, copy **everything**, paste it in the editor, click **Run**.
   → creates all 13 tables, constraints, triggers, lookup data, and the two login accounts.
5. New query again → paste all of `database/seed.sql` → **Run**.
   → adds 15 farmers, 15 farms, 10 crops, 50 plantings, 20 harvests.
6. Open **Table Editor** and confirm the tables are filled (e.g. `farmer` has 15 rows, `harvest_remark` has 5 rows).

### Step 3 – Get your connection details
1. In your Supabase project click the **Connect** button (top bar).
2. Choose the **Session pooler** connection (it works on normal home/school Wi-Fi; the "Direct connection" needs IPv6 and often fails).
3. Note the values: **host** (like `aws-0-ap-southeast-1.pooler.supabase.com`), **port** `5432`, **user** (like `postgres.abcdefghijklmnop`), and your **database password** from Step 2.

### Step 4 – Configure the project (credentials stay OUT of the code)
1. Copy `.env.example` → rename the copy to `.env`.
2. Fill it in:
   ```
   DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres.abcdefghijklmnop
   DB_PASSWORD=your-real-password
   DB_SSLMODE=require
   APP_BASE_PATH=
   APP_DEBUG=0
   ```
3. `.env` is in `.gitignore`, so it will never be pushed to GitHub. **Never commit it.**

### Step 5 – Run the system
Open a terminal **inside the project folder** (the one that contains `public/`) and run:
```
php -S localhost:8000 -t public
```
Open <http://localhost:8000> in your browser.

*Using XAMPP instead?* Copy the whole folder to `C:\xampp\htdocs\agriculture-system`, then set
`APP_BASE_PATH=/agriculture-system/public` in `.env` and open <http://localhost/agriculture-system/public/>.

### Step 6 – Log in
| Role | Username | Password | Can do |
|---|---|---|---|
| Administrator | `admin` | `Admin@123` | everything, including **delete** and managing **crops** |
| Staff | `staff` | `Staff@123` | view / add / edit farmers, farms, plantings, harvests; reports; **cannot delete** or edit crops |

Change these passwords for your final submission (see "Changing a password" below).

### Step 7 – Try every feature (also your demo script)
1. **Dashboard** – 5 summary cards, harvested quantity, estimated value, status doughnut chart, harvest-per-crop bar chart, recent plantings & harvests.
2. **Farmers / Farms / Crops / Plantings / Harvests** – each page has search, filters, sortable column headings, pagination (10 per page), and View / Edit / Delete.
3. **Dropdowns** – in *Add Harvest* the **Remarks** list shows "Good Harvest", etc., but the database stores `remark_id` (1–5). Open a harvest's View page to see it.
4. **Validation** – submit empty forms, a negative quantity, a harvest date before the planting date, an area larger than the farm, a duplicate contact number.
5. **Delete confirmation** – log in as admin, click Delete → a confirmation dialog appears. Try deleting Farmer #1 (has farms) → friendly "cannot be deleted" message (referential integrity).
6. **Reports** – five reports with date-range filters, Print and CSV export.
7. **Roles** – log in as `staff`: Delete buttons and Crop editing are gone.

### Step 8 – Take screenshots & write the documentation
Everything the document needs is in `docs/`:
* `docs/ERD.png` (+ `docs/erd.mmd`) – Entity-Relationship Diagram
* `docs/NORMALIZATION.md` – UNF → 1NF → 2NF → 3NF
* `docs/DATA_DICTIONARY.md` – every table/column/key/constraint
* `docs/TESTING.md` – test-case table to fill in with your results
Screenshots to take: login, dashboard, each module list, each add/edit form, a validation error, delete confirmation, a blocked delete, each report, Supabase Table Editor, `.env` hidden (to show credentials are not in the code).

---

## PROJECT STRUCTURE
```
agriculture-system/
├── .env.example          ← copy to .env and fill in (secrets live only here)
├── database/
│   ├── schema.sql        ← run first in Supabase SQL Editor
│   └── seed.sql          ← run second (sample data)
├── includes/             ← bootstrap (session/config), helpers, shared header/footer
├── classes/              ← Database, Auth, Validator, Lookup, Farmer, Farm, Crop, Planting, Harvest, Dashboard, Report
├── public/               ← the web root (only this folder is served)
│   ├── index.php         ← dashboard
│   ├── login.php  logout.php
│   ├── farmers/ farms/ crops/ plantings/ harvests/   ← index / form / view / delete
│   └── reports/index.php
└── docs/                 ← ERD, normalization, data dictionary, testing
```

## HOW IT MEETS EACH REQUIREMENT
| # | Requirement | Where |
|---|---|---|
| 1 | Normalization UNF–3NF | `docs/NORMALIZATION.md`, `schema.sql` |
| 2 | Supabase + secure credentials | `classes/Database.php`, `.env`, RLS enabled in `schema.sql` |
| 3 | Dropdowns from lookup tables | 7 lookup tables; `Lookup.php`; forms store numeric IDs |
| 4 | CRUD + JOINs | `classes/*.php`, `public/*/` |
| 5 | Dashboard | `public/index.php`, `classes/Dashboard.php` |
| 6 | Login + roles + hashed passwords | `Auth.php` (`password_hash`/`password_verify`, bcrypt) |
| 7 | Validation (app + DB) | `validate()` in each class + `CHECK`/`UNIQUE`/`NOT NULL`/trigger in `schema.sql` |
| 8 | Search / filter / sort | each module's `index.php` + `search()` |
| 9 | Reports | `public/reports/`, `classes/Report.php` |
| 10 | Tables + pagination | `BaseModel::paginate()`, `render_pagination()` |
| 11 | Delete confirmation + integrity | Bootstrap modal (`footer.php`), `ON DELETE RESTRICT`, `handle_delete()` |
| 12 | Responsive UI | Bootstrap 5 grid, responsive tables, collapsing navbar |
| 13 | Messages | flash messages; DB errors only go to the error log |
| 14 | Audit fields | `created_at`, `updated_at` (trigger), `created_by`, `updated_by` |
| 15 | Constraints | `schema.sql` |
| 16-17 | Documentation / testing | `docs/` |
| 18 | Deployment | env vars (see below) |

## Security features included
Prepared statements everywhere (no SQL injection) · output escaping with `htmlspecialchars` (no XSS) · CSRF token on every form · bcrypt passwords · session ID regenerated on login · login slow-down after 5 failures · whitelisted sort columns · RLS enabled so Supabase's public API can't read your tables · no technical error details shown to users.

## Changing a password
Run this in a terminal to get a hash, then paste it into Supabase **SQL Editor**:
```
php -r "echo password_hash('MyNewPassword', PASSWORD_BCRYPT), PHP_EOL;"
```
```sql
UPDATE app_user SET password_hash = '<paste hash here>' WHERE username = 'admin';
```

## Deployment (requirement 18)
The database is already hosted on Supabase. To host the PHP app, use any PHP host (or a Docker-based host such as Render):
set the document root to the `public/` folder, and add `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SSLMODE=require` as **environment variables** in the host's dashboard (the app reads real environment variables before `.env`). Do not upload `.env`.

## Troubleshooting
| Problem | Fix |
|---|---|
| `could not find driver` | `pdo_pgsql` not enabled – see Step 1, then restart. |
| "The system cannot reach the database" | Wrong `.env` value, or you used the *Direct* connection. Use **Session pooler** host and the user `postgres.<project-ref>`. See the PHP error log for the real reason. Set `APP_DEBUG=1` temporarily. |
| Blank page / 500 | Set `APP_DEBUG=1` in `.env` to see the error, then set it back to `0`. |
| Page links go to 404 on XAMPP | Set `APP_BASE_PATH=/agriculture-system/public`. |
| Login says incorrect password | Re-run `schema.sql` (it recreates the default accounts). |
