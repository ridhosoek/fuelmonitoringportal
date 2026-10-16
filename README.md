# VHS Fuel Monitoring Portal

Laravel 13 project for the VHS Fuel Monitoring Portal tender prototype. The build follows the attached system requirement document as requirement input, with Blade, AdminLTE 4, Bootstrap 5.3, ApexCharts, Bootstrap Icons, Vite, Laravel session authentication, and a MySQL 8+ deployment target.

## Scope

Implemented modules:
- Dashboard KPI, charts, latest dispensing, and alerts
- Customer, site, storage, fuel source, unit, and user master data
- Fuel receiving with DRAFT, POSTED, and REVERSED workflow
- Stock ledger and current stock calculated from `stock_movements`
- Fuel order header-detail with OPEN, PARTIAL, COMPLETED lifecycle
- Fuel dispensing with KM/HM, FO remaining, stock, and override validation
- BAST header-detail with FINAL workflow
- Daily reconciliation with DRAFT, SUBMITTED, APPROVED workflow
- Reports with XLSX export
- Role-based access and audit trail

Explicitly excluded per requirement:
- SPH, sales order, commercial PO
- Invoice, tax, payment, accounting journal
- IoT tank sensor and automated flow meter pilot
- AI anomaly detection pilot
- React, Vue, Inertia, or SPA frontend

## Demo Accounts

All seeded users use password `password`.

| Role | Email | Username |
| --- | --- | --- |
| Super Admin | `superadmin@vhs.test` | `superadmin` |
| VHS Admin | `admin@vhs.test` | `vhsadmin` |
| VHS Operator | `operator@vhs.test` | `operator` |
| Supervisor | `supervisor@vhs.test` | `supervisor` |
| Customer / BNCT Viewer | `viewer@bnct.test` | `bnctviewer` |

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

For a quick local demo without MySQL, use SQLite in `.env`:

```ini
DB_CONNECTION=sqlite
```

Then run:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Open `http://127.0.0.1:8000/login`.

## Tender Demo Flow

Seeded data demonstrates the required acceptance flow:
- BNCT customer and BNCT Yard site
- Storage VHS BNCT capacity 30,000 L and minimum stock 10,000 L
- Receiving 8,000 L posted to stock
- FO for TT-001 100 L and CRANE-01 200 L
- Posted dispensing with KM 120,500 and HM 4,820
- Final BAST from both dispensing records
- Approved daily reconciliation with 7,700 L system stock, 7,700 L physical stock, and 0 variance

## Requirement Traceability

Requirement extraction and implementation mapping are in:
- `docs/source-requirement-extract.md`
- `docs/source-requirement-extract.json`
- `docs/requirements-traceability.md`

The DOCX was structurally extracted. Visual DOCX rendering could not run because LibreOffice (`soffice.exe`) was not available in the workspace runtime.

## Verification

Commands used for QA:

```bash
php artisan migrate:fresh --seed
npm run build
php artisan test
php artisan view:cache
vendor/bin/pint --dirty --format agent
```
