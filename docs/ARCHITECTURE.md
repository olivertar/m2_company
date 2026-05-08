# Orangecat_Company — Architecture

<!-- refreshed: 2026-05-07 -->

## Module Overview

Magento 2 module that adds **B2B Company management** to the storefront. It allows:

- Companies to register from the frontend (with admin approval workflow).
- Admins to manage companies, assign customers, and define roles.
- Role-based access inside a company (Admin, Manager, Buyer).
- Customer login restrictions based on approval status.
- Email notifications for company events.

---

## System Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                        MAGENTO 2 CORE                        │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────┐ │
│  │  Customer   │  │   Backend    │  │    Email System     │ │
│  │  (Frontend) │  │   (Admin)    │  │  (TransportBuilder) │ │
│  └──────┬──────┘  └──────┬───────┘  └──────────┬──────────┘ │
└─────────┼────────────────┼─────────────────────┼────────────┘
          │                │                     │
          ▼                ▼                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Orangecat_Company MODULE                        │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                    Controllers                         │  │
│  │  Frontend: Account/Create, Account/CreatePost         │  │
│  │  Frontend: Users/Index, Edit, Create, Save, Delete    │  │
│  │  Admin: Company/Index, Edit, Save, Delete, InlineEdit │  │
│  │  Admin: Customer/CompanyGrid                          │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                   API (REST)                           │  │
│  │  POST   /V1/mycompany/           → save               │  │
│  │  PUT    /V1/mycompany/:id        → save               │  │
│  │  GET    /V1/mycompany/:id        → get                │  │
│  │  DELETE /V1/mycompany/:id        → deleteById         │  │
│  │  GET    /V1/mycompany/           → getList            │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                   Services / Models                    │  │
│  │  CompanyRepository        → CRUD + validation + email │  │
│  │  CompanyManagement        → assign/remove customers   │  │
│  │  RoleRepository           → role CRUD                 │  │
│  │  Config                   → scope config helper       │  │
│  └───────────────────────────────────────────────────────┘  │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                   Plugins & Observers                  │  │
│  │  LoginPost (around)       → block unapproved login    │  │
│  │  RegistrationRestriction (before) → 404 if disabled   │  │
│  │  RegisterLink (after)     → hide register link        │  │
│  │  TransportBuilderPlugin   → swap email templates      │  │
│  │  CustomerRepository plugins → prevent admin deletion  │  │
│  │  AutoApproveCustomer      → set approve_account attr  │  │
│  │  SaveCompanyObserver      → admin customer assignment │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────────────────────────────────────────┐
│                        DATABASE                              │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐ │
│  │   mycompany  │  │ mycompany_role│  │ mycompany_customer │ │
│  │  (companies) │  │   (roles)     │  │ (customer links)   │ │
│  └──────────────┘  └──────────────┘  └────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## Component Responsibility Table

| Component | Responsibility | Key File |
|-----------|---------------|----------|
| `CompanyRepository` | CRUD for companies, unique validation (email/tax_id), sync admin approval status, send status change emails | `Model/CompanyRepository.php` |
| `CompanyManagement` | Assign/remove customers from companies, validate role existence, enforce single-admin policy, secure customer deletion | `Model/CompanyManagement.php` |
| `RoleRepository` | CRUD for company roles | `Model/RoleRepository.php` |
| `Config` | Centralized scope-config reader for all module settings | `Model/Config.php` |
| `Company` | Data model with status constants (Pending, Approved, Suspended, Rejected) | `Model/Company.php` |
| `LoginPost` Plugin | Intercepts customer login; blocks if `approve_account` is 0 or missing when approval is required | `Plugin/Customer/Controller/Account/LoginPost.php` |
| `RegistrationRestriction` Plugin | Throws 404 on customer registration pages when disabled in config | `Plugin/Customer/Controller/Account/RegistrationRestriction.php` |
| `TransportBuilderPlugin` | Swaps Magento's default "no password" email template with company-specific template when customer is a company user | `Plugin/Mail/Template/TransportBuilderPlugin.php` |
| `SaveCompanyObserver` | Admin customer save hook; validates company assignment, enforces single-admin rule | `Observer/Adminhtml/Customer/SaveCompanyObserver.php` |
| `AutoApproveCustomer` | Frontend customer registration hook; sets `approve_account` attribute based on config | `Observer/AutoApproveCustomer.php` |
| `Account/CreatePost` Controller | Frontend company registration form processing; creates company + customer + link + sends notification email | `Controller/Account/CreatePost.php` |

---

## Data Flow Traces

### 1. Frontend Company Registration

```
Guest visits /company/account/create
    → Form submission (POST /company/account/createPost)
        → Controller: Account/CreatePost::execute()
            1. Validates form key
            2. Validates company_email != admin_email
            3. Validates admin_email is unique (no existing customer)
            4. Validates company_email is unique (no existing company)
            5. Validates tax_id is unique
            6. Creates Company (status = PENDING)
            7. Creates Customer via AccountManagement (no password)
            8. Links customer to company as ADMIN (RoleInterface::ADMIN_ROLE_ID)
            9. Sends notification email to admin(s)
            10. Redirects to success page
```

### 2. Admin Approves a Company

```
Admin edits company in Backend
    → Changes status to APPROVED
        → Controller: Adminhtml/Company/Save::execute()
            → CompanyRepository::save()
                1. Detects status change (old != new)
                2. Saves company
                3. syncCompanyAdminStatus():
                    → Finds admin customer for this company
                    → Sets customer.approve_account = 1
                4. sendStatusChangeEmail():
                    → Sends "Approved" email to company email address
```

### 3. Customer Login (with restriction)

```
Customer submits login form
    → LoginPost controller
        → Plugin: LoginPost::aroundExecute()
            1. Reads customer by email
            2. Checks `approve_account` custom attribute
            3. If attribute = false → BLOCK (error message + redirect)
            4. If attribute is null AND approval is required → BLOCK
            5. Otherwise → proceed to normal login
```

### 4. Admin Assigns Customer to Company

```
Admin saves customer with company_id/role_id
    → Event: adminhtml_customer_save_after
        → Observer: SaveCompanyObserver::execute()
            1. Reads customer[company_id] and customer[role_id] from POST
            2. Validates role exists
            3. Single Admin Policy:
               → If assigning Admin role, checks company has no other admin
            4. Mandatory Admin Policy:
               → If demoving/unassigning admin, checks another admin exists
            5. Creates or updates mycompany_customer link row
```

---

## Database Schema

### `mycompany` — Company Flat Table

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | int PK | Auto-increment |
| `name` | varchar(255) | Company display name |
| `name_legal` | varchar(255) | Legal name |
| `email` | varchar(255) | Unique |
| `tax_id` | varchar(50) | Unique, nullable |
| `address` | varchar(255) | Street address |
| `city` | varchar(100) | |
| `country` | varchar(2) | ISO country code |
| `region` | varchar(255) | State/Province |
| `postalcode` | varchar(20) | ZIP |
| `telephone` | varchar(50) | |
| `status` | smallint | 0=Pending, 1=Approved, 2=Suspended, 3=Rejected |
| `website_id` | smallint | FK → store_website |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `mycompany_role` — Company Roles

| Column | Type | Notes |
|--------|------|-------|
| `role_id` | int PK | |
| `role_name` | varchar(100) | e.g. "Company Admin" |
| `permissions` | text | JSON array of permission strings |

**Default roles** (installed via `InstallDefaultRoles` data patch):
- Company Admin — permissions `["all"]`
- Company Manager — permissions `["manage_users", "view_orders"]`
- Company Buyer — permissions `["place_order"]`

### `mycompany_customer` — Company ↔ Customer Link

| Column | Type | Notes |
|--------|------|-------|
| `link_id` | int PK | |
| `company_id` | int | FK → mycompany |
| `customer_id` | int | FK → customer_entity |
| `role_id` | int | FK → mycompany_role |
| `max_purchase_amount` | decimal(12,4) | Nullable spending limit |
| `max_period_amount` | decimal(12,4) | Nullable period limit |

**Unique constraint:** `(company_id, customer_id)` — one customer belongs to one company.

---

## Extension Points

### Customer Extension Attribute

`etc/extension_attributes.xml` adds `company_attributes` to `CustomerInterface`:

```xml
<extension_attributes for="Magento\Customer\Api\Data\CustomerInterface">
    <attribute code="company_attributes" type="Orangecat\Company\Api\Data\CompanyCustomerInterface" />
</extension_attributes>
```

This allows the API to expose company link data alongside customer data.

---

## Dependencies

```xml
<sequence>
    <module name="Magento_Customer"/>
    <module name="Magento_Eav"/>
    <module name="Magento_Backend"/>
    <module name="Magento_Ui"/>
    <module name="Magento_Theme"/>
    <module name="Orangecat-Core"/>
</sequence>
```

---

## Anti-Patterns to Avoid

1. **Do not delete a company without checking `mycompany_customer` links** — FK constraints with `ON DELETE CASCADE` will remove link rows, but orphaned customers may remain with `approve_account = 1`.
2. **Do not hard-code role IDs** — Use `RoleInterface::ADMIN_ROLE_ID` constant (value `1`) or query by role name.
3. **Do not bypass `CompanyManagement::validateManageUser()`** — Always validate admin permissions before allowing user management operations in frontend controllers.
4. **Do not modify email templates directly in `view/frontend/email/` without updating `email_templates.xml`** — The template IDs are referenced in `Config` and admin system config.
