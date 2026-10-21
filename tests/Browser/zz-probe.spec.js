import { expect, test } from '@playwright/test';
import { resetDatabase } from './environment.js';
const out = '/tmp/claude-1000/-home-bertolli-Projects-temp-memoria-vivas/fd24b491-effc-4695-bde7-1da201c03821/scratchpad';
test.beforeAll(() => resetDatabase(['E2eVisualSeeder']));
test('combobox', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (e) => { errors.push(e.message); console.log('STACK', e.stack.split('\n').slice(0, 6).join(' | ')); });
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('admin@memorias.test');
    await page.getByLabel('Senha').fill('password');
    await page.getByRole('button', { name: 'Entrar no admin' }).click();
    await page.goto('/admin/fotografias/create');
    await page.getByLabel(/Título obrigatório/).fill('Teste combobox');

    const categorias = page.getByTestId('catalog-picker-categoria');
    const busca = categorias.getByRole('combobox', { name: 'Categorias' });
    await busca.fill('pra');
    await expect(categorias.getByRole('option', { name: 'Praças e logradouros' })).toBeVisible();
    await page.screenshot({ path: `${out}/combobox-aberto.png`, fullPage: false, clip: { x: 0, y: 0, width: 1280, height: 800 } });
    await busca.press('ArrowDown');
    await busca.press('ArrowUp');
    await busca.press('Enter');
    await expect(categorias.getByRole('button', { name: 'Remover Praças e logradouros' })).toBeVisible();

    // Criar pelo termo buscado.
    await busca.fill('Feiras livres');
    await categorias.getByRole('option', { name: /Criar categoria: “Feiras livres”/ }).click();
    const dialog = page.getByRole('dialog', { name: 'Criar categoria' });
    await expect(dialog.getByLabel('Título')).toHaveValue('Feiras livres');
    await dialog.getByRole('button', { name: 'Criar e selecionar' }).click();
    await expect(dialog).toBeHidden();
    await expect(page.getByText('Categoria “Feiras livres” criada e selecionada.')).toBeVisible();
    await expect(categorias.getByRole('button', { name: 'Remover Feiras livres' })).toBeVisible();

    // Autor (seleção única) e pessoa.
    const autor = page.getByTestId('catalog-picker-autor');
    await autor.getByRole('combobox').fill('estúdio');
    await autor.getByRole('option', { name: /Foto Estúdio Umuarama/ }).click();
    await expect(autor.getByRole('button', { name: 'Remover Foto Estúdio Umuarama' })).toBeVisible();
    const pessoas = page.getByTestId('catalog-picker-pessoa');
    await pessoas.getByRole('combobox').fill('maria');
    await pessoas.getByRole('option', { name: 'Maria da Silva' }).click();

    // Remover chip.
    await categorias.getByRole('button', { name: 'Remover Praças e logradouros' }).click();
    await expect(categorias.getByRole('button', { name: 'Remover Praças e logradouros' })).toBeHidden();
    await page.screenshot({ path: `${out}/combobox-form.png`, fullPage: true });

    await page.getByRole('button', { name: 'Salvar fotografia' }).click();
    await expect(page).toHaveURL(/\/admin\/fotografias$/);
    await page.getByRole('row').filter({ hasText: 'Teste combobox' }).getByRole('link', { name: 'Visualizar' }).click();
    await expect(page.getByText('Feiras livres')).toBeVisible();
    await expect(page.getByText('Maria da Silva')).toBeVisible();
    await expect(page.getByText('Foto Estúdio Umuarama')).toBeVisible();
    await expect(page.getByText('Praças e logradouros')).toHaveCount(0);
    expect(errors).toEqual([]);
});
