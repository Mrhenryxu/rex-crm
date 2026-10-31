import { expect, test, type APIRequestContext, type Page } from '@playwright/test';

/**
 * Smoke test of the main Contacts workflow against the real API and database.
 * Every record uses a per-run surname, so runs never depend on or collide with existing data.
 */
const run = `Zz${Date.now().toString(36)}`;
const seededIds: string[] = [];

async function seedContacts(request: APIRequestContext, count: number): Promise<void> {
    for (let index = 1; index <= count; index++) {
        const response = await request.post('/api/v1/contacts', {
            headers: { Accept: 'application/json' },
            data: { first_name: `Seed${String(index).padStart(2, '0')}`, last_name: run, email: `seed${index}-${run.toLowerCase()}@e2e.example` },
        });
        expect(response.status()).toBe(201);
        seededIds.push((await response.json()).data.id);
    }
}

async function createContact(page: Page, fields: { first: string; email?: string; phone?: string }): Promise<void> {
    await page.getByRole('link', { name: 'New contact' }).first().click();
    await page.getByRole('textbox', { name: 'First name' }).fill(fields.first);
    await page.getByRole('textbox', { name: 'Last name' }).fill(run);
    if (fields.email) {
        await page.getByRole('textbox', { name: 'Email' }).fill(fields.email);
    }
    if (fields.phone) {
        await page.getByRole('textbox', { name: 'Phone' }).fill(fields.phone);
    }
    await page.getByRole('button', { name: 'Create contact' }).click();
    await expect(page.getByText('Contact created.')).toBeVisible();
    await expect(page.getByRole('heading', { name: `${fields.first} ${run}` })).toBeVisible();
}

test.afterAll(async ({ request }) => {
    for (const id of seededIds) {
        await request.delete(`/api/v1/contacts/${id}`, { headers: { Accept: 'application/json' } });
    }
});

test('create, search, sort, paginate, call and delete contacts', async ({ page, request }) => {
    await page.goto('/contacts');
    await expect(page.getByRole('heading', { name: 'Contacts', level: 1 })).toBeVisible();

    // Create one contact with an email and one with a phone only.
    await createContact(page, { first: 'Alice', email: `alice-${run.toLowerCase()}@e2e.example` });
    await createContact(page, { first: 'Bob', phone: '0412 345 678' });
    await expect(page.getByText('+61 412 345 678')).toBeVisible();
    seededIds.push(page.url().split('/').pop()!);

    // Nine more through the API, so the search result spans two pages of ten.
    await seedContacts(request, 9);

    // Search through the backend.
    await page.getByRole('link', { name: 'Contacts', exact: true }).first().click();
    await page.getByRole('textbox', { name: 'Name' }).fill(run);
    const searched = page.waitForRequest((req) => req.url().includes('/api/v1/contacts?') && new URL(req.url()).searchParams.get('name') === run);
    await page.getByRole('button', { name: 'Search' }).click();
    await searched;
    await expect(page.getByText('Showing 1–11 of 11 contacts')).toBeVisible();

    // Page size and pagination are backend parameters.
    await page.getByRole('combobox', { name: 'Contacts per page' }).click();
    await page.getByRole('option', { name: '10 per page' }).click();
    await expect(page.getByText('Showing 1–10 of 11 contacts')).toBeVisible();
    await page.getByRole('button', { name: 'Page 2' }).click();
    await expect(page.getByText('Showing 11–11 of 11 contacts')).toBeVisible();

    // Sorting is a backend parameter; the first row reflects the server's order.
    const sorted = page.waitForRequest((req) => new URL(req.url()).searchParams.get('sort') === 'first_name' && new URL(req.url()).searchParams.get('direction') === 'desc');
    await page.getByRole('combobox', { name: 'Sort by' }).click();
    await page.getByRole('option', { name: 'First name (Z–A)' }).click();
    await sorted;
    const firstRow = page.getByRole('table', { name: 'Contacts' }).getByRole('row').nth(1);
    await expect(firstRow).toContainText(`Seed09 ${run}`);

    // Open the phone-only contact and place a mock call.
    await page.getByRole('combobox', { name: 'Sort by' }).click();
    await page.getByRole('option', { name: 'First name (A–Z)' }).click();
    await page.getByRole('link', { name: `Bob ${run}` }).click();
    await page.getByRole('button', { name: 'Call' }).click();
    await expect(page.getByRole('status')).toContainText('Outcome:');
    await expect(page.getByRole('table', { name: 'Latest calls' }).getByRole('row')).toHaveCount(2);

    // Delete Alice after confirming.
    await page.getByRole('link', { name: 'Back to contacts' }).click();
    await page.getByRole('link', { name: `Alice ${run}` }).click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await page.getByRole('dialog', { name: 'Delete contact' }).getByRole('button', { name: 'Delete contact' }).click();
    await expect(page.getByText('Contact deleted.')).toBeVisible();
    await page.getByRole('textbox', { name: 'Name' }).fill(`Alice ${run}`);
    await page.getByRole('button', { name: 'Search' }).click();
    await expect(page.getByText('No contacts found')).toBeVisible();
});
