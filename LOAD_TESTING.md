# Load Testing Guide

This document covers how to seed test data, run k6 load tests, and interpret results.

---

## Prerequisites

### 1. Install k6

```bash
# macOS
brew install k6

# Linux (Debian/Ubuntu)
sudo gpg -k
sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg \
    --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" \
    | sudo tee /etc/apt/sources.list.d/k6.list
sudo apt-get update && sudo apt-get install k6

# Windows (winget)
winget install k6

# Docker (no install needed)
docker pull grafana/k6
```

### 2. Seed test data

**Never run this on production.** The command is disabled in `APP_ENV=production`.

```bash
# Seed 30,000 applicants, 20 vacancies, 30,000 applications
php artisan recruitment:seed-load-test --applicants=30000 --vacancies=20 --applications=30000

# Smaller dataset for quick smoke test
php artisan recruitment:seed-load-test --applicants=200 --vacancies=5 --applications=200
```

The command outputs:
- Applicant emails follow the pattern `loadtest_applicant_{N}@testmail.invalid`, password `Password123!`
- A sample vacancy UUID you can pass to `VACANCY_ID`

---

## Scenarios

### A. Public vacancy browsing (100 VUs, 5 min)

```bash
k6 run -e BASE_URL=http://localhost:8000 load-tests/k6/vacancy-browse.js
```

**Expected thresholds:**
- `p(95) < 800ms`
- `error rate < 1%`

---

### B. Applicant registration (50 VUs, 5 min)

```bash
k6 run -e BASE_URL=http://localhost:8000 load-tests/k6/applicant-registration.js
```

**Expected thresholds:**
- `p(95) < 2000ms`
- `error rate < 1%`
- Every registered email must be unique (seeder verifies this)

---

### C/D. Application submission (30 VUs, 5 min)

Requires pre-seeded applicants and an open vacancy.

```bash
# Get a vacancy UUID from the seeder output or:
php artisan tinker --execute="echo App\Models\Vacancy::where('status','open')->value('id');"

k6 run \
  -e BASE_URL=http://localhost:8000 \
  -e VACANCY_ID=<vacancy-uuid> \
  load-tests/k6/application-submit.js
```

**Expected thresholds:**
- `p(95) < 2500ms`
- `error rate < 1%`
- `duplicates_caught == 0` — any duplicate means the guard failed

---

### E. Spike test (0 → 200 VUs in 30s)

```bash
k6 run -e BASE_URL=http://localhost:8000 load-tests/k6/spike-test.js
```

**Expected thresholds:**
- `p(95) < 3000ms`
- `server_errors rate == 0` (zero HTTP 5xx)
- `error rate < 2%`

---

## Running with Docker

```bash
docker run --rm -i --network host grafana/k6 run \
  -e BASE_URL=http://localhost:8000 \
  - < load-tests/k6/vacancy-browse.js
```

---

## Interpreting results

| Metric | What it means |
|---|---|
| `http_req_duration` | Total round-trip time per request |
| `p(95)` | 95th percentile — 95% of requests were faster than this |
| `errors` rate | Share of requests that returned a non-2xx/3xx status |
| `server_errors` rate | Share of 5xx responses — must be zero |
| `duplicates_caught` | Applications that the system correctly blocked as duplicates |
| `successful_registrations` | Count of completed registrations |

**A passing run looks like:**
```
✓ p(95) of http_req_duration............: 450ms < 800ms
✓ errors.....................................: 0.00% ✓ < 1%
✓ server_errors..............................: 0.00% ✓ = 0%
```

**A failing run looks like:**
```
✗ p(95) of http_req_duration............: 1800ms > 800ms  ← DB missing index or slow query
✗ server_errors..............................: 0.30%         ← 500 error — check laravel.log
```

---

## What to monitor during tests

1. **`storage/logs/laravel.log`** — watch for exceptions, slow query warnings
2. **Database process list** — `SHOW PROCESSLIST` (MySQL) or `pg_stat_activity` (PostgreSQL) during the test
3. **CPU and memory** on the app server — `htop` or equivalent
4. **Queue worker** — ensure `php artisan queue:work` is running for notifications

---

## Recommended thresholds (production baseline)

| Scenario | p95 target | Error rate |
|---|---|---|
| Public pages (vacancy list, home) | < 800ms | < 0.5% |
| Authenticated applicant pages | < 1200ms | < 0.5% |
| Application submission (no files) | < 2500ms | < 1% |
| Application submission (with files) | < 5000ms | < 1% |
| Spike burst (200 VUs) | < 3000ms | < 2% |
| Admin dashboard | < 2000ms | < 1% |

---

## Minimum server specs for 30,000 applicants

| Resource | Minimum | Recommended |
|---|---|---|
| vCPU | 2 | 4 |
| RAM | 4 GB | 8 GB |
| Database | MySQL 8 / PostgreSQL 15 | Same with read replica |
| Cache/Queue | File/DB driver (low concurrency) | Redis |
| Storage | Local disk | S3-compatible object storage |
| PHP-FPM workers | 8 | 16–32 |
| Queue workers | 2 | 4–8 |

---

## Proving capacity (step-by-step)

```bash
# 1. Wipe and reseed with 30k dataset
php artisan migrate:fresh --seed
php artisan recruitment:seed-load-test --applicants=30000 --vacancies=20 --applications=30000

# 2. Run all scenarios sequentially
k6 run -e BASE_URL=http://your-server load-tests/k6/vacancy-browse.js
k6 run -e BASE_URL=http://your-server load-tests/k6/applicant-registration.js
k6 run -e BASE_URL=http://your-server -e VACANCY_ID=<uuid> load-tests/k6/application-submit.js
k6 run -e BASE_URL=http://your-server load-tests/k6/spike-test.js

# 3. Verify zero duplicates
php artisan tinker --execute="
\$dups = DB::table('applications')
    ->selectRaw('applicant_id, vacancy_id, count(*) as cnt')
    ->groupBy('applicant_id','vacancy_id')
    ->having('cnt','>',1)
    ->get();
echo \$dups->isEmpty() ? 'PASS: No duplicates' : 'FAIL: '.\$dups->count().' duplicates found';
"

# 4. Verify unique reference numbers
php artisan tinker --execute="
\$dups = DB::table('applications')
    ->selectRaw('reference_number, count(*) as cnt')
    ->groupBy('reference_number')
    ->having('cnt','>',1)
    ->get();
echo \$dups->isEmpty() ? 'PASS: All reference numbers unique' : 'FAIL: duplicates found';
"
```
