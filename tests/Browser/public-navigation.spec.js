import { expect, test } from '@playwright/test';
import { resetDatabase } from './environment.js';

test('global search preserves the term and the mobile menu supports keyboard navigation', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/contato');

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

    await expect(page).toHaveURL(/\/catalogo\?q=pra%C3%A7a\+central$/);
    await expect(page.getByText('Resultados para “praça central”.', { exact: true })).toBeVisible();
    await expect(page.getByRole('searchbox', { name: 'Buscar no catálogo' })).toHaveValue('praça central');
});

test('public carousel keeps only the visible photograph interactive', async ({ page }) => {
    await page.goto('/');

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
    const response = await page.goto('/nao-existe');

    expect(response?.status()).toBe(404);
    await expect(page.getByRole('heading', { name: 'Esta página não foi encontrada' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Ir para o início' })).toHaveAttribute('href', /\/publico$/);
    await expect(page.getByRole('link', { name: 'Abrir o catálogo' })).toHaveAttribute('href', /\/catalogo$/);
});

test.describe('catálogo', () => {
    // O seed visual traz uma fotografia pública com categoria, para haver opção de filtro.
    test.beforeAll(() => {
        test.setTimeout(120_000);
        resetDatabase(['E2eVisualSeeder']);
    });

    test('catalog filters drawer applies filters as removable chips and returns focus', async ({ page }) => {
        await page.goto('/catalogo');

        // A busca do header não aparece no catálogo: só a da própria página.
        await expect(page.getByRole('searchbox', { name: 'Buscar no catálogo' })).toHaveCount(1);

        const trigger = page.getByRole('button', { name: /^Filtros/ });
        const drawer = page.getByRole('dialog', { name: 'Filtrar fotografias' });

        await trigger.click();
        await expect(drawer).toBeVisible();
        await expect(page.locator('html')).toHaveCSS('overflow', 'hidden');
        await page.keyboard.press('Escape');
        await expect(drawer).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(page.locator('html')).not.toHaveCSS('overflow', 'hidden');

        await trigger.click();
        const categoria = drawer.getByLabel('Categoria');
        const opcao = await categoria.locator('option').nth(1).textContent();
        await categoria.selectOption({ index: 1 });
        await drawer.getByRole('button', { name: 'Aplicar filtros' }).click();

        await expect(page).toHaveURL(/categoria=\d+/);
        const chip = page.getByRole('link', { name: `${opcao.trim()} (remover filtro)` });
        await expect(chip).toBeVisible();
        await expect(page.getByRole('button', { name: /^Filtros 1 aplicados/ })).toBeVisible();
        await expect(page.getByText(/\d+ fotografias? encontradas?/)).toBeVisible();

        await chip.click();
        await expect(page).not.toHaveURL(/categoria=/);
        await expect(page.getByRole('link', { name: /remover filtro/ })).toHaveCount(0);
    });

    test('gallery items are keyboard reachable with a visible focus ring', async ({ page }) => {
        await page.goto('/catalogo');
        const item = page.getByRole('list', { name: 'Fotografias do acervo' }).getByRole('link').first();

        await item.focus();
        await expect(item).toBeFocused();
        await expect(item).toHaveCSS('outline-style', 'solid');
        await expect(item).toHaveAttribute('href', /\/fotografias\//);
    });
});
