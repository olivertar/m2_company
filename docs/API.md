# Orangecat_Company — API Reference

<!-- refreshed: 2026-05-07 -->

## REST API Endpoints

All endpoints require authentication with a user that has the `Orangecat_Company::company` ACL resource.

### Company CRUD

| Method | Endpoint | Service Method | Description |
|--------|----------|---------------|-------------|
| `POST` | `/V1/mycompany/` | `save` | Create a new company |
| `PUT` | `/V1/mycompany/:entity_id` | `save` | Update an existing company |
| `GET` | `/V1/mycompany/:companyId` | `get` | Retrieve company by ID |
| `DELETE` | `/V1/mycompany/:companyId` | `deleteById` | Delete company by ID |
| `GET` | `/V1/mycompany/` | `getList` | Search/list companies (SearchCriteria) |

### Request/Response Examples

#### Create Company

```http
POST /rest/V1/mycompany/
Content-Type: application/json

{
  "company": {
    "name": "Acme Corp",
    "email": "acme@example.com",
    "tax_id": "B12345678",
    "address": "123 Main St",
    "city": "Madrid",
    "country": "ES",
    "region": "Madrid",
    "postalcode": "28001",
    "telephone": "+34 123 456 789",
    "status": 0,
    "website_id": 1
  }
}
```

**Response:** `200 OK` — Returns the saved company with `entity_id` populated.

#### Update Company Status

```http
PUT /rest/V1/mycompany/42
Content-Type: application/json

{
  "company": {
    "entity_id": 42,
    "status": 1
  }
}
```

**Side effects:**
- If status changes, syncs the admin customer's `approve_account` attribute.
- If status changes and email notifications are enabled, sends a status change email.

#### Search Companies

```http
GET /rest/V1/mycompany/?searchCriteria[filterGroups][0][filters][0][field]=status&searchCriteria[filterGroups][0][filters][0][value]=1&searchCriteria[filterGroups][0][filters][0][conditionType]=eq
```

---

## PHP Service Interfaces

### `Orangecat\Company\Api\CompanyRepositoryInterface`

```php
interface CompanyRepositoryInterface
{
    public function save(CompanyInterface $company): CompanyInterface;
    public function get(int $companyId): CompanyInterface;
    public function delete(CompanyInterface $company): bool;
    public function deleteById(int $companyId): bool;
    public function getList(SearchCriteriaInterface $searchCriteria): CompanySearchResultsInterface;
}
```

**Validation rules in `save()`:**
- `email` must be unique across companies.
- `email` must not match any existing customer email.
- `tax_id` must be unique across companies (if provided).

### `Orangecat\Company\Api\CompanyManagementInterface`

```php
interface CompanyManagementInterface
{
    public function assignCustomer(int $companyId, int $customerId, int $roleId, array $data = []): bool;
    public function removeCustomer(int $customerId): bool;
    public function getCompanyIdByCustomerId(int $customerId): ?int;
    public function getRoleIdByCustomerId(int $customerId): ?int;
    public function deleteCustomerById(int $customerId): void;
    public function isCompanyAdmin(int $customerId): bool;
    public function validateManageUser(int $adminId, ?int $targetUserId): void;
}
```

### `Orangecat\Company\Api\RoleRepositoryInterface`

Standard Magento repository interface for roles (save/get/delete/getList).

### `Orangecat\Company\Api\Data\CompanyInterface`

Data interface with constants and getters/setters for all company fields:

```php
interface CompanyInterface
{
    public const STATUS_PENDING   = 0;
    public const STATUS_APPROVED  = 1;
    public const STATUS_SUSPENDED = 2;
    public const STATUS_REJECTED  = 3;

    public function getId(): ?int;
    public function setId(int $id): static;
    public function getName(): ?string;
    public function setName(string $name): static;
    public function getEmail(): ?string;
    public function setEmail(string $email): static;
    public function getStatus(): ?int;
    public function setStatus(int $status): static;
    // ... (address, city, country, region, postalcode, telephone, taxId, nameLegal)
}
```

### `Orangecat\Company\Api\Data\RoleInterface`

```php
interface RoleInterface
{
    public const ADMIN_ROLE_ID = 1;

    public function getId(): ?int;
    public function setId(int $id): static;
    public function getRoleName(): ?string;
    public function setRoleName(string $roleName): static;
    public function getPermissions(): ?string;
    public function setPermissions(string $permissions): static;
}
```

### `Orangecat\Company\Api\Data\CompanyCustomerInterface`

Represents the link between a customer and a company:

```php
interface CompanyCustomerInterface
{
    public function getId(): ?int;
    public function setId(int $id): static;
    public function getCompanyId(): ?int;
    public function setCompanyId(int $companyId): static;
    public function getCustomerId(): ?int;
    public function setCustomerId(int $customerId): static;
    public function getRoleId(): ?int;
    public function setRoleId(int $roleId): static;
    public function getMaxPurchaseAmount(): ?float;
    public function setMaxPurchaseAmount(float $amount): static;
    public function getMaxPeriodAmount(): ?float;
    public function setMaxPeriodAmount(float $amount): static;
}
```

---

## Admin Controllers (Backend)

| Controller | Route | Purpose |
|-----------|-------|---------|
| `Adminhtml\Company\Index` | `mycompany/company/index` | Company grid listing |
| `Adminhtml\Company\Edit` | `mycompany/company/edit` | Company edit form |
| `Adminhtml\Company\Save` | `mycompany/company/save` | Save company |
| `Adminhtml\Company\Delete` | `mycompany/company/delete` | Delete company |
| `Adminhtml\Company\NewAction` | `mycompany/company/new` | New company |
| `Adminhtml\Company\InlineEdit` | `mycompany/company/inlineEdit` | Inline grid editing |
| `Adminhtml\Company\CustomerGrid` | `mycompany/company/customerGrid` | Customer grid for modal |
| `Adminhtml\Customer\CompanyGrid` | `mycompany/customer/companygrid` | Ajax company grid for customer tab |

---

## Frontend Controllers

| Controller | Route | Purpose |
|-----------|-------|---------|
| `Account\Create` | `company/account/create` | Company registration form |
| `Account\CreatePost` | `company/account/createPost` | Process registration |
| `Account\Success` | `company/account/success` | Registration success page |
| `Users\Index` | `company/users/index` | List company users |
| `Users\Edit` | `company/users/edit` | Edit company user |
| `Users\Create` | `company/users/create` | Create company user |
| `Users\Save` | `company/users/save` | Save company user |
| `Users\Delete` | `company/users/delete` | Delete company user |
| `Users\Status` | `company/users/status` | Toggle user status |

---

## Email Templates

| Template ID | File | Description |
|------------|------|-------------|
| `mycompany_email_email_template` | `new_company.html` | Sent to admin when a new company registers |
| `mycompany_email_company_no_password_template` | `company_account_new_no_password.html` | Sent to company users created without password |
| `company_user_welcome` | `company_user_welcome.html` | Welcome email for company users |
| `company_status_change` | `company_status_change.html` | Sent when company status changes |

---

## ACL Resources

```
Magento_Backend::admin
└── Orangecat_Company::company_manage (title: "Company Management")
    └── Orangecat_Company::company (title: "Companies")

Magento_Backend::stores
└── Magento_Backend::stores_settings
    └── Magento_Config::config
        └── Orangecat_Company::config (title: "Company Settings")
```
