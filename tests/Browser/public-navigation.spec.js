import { expect, test } from '@playwright/test';
import { resetDatabase } from './environment.js';

test('desktop header: keyboard order, search shortcut and no admin link', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto('/contato');
    const header = page.getByRole('banner');

    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Pular para o conteúdo' })).toBeFocused();
    await page.keyboard.press('Tab');
    await expect(page.getByTestId('public-brand')).toBeFocused();
    for (const nome of ['Início', 'Catálogo', 'Sobre nós', 'Como colaborar', 'Contato']) {
        await page.keyboard.press('Tab');
        await expect(header.getByRole('link', { name: nome, exact: true })).toBeFocused();
    }
    await expect(header.getByRole('link', { name: 'Contato', exact: true })).toHaveAttribute('aria-current', 'page');
    await page.keyboard.press('Tab');
    const lupa = header.getByRole('link', { name: 'Pesquisar no acervo' });
    await expect(lupa).toBeFocused();
    await expect(lupa).toHaveCSS('outline-style', 'solid');

    await expect(header.getByRole('searchbox')).toHaveCount(0);
    await expect(header.getByText('Área administrativa')).toHaveCount(0);
    await expect(page.getByRole('contentinfo').getByRole('link', { name: 'Área administrativa' })).toBeVisible();

    // A lupa leva à busca do catálogo, com o campo já focado.
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/catalogo#busca$/);
    const busca = page.getByRole('searchbox', { name: 'Buscar no catálogo' });
    await expect(busca).toBeFocused();
    await busca.fill('praça central');
    await busca.press('Enter');
    await expect(page).toHaveURL(/\/catalogo\?q=pra%C3%A7a\+central$/);
    await expect(page.getByText('Resultados para “praça central”.', { exact: true })).toBeVisible();

    // No catálogo, a lupa preserva a pesquisa atual.
    await expect(page.getByRole('banner').getByRole('link', { name: 'Pesquisar no acervo' })).toHaveAttribute('href', /\/catalogo\?q=pra%C3%A7a(\+|%20)central#busca$/);
});

test('mobile header: menu drawer opens, highlights the page, closes with Escape and on navigation', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/contato');
    const header = page.getByRole('banner');
    const trigger = header.getByRole('button', { name: 'Abrir menu' });
    const menu = page.getByTestId('public-menu');

    await expect(header.getByRole('link', { name: 'Pesquisar no acervo' })).toBeVisible();
    await expect(header.getByRole('navigation').first()).toBeHidden();
    expect(await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(0);

    await trigger.focus();
    await page.keyboard.press('Enter');
    await expect(menu).toBeVisible();
    await expect(menu.locator('a[aria-current="page"]')).toHaveText('Contato');
    await expect(menu.getByText('Área administrativa')).toHaveCount(0);
    await page.keyboard.press('Escape');
    await expect(menu).toBeHidden();
    await expect(trigger).toBeFocused();

    await trigger.click();
    await menu.getByRole('link', { name: 'Sobre nós' }).click();
    await expect(page).toHaveURL(/\/sobre$/);
    await expect(page.getByTestId('public-menu')).toBeHidden();
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
    await expect(page.getByRole('link', { name: 'Ir para o início' })).toHaveAttribute('href', /^https?:\/\/[^/]+\/?$/);
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
