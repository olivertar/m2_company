# Orangecat_Company

**Module:** `Orangecat_Company`
**Version:** 1.0.0
**License:** OSL-3.0
**Author:** Oliverio Gombert <olivertar@gmail.com>

---

## Table of Contents

1. [Overview](#overview)
2. [Theme Compatibility](#theme-compatibility)
3. [Requirements](#requirements)
4. [Installation](#installation)
5. [What Gets Installed](#what-gets-installed)
6. [Configuration](#configuration)
7. [Store Admin Guide](#store-admin-guide)
8. [Frontend User Guide](#frontend-user-guide)
9. [Developer Guide](#developer-guide)
10. [REST API](#rest-api)
11. [Frontend Routes Reference](#frontend-routes-reference)
12. [DevOps & Integrator Notes](#devops--integrator-notes)

---

## Overview

`Orangecat_Company` is the **core B2B module** of the Orangecat suite. It introduces the concept of a **Company** entity into Magento 2 — a legal business unit that groups multiple customers under a shared identity with role-based access control.

The module handles:

- Company entity management (create, approve, suspend, reject)
- Company registration form on the frontend (separate from standard customer registration)
- Customer approval workflow — accounts can require admin approval before login is allowed
- Role assignment per company user (Admin, Manager, Buyer)
- Frontend user management panel for Company Admins
- Access controls for standard registration and login flows
- Email notifications for registration events and status changes
- Full REST API and Admin UI for company management

### Position in the Orangecat B2B Dependency Chain

```
Orangecat_Core (via composer: orangecat/core)
  └── Orangecat_Company                     ← this module
        ├── Orangecat_Prices
        │     ├── Orangecat_PricesList
        │     └── Orangecat_PricesCompany
        ├── Orangecat_CompanyCredit
        ├── Orangecat_CompanyMethods
        ├── Orangecat_CompanySalesRep
        └── Orangecat_PurchaseOrder
```

All other B2B modules depend on this one. It must be installed and enabled first.

---

## Theme Compatibility

| Theme | Status | Notes |
|---|---|---|
| **Luma** | Supported | "Create a Company" link placed in `header.links`. Standard `.phtml` templates. |
| **Breeze Evolution** | Supported | Custom template `breeze/company-register-link.phtml`. Link placed in `top.links`. |
| **Hyvä** | Supported | Alpine.js-compatible templates. Link placed in `header.customer.logged.out.links`. Custom CSS loaded from `web/css/hyva/module.css`. |

Each theme has its own layout handles and templates. No cross-theme interference.

---

## Requirements

- Magento 2.4.x
- PHP > 8.1
- `orangecat/core` (composer dependency)
- Modules: `Magento_Customer`, `Magento_Eav`, `Magento_Backend`, `Magento_Ui`, `Magento_Theme`

---

## Installation

### Via Git Submodule (recommended for this project)

```bash
# From repo root
git submodule add git@github.com:olivertar/m2_company.git app/code/Orangecat/Company
git submodule update --init --recursive
```

### Enable the Module

Run inside the PHP container (`reward shell`):

```bash
bin/magento module:enable Orangecat_Company
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

---

## What Gets Installed

### Database Tables

| Table | Description |
|---|---|
| `mycompany` | Company flat table — name, legal name, email, tax ID, address, status, website |
| `mycompany_role` | Company roles with JSON permissions |
| `mycompany_customer` | Link table joining companies, customers, and roles. Also stores per-user purchase limits. |

**`mycompany` columns:** `entity_id`, `name`, `name_legal`, `email`, `tax_id`, `address`, `city`, `country`, `region`, `postalcode`, `telephone`, `status`, `website_id`, `created_at`, `updated_at`

**`mycompany_customer` columns:** `link_id`, `company_id`, `customer_id`, `role_id`, `max_purchase_amount`, `max_period_amount`

> A customer can belong to only one company (unique constraint on `customer_id`).

### Customer EAV Attribute

| Attribute | Type | Purpose |
|---|---|---|
| `approve_account` | `int` (boolean) | Controls whether a customer can log in. `1` = approved, `0` = pending/blocked. Visible in Admin > Customer grid and form. |

### Default Roles

Installed automatically via data patch:

| Role ID | Name | Permissions |
|---|---|---|
| 1 | Company Admin | `all` |
| 2 | Company Manager | `manage_users`, `view_orders` |
| 3 | Company Buyer | `place_order` |

### CMS Page

A placeholder CMS page is created at URL key `company-terms` (Company Terms and Conditions). **Edit its content** via Admin > Content > Pages before going live.

---

## Configuration

**Path:** `Stores > Configuration > Orangecat > Company (B2B)`

### Email Notification

| Field | Description | Default |
|---|---|---|
| Enable New Company Notification | Send email to admin(s) when a new company is registered | No |
| Notification Email Address | Comma-separated list of recipient email addresses | — |
| Email Template | Template for the admin notification | `New Company Registration Notification` |
| Company - New Account No Password Template | Email sent to the company admin when their account is created (no password set) | `New Company Customer - No Password` |
| Company - Status Change Template | Email sent to the company email when its status changes | `Company Status Change Notification` |

Config paths:

```
mycompany/email/enable_email_notification
mycompany/email/notification_email
mycompany/email/email_template
mycompany/email/company_no_password_template
mycompany/email/company_status_change_template
```

### Customers

| Field | Description | Default |
|---|---|---|
| Allow Frontend Customer Registration | If No, hides "Create an Account" and blocks `/customer/account/create` (returns 404) | No |
| Require Approval for Customer | If Yes, newly registered customers start with `approve_account = 0` and cannot log in until manually approved. Only available when registration is allowed. | Yes |

Config paths:

```
mycompany/customers/allow_frontend_customer_registration
mycompany/customers/require_approval_for_customer
```

---

## Store Admin Guide

### Managing Companies

**Path:** Admin menu > **Companies > Companies**

The company grid supports:
- Inline editing of company name, email, and status
- Full create/edit form with all company fields and customer assignment

#### Company Statuses

| Status | Value | Meaning |
|---|---|---|
| Pending | 0 | Submitted via frontend, awaiting review |
| Approved | 1 | Active — associated customers can log in |
| Suspended | 2 | Temporarily blocked |
| Rejected | 3 | Registration denied |

> Changing status to Approved/Suspended/Rejected triggers the **Status Change** email to the company's email address (if the template is configured).

#### Company Form Fields

| Field | Required | Notes |
|---|---|---|
| Company Name | Yes | Display name |
| Legal Name | No | Legal/registered name |
| Company Email | Yes | Must be unique. Must differ from the admin's email. |
| Tax / VAT ID | No | Must be unique if provided |
| Address, City, Region, Postal Code, Country | No | |
| Telephone | No | |
| Status | Yes | See statuses above |

#### Assigning Customers to a Company

From the Company edit form, use the **Customer Assignment** section to select a customer as the Company Admin via a grid chooser. The customer is linked with Role ID 1 (Company Admin).

Alternatively, open any **Customer** in Admin > Customers > All Customers and use the **Company Assignment** tab:

- Select a company using the radio-button grid
- Assign a role from the dropdown
- Click **Save Customer**

**Business rules enforced on save:**

1. A company can have only **one** Company Admin (Role ID 1). Attempting to assign a second admin is blocked.
2. A company **must always** have one Admin. Changing the only admin's role or removing them is blocked. Assign a replacement admin first.

---

### Approving Customers

When `Require Approval for Customer` is enabled, new registrations set `approve_account = 0` (and force logout to prevent auto-login).

To approve:
1. Admin > Customers > All Customers
2. Open the customer
3. Set **Approve Account** to **Yes**
4. Save

Unapproved customers attempting to log in see:
> *"Your account is not enabled or your company has not yet been enabled."*

---

## Frontend User Guide

### Company Registration

Guests can register a new company at: `/company/account/create`

The "Create a Company" link appears in the header for guests (not visible when logged in).

**Registration form fields:**

| Section | Fields |
|---|---|
| Company Information | Company Name, Legal Name, Company Email, Tax ID, Address, City, Region, Postal Code, Country, Telephone |
| Company Administrator | First Name, Last Name, Email |

**Validations applied:**
- Company email and admin email cannot be the same
- Admin email must not already exist as a customer
- Company email must be unique (no existing company with same email)
- Tax ID must be unique (if provided)

After submission, the company is created with **Pending** status. The admin's customer account is created without a password. A confirmation page is shown and an email is sent to the admin(s) if notification is enabled.

---

### Company User Management (Company Admin only)

Accessible via Account Dashboard > **Company Users** — visible only to users with the Company Admin role.

**Available actions:**

- **List users** — shows all members of the company with name, email, role, and status
- **Add user** — create a new customer account and link it to the company
- **Edit user** — change name, email, or role
- **Toggle status** — enable or disable a user's `approve_account` flag
- **Delete user** — permanently removes the customer account

Non-admin company members are redirected to the account dashboard if they try to access this section.

---

## Developer Guide

### Module Structure

```
Orangecat/Company/
├── Api/                          Service contracts (interfaces)
│   ├── CompanyRepositoryInterface.php
│   ├── CompanyManagementInterface.php
│   ├── RoleRepositoryInterface.php
│   └── Data/
│       ├── CompanyInterface.php
│       ├── CompanyCustomerInterface.php
│       ├── RoleInterface.php
│       └── CompanySearchResultsInterface.php
├── Block/
│   ├── Account/                  Frontend: registration link + create form
│   └── Adminhtml/               Admin: company + customer tab blocks
│       ├── Company/Edit/
│       └── Edit/Tab/
├── Controller/
│   ├── Account/                  company/account/* routes
│   ├── Users/                    company/users/* routes
│   └── Adminhtml/Company/        mycompany/* admin routes
├── Model/                        Entities, repositories, config
├── Observer/                     Auto-approve + admin save
├── Plugin/                       Login, registration, Hyvä integration
├── Setup/Patch/Data/             EAV attribute + default roles + T&C page
├── Ui/Component/                 UI form data providers and modifiers
└── view/
    ├── adminhtml/                Admin UI components and layouts
    └── frontend/
        ├── layout/               Luma, Breeze (breeze_*), Hyvä (hyva_*) handles
        ├── templates/            Luma + Breeze + Hyvä (hyva/) templates
        ├── email/                Transactional email templates
        └── web/css/hyva/         Hyvä-specific CSS
```

### Service Contracts

#### `CompanyRepositoryInterface`

```php
save(CompanyInterface $company): CompanyInterface
get(int $companyId): CompanyInterface
getList(SearchCriteriaInterface $searchCriteria): CompanySearchResultsInterface
delete(CompanyInterface $company): bool
deleteById(int $companyId): bool
```

#### `CompanyManagementInterface`

```php
assignCustomer(int $companyId, int $customerId, int $roleId, array $data = []): bool
removeCustomer(int $customerId): bool
getCompanyIdByCustomerId(int $customerId): ?int
getRoleIdByCustomerId(int $customerId): ?int
isCompanyAdmin(int $customerId): bool
validateManageUser(int $adminId, ?int $targetUserId): void  // throws LocalizedException
```

#### `RoleRepositoryInterface`

Standard CRUD + `getList` for `RoleInterface`.

---

### Key Models

#### `Orangecat\Company\Model\Company`

Extends `AbstractModel`. Constants:

```php
Company::STATUS_PENDING    = 0
Company::STATUS_APPROVED   = 1
Company::STATUS_SUSPENDED  = 2
Company::STATUS_REJECTED   = 3
```

#### `Orangecat\Company\Model\Config`

Helper for reading all module config values. Inject and use:

```php
$config->isFrontendCustomerRegistrationAllowed(): bool
$config->isCustomerApprovalRequired(): bool
$config->isNotificationEnabled(): bool
$config->getNotificationEmail(): string
$config->getEmailTemplate(): string
$config->getCompanyNoPasswordEmailTemplate(): string
$config->getCompanyStatusChangeEmailTemplate(): string
$config->isCompanyUser(int $customerId): bool
$config->isCompanyRegistrationContext(): bool
```

---

### Plugins

| Plugin | Target | Type | Purpose |
|---|---|---|---|
| `RegistrationRestriction` | `Customer\Controller\Account\Create` & `CreatePost` | `before` | Throws 404 if registration is disabled |
| `RegisterLink` | `Customer\Block\Account\RegisterLink` | `after` | Hides "Create an Account" if registration is disabled |
| `CompanyRegisterLinkVisibility` | `Block\Account\CompanyRegisterLink` | `after` | Controls "Create a Company" link visibility for guests |
| `CustomerRegistration` (Hyvä) | `Hyva\Theme\ViewModel\CustomerRegistration` | `after` | Hides registration via Hyvä's ViewModel when disabled |
| `LoginPost` | `Customer\Controller\Account\LoginPost` | `around` | Blocks login for unapproved customers |
| `CustomerRepositoryInterfacePlugin` | `CustomerRepositoryInterface` | `around` | Prevents deletion of a company admin |
| `CustomerRepositoryInterfaceSavePlugin` | `CustomerRepositoryInterface` | `after` | Persists company assignment data on customer save |
| `TransportBuilderPlugin` | `Mail\Template\TransportBuilder` | `around` | Swaps email template for company users (no-password flow) |

---

### Observers

| Observer | Event | Area | Purpose |
|---|---|---|---|
| `AutoApproveCustomer` | `customer_register_success` | `frontend` | Sets `approve_account` = 1 or 0 based on config. Forces logout if not approved. |
| `SaveCompanyObserver` | `adminhtml_customer_save_after` | `adminhtml` | Reads `customer[company_id]` and `customer[role_id]` from POST and saves the company link. Enforces single-admin and mandatory-admin rules. |

---

### Adding Hyvä Layout Support

Hyvä-specific layout handles use the `hyva_` prefix. This module provides:

```
hyva_default.xml                   — "Create a Company" header link + CSS
hyva_customer_account_login.xml    — Login page adjustments
hyva_company_users_index.xml       — Users list page
hyva_company_users_create.xml      — Create user page
hyva_company_users_edit.xml        — Edit user page
```

Templates for Hyvä are under `view/frontend/templates/hyva/` and `view/frontend/templates/users/edit_hyva.phtml` / `list_hyva.phtml`.

> Do not put Hyvä templates in `view/hyva/` — this project uses `view/frontend/templates/hyva/` as the convention.

---

### Email Templates

| Template ID | File | Trigger |
|---|---|---|
| `mycompany_email_email_template` | `new_company.html` | New company registered (sent to store admin) |
| `mycompany_email_company_no_password_template` | `company_account_new_no_password.html` | Company admin account created without password |
| `company_user_welcome` | `company_user_welcome.html` | New company user added |
| `mycompany_email_company_status_change_template` | `company_status_change.html` | Company status changed |

Templates are in `view/frontend/email/`. Customize via Admin > Marketing > Email Templates.

---

### ACL Resources

| Resource ID | Title | Location |
|---|---|---|
| `Orangecat_Company::company_manage` | Company Management | Admin top-level |
| `Orangecat_Company::company` | Companies | Under Company Management |
| `Orangecat_Company::config` | Company Settings | Under Stores > Settings |

---

## REST API

All endpoints require admin authorization (`Orangecat_Company::company` resource).

### Endpoints

| Method | URL | Action |
|---|---|---|
| `POST` | `/V1/mycompany/` | Create company |
| `PUT` | `/V1/mycompany/:entity_id` | Update company |
| `GET` | `/V1/mycompany/:companyId` | Get company by ID |
| `DELETE` | `/V1/mycompany/:companyId` | Delete company |
| `GET` | `/V1/mycompany/` | Search companies (with `searchCriteria`) |

### Example: Create Company

```bash
curl -X POST https://your-store.test/rest/V1/mycompany/ \
  -H "Authorization: Bearer <admin_token>" \
  -H "Content-Type: application/json" \
  -d '{
    "company": {
      "name": "Acme Corp",
      "email": "billing@acme.com",
      "tax_id": "US12345678",
      "status": 1
    }
  }'
```

### Example: Search with Filter

```bash
GET /V1/mycompany/?searchCriteria[filter_groups][0][filters][0][field]=status
    &searchCriteria[filter_groups][0][filters][0][value]=1
    &searchCriteria[filter_groups][0][filters][0][condition_type]=eq
```

---

## Frontend Routes Reference

| Route | Controller | Access |
|---|---|---|
| `GET /company/account/create` | `Controller\Account\Create` | Guests only |
| `POST /company/account/createPost` | `Controller\Account\CreatePost` | Guests only |
| `GET /company/account/success` | `Controller\Account\Success` | Public |
| `GET /company/users` | `Controller\Users\Index` | Company Admin only |
| `GET /company/users/create` | `Controller\Users\Create` | Company Admin only |
| `POST /company/users/save` | `Controller\Users\Save` | Company Admin only |
| `GET /company/users/edit` | `Controller\Users\Edit` | Company Admin only |
| `POST /company/users/delete` | `Controller\Users\Delete` | Company Admin only |
| `POST /company/users/status` | `Controller\Users\Status` | Company Admin only |

---

## DevOps & Integrator Notes

### Deployment Checklist

```bash
# After deploying or updating this module:
bin/magento module:enable Orangecat_Company
bin/magento setup:upgrade          # runs db_schema.xml migrations + data patches (EAV, roles, CMS page)
bin/magento setup:di:compile       # regenerates interceptors and proxies
bin/magento setup:static-content:deploy -f
bin/magento cache:flush
```

### Integration Token Scope

Minimum permissions for an ERP or external integration reading company data:
- `Orangecat_Company::company`
- `Orangecat_Company::company_manage` (parent required)

### Disabling Without Uninstalling

**Warning:** all dependent modules (`Orangecat_CompanyCredit`, `Orangecat_CompanyMethods`, `Orangecat_Prices`, etc.) must be disabled first.

```bash
bin/magento module:disable Orangecat_Company
bin/magento setup:upgrade
bin/magento cache:flush
```

### Data Integrity

- `mycompany_customer` enforces a unique constraint on `customer_id` — one company per customer.
- Deleting a company does not automatically delete linked customers; only the `mycompany_customer` link row is removed.
- The `approve_account` EAV attribute persists after module disable. Re-enabling the module restores the approval gate.
- The `company-terms` CMS page is created by data patch and is not removed on module disable or uninstall.
