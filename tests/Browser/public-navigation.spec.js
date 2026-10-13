import { expect, test } from '@playwright/test';

test('global search preserves the term and the mobile menu supports keyboard navigation', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/publico/contato');

    for (let index = 0; index < 4; index++) {
        await page.keyboard.press('Tab');
    }

    const menu = page.locator('.public-menu summary');
    await expect(menu).toBeFocused();
    await expect(menu).toHaveCSS('outline-style', 'solid');
    await page.keyboard.press('Enter');
    await expect(page.locator('details.public-menu')).toHaveAttribute('open', '');
    await expect(page.locator('.public-nav--menu a[aria-current="page"]')).toHaveText('Contato');

    const search = page.getByRole('searchbox', { name: 'Buscar no catálogo' });
    await search.fill('praça central');
    await search.press('Enter');

    await expect(page).toHaveURL(/\/publico\/catalogo\?q=pra%C3%A7a\+central$/);
    await expect(page.getByText('Busca por “praça central”.')).toBeVisible();
    await expect(page.getByRole('searchbox', { name: 'Buscar no catálogo' })).toHaveValue('praça central');
});

test('unknown pages offer a clear route back into the public area', async ({ page }) => {
    const response = await page.goto('/publico/nao-existe');

    expect(response?.status()).toBe(404);
    await expect(page.getByRole('heading', { name: 'Esta página não foi encontrada' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Ir para o início' })).toHaveAttribute('href', /\/publico$/);
    await expect(page.getByRole('link', { name: 'Abrir o catálogo' })).toHaveAttribute('href', /\/publico\/catalogo$/);
});
