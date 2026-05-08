# Orangecat_Company — Configuration Guide

<!-- refreshed: 2026-05-07 -->

## Admin Configuration

Navigate to: **Stores → Configuration → Company (B2B)**

---

## Email Notification Settings

**Section:** `mycompany/email`

| Field | Path | Type | Default | Description |
|-------|------|------|---------|-------------|
| Enable New Company Notification | `mycompany/email/enable_email_notification` | Yes/No | `0` (No) | Master switch for admin notification emails when a company registers. |
| Notification Email Address | `mycompany/email/notification_email` | Text | *(empty)* | Comma-separated list of admin email addresses to notify. |
| Email Template | `mycompany/email/email_template` | Select | `mycompany_email_email_template` | Template used for new company registration notifications. |
| Company - New Account No Password Template | `mycompany/email/company_no_password_template` | Select | `mycompany_email_company_no_password_template` | Template sent to company users created without a password. Auto-swapped by `TransportBuilderPlugin` when the recipient is a company user. |

### Email Flow

```
Frontend Company Registration
    → CreatePost controller
        → If notifications enabled AND notification_email is set
            → Sends "New Company Registration" email to admin(s)
            → Template: mycompany_email_email_template

Admin changes company status
    → CompanyRepository::save()
        → If status changed AND notifications enabled
            → Sends "Status Change" email to company email
            → Template: company_status_change

Company Admin creates user (no password)
    → Magento sends default "No Password" email
        → TransportBuilderPlugin detects company user
            → Swaps template to company_no_password_template
```

---

## Customer Settings

**Section:** `mycompany/customers`

| Field | Path | Type | Default | Description |
|-------|------|------|---------|-------------|
| Allow Frontend Customer Registration | `mycompany/customers/allow_frontend_customer_registration` | Yes/No | `0` (No) | If **No**, hides the "Create an Account" link and blocks `/customer/account/create/` (returns 404). |
| Require Approval for Customer | `mycompany/customers/require_approval_for_customer` | Yes/No | `1` (Yes) | If **Yes**, new customers are created with `approve_account = 0` (unapproved) and cannot log in until approved. **Depends on** "Allow Frontend Customer Registration" = Yes. |

### Customer Approval Logic

```
Customer registers on frontend
    → Event: customer_register_success
        → Observer: AutoApproveCustomer
            IF allow_frontend_customer_registration = NO
                → Observer exits early (no action)
            ELSE IF require_approval_for_customer = YES
                → Sets approve_account = 0
                → Forces logout (prevents auto-login)
            ELSE
                → Sets approve_account = 1 (auto-approved)

Customer attempts login
    → Plugin: LoginPost::aroundExecute()
        → Reads customer.approve_account attribute
            IF attribute exists AND value = 0
                → BLOCK login
            ELSE IF attribute is NULL AND require_approval = YES
                → BLOCK login
            ELSE
                → Allow normal login
```

---

## Setup Patches (Data Installation)

### `InstallDefaultRoles`

Installs three default roles into `mycompany_role`:

| Role Name | Permissions |
|-----------|-------------|
| Company Admin | `["all"]` |
| Company Manager | `["manage_users", "view_orders"]` |
| Company Buyer | `["place_order"]` |

### `AddApproveAccountAttribute`

Adds the `approve_account` EAV attribute to the Customer entity:

| Property | Value |
|----------|-------|
| Type | `int` (Boolean) |
| Input | `select` |
| Source | `Magento\Eav\Model\Entity\Attribute\Source\Boolean` |
| Default | `false` (0) |
| Used in forms | `adminhtml_customer`, `customer_account_create`, `customer_account_edit` |
| Grid visibility | Yes (filterable, visible) |

### `CreateTermsAndConditionsPage`

Creates a CMS page for company registration terms and conditions.

---

## Config.xml Defaults

```xml
<default>
    <mycompany>
        <email>
            <enable_email_notification>0</enable_email_notification>
            <email_template>mycompany_email_email_template</email_template>
            <company_no_password_template>mycompany_email_company_no_password_template</company_no_password_template>
        </email>
        <customers>
            <allow_frontend_customer_registration>0</allow_frontend_customer_registration>
            <require_approval_for_customer>1</require_approval_for_customer>
        </customers>
    </mycompany>
</default>
```

---

## System Config XML Structure

```xml
<section id="mycompany" translate="label" sortOrder="9000" showInDefault="1" showInWebsite="1" showInStore="1">
    <tab>orangecat</tab>
    <resource>Orangecat_Company::config</resource>
    <group id="email" translate="label" type="text" sortOrder="20">
        <field id="enable_email_notification" .../>
        <field id="notification_email" .../>
        <field id="email_template" .../>
        <field id="company_no_password_template" .../>
    </group>
    <group id="customers" translate="label" type="text" sortOrder="30">
        <field id="allow_frontend_customer_registration" .../>
        <field id="require_approval_for_customer" ...>
            <depends>
                <field id="allow_frontend_customer_registration">1</field>
            </depends>
        </field>
    </group>
</section>
```

---

## Troubleshooting

### Customers cannot log in after registration

- Check `require_approval_for_customer` setting.
- Check `approve_account` attribute value in customer grid.
- If approval is required, ensure an admin has approved the company (status = 1).

### Company registration emails not sent

- Verify `enable_email_notification` = Yes.
- Verify `notification_email` is filled with valid address(es).
- Check Magento email configuration (SMTP, cron, etc.).

### "Create an Account" link still visible

- Verify `allow_frontend_customer_registration` = No.
- Clear cache (`bin/magento cache:clean`).
- Check if theme overrides `header.links` block.

### Email template not swapped for company users

- Verify `company_no_password_template` is set.
- Check that `TransportBuilderPlugin` is registered in `etc/di.xml`.
- Ensure customer exists in `mycompany_customer` table.
