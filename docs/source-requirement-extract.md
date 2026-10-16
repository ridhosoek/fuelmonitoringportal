# Source Requirement Extract

Source: `C:\Users\indra\Downloads\VHS_Fuel_Monitoring_Portal_System_Requirement.docx`

## Document Properties
- title: 
- subject: 
- author: python-docx
- created: 2013-12-23 23:15:00+00:00
- modified: 2013-12-23 23:15:00+00:00
- last_modified_by: 
- embedded_image_count: 0
- comments_found: 0
- tracked_changes: {'ins': 0, 'del': 0, 'moveFrom': 0, 'moveTo': 0}

## Headers
- Section 1: VHS FUEL MONITORING PORTAL

## Footers
- Section 1: System Requirement | Page

## Body

### P1 [Normal]
SYSTEM REQUIREMENT

### P2 [Normal]
VHS Fuel Monitoring Portal

### P3 [Normal]
Operational Fuel Supply, Inventory & Consumption Monitoring

### P4 [Normal]
PILOT PROJECT

### Table 1
| Laravel 13 • MySQL 8+ • AdminLTE 4 • Bootstrap 5.3 • ApexCharts |
| --- |

### P5 [Normal]
Document Version 1.0

### P6 [Normal]
Prepared for internal development & Codex implementation

### P7 [Normal]
September 2026

### P8 [Heading 1]
Document Control

### Table 2
| Item | Detail |
| --- | --- |
| Document | System Requirement - VHS Fuel Monitoring Portal |
| Version | 1.0 |
| Status | Draft for development / pilot implementation |
| Primary Scope | Supply → Receiving VHS → Stock VHS → FO → Fuel Dispensing → Unit/Crane → BAST → KM/HM + Quantity → Daily Reconciliation |
| Primary Users | Super Admin, VHS Admin, VHS Operator, Supervisor, Customer/BNCT Viewer |
| Technology | Laravel 13, MySQL 8+, Blade, AdminLTE 4, Bootstrap 5.3, ApexCharts |

### P9 [Heading 1]
Table of Contents

### P10 [Normal]
1. Executive Summary

### P11 [Normal]
2. Scope & Business Flow

### P12 [Normal]
3. Objectives & Success Criteria

### P13 [Normal]
4. Technology & UI Standards

### P14 [Normal]
5. User Roles & Authorization

### P15 [Normal]
6. Master Data

### P16 [Normal]
7. Fuel Receiving

### P17 [Normal]
8. Inventory & Stock Ledger

### P18 [Normal]
9. Fuel Order (FO)

### P19 [Normal]
10. Fuel Dispensing

### P20 [Normal]
11. BAST & Reconciliation

### P21 [Normal]
12. Daily Reconciliation

### P22 [Normal]
13. Dashboard & Charts

### P23 [Normal]
14. Unit Monitoring & Reports

### P24 [Normal]
15. Database & Application Architecture

### P25 [Normal]
16. Security, Audit & Data Integrity

### P26 [Normal]
17. Testing & Seed Data

### P27 [Normal]
18. Definition of Done & Codex Build Order

### P28 [Normal]
19. Open Business Decisions

### P29 [Heading 1]
1. Executive Summary

### P30 [Body Text]
VHS Fuel Monitoring Portal adalah aplikasi web untuk mendigitalisasi dan memonitor proses operasional BBM di lokasi customer. Sistem difokuskan pada transparansi supply, akurasi stock, kontrol penyaluran ke unit, pencatatan KM/HM, BAST, serta rekonsiliasi harian.

### Table 3
| Scope dipertahankan secara ketat Tidak mencakup SPH, Sales Order, Purchase Order komersial, invoice, pajak, pembayaran, maupun accounting. |
| --- |

### P31 [Body Text]
Sistem dirancang sebagai pilot project yang tetap siap dikembangkan menjadi platform multi-customer pada fase berikutnya. Walaupun implementasi awal untuk BNCT, struktur data harus mendukung customer, site, dan storage lebih dari satu.

### P32 [Heading 1]
2. Scope & Business Flow

### P33 [Heading 2]
2.1 In-Scope Business Flow

### Table 4
| SUPPLY BBM ↓ Depo / Storage Cemara ↓ Mobil Tangki ↓ Receiving VHS BNCT ↓ STOCK VHS ↓ BNCT / Admin membuat FO ↓ Fuel Dispensing ↓ Unit / Crane BNCT ↓ BAST ↓ KM / HM + Quantity ↓ Daily Reconciliation |
| --- |

### P34 [Heading 2]
2.2 In Scope

### P35 [List Bullet]
Master customer, site, storage, fuel source/depot, unit, dan user.

### P36 [List Bullet]
Fuel Receiving untuk BBM masuk ke storage VHS.

### P37 [List Bullet]
Stock ledger dan current stock.

### P38 [List Bullet]
Fuel Order (FO) header-detail.

### P39 [List Bullet]
Fuel Dispensing berdasarkan FO.

### P40 [List Bullet]
Pencatatan KM atau HM setiap pengisian.

### P41 [List Bullet]
BAST sebagai bukti serah terima.

### P42 [List Bullet]
FO vs Dispensing vs BAST reconciliation.

### P43 [List Bullet]
Daily stock reconciliation antara system stock dan physical stock.

### P44 [List Bullet]
Dashboard dengan KPI dan chart.

### P45 [List Bullet]
Unit monitoring, report, export Excel, attachment, role/permission, dan audit trail.

### P46 [Heading 2]
2.3 Out of Scope

### P47 [List Bullet]
SPH / price commercial

### P48 [List Bullet]
Sales Order

### P49 [List Bullet]
Purchase Order commercial

### P50 [List Bullet]
Invoice & tax

### P51 [List Bullet]
Payment monitoring

### P52 [List Bullet]
Accounting journal

### P53 [List Bullet]
IoT tank sensor / automated flow meter pada pilot awal

### P54 [List Bullet]
AI anomaly detection pada pilot awal

### P55 [Heading 1]
3. Objectives & Success Criteria

### Table 5
| Objective | Success Indicator |
| --- | --- |
| Digitalisasi receiving | Setiap supply yang diterima mempunyai nomor transaksi, source, storage, quantity, attachment, user dan status. |
| Stock akurat | Stock berasal dari stock movement; tidak ada editable stock balance sebagai sumber kebenaran. |
| Kontrol FO | Dispensing selalu dikaitkan ke FO aktif dan remaining quantity tervalidasi. |
| Unit traceability | Setiap dispensing mencatat unit, quantity, KM/HM dan operator. |
| Reconciliation | FO, dispensing, BAST dan daily stock dapat dibandingkan. |
| Management visibility | Dashboard memperlihatkan current stock, receiving, consumption, open FO, variance, trend dan top consuming units. |
| Auditability | Transaksi POSTED/FINAL/APPROVED tidak di-hard-delete dan seluruh perubahan penting tercatat. |

### P56 [Heading 1]
4. Technology & UI Standards

### P57 [Heading 2]
4.1 Technology Stack

### Table 6
| Layer | Standard |
| --- | --- |
| Backend | Laravel 13 |
| Database | MySQL 8+ |
| Frontend | Blade Template |
| Admin UI | AdminLTE 4 |
| CSS | Bootstrap 5.3 |
| Charts | ApexCharts |
| Icons | Bootstrap Icons |
| Asset Build | Vite |
| Authentication | Laravel session authentication |

### P58 [Body Text]
Jangan menggunakan React, Vue, Inertia atau SPA framework. Arsitektur Blade dipilih agar deployment, debugging, dan maintenance tetap sederhana untuk pilot project.

### P59 [Heading 2]
4.2 UI/UX Direction

### P60 [List Bullet]
Clean, modern, enterprise, responsive dan professional.

### P61 [List Bullet]
AdminLTE digunakan sebagai base, tetapi tidak dibiarkan tampil default tanpa refinement.

### P62 [List Bullet]
Sidebar tidak terlalu ramai; menu dikelompokkan berdasarkan domain.

### P63 [List Bullet]
Dashboard memakai KPI cards, progress, badge, chart dan concise alerts.

### P64 [List Bullet]
Tabel mempunyai search, filter, pagination dan status badge.

### P65 [List Bullet]
Fuel Dispensing harus nyaman digunakan dari tablet/smartphone, dengan input dan tombol yang cukup besar.

### P66 [List Bullet]
Gunakan warna status secara konsisten: normal/success, warning, danger, info.

### P67 [Heading 2]
4.3 Main Navigation

### Table 7
| Dashboard Operations ├── Fuel Receiving ├── Fuel Order ├── Fuel Dispensing ├── BAST └── Daily Reconciliation Inventory ├── Current Stock └── Stock Movement Unit Monitoring ├── Units └── Fuel Consumption Reports ├── Receiving Report ├── Dispensing Report ├── Stock Report ├── Unit Consumption Report ├── FO vs Dispensing └── Daily Reconciliation Report Master Data ├── Customer ├── Site ├── Storage ├── Fuel Source / Depot ├── Unit └── User Management Administration └── Audit Trail |
| --- |

### P68 [Heading 1]
5. User Roles & Authorization

### Table 8
| Role | Primary Access | Restrictions / Notes |
| --- | --- | --- |
| Super Admin | Seluruh modul, konfigurasi, master, transaction, report, audit | Full system access. |
| VHS Admin | Receiving, stock, FO, dispensing, BAST, reconciliation, report | Tidak mengubah konfigurasi inti tanpa permission. |
| VHS Operator | Open FO, dispensing, input KM/HM, attachment, draft BAST | Tidak approve reconciliation/correction. |
| Supervisor | Dashboard, all transactions, correction approval, reconciliation approval, report | Dapat override tertentu dengan reason. |
| Customer / BNCT Viewer | Dashboard, stock, transaction, FO, BAST, unit consumption, report | Read-only. Desain permission harus memungkinkan customer membuat FO pada fase berikutnya. |

### P69 [Body Text]
Authorization wajib divalidasi di backend melalui middleware/policy/permission. Menyembunyikan menu di frontend saja tidak dianggap sebagai security control.

### P70 [Heading 1]
6. Master Data

### Table 9
| Master | Required Fields / Rules |
| --- | --- |
| Customer | customer_code, customer_name, address, contact_person, phone, email, status. Multi-customer ready. |
| Site | customer_id, site_code, site_name, address, status. |
| Storage | site_id, storage_code, storage_name, capacity_liter, minimum_stock_liter, status. Current stock tidak diedit manual. |
| Fuel Source / Depot | code, name, source_type, address, status. Contoh: Depo Pertamina, Storage Cemara. |
| Unit | customer_id, site_id, unit_code, unit_name, plate_number, unit_type, brand, model, meter_type, status. meter_type = KM atau HM. |
| User | name, username/email, role/permission, active status, password hash. |

### P71 [Heading 1]
7. Fuel Receiving

### P72 [Heading 2]
7.1 Process

### Table 10
| BBM dikirim → Mobil Tangki tiba → Admin input Receiving → Quantity diverifikasi → POSTED → Stock bertambah |
| --- |

### P73 [Heading 2]
7.2 Required Fields

### P74 [List Bullet]
receiving_number

### P75 [List Bullet]
receiving_date & receiving_time

### P76 [List Bullet]
site_id

### P77 [List Bullet]
storage_id

### P78 [List Bullet]
fuel_source_id

### P79 [List Bullet]
delivery_document_number

### P80 [List Bullet]
truck_plate_number

### P81 [List Bullet]
driver_name

### P82 [List Bullet]
quantity_liter

### P83 [List Bullet]
notes

### P84 [List Bullet]
attachment

### P85 [List Bullet]
status

### P86 [List Bullet]
created_by

### P87 [List Bullet]
posted_by

### P88 [List Bullet]
posted_at

### P89 [Heading 2]
7.3 Status & Rules

### Table 11
| Status | Rule |
| --- | --- |
| DRAFT | Masih dapat diedit sesuai permission. |
| POSTED | Membentuk stock movement IN. Tidak boleh dihapus. |
| REVERSED | Membentuk reversal movement; wajib reason dan audit trail. |

### P90 [Body Text]
Posting receiving wajib berada dalam satu database transaction bersama pencatatan stock movement. Jika salah satu operasi gagal, seluruh proses harus rollback.

### P91 [Heading 1]
8. Inventory & Stock Ledger

### P92 [Body Text]
Current stock harus dihitung dari stock ledger/stock movement. Jangan menggunakan field storage.stock sebagai sumber kebenaran yang dapat diedit manual.

### P93 [Heading 2]
8.1 Movement Types

### Table 12
| Movement Type | Sign / Source |
| --- | --- |
| RECEIVING | Positive / Fuel Receiving |
| DISPENSING | Negative / Fuel Dispensing |
| ADJUSTMENT_IN | Positive / Approved adjustment |
| ADJUSTMENT_OUT | Negative / Approved adjustment |
| REVERSAL | Opposite movement dari transaksi yang direverse |

### P94 [Heading 2]
8.2 Current Stock

### Table 13
| Current Stock = SUM(stock_movements.quantity) IN = positive OUT = negative |
| --- |

### P95 [Heading 2]
8.3 Current Stock Page

### P96 [List Bullet]
Storage

### P97 [List Bullet]
Capacity

### P98 [List Bullet]
Current Stock

### P99 [List Bullet]
Available %

### P100 [List Bullet]
Minimum Stock

### P101 [List Bullet]
Last Receiving

### P102 [List Bullet]
Last Dispensing

### P103 [List Bullet]
LOW STOCK badge jika current_stock <= minimum_stock

### P104 [Heading 1]
9. Fuel Order (FO)

### P105 [Body Text]
FO adalah permintaan BBM sebelum penyaluran. Gunakan struktur Header + Detail agar satu FO dapat berisi banyak unit.

### P106 [Heading 2]
9.1 FO Header

### P107 [List Bullet]
fo_number

### P108 [List Bullet]
fo_date

### P109 [List Bullet]
customer_id

### P110 [List Bullet]
site_id

### P111 [List Bullet]
requested_by

### P112 [List Bullet]
notes

### P113 [List Bullet]
status

### P114 [List Bullet]
created_by

### P115 [Heading 2]
9.2 FO Detail

### P116 [List Bullet]
fuel_order_id

### P117 [List Bullet]
unit_id

### P118 [List Bullet]
requested_quantity

### P119 [List Bullet]
dispensed_quantity (derived / calculated)

### P120 [List Bullet]
notes

### P121 [Heading 2]
9.3 Status

### Table 14
| Status | Meaning |
| --- | --- |
| DRAFT | Belum aktif untuk dispensing. |
| OPEN | Dapat digunakan untuk dispensing. |
| PARTIAL | Sebagian quantity sudah disalurkan. |
| COMPLETED | Seluruh item terpenuhi sesuai rule. |
| CANCELLED | Tidak dapat digunakan untuk transaksi baru. |

### P122 [Heading 2]
9.4 Example

### Table 15
| FO-20260915-001 TT-001 100 L TT-002 150 L CRANE-001 200 L ----------------- Total 450 L |
| --- |

### P123 [Heading 1]
10. Fuel Dispensing

### P124 [Heading 2]
10.1 User Flow

### Table 16
| Pilih FO → Pilih Unit → Tampilkan Requested / Already Dispensed / Remaining → Input KM/HM → Input Actual Quantity → Upload Bukti → Submit / Post → Stock berkurang |
| --- |

### P125 [Heading 2]
10.2 Required Fields

### P126 [List Bullet]
dispensing_number

### P127 [List Bullet]
dispensing_date & dispensing_time

### P128 [List Bullet]
fuel_order_id

### P129 [List Bullet]
fuel_order_item_id

### P130 [List Bullet]
storage_id

### P131 [List Bullet]
unit_id

### P132 [List Bullet]
meter_type

### P133 [List Bullet]
meter_value

### P134 [List Bullet]
quantity_liter

### P135 [List Bullet]
operator_name

### P136 [List Bullet]
notes

### P137 [List Bullet]
attachment

### P138 [List Bullet]
status

### P139 [List Bullet]
created_by

### P140 [List Bullet]
posted_by

### P141 [List Bullet]
posted_at

### P142 [Heading 2]
10.3 Validation Rules

### Table 17
| Validation | Rule |
| --- | --- |
| Unit | Harus aktif. |
| FO | Harus OPEN atau PARTIAL dan item aktif. |
| Quantity | > 0 dan tidak melebihi remaining FO kecuali supervisor override. |
| Stock | Harus cukup sebelum POST. |
| Meter | KM/HM wajib terisi sesuai meter_type. |
| Storage | Harus aktif. |
| Posting | Stock movement OUT dibuat dalam transaction yang sama. |

### P143 [Heading 2]
10.4 FO Remaining Calculation

### Table 18
| FO Requested 200 L Already Dispensed 120 L Remaining 80 L Current input must be <= 80 L unless approved override. |
| --- |

### P144 [Heading 2]
10.5 KM/HM Validation

### P145 [Body Text]
Sistem mencari meter terakhir unit. Meter baru secara default tidak boleh lebih rendah dari previous meter. Supervisor dapat override hanya dengan reason, approved_by dan approved_at. Meter history disimpan pada transaction history, bukan hanya pada unit master.

### P146 [Heading 1]
11. BAST & FO Reconciliation

### P147 [Heading 2]
11.1 BAST

### P148 [Body Text]
BAST adalah bukti serah terima penyaluran BBM. Gunakan header-detail dan izinkan satu BAST mereferensikan satu atau lebih dispensing dalam FO yang sama.

### Table 19
| BAST Header | BAST Detail |
| --- | --- |
| bast_number | fuel_dispensing_id |
| bast_date | unit_id |
| fuel_order_id | quantity_liter |
| customer_id / site_id | meter_type |
| received_by / operator_name | meter_value |
| notes / status / created_by | — |

### P149 [Body Text]
Status BAST: DRAFT, FINAL, CANCELLED. BAST FINAL tidak boleh diedit langsung.

### P150 [Heading 2]
11.2 FO vs Dispensing vs BAST

### Table 20
| Scenario | FO | Dispensing | BAST | System Status |
| --- | --- | --- | --- | --- |
| Exact | 200 L | 200 L | 200 L | MATCHED |
| Partial | 200 L | 198 L | 198 L | PARTIAL |
| Over | 200 L | 240 L | 240 L | OVER DISPENSING / requires control |

### P151 [Heading 1]
12. Daily Reconciliation

### P152 [Body Text]
Daily Reconciliation dilakukan pada akhir operasional untuk membandingkan system closing stock dengan physical closing stock.

### P153 [Heading 2]
12.1 Formula

### Table 21
| Opening Stock + Today Receiving - Today Dispensing +/- Approved Adjustment = System Closing Stock Physical Closing Stock - System Closing Stock = Variance |
| --- |

### P154 [Heading 2]
12.2 Required Data

### P155 [List Bullet]
reconciliation_date

### P156 [List Bullet]
site_id

### P157 [List Bullet]
storage_id

### P158 [List Bullet]
opening_stock

### P159 [List Bullet]
total_receiving

### P160 [List Bullet]
total_dispensing

### P161 [List Bullet]
total_adjustment

### P162 [List Bullet]
system_closing_stock

### P163 [List Bullet]
physical_closing_stock

### P164 [List Bullet]
variance

### P165 [List Bullet]
variance_percentage

### P166 [List Bullet]
notes

### P167 [List Bullet]
status

### P168 [List Bullet]
prepared_by

### P169 [List Bullet]
submitted_at

### P170 [List Bullet]
approved_by

### P171 [List Bullet]
approved_at

### P172 [Heading 2]
12.3 Business Rules

### P173 [List Bullet]
Opening stock berasal dari closing stock hari sebelumnya.

### P174 [List Bullet]
Receiving, dispensing dan adjustment dihitung otomatis dari transaksi POSTED pada tanggal terkait.

### P175 [List Bullet]
User hanya menginput physical closing stock dan notes.

### P176 [List Bullet]
Status: DRAFT → SUBMITTED → APPROVED.

### P177 [List Bullet]
Setelah APPROVED, perubahan hanya melalui controlled correction/reversal dengan audit trail.

### P178 [Heading 1]
13. Dashboard & Charts

### P179 [Heading 2]
13.1 Dashboard Filters

### P180 [List Bullet]
Customer

### P181 [List Bullet]
Site

### P182 [List Bullet]
Storage

### P183 [List Bullet]
Date Range (default Today)

### P184 [Heading 2]
13.2 KPI Cards

### Table 22
| KPI | Display Example |
| --- | --- |
| Current Stock | 18,250 L |
| Today Receiving | 8,000 L |
| Today Consumption | 4,820 L |
| Today Transactions | 38 |
| Active Units | 42 |
| Open FO | 8 |
| Stock Variance | -25 L |

### P185 [Heading 2]
13.3 Required ApexCharts

### Table 23
| Chart | Type | Purpose |
| --- | --- | --- |
| Stock Trend | Line | Closing/current stock trend, default 30 days. |
| Receiving vs Consumption | Column / combination | Daily incoming vs outgoing fuel. |
| Top Fuel Consumption by Unit | Horizontal bar | Top 10 unit berdasarkan liter. |
| Consumption by Unit Type | Donut | Truck, Crane, RTG, Reach Stacker, Others. |
| KM/HM Efficiency | Bar / line | KM/L untuk unit KM dan L/HM untuk unit HM; jangan dicampur dalam satu ranking. |

### P186 [Heading 2]
13.4 Latest Transactions & Alerts

### P187 [List Bullet]
Latest Fuel Dispensing table: time, FO, unit, KM/HM, quantity, operator, status.

### P188 [List Bullet]
Alerts: LOW STOCK, FO OVER DISPENSING, STOCK VARIANCE, INVALID METER, PENDING RECONCILIATION.

### P189 [Heading 1]
14. Unit Monitoring & Reports

### P190 [Heading 2]
14.1 Unit Detail

### P191 [List Bullet]
Unit information

### P192 [List Bullet]
Current KM/HM

### P193 [List Bullet]
Last refueling

### P194 [List Bullet]
Total fuel consumption

### P195 [List Bullet]
Number of refueling

### P196 [List Bullet]
Total distance / operating hours

### P197 [List Bullet]
Efficiency

### P198 [List Bullet]
Fuel consumption trend

### P199 [List Bullet]
Transaction history

### P200 [Heading 2]
14.2 Efficiency Metrics

### Table 24
| Unit with KM meter: Efficiency = Distance / Fuel = KM/L Unit with HM meter: Consumption Rate = Fuel / Operating Hours = L/HM |
| --- |

### P201 [Heading 2]
14.3 Reports

### Table 25
| Report | Minimum Columns |
| --- | --- |
| Receiving | Date, Receiving No, Source, Truck, Storage, Quantity, Status |
| Dispensing | Date, FO, Unit, KM/HM, Quantity, Storage, Operator |
| Stock Movement | Date, Reference, Type, IN, OUT, Balance |
| Unit Consumption | Unit, Opening Meter, Closing Meter, Distance/HM, Fuel, Efficiency |
| FO Reconciliation | FO, Requested, Dispensed, BAST, Difference, Status |
| Daily Reconciliation | Date, Opening, Receiving, Dispensing, System Closing, Physical, Variance, Status |

### P202 [Body Text]
Semua report wajib mendukung date range, customer/site/unit/storage filter sesuai konteks, search, pagination dan export minimal ke Excel.

### P203 [Heading 1]
15. Database & Application Architecture

### P204 [Heading 2]
15.1 Minimum Tables

### Table 26
| users customers sites storages fuel_sources units fuel_receivings fuel_orders fuel_order_items fuel_dispensings basts bast_items stock_movements daily_reconciliations attachments audit_logs |
| --- |

### P205 [Heading 2]
15.2 Database Standards

### P206 [List Bullet]
Gunakan foreign key pada relational data.

### P207 [List Bullet]
Index minimum pada transaction date, customer_id, site_id, storage_id, unit_id, fuel_order_id dan status.

### P208 [List Bullet]
Quantity liter menggunakan DECIMAL(15,3), bukan FLOAT.

### P209 [List Bullet]
Nomor transaksi harus unique.

### P210 [List Bullet]
File attachment disimpan melalui Laravel Storage; MySQL hanya menyimpan metadata/path.

### P211 [Heading 2]
15.3 Application Layers

### Table 27
| Controller ↓ Form Request ↓ Service / Action ↓ Model ↓ Database |
| --- |

### P212 [Body Text]
Business logic seperti stock posting, FO calculation, dispensing validation, reconciliation dan unit efficiency tidak boleh ditumpuk seluruhnya di controller.

### P213 [Heading 2]
15.4 Suggested Services

### P214 [List Bullet]
FuelReceivingService

### P215 [List Bullet]
FuelDispensingService

### P216 [List Bullet]
StockService

### P217 [List Bullet]
FuelOrderService

### P218 [List Bullet]
BastService

### P219 [List Bullet]
ReconciliationService

### P220 [List Bullet]
UnitConsumptionService

### P221 [List Bullet]
DashboardService

### P222 [Heading 2]
15.5 Number Format

### Table 28
| RCV-20260915-0001 FO-20260915-0001 DSP-20260915-0001 BAST-20260915-0001 REC-20260915-0001 |
| --- |

### P223 [Heading 1]
16. Security, Audit & Data Integrity

### P224 [Heading 2]
16.1 Database Transaction Integrity

### P225 [Body Text]
Operasi berikut wajib dibungkus dalam database transaction: Post Receiving, Post Dispensing, Reverse Receiving, Reverse Dispensing, BAST Finalization dan Daily Reconciliation. Jika salah satu langkah gagal, lakukan rollback penuh.

### P226 [Heading 2]
16.2 Audit Trail

### P227 [List Bullet]
Simpan user, action, module, record id/reference, old_values, new_values, IP address, timestamp.

### P228 [List Bullet]
Action minimum: CREATE, UPDATE, POST, REVERSE, APPROVE, LOGIN.

### P229 [List Bullet]
POSTED/FINAL/APPROVED records tidak di-hard-delete.

### P230 [Heading 2]
16.3 Application Security

### P231 [List Bullet]
Laravel authentication

### P232 [List Bullet]
Authorization middleware/policy/permission

### P233 [List Bullet]
CSRF protection

### P234 [List Bullet]
XSS-safe Blade rendering

### P235 [List Bullet]
Server-side Form Request validation

### P236 [List Bullet]
Password hashing

### P237 [List Bullet]
Foreign key constraints

### P238 [List Bullet]
Secure file upload validation

### P239 [List Bullet]
Least privilege role access

### P240 [Heading 1]
17. Testing & Seed Data

### P241 [Heading 2]
17.1 Feature Tests

### P242 [List Bullet]
Login & authorization

### P243 [List Bullet]
Fuel receiving

### P244 [List Bullet]
Stock increases after posted receiving

### P245 [List Bullet]
FO creation

### P246 [List Bullet]
Fuel dispensing

### P247 [List Bullet]
Stock decreases after posted dispensing

### P248 [List Bullet]
Prevent insufficient stock

### P249 [List Bullet]
Prevent dispensing above remaining FO without override

### P250 [List Bullet]
KM/HM validation

### P251 [List Bullet]
BAST finalization

### P252 [List Bullet]
Daily reconciliation

### P253 [List Bullet]
Transaction reversal

### P254 [Heading 2]
17.2 Seeder Demo

### P255 [List Bullet]
1 Customer: BNCT

### P256 [List Bullet]
1 Site: Belawan New Container Terminal

### P257 [List Bullet]
1 Storage: VHS BNCT, capacity 30,000 L, minimum stock 10,000 L

### P258 [List Bullet]
2 Fuel Sources: Depo Pertamina dan Storage Cemara

### P259 [List Bullet]
10 Units: campuran KM dan HM

### P260 [List Bullet]
Users: Super Admin, VHS Admin, Operator, Supervisor, Customer Viewer

### P261 [List Bullet]
Sample receiving, FO, dispensing, BAST, stock movement dan daily reconciliation agar dashboard langsung berisi data

### P262 [Heading 1]
18. Definition of Done & Codex Build Order

### P263 [Heading 2]
18.1 End-to-End Definition of Done

### P264 [List Number]
Admin login dan memiliki menu sesuai role.

### P265 [List Number]
Admin menerima supply 8,000 L dan mem-posting receiving.

### P266 [List Number]
Stock VHS otomatis bertambah 8,000 L melalui stock movement.

### P267 [List Number]
Admin membuat FO: TT-001 100 L dan Crane-01 200 L.

### P268 [List Number]
Operator melakukan dispensing TT-001 dengan KM 120,500 dan quantity 100 L.

### P269 [List Number]
Operator melakukan dispensing Crane-01 dengan HM 4,820 dan quantity 200 L.

### P270 [List Number]
Stock otomatis berkurang total 300 L.

### P271 [List Number]
BAST dibuat berdasarkan dispensing terkait.

### P272 [List Number]
FO menunjukkan requested, dispensed dan remaining dengan benar.

### P273 [List Number]
Unit history menampilkan KM/HM dan quantity.

### P274 [List Number]
Dashboard KPI dan charts ikut berubah berdasarkan transaction data.

### P275 [List Number]
Admin melakukan Daily Reconciliation; system menghitung opening, receiving, dispensing, system closing, physical dan variance.

### P276 [List Number]
Supervisor approve reconciliation.

### P277 [List Number]
Seluruh aktivitas penting tersimpan pada Audit Trail.

### P278 [Heading 2]
18.2 Codex Build Order

### Table 29
| Foundation ↓ Authentication + Role ↓ Master Data ↓ Fuel Receiving ↓ Stock Ledger ↓ Fuel Order ↓ Fuel Dispensing ↓ BAST ↓ Daily Reconciliation ↓ Unit Monitoring ↓ Dashboard + ApexCharts ↓ Reports ↓ Audit Trail ↓ Automated Testing ↓ UI Refinement |
| --- |

### P279 [Body Text]
Setiap modul harus dikerjakan sebagai vertical slice: migration → model/relationship → validation → service/action → controller → route → Blade view → permission → test. Jangan membuat dummy UI tanpa backend yang berfungsi.

### P280 [Heading 1]
19. Open Business Decisions

### P281 [Body Text]
Beberapa keputusan bisnis masih perlu dikonfirmasi sebelum production. Requirement teknis dirancang agar keputusan ini tidak memaksa perubahan besar pada database.

### Table 30
| Decision | Question to Confirm | Current Design Assumption |
| --- | --- | --- |
| FO creator | Apakah BNCT membuat FO langsung di portal atau FO diberikan kepada Admin VHS untuk diinput? | Keduanya dapat didukung melalui permission. |
| Dispensing over FO | Apakah quantity boleh melebihi remaining FO? | Default tidak; supervisor override dengan reason. |
| BAST signature | Apakah BAST cukup dicatat/diupload atau harus digital signature? | Pilot: data + attachment; digital signature future option. |
| Physical stock measurement | Bagaimana physical closing stock ditentukan? Dipstick, tank gauge, atau metode lain? | System menerima angka physical stock dari user yang berwenang. |
| Adjustment | Siapa yang boleh membuat stock adjustment? | Supervisor / privileged admin only, wajib reason & audit. |
| Customer access | Apakah customer hanya read-only atau dapat membuat FO? | Pilot read-only; architecture siap untuk customer FO. |

### Table 31
| Implementation Principle Pilot harus sederhana, stabil, audit-able, dan berfungsi end-to-end sebelum penambahan IoT, AI, atau modul komersial. |
| --- |
