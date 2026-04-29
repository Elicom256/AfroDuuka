You are building a modern (2026-ready) multi-tenant Inventory Management API using Laravel, Laravel Sail, PostgreSQL, and Laravel Sanctum for authentication.

The system MUST follow clean architecture principles:
- Controllers must be thin
- Business logic must be abstracted into Service classes
- Use Form Request classes for validation
- Use Policies for authorization
- Use Laravel Sanctum for API authentication

Multi-tenancy (Business-based):
- Use a single database with business isolation
- Every business-related table MUST include business_id
- All queries must be scoped by business_id
- business_id must be indexed in all relevant tables

Authentication & Roles:
- Users belong to a business
- Implement a simple roles system:
  - roles table
  - role_user pivot table (many-to-many)
- Example roles: admin, staff

Database rules:
- Use Laravel Schema Builder
- Use foreignId()->constrained()->cascadeOnDelete() where appropriate
- Add indexes on:
  - business_id
  - sku
  - barcode
  - foreign keys
- Use decimal(12,2) for money fields
- Use integer for quantities
- Use timestamps() on all tables
- Use softDeletes() where appropriate
- Ensure PostgreSQL compatibility
- Use enum fields for statuses where needed

System design:

0. businesses
- id
- name (string)
- email (string nullable)
- phone (string nullable)
- timestamps

1. users
- name
- email (unique)
- password
- business_id (foreign key, indexed)
- timestamps

2. roles
- name (unique)

3. role_user (pivot)
- user_id (foreign key)
- role_id (foreign key)

4. categories
- business_id (foreign key, indexed)
- name (string)
- description (nullable text)
- status (boolean default true)
- timestamps

5. products
- business_id (foreign key, indexed)
- name (string)
- sku (string)
- barcode (string nullable, indexed)
- category_id (foreign key)
- price (decimal 12,2)
- cost_price (decimal 12,2)
- quantity (integer default 0)
- minimum_stock (integer default 0)
- status (boolean default true)
- description (text nullable)
- timestamps
- soft deletes

IMPORTANT:
- SKU must be unique per business (composite unique: business_id + sku)

6. suppliers
- business_id (foreign key, indexed)
- name (string)
- email (string nullable)
- phone (string nullable)
- address (text nullable)
- status (boolean default true)
- timestamps
- soft deletes

7. purchases
- business_id (foreign key, indexed)
- supplier_id (foreign key)
- total_amount (decimal 12,2)
- purchased_at (timestamp)
- status (enum: pending, completed, cancelled)
- timestamps

8. purchase_items
- purchase_id (foreign key)
- product_id (foreign key)
- quantity (integer)
- cost_price (decimal 12,2)
- subtotal (decimal 12,2)
- timestamps

9. sales
- business_id (foreign key, indexed)
- total_amount (decimal 12,2)
- sold_at (timestamp)
- status (enum: pending, completed, cancelled)
- timestamps

10. sale_items
- sale_id (foreign key)
- product_id (foreign key)
- quantity (integer)
- price (decimal 12,2)
- subtotal (decimal 12,2)
- timestamps

11. stock_movements
- business_id (foreign key, indexed)
- product_id (foreign key)
- type (enum: in, out, adjustment)
- quantity (integer)
- reference_type (string nullable)
- reference_id (bigInteger nullable)
- notes (text nullable)
- timestamps

IMPORTANT CONSTRAINTS:
- Enforce foreign key constraints with cascadeOnDelete() where appropriate
- Prevent orphan records
- Ensure all business-owned tables include business_id
- Add composite unique constraints where necessary
- Ensure proper indexing for performance

OUTPUT REQUIREMENTS:
- Generate complete Laravel migration files
- Include full up() and down() methods
- Order migrations correctly to respect foreign key dependencies
- Keep code clean, readable, and production-ready