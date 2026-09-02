# Product Requirements Document (PRD)

## Point of Sale (POS) System

**Product Name:** POS System
**Version:** 1.0
**Status:** Draft / MVP
**Platform:** Web Application
**Target:** Retail Store, Minimarket, Cafe, Toko Online/Offline
**Architecture:** Admin Dashboard + Cashier POS

---

# 1. Product Overview

POS System adalah aplikasi kasir berbasis web yang digunakan untuk mengelola transaksi penjualan, produk, stok, pelanggan, supplier, pembelian barang, laporan penjualan, dan operasional toko.

Sistem dirancang agar dapat digunakan mulai dari:

* Toko kecil
* Retail
* Minimarket
* Cafe
* Distributor
* Multi outlet / multi branch

Sistem memiliki dua halaman utama:

### Admin Dashboard

Digunakan oleh:

* Owner
* Administrator
* Manager
* Supervisor

Untuk mengelola:

* Produk
* Harga
* Stok
* Supplier
* Customer
* User
* Cabang
* Laporan
* Setting

### POS / Cashier

Digunakan oleh kasir untuk:

* Scan barcode
* Mencari produk
* Menambahkan produk ke keranjang
* Memberikan diskon
* Memilih customer
* Menerima pembayaran
* Mencetak struk
* Melihat transaksi kasir

---

# 2. Product Goals

Tujuan utama sistem POS adalah:

1. Mempercepat proses transaksi kasir.
2. Mengurangi kesalahan pencatatan transaksi.
3. Mengelola stok secara otomatis.
4. Mengetahui profit dan omzet secara real-time.
5. Mengetahui produk paling laku.
6. Mengelola banyak cabang.
7. Mengontrol aktivitas kasir.
8. Mengelola pembelian barang dari supplier.
9. Memberikan laporan bisnis kepada owner.
10. Menyediakan data yang dapat digunakan untuk pengambilan keputusan.

---

# 3. User Roles

## 3.1 Owner

Memiliki akses penuh.

Owner dapat:

* Melihat semua cabang
* Melihat dashboard
* Melihat omzet
* Melihat profit
* Mengelola produk
* Mengelola stok
* Mengelola supplier
* Mengelola user
* Mengelola cabang
* Melihat laporan
* Melihat aktivitas user
* Mengubah konfigurasi sistem

---

## 3.2 Administrator

Administrator bertugas melakukan konfigurasi dan administrasi sistem.

Hak akses:

* Product management
* Category management
* Customer management
* Supplier management
* Stock management
* Purchase management
* User management

Akses dapat dibatasi menggunakan permission.

---

## 3.3 Manager

Manager dapat:

* Melihat dashboard cabang
* Melihat transaksi
* Melihat laporan
* Mengelola stok
* Melakukan stock adjustment
* Melakukan approval tertentu
* Melihat aktivitas kasir

---

## 3.4 Cashier

Cashier memiliki akses POS.

Cashier dapat:

* Membuka shift
* Melakukan transaksi
* Scan barcode
* Memberikan diskon sesuai permission
* Menerima pembayaran
* Print receipt
* Hold transaction
* Resume transaction
* Melihat transaksi sendiri
* Menutup shift

---

# 4. Main Modules

Sistem mempunyai modul utama:

1. Authentication
2. Dashboard
3. POS / Cashier
4. Product Management
5. Category Management
6. Inventory
7. Customer
8. Supplier
9. Purchase
10. Sales
11. Expense
12. Cashier Shift
13. User & Permission
14. Branch
15. Reports
16. Settings
17. Activity Logs

---

# 5. Authentication

## Features

* Login
* Logout
* Forgot password
* Reset password
* Change password

Optional:

* Login PIN untuk kasir
* 2FA untuk administrator
* Login menggunakan QR / employee card

---

# 6. Dashboard

Dashboard menampilkan informasi bisnis secara realtime.

## Summary Cards

Menampilkan:

* Sales Today
* Transactions Today
* Gross Profit
* Total Products
* Low Stock Products
* Total Customers

Contoh:

Revenue Today
Rp 15.500.000

Transactions
125

Gross Profit
Rp 3.800.000

---

## Sales Chart

Chart penjualan berdasarkan:

* Today
* Yesterday
* 7 Days
* 30 Days
* Monthly
* Custom Date

---

## Top Selling Products

Menampilkan:

* Product
* Quantity Sold
* Revenue
* Profit

---

## Low Stock Products

Menampilkan:

* Product
* Current Stock
* Minimum Stock

---

## Recent Transactions

Menampilkan:

* Invoice Number
* Cashier
* Customer
* Payment
* Total
* Date

---

# 7. POS / Cashier

Halaman POS harus dibuat sangat cepat dan sederhana.

Layout utama:

### Left Side

Product List

### Right Side

Shopping Cart / Current Transaction

---

# 8. POS Product Search

Kasir dapat mencari produk berdasarkan:

* Product Name
* SKU
* Barcode

Barcode scanner harus dapat langsung menambahkan produk.

Contoh:

Scan:

89999999901

Sistem langsung menambahkan:

Coca Cola 330ml
Rp 8.000

ke cart.

---

# 9. Product Categories

Kategori contoh:

* Food
* Drink
* Snacks
* Cigarette
* Electronics
* Accessories

Cashier dapat melakukan filter berdasarkan category.

---

# 10. Shopping Cart

Cart menampilkan:

Product

Quantity

Price

Discount

Subtotal

Contoh:

Coca Cola

2 x Rp8.000

Subtotal:

Rp16.000

---

Kasir dapat:

* Increase quantity
* Decrease quantity
* Remove item
* Change quantity
* Apply item discount

---

# 11. Discount

Jenis diskon:

### Product Discount

Contoh:

Coca Cola

10%

### Transaction Discount

Contoh:

Total

Rp100.000

Discount

10%

Final

Rp90.000

---

Diskon dapat menggunakan:

* Percentage
* Fixed amount

Contoh:

10%

atau

Rp10.000

---

# 12. Customer

Transaksi dapat dilakukan tanpa customer.

Default:

Walk-in Customer

Kasir dapat memilih customer.

Customer data:

* Name
* Phone
* Email
* Address
* Birthday
* Member Number
* Points

---

# 13. Payment

Sistem harus mendukung berbagai metode pembayaran.

Payment methods:

* Cash
* Debit Card
* Credit Card
* QRIS
* Bank Transfer
* E-Wallet
* Custom Payment

---

# 14. Cash Payment

Contoh:

Total:

Rp85.000

Customer pays:

Rp100.000

Change:

Rp15.000

---

# 15. Split Payment

Optional feature.

Contoh:

Total:

Rp200.000

Pembayaran:

Cash
Rp100.000

QRIS
Rp100.000

---

# 16. Transaction Success

Setelah pembayaran berhasil:

System:

1. Generate invoice.
2. Save transaction.
3. Reduce stock.
4. Record payment.
5. Update cashier shift.
6. Calculate profit.
7. Generate receipt.

---

# 17. Invoice Number

Format contoh:

INV-20260902-00001

Atau:

INV-JKT01-20260902-00001

Format dapat dikonfigurasi.

---

# 18. Receipt

Receipt berisi:

Store Name

Address

Phone

Invoice Number

Cashier

Date

Products

Subtotal

Discount

Tax

Grand Total

Payment Method

Paid

Change

Footer

Contoh:

Thank you for shopping with us.

---

Receipt dapat:

* Print
* Download PDF
* Send WhatsApp
* Send Email

---

# 19. Hold Transaction

Kasir dapat menyimpan transaksi sementara.

Contoh:

Customer A sedang mengambil barang tambahan.

Cashier klik:

HOLD

Kemudian melayani customer berikutnya.

---

# 20. Resume Transaction

Cashier dapat membuka kembali transaksi yang di-hold.

Menu:

Held Transactions

Contoh:

HOLD-001

Customer:

Walk-in Customer

Total:

Rp120.000

---

# 21. Sales Management

Admin dapat melihat seluruh transaksi.

Data:

* Invoice
* Date
* Branch
* Cashier
* Customer
* Total
* Discount
* Tax
* Payment
* Profit
* Status

---

# 22. Sales Detail

Admin dapat melihat detail transaksi:

Invoice

Products

Quantity

Buying Price

Selling Price

Discount

Profit

Payment

Cashier

Customer

Branch

---

# 23. Refund

Transaction dapat direfund.

Jenis refund:

Full Refund

Partial Refund

Contoh:

Original transaction:

3 products

Customer mengembalikan:

1 product

Sistem:

* Mengembalikan stock
* Mencatat refund
* Mengurangi revenue
* Menyimpan refund reason

---

# 24. Void Transaction

Transaction dapat di-void.

Void membutuhkan permission.

Optional:

Manager PIN.

Sistem menyimpan:

* User
* Reason
* Time
* Original transaction

---

# 25. Product Management

Product mempunyai data:

* Product Name
* SKU
* Barcode
* Category
* Brand
* Description
* Purchase Price
* Selling Price
* Wholesale Price
* Stock
* Minimum Stock
* Unit
* Tax
* Image
* Status

---

# 26. Product Variant

Produk dapat mempunyai variant.

Contoh:

T-Shirt

Variants:

Black / S

Black / M

Black / L

White / S

White / M

White / L

Setiap variant mempunyai:

* SKU
* Barcode
* Price
* Stock

---

# 27. Product Unit

Contoh:

PCS

BOX

PACK

KG

GRAM

LITER

BOTTLE

---

# 28. Inventory

Inventory mencatat seluruh pergerakan stock.

Jenis:

PURCHASE

SALE

REFUND

ADJUSTMENT

TRANSFER

RETURN

---

# 29. Stock Movement

Contoh:

Product:

Coca Cola

Type:

SALE

Quantity:

-2

Before:

100

After:

98

Reference:

INV-001

---

# 30. Stock Adjustment

Admin dapat menambah atau mengurangi stock secara manual.

Contoh alasan:

Damaged Product

Lost Product

Stock Opname

Expired Product

Correction

---

# 31. Stock Opname

Sistem mendukung proses stock opname.

Admin menginput:

System Stock

Actual Stock

Sistem menghitung:

Difference

Contoh:

System:

100

Actual:

98

Difference:

-2

---

# 32. Stock Transfer

Untuk multi branch.

Contoh:

Warehouse

→

Branch Jakarta

Product:

Coca Cola

Qty:

100

Status:

Pending

In Transit

Received

---

# 33. Low Stock Alert

Setiap produk mempunyai minimum stock.

Contoh:

Current Stock:

5

Minimum Stock:

10

Sistem memberikan alert:

LOW STOCK

---

# 34. Supplier

Supplier mempunyai data:

* Supplier Name
* Contact Person
* Phone
* Email
* Address
* Company
* Notes

---

# 35. Purchase Order

Admin dapat membuat purchase order.

Data:

Supplier

Products

Quantity

Purchase Price

Tax

Discount

Total

Expected Date

---

Status:

Draft

Ordered

Partial

Received

Cancelled

---

# 36. Goods Receiving

Ketika barang datang:

Admin melakukan:

Receive Goods

Stock otomatis bertambah.

---

# 37. Purchase Return

Barang yang rusak dapat dikembalikan ke supplier.

System mencatat:

Product

Quantity

Supplier

Reason

Date

---

# 38. Expenses

Sistem dapat mencatat pengeluaran.

Contoh:

Electricity

Rent

Internet

Salary

Delivery

Operational Cost

Other

---

Expense data:

Category

Amount

Date

Branch

Description

Attachment

---

# 39. Cashier Shift

Sebelum menggunakan POS:

Cashier harus melakukan:

OPEN SHIFT

---

# 40. Open Shift

Cashier memasukkan:

Opening Cash

Contoh:

Rp500.000

---

# 41. Shift Transactions

Selama shift sistem mencatat:

Cash Sales

QRIS

Transfer

Card

Refund

Cash In

Cash Out

---

# 42. Close Shift

Ketika selesai kerja:

Cashier melakukan:

CLOSE SHIFT

Cashier memasukkan:

Actual Cash

System menghitung:

Expected Cash

Actual Cash

Difference

---

Contoh:

Expected:

Rp5.000.000

Actual:

Rp4.950.000

Difference:

-Rp50.000

---

# 43. Cash In / Cash Out

Contoh Cash Out:

Cash digunakan membeli air minum.

Amount:

Rp50.000

Reason:

Operational Expense

---

# 44. Customer Loyalty

Optional.

Customer mendapatkan point.

Contoh:

Rp10.000

=

1 point

100 points

=

Rp10.000 discount

---

# 45. Promotions

Optional.

Contoh:

Buy 2 Get 1

Discount 20%

Happy Hour

Member Discount

Bundle Product

---

# 46. Reports

Sistem menyediakan laporan:

Sales Report

Profit Report

Product Sales

Category Sales

Cashier Sales

Payment Report

Inventory Report

Stock Movement

Purchase Report

Expense Report

Tax Report

Customer Report

---

# 47. Sales Report

Filter:

Date Range

Branch

Cashier

Payment Method

Product

Category

Customer

---

Data:

Revenue

Cost

Gross Profit

Discount

Tax

Net Sales

Transactions

Average Transaction

---

# 48. Profit Calculation

Gross Profit:

Selling Price - Purchase Price

Contoh:

Purchase Price:

Rp5.000

Selling Price:

Rp8.000

Profit:

Rp3.000

---

# 49. Product Performance

Menampilkan:

Best Selling Product

Highest Revenue

Highest Profit

Slow Moving Product

Dead Stock

---

# 50. Multi Branch

Sistem harus mendukung banyak cabang.

Contoh:

Jakarta

Bandung

Surabaya

Setiap branch memiliki:

* Stock
* Cashier
* Transactions
* Expenses
* Reports

---

# 51. Warehouse

Optional.

Warehouse dapat menyimpan stock pusat.

Flow:

Supplier

↓

Warehouse

↓

Branch

↓

Customer

---

# 52. User Management

User mempunyai:

Name

Email

Phone

Role

Branch

Status

---

# 53. Role & Permission

Permission granular.

Contoh:

product.view

product.create

product.update

product.delete

sales.view

sales.refund

sales.void

discount.apply

inventory.adjust

report.view

user.manage

---

# 54. Activity Logs

Semua aktivitas penting dicatat.

Contoh:

User:

John

Action:

Updated Product Price

Product:

Coca Cola

Old Price:

Rp7.000

New Price:

Rp8.000

Time:

10:15

---

Activity log terutama untuk:

Price Changes

Stock Adjustment

Refund

Void

Discount

Login

User Changes

Settings

---

# 55. Settings

Store Settings:

Store Name

Logo

Address

Phone

Email

Currency

Timezone

---

POS Settings:

Default Customer

Allow Discount

Allow Negative Stock

Receipt Settings

Invoice Format

---

# 56. Tax

System dapat menggunakan tax.

Contoh:

PPN

11%

Tax dapat:

Tax Inclusive

atau

Tax Exclusive

---

# 57. Barcode

Sistem mendukung:

EAN-13

EAN-8

UPC

CODE128

Custom Barcode

---

Sistem dapat:

Scan barcode

Generate barcode

Print barcode label

---

# 58. Export Data

Data dapat diexport menjadi:

Excel

CSV

PDF

Contoh:

Sales Report.xlsx

Inventory Report.xlsx

---

# 59. Import Data

Admin dapat import:

Products

Customers

Suppliers

Stock

menggunakan:

Excel / CSV

---

# 60. Notification System

Notification:

Low Stock

Out of Stock

New Purchase

Pending Transfer

Large Refund

Cashier Difference

---

# 61. Database Main Tables

Contoh struktur database:

users

roles

permissions

branches

products

product_variants

categories

brands

units

stocks

stock_movements

customers

suppliers

sales

sale_items

payments

refunds

refund_items

purchases

purchase_items

stock_transfers

stock_transfer_items

expenses

cashier_shifts

cash_movements

activity_logs

settings

notifications

---

# 62. Sales Table

sales

id

invoice_number

branch_id

cashier_id

customer_id

subtotal

discount

tax

grand_total

cost_total

profit

payment_status

status

created_at

updated_at

---

# 63. Sale Items

sale_items

id

sale_id

product_id

variant_id

quantity

purchase_price

selling_price

discount

tax

subtotal

profit

---

# 64. Payments

payments

id

sale_id

payment_method_id

amount

reference_number

status

paid_at

---

# 65. Stock

stocks

id

branch_id

product_id

variant_id

quantity

minimum_stock

---

# 66. Stock Movement

stock_movements

id

branch_id

product_id

variant_id

type

quantity

stock_before

stock_after

reference_type

reference_id

user_id

notes

created_at

---

# 67. Cashier Shift

cashier_shifts

id

user_id

branch_id

opening_cash

expected_cash

actual_cash

difference

opened_at

closed_at

status

---

# 68. POS Flow

Cashier Login

↓

Open Shift

↓

Scan Product

↓

Add Product to Cart

↓

Select Customer

↓

Apply Discount

↓

Checkout

↓

Select Payment

↓

Payment

↓

Transaction Created

↓

Stock Reduced

↓

Receipt Printed

↓

Next Transaction

---

# 69. Purchase Flow

Create Purchase Order

↓

Select Supplier

↓

Add Products

↓

Submit Order

↓

Supplier Sends Products

↓

Receive Products

↓

Stock Increased

↓

Purchase Completed

---

# 70. Return Flow

Find Transaction

↓

Select Product

↓

Select Quantity

↓

Input Reason

↓

Refund Payment

↓

Stock Returned

↓

Refund Recorded

---

# 71. Suggested Technology Stack

## Backend

Laravel

Recommended:

Laravel 13+

REST API / Internal API

---

## Frontend

Recommended:

Vue.js

atau

React / Next.js

Jika menggunakan Laravel:

Laravel + Inertia + Vue

cukup ideal untuk POS.

---

## UI

Tailwind CSS

Component:

Shadcn/Vue

PrimeVue

atau

custom component.

---

## Database

PostgreSQL

Recommended.

Alternative:

MySQL.

---

## Cache

Redis

Digunakan untuk:

Session

Queue

Cache

Realtime data

---

## Queue

Laravel Queue

Optional:

Laravel Horizon

Untuk:

Report Generation

Email Receipt

WhatsApp Receipt

Bulk Import

Notification

---

# 72. Optional Realtime System

Menggunakan:

Laravel Reverb

atau

WebSocket.

Contoh kegunaan:

Real-time dashboard

Real-time stock

Real-time sales

Notification

---

# 73. Offline POS

Phase berikutnya dapat mendukung offline mode.

Contoh:

Internet mati.

Cashier tetap dapat transaksi.

Data disimpan local:

IndexedDB

Setelah internet kembali:

POS melakukan synchronization.

---

# 74. Security Requirements

System harus menggunakan:

HTTPS

Password Hashing

CSRF Protection

Rate Limiting

RBAC

Secure Session

Database Backup

Audit Logs

---

Critical action seperti:

Refund

Void

Stock Adjustment

Large Discount

dapat memerlukan:

Manager Authorization.

---

# 75. Performance Requirements

Target:

POS screen load:

< 2 seconds

Product search:

< 300ms

Barcode scan response:

< 200ms

Checkout:

< 1 second

Dashboard:

< 3 seconds

---

# 76. MVP Scope

Untuk versi pertama jangan langsung membuat semua fitur.

MVP cukup:

Authentication

Dashboard

POS

Products

Categories

Inventory

Customers

Suppliers

Sales

Payments

Cashier Shift

Reports

Users

Roles

Settings

---

# 77. MVP Development Priority

## Phase 1 — Foundation

Authentication

User

Role

Permission

Branch

Settings

---

## Phase 2 — Products

Category

Product

Product Variant

Barcode

Stock

---

## Phase 3 — POS

POS Screen

Cart

Barcode

Customer

Discount

Payment

Receipt

---

## Phase 4 — Sales

Sales History

Transaction Detail

Refund

Void

---

## Phase 5 — Inventory

Stock Movement

Stock Adjustment

Low Stock Alert

Stock Opname

---

## Phase 6 — Purchase

Supplier

Purchase Order

Goods Receiving

Purchase Return

---

## Phase 7 — Reports

Dashboard

Sales Report

Profit Report

Inventory Report

Cashier Report

Expense Report

---

# 78. Future Features

Setelah MVP stabil dapat ditambahkan:

Multi warehouse

Loyalty point

Membership

Voucher

Promotion engine

Online ordering

E-commerce integration

Shopee integration

Tokopedia integration

GrabFood integration

GoFood integration

WhatsApp receipt

Accounting module

Employee management

Payroll

AI analytics

Mobile app

---

# 79. AI Features

Future version dapat memiliki AI Business Assistant.

Owner dapat bertanya:

"Berapa omzet hari ini?"

AI:

"Omzet hari ini Rp15.500.000 dari 125 transaksi."

---

Owner:

"Produk mana yang mulai kehabisan?"

AI:

"Coca Cola tersisa 8 pcs dan minimum stock 10."

---

Owner:

"Produk apa yang paling laku minggu ini?"

AI memberikan:

Top Product

Quantity

Revenue

Profit

---

AI juga dapat mendeteksi:

Slow moving products

Stock anomalies

Cashier anomalies

Unusual discount

Potential fraud

Recommended reorder

Sales forecasting

---

# 80. SaaS POS

Jika sistem ingin dijadikan bisnis SaaS:

Architecture:

Platform

↓

Tenant / Merchant

↓

Company

↓

Branch

↓

Users

---

Subscription plans:

Free

Basic

Professional

Enterprise

---

Contoh:

Basic

1 Branch

3 Cashiers

1,000 Products

---

Professional

5 Branches

Unlimited Cashiers

Unlimited Products

Advanced Reports

---

Enterprise

Unlimited Branch

API Access

Custom Integration

Priority Support

---

# 81. Success Metrics

Produk dinilai berhasil apabila:

Checkout dapat dilakukan < 10 detik.

Stock selalu sinkron dengan transaksi.

Tidak ada duplicate transaction.

Laporan revenue akurat.

Profit dapat dihitung berdasarkan transaction.

Cashier difference dapat diketahui.

Owner dapat mengetahui kondisi bisnis melalui dashboard.

---

# 82. Definition of Done MVP

MVP dianggap selesai apabila user dapat:

Login

↓

Create Product

↓

Set Stock

↓

Open Cashier Shift

↓

Scan Product

↓

Checkout

↓

Accept Payment

↓

Print Receipt

↓

Stock Automatically Reduced

↓

Transaction Appears in Sales

↓

Revenue Appears in Dashboard

↓

Close Shift

↓

Generate Sales Report

Tanpa memerlukan proses manual di database.
