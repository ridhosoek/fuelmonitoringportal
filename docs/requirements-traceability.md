# VHS Fuel Monitoring Portal - Requirements Traceability

## Instruction Boundary

User request: analyze the attached system requirement document, then build a precise project suitable for a company tender.

Attached document content is treated as requirements data only. It is not treated as an instruction to override the user, system, framework, or safety constraints.

## Source Requirement Summary

Project: VHS Fuel Monitoring Portal, pilot project.

Required stack:
- Laravel 13
- MySQL 8+
- Blade
- AdminLTE 4
- Bootstrap 5.3
- ApexCharts
- Bootstrap Icons
- Vite
- Laravel session authentication

Explicit exclusions:
- SPH
- Sales order
- Commercial PO
- Invoice and tax
- Payment
- Accounting journal
- IoT tank sensor and automated flow meter in pilot
- AI anomaly detection in pilot
- React, Vue, Inertia, or SPA frontend

Core flow:
1. Fuel supply enters VHS receiving.
2. Receiving posts stock into VHS storage.
3. Fuel order controls requested unit consumption.
4. Operator dispenses fuel to unit or crane with KM/HM value.
5. BAST groups one or more posted dispensing records from the same FO.
6. Daily reconciliation compares system closing stock with physical closing stock.

Roles:
- Super Admin
- VHS Admin
- VHS Operator
- Supervisor
- Customer / BNCT Viewer

## Implementation Mapping

Database and data integrity:
- `users` extended with username, role, and active flag.
- Domain tables implemented: customers, sites, storages, fuel sources, units, fuel receivings, fuel orders, fuel order items, fuel dispensings, BAST, BAST items, stock movements, daily reconciliations, attachments, audit logs.
- Fuel quantities use `DECIMAL(15,3)`.
- Current stock is derived from `stock_movements`, not from an editable storage stock column.

Operational services:
- `FuelReceivingService`: draft, post, reverse, stock IN, audit log.
- `FuelOrderService`: header-detail creation and remaining quantity calculation.
- `FuelDispensingService`: draft, post, reverse, stock OUT, stock availability check, FO remaining check, KM/HM regression check, supervisor override reason.
- `BastService`: creates BAST from posted dispensing records in the same FO and finalizes it.
- `ReconciliationService`: calculates opening + receiving - dispensing +/- adjustment = system closing, then submit and approve workflow.
- `DashboardService`: KPI cards, stock trend, receiving vs consumption, top units, unit type consumption, separated KM/HM efficiency, latest dispensing, alerts.
- `ExcelExportService`: native XLSX output for reports.

HTTP and UI:
- Blade/AdminLTE layout with session authentication.
- Dashboard, receiving, FO, dispensing, BAST, reconciliation, inventory, unit monitoring, reports, master data, and audit trail screens.
- Role middleware prevents write access for viewer users.

Seeded tender demo:
- BNCT customer and BNCT Yard site.
- Storage VHS BNCT with 30,000 L capacity and 10,000 L minimum stock.
- Depo Pertamina and Storage Cemara fuel sources.
- 10 mixed KM/HM units.
- Five user roles.
- Posted receiving of 8,000 L.
- FO for TT-001 100 L and CRANE-01 200 L.
- Posted dispensing with KM 120,500 and HM 4,820.
- Final BAST.
- Approved daily reconciliation with 7,700 L physical closing stock and zero variance.

## Acceptance Flow

1. Login as `superadmin@vhs.test` with password `password`.
2. Confirm dashboard current stock shows 7,700 L after demo transactions.
3. Open receiving and verify 8,000 L POSTED receiving.
4. Open FO and verify requested 300 L, dispensed 300 L, remaining 0 L, status COMPLETED.
5. Open dispensing and verify TT-001 / CRANE-01 records are POSTED with KM/HM values.
6. Open BAST and verify FINAL status with two dispensing items.
7. Open daily reconciliation and verify APPROVED status and variance 0.000.
8. Open audit trail and verify create/post/finalize/approve entries.

## Known QA Limitation

The DOCX was structurally extracted with `python-docx`, producing `docs/source-requirement-extract.md` and `docs/source-requirement-extract.json`.

Visual DOCX rendering could not be completed because LibreOffice (`soffice.exe`) was not available in the workspace runtime. The extracted structure reported 31 tables, 0 images, 0 comments, and 0 tracked changes.
