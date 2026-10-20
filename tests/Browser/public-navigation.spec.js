import { expect, test } from '@playwright/test';

test('global search preserves the term and the mobile menu supports keyboard navigation', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/publico/contato');

    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Pular para o conteúdo' })).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.getByTestId('public-brand')).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.getByRole('searchbox', { name: 'Buscar no catálogo' })).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.getByRole('button', { name: 'Buscar no catálogo' })).toBeFocused();
    await page.keyboard.press('Tab');

    const publicMenu = page.getByTestId('public-menu');
    const menu = publicMenu.locator('summary');
    await expect(menu).toBeFocused();
    await expect(menu).toHaveCSS('outline-style', 'solid');
    await page.keyboard.press('Enter');
    await expect(publicMenu).toHaveAttribute('open', '');
    await expect(publicMenu.locator('a[aria-current="page"]')).toHaveText('Contato');

    const search = page.getByRole('searchbox', { name: 'Buscar no catálogo' });
    await search.fill('praça central');
    await search.press('Enter');

    await expect(page).toHaveURL(/\/publico\/catalogo\?q=pra%C3%A7a\+central$/);
    await expect(page.getByText('Resultados para “praça central”.', { exact: true })).toBeVisible();
    await expect(page.getByRole('searchbox', { name: 'Buscar no catálogo' })).toHaveValue('praça central');
});

test('public carousel keeps only the visible photograph interactive', async ({ page }) => {
    await page.goto('/publico');

    const carousel = page.locator('[data-carousel]');
    const slides = carousel.locator('[data-carousel-slide]');
    const firstSlide = slides.nth(0);
    const secondSlide = slides.nth(1);

    await expect(slides).toHaveCount(3);
    await expect(firstSlide).toHaveAttribute('aria-hidden', 'false');
    await expect(firstSlide).not.toHaveAttribute('inert', '');
    await expect(secondSlide).toHaveAttribute('aria-hidden', 'true');
    await expect(secondSlide).toHaveAttribute('inert', '');

    await carousel.getByRole('button', { name: 'Próxima fotografia' }).click();
    await expect(firstSlide).toHaveAttribute('aria-hidden', 'true');
    await expect(firstSlide).toHaveAttribute('inert', '');
    await expect(secondSlide).toHaveAttribute('aria-hidden', 'false');
    await expect(secondSlide).not.toHaveAttribute('inert', '');

    await secondSlide.getByRole('link').focus();
    await expect(secondSlide.getByRole('link')).toBeFocused();

    await carousel.focus();
    await page.keyboard.press('ArrowRight');
    await expect(slides.nth(2)).toHaveAttribute('aria-hidden', 'false');
    await expect(secondSlide).toHaveAttribute('inert', '');
});

test('unknown pages offer a clear route back into the public area', async ({ page }) => {
    const response = await page.goto('/publico/nao-existe');

    expect(response?.status()).toBe(404);
    await expect(page.getByRole('heading', { name: 'Esta página não foi encontrada' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Ir para o início' })).toHaveAttribute('href', /\/publico$/);
    await expect(page.getByRole('link', { name: 'Abrir o catálogo' })).toHaveAttribute('href', /\/publico\/catalogo$/);
});
