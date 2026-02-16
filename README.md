
# Orangecat_Company Module Documentation

This module manages Company entities, Customer assignment, and implements strict Frontend Registration and Login controls.

## 1. Admin Configuration

Located at: `Stores > Configuration > My Company > General`

### Email Settings

- **Enable Email Notification**: Toggles email sending functionality.
- **Notification Email**: Recipient email address(es).
- **Email Template**: Template used for notifications (ID: `mycompany_email_email_template`).
- **Company - New Account No Password Template**: Selects the template sent to company users created without a password.
  - Default: "New Company Customer - No Password" (`mycompany_customer_create_account_email_no_password_company_template`).
  - Logic: Automatically swaps the standard "No Password" template if the recipient is identified as a Company User (via `mycompany_customer` table).

### Customer Settings

- **Allow Frontend Customer Registration**: (Yes/No)
  - If **No**: Hides "Create an Account" link and blocks access to `/customer/account/create/` (returns 404).
- **Require Approval for Customer**: (Yes/No)
  - If **Yes**: New customers are created with `approve_account` = 0 (Unapproved).
  - Unapproved customers cannot log in.

## 2. Admin Functionality

### Company Management

- **Path**: `Companies > Add New Company`
- **Form**: `mycompany_company_form.xml`
- **Customer Assignment**:
  - Uses a Modal Chooser pattern (similar to `CustomerPrice` module).
  - Select a customer from grid to assign as Company Admin.
  - Features custom `Actions` column in the modal grid.

### Customer Management

- **Attribute**: `approve_account` (Boolean).
- **Visibility**: Added to `adminhtml_customer` form.
- **Purpose**: Controls whether a customer can log in to the frontend.

## 3. Frontend Functionality & Restrictions

### Key Features

1. **Registration Restriction**:
    - **Plugin**: `Orangecat\Company\Plugin\Customer\Controller\Account\RegistrationRestriction`
    - Intercepts `Create` and `CreatePost` actions.
    - Result: If registration is disabled in config, throws `NotFoundException` (404 Page).

2. **Login Restriction**:
    - **Plugin**: `Orangecat\Company\Plugin\Customer\Controller\Account\LoginPost`
    - **Logic**: Blocks login if:
        - Attribute `approve_account` is explicitly `0` (False).
        - Attribute `approve_account` is **missing/null** AND "Require Approval" is enabled (Config).
    - **Result**: Redirects to login page with error: "Your account is not enabled. Please contact the store administrator."

3. **Auto-Approval Workflow**:
    - **Observer**: `Orangecat\Company\Observer\AutoApproveCustomer` (Event: `customer_register_success`).
    - **Logic**:
        - If Approval Required (Config: Yes): Sets `approve_account` = 0. **Forces immediate logout** to prevent auto-login after register.
        - If Approval NOT Required (Config: No): Sets `approve_account` = 1 (Auto-approve).

4. **"Create a Company" Link**:
    - **Block**: `Orangecat\Company\Block\Account\CompanyRegisterLink`.
    - **Location**: Header Links.
    - **Compatibility**: Specifically adjusted for **Luma Theme** (moved to `header.links` via `default.xml`).
    - **Visibility**: Only visible to guests (not logged in).

5. **"Create an Account" Link**:
    - **Plugin**: `Orangecat\Company\Plugin\Customer\Block\Account\RegisterLink`.
    - **Logic**: Hides the default registration link if "Allow Frontend Customer Registration" is set to No.

## 4. Technical Implementation Details

### Configuration Paths

Defined in `Orangecat\Company\Model\Config`:

- `mycompany/email/enable_email_notification`
- `mycompany/email/notification_email`
- `mycompany/email/email_template`
- `mycompany/customers/allow_frontend_customer_registration`
- `mycompany/customers/require_approval_for_customer`
- `mycompany/email/company_no_password_template`

### Dependency Injection (di.xml)

- Registers Plugins for:
  - `Magento\Customer\Controller\Account\LoginPost`
  - `Magento\Customer\Block\Account\RegisterLink`
  - `Magento\Customer\Controller\Account\Create`
  - `Magento\Customer\Controller\Account\CreatePost`
- Registers Modifiers for Company Form UI Component.

### ACL Resources

- `Orangecat_Company::config` nested under `Magento_Backend::stores_settings`.

## 5. Recent Fixes (Session History)

- **Luma Theme Compatibility**: Detected Luma theme moves `top.links` to `customer` block. Moved `company-register-link` to `header.links` to fix visibility.
- **Null Attribute Handling**: Updated LoginPost plugin to correctly handle legacy customers (null attribute) vs new unapproved customers (null attribute + require approval).
- **Auto-Logout**: Added logic to `AutoApproveCustomer` observer to prevent Magento's default auto-login behavior for unapproved registrations.

## 6. Admin Customer Assignment (New Feature)

### Overview

A new tab **"Company Assignment"** is added to the Customer Edit form. This allows administrators to manage the company and role relationship directly from the customer view.

### UI Components

- **Tab**: An Ajax-loaded tab (`Orangecat\Company\Block\Adminhtml\Edit\Tab\Company`) integrated via `customer_form.xml`.
- **Role Selector**: A dropdown select (`Orangecat\Company\Block\Adminhtml\Edit\Tab\Role`) placed at the top of the tab content.
- **Company Grid**: A legacy-style grid (`Orangecat\Company\Block\Adminhtml\Edit\Tab\CompanyGrid`) listing all active companies.
  - Features a **Radio Button** column (`customer[company_id]`) to select a single company.
  - Includes an **"Unassign from Company"** button to clear the selection.

### Logic & Architecture

- **Ajax Implementation**: The Tab content is loaded via an Ajax call to `Orangecat\Company\Controller\Adminhtml\Customer\CompanyGrid`.
  - The Controller programmatically instantiates the `Role` and `Grid` blocks and returns their concatenated HTML, ensuring a unified interface.
- **Visibility Behavior (UX)**:
  - **Initial Load**: When the tab is first opened, the grid is **filtered** to show only the currently assigned company (or an empty grid if none is assigned). This keeps the interface clean.
  - **Search / Reset**: Clicking "Reset Filter" or performing a search will clear this default filter and display **All Companies**, allowing you to find and select a new one.
- **Persistence**:
  - Selection is saved via the `SaveCompanyObserver` listening to `adminhtml_customer_save_after`.
  - Reads `customer[company_id]` and `customer[role_id]` from the POST data.

### Business Rules (Validation)

Strict validation is enforced during save:

1. **Single Admin Policy**: A Company can have only **one** Administrator.
    - If you try to assign the "Company Admin" role to a customer for a company that already has an admin, the save is blocked with an error.
2. **Mandatory Admin Policy**: A Company **must** have an Administrator.
    - If you act on the *only* administrator of a company (e.g., try to unassign them or change their role to User), the save is blocked.
    - You must assign a new administrator to the company *before* demoting or removing the old one.
