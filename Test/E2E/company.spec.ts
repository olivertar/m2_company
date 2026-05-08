import { test, expect, Page } from '@playwright/test';
import { ENV } from '../lib/env';

/**
 * Helper: Login as a frontend customer
 */
async function customerLogin(page: Page, email: string, password: string) {
  await page.goto('/customer/account/login/');
  await page.waitForLoadState('networkidle');
  await page.fill('#email', email);
  await page.fill('#password', password);
  // Target the actual Sign In button (not the one inside popup/modal)
  const submitButton = page.locator('button.login.primary');
  await expect(submitButton).toBeVisible({ timeout: 10000 });
  await expect(submitButton).toBeEnabled({ timeout: 10000 });
  await submitButton.click();
  // Wait for navigation to account dashboard or similar
  await page.waitForURL(/customer\/account/, { waitUntil: 'domcontentloaded' });
}

/**
 * Helper: Login as Magento admin
 */
async function adminLogin(page: Page) {
  await page.goto(ENV.ADMIN_URL);
  await page.fill('#username', ENV.ADMIN_USER);
  await page.fill('#login', ENV.ADMIN_PASS);
  await page.click('.action-login');
  // Wait for admin dashboard
  await expect(page.locator('.admin__menu')).toBeVisible({ timeout: 10000 });
}

test.describe('Company Frontend', () => {
  test('Company registration form loads and submits successfully', async ({ page }) => {
    test.setTimeout(60000);
    const timestamp = Date.now();
    const companyEmail = `testcompany-${timestamp}@example.com`;
    const adminEmail = `testadmin-${timestamp}@example.com`;

    await page.goto('/company/account/create/');
    await page.waitForLoadState('networkidle');
    await expect(page.locator('h1, .page-title')).toContainText('Company');

    // Fill company information
    await page.fill('#company_name', `Test Company ${timestamp}`);
    await page.fill('#name_legal', `Test Legal Name ${timestamp}`);
    await page.fill('#company_email', companyEmail);
    await page.fill('#tax_id', `TAX-${timestamp}`);

    // Fill address
    await page.fill('#street', '123 Test St');
    await page.fill('#city', 'Test City');
    await page.selectOption('#country', 'US');
    await page.fill('#region', 'California');
    await page.fill('#postcode', '12345');
    await page.fill('#telephone', '555-1234');

    // Fill administrator
    await page.fill('#firstname', 'Test');
    await page.fill('#lastname', 'Admin');
    await page.fill('#admin_email', adminEmail);

    // Accept terms
    await page.check('#agreement');

    // Submit and wait for redirect
    await Promise.all([
      page.waitForURL('/company/account/success/', { waitUntil: 'domcontentloaded' }),
      page.click('button[title="Submit Request"]')
    ]);
    await expect(page.locator('.company-registration-success')).toBeVisible();
    await expect(page.locator('body')).toContainText('Thank you for your company registration request');
  });

  test('Company admin can login and access user management', async ({ page }) => {
    await customerLogin(page, ENV.COMPANY_ADMIN, ENV.COMPANY_PASS);

    // Navigate to company users
    await page.goto('/company/users/index');
    await expect(page.locator('h1, .page-title')).toContainText('Company Users');
    await expect(page.locator('.company-users-container')).toBeVisible();
  });

  test('Company manager is redirected away from user management', async ({ page }) => {
    await customerLogin(page, ENV.COMPANY_MANAGER, ENV.COMPANY_PASS);

    // Attempt to access user management
    await page.goto('/company/users/index');

    // Should be redirected to customer account
    await page.waitForURL(/customer\/account/, { waitUntil: 'domcontentloaded' });
    await expect(page.url()).not.toContain('company/users');
  });

  test('Company admin can create, edit and delete a user', async ({ page }) => {
    test.setTimeout(90000);
    const timestamp = Date.now();
    const userEmail = `companyuser-${timestamp}@example.com`;

    await customerLogin(page, ENV.COMPANY_ADMIN, ENV.COMPANY_PASS);
    await page.goto('/company/users/index');
    await page.waitForLoadState('networkidle');

    // Click add new user
    await page.click('a:has-text("Add New User")');
    await page.waitForURL('/company/users/create/', { waitUntil: 'domcontentloaded' });
    await page.waitForLoadState('networkidle');

    // Fill form
    await page.fill('#firstname', 'New');
    await page.fill('#lastname', 'User');
    await page.fill('#email', userEmail);
    await page.selectOption('#role_id', '2'); // Manager role
    await page.selectOption('#status', '1'); // Enabled

    // Submit
    await page.click('button[title="Save User"]');

    // Wait for the user to appear in the list
    const emailCell = page.locator('table.table-company-users td', { hasText: userEmail });
    await expect(emailCell).toBeVisible({ timeout: 15000 });

    // Click edit in the same row
    const editLink = emailCell.locator('xpath=ancestor::tr').locator('a:has-text("Edit")');
    await editLink.click();
    await expect(page.locator('#lastname')).toBeVisible({ timeout: 10000 });

    // Edit last name
    await page.fill('#lastname', 'Updated');
    await page.click('button[title="Save User"]');

    // Verify updated name appears in list
    await expect(page.locator('table.table-company-users')).toContainText('Updated', { timeout: 15000 });

    // Delete user
    const updatedCell = page.locator('table.table-company-users td', { hasText: 'Updated' });
    const deleteLink = updatedCell.locator('xpath=ancestor::tr').locator('a:has-text("Delete")');

    // Accept the confirmation dialog
    page.once('dialog', dialog => dialog.accept());
    await deleteLink.click();

    // Wait for the user to disappear from the list
    await expect(page.locator('table.table-company-users')).not.toContainText(userEmail);
  });
});

test.describe('Magento Admin', () => {
  test('Admin can access Companies grid', async ({ page }) => {
    await adminLogin(page);

    // Navigate via admin menu (extract href to avoid key/visibility issues)
    await page.hover('a:has-text("Orangecat")');
    const companiesHref = await page.locator('a:has-text("Manage Companies")').getAttribute('href');
    if (companiesHref) {
      await page.goto(companiesHref);
    }

    // Wait for grid to load
    await expect(page.locator('button:has-text("Add New Company")')).toBeVisible({ timeout: 15000 });
    await expect(page.locator('body')).toContainText('Companies');
  });

  test('Admin can create a new company', async ({ page }) => {
    test.setTimeout(60000);
    const timestamp = Date.now();
    const companyName = `Admin Company ${timestamp}`;
    const companyEmail = `adminco-${timestamp}@example.com`;
    const adminEmail = `adminuser-${timestamp}@example.com`;

    await adminLogin(page);

    // Navigate via admin menu
    await page.hover('a:has-text("Orangecat")');
    const companiesHref = await page.locator('a:has-text("Manage Companies")').getAttribute('href');
    if (companiesHref) {
      await page.goto(companiesHref);
    }
    await expect(page.locator('button:has-text("Add New Company")')).toBeVisible({ timeout: 15000 });

    // Click Add New Company
    await page.click('button:has-text("Add New Company")');

    // Wait for form
    await expect(page.locator('input[name="general[name]"]')).toBeVisible({ timeout: 10000 });

    // Fill general info
    await page.fill('input[name="general[name]"]', companyName);
    await page.fill('input[name="general[email]"]', companyEmail);
    await page.fill('input[name="general[tax_id]"]', `TAX-${timestamp}`);
    await page.fill('input[name="general[name_legal]"]', `Legal ${timestamp}`);
    await page.selectOption('select[name="general[status]"]', '1'); // Active

    // Open Company Administrator tab and fill admin info
    await page.click('a:has-text("Company Administrator")');
    await page.fill('input[name="company_admin[new_admin_fieldset][admin_firstname]"]', 'Admin');
    await page.fill('input[name="company_admin[new_admin_fieldset][admin_lastname]"]', 'User');
    await page.fill('input[name="company_admin[new_admin_fieldset][admin_email]"]', adminEmail);
    const websiteSelect = page.locator('select[name="company_admin[new_admin_fieldset][admin_website_id]"]');
    if (await websiteSelect.isVisible().catch(() => false)) {
      await websiteSelect.selectOption('1');
    }

    // Save
    await page.click('#save');

    // Wait for success message and grid reload
    await expect(page.locator('.admin__messages .message-success')).toContainText('Company has been saved.', { timeout: 15000 });
    await expect(page.locator('button:has-text("Add New Company")')).toBeVisible({ timeout: 15000 });

    // Refresh grid to ensure new company appears
    await page.reload();
    await page.waitForLoadState('networkidle');
    await expect(page.locator('button:has-text("Add New Company")')).toBeVisible({ timeout: 15000 });
    await expect(page.locator('body')).toContainText(companyName);
  });
});
