# DuukaFlow — Remaining Work

**Reviewed:** 2026-10-03  
**Scope:** Core product launch readiness: operations, data integrity, UI, testing, and recovery.  
**Excluded:** WhatsApp/email messaging and notifications, URA integration, payment/subscription providers, and other external APIs.

## Status

DuukaFlow has broad operational coverage across inventory, POS and sales, purchasing, finance, staff, reporting, and audit workflows. The remaining work is primarily release verification and operational readiness, not adding more feature breadth.

**Not yet ready for production rollout.** Verify production backups and restore, clear the frontend CI lint failure, and complete business-flow UAT before handling live business data.

## P0 — Before Production

### 1. Verify scheduled off-host backup and restore

The project now has a PostgreSQL backup command and DigitalOcean Spaces configuration, but the off-host scheduled path and recovery procedure have not been verified against a configured Spaces bucket. The development restore drill was deferred. Do not treat local dumps or successful fake-storage tests as proof of production recovery.

Evidence: [api/app/Console/Commands/DatabaseBackup.php](api/app/Console/Commands/DatabaseBackup.php), [api/config/filesystems.php](api/config/filesystems.php), and the Docker backup/restore scripts under [ops](ops).

**Action:** Configure Spaces credentials as deployment secrets, verify the scheduled VPS backup uploads successfully, set retention and failure monitoring, then restore a real archive into an isolated database. Record the measured recovery point and recovery time.

### 2. Clear the frontend lint CI failure

CI runs `npm run lint`. The latest verified run reports **1,080 errors and 18 warnings**, so the frontend CI job remains red even though the TypeScript/Vite production build passes.

Evidence: [.github/workflows/ci.yml](.github/workflows/ci.yml), [ui/package.json](ui/package.json), and [review.md](review.md).

**Action:** Reduce or deliberately baseline the lint backlog without disabling meaningful checks. Keep lint and the production build required in CI.

## P1 — Before Broader Rollout

### 3. Validate POS on target devices and document connectivity assumptions

The UI backlog still lists mobile support and offline sync. A connected-only pilot may be reasonable, but network outages can stop checkout and complicate reconciliation.

Evidence: [ui/README.md](ui/README.md).

**Action:** Validate POS on the actual tablet/phone, scanner, and printer setup. Document the initial connectivity requirement and outage procedure; plan offline sale queuing and conflict-safe stock reconciliation before expanding to low-connectivity sites.

### 4. Run end-to-end financial and inventory UAT

The application has stock movements, product-loss records, financial audits, and sale/purchase return flows, but the linked correction paths have not been verified together in a complete business UAT.

**Action:** Test sale, partial return, purchase receipt/return, stock write-off, cash adjustment, and void/refusal paths. Assert final stock, sale and payment totals, cash flow, audit history, and branch ownership after each scenario.

## Review Limitations

This is a source-based readiness list, not a production penetration test or completed manual UAT. The production UI build passes; ESLint remains failing as noted above. Backup provider credentials and an isolated restore target were not available for end-to-end recovery verification. No excluded integrations were assessed.
