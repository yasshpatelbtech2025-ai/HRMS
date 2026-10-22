# HRMS â€” HR, Attendance & Payroll Management

A full-stack HR management system for staffing and contract-labour firms in India. It runs the whole monthly cycle for many client companies from one place: employee onboarding, attendance and leave, salary generation with statutory deductions, compliance registers, and client invoicing.

Built with **Laravel 13**, **PHP 8.3**, **Blade + Alpine.js** and **Tailwind CSS**, and deployed on Windows Server / IIS.

## Features

### Employees
- Complete employee records: basic info, addresses, bank and statutory details (PF / ESI numbers), family and nominee, qualifications, experience and medical examinations
- Bulk import of employees and companies from Excel/CSV, with an import history
- Positions, departments and sub-departments; separations (exits)
- Printable employee ID cards with configurable card formats
- Document storage and assignment per employee

### Attendance & Leave
- Raw punch-data import and processing into daily attendance
- Smart attendance, missing-punch detection and attendance correction requests
- Shift management and shift rosters
- Monthly attendance sheets and per-employee reports
- Leave types, leave applications, leave balances and comp-offs
- National and company-specific holiday calendars

### Payroll
- Monthly salary generation from attendance, with configurable salary elements and payroll rules
- Statutory deductions: **PF, ESI, LWF** and **TDS** (income tax under the old and new regimes, with slabs, Section 87A rebate and cess)
- Overtime, salary adjustments, advances and loans
- Salary revisions with arrears, and annual bonus sheets
- Payslip generation as PDF

### Compliance
- Statutory registers and forms generated from payroll and attendance data, e.g. Forms Aâ€“D, 13, 14, 16â€“21, Gratuity Form F, and PF Forms 10C and 19
- Monthly compliance task tracking

### Invoicing & Administration
- Tax invoices for client companies, filled in from payroll and downloadable as branded PDFs (admin only)
- Multi-company setup: each user only sees the companies they have access to
- Admin and user roles, activity/audit log, global search and a recycle bin for deleted records

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Frontend | Blade templates, Alpine.js, Tailwind CSS, Vite |
| Database | SQLite by default; MySQL, PostgreSQL or SQL Server via Laravel |
| PDF | Dompdf |
| Excel import | OpenSpout |
| Auth | Laravel Breeze |
| Testing | PHPUnit |
| Hosting | IIS on Windows Server |

## Getting Started

**Requirements:** PHP 8.3+, Composer and Node.js 20+.

```bash
git clone https://github.com/yasshpatelbtech2025-ai/HRMS.git
cd HRMS

composer install
npm install

cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # SQLite is the default; set DB_* in .env for another database
php artisan migrate --seed

npm run build
php artisan serve
```

Open http://localhost:8000 and sign in as `test@example.com` / `password` (the seeded demo user).

To run the tests:

```bash
php artisan test
```

## Project Structure

```
app/
  Http/Controllers/   Employees, Attendance, Payroll, Compliance, Invoices
  Models/             About 40 Eloquent models (Employee, SalarySheet, Payslip, ...)
  Services/
    Attendance/       Punch processing, monthly attendance, leave balances
    Payroll/          Salary generation, payroll calculator, income tax, bonus
    Compliance/       Statutory forms and compliance tasks
    Invoice/          Invoice lines built from payroll
resources/views/      Blade UI
database/migrations/  Schema
tests/                Feature and unit tests
```

## Author

**Yassh Patel** â€” [GitHub](https://github.com/yasshpatelbtech2025-ai)
