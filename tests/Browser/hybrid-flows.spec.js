import { expect, test } from '@playwright/test';

test.describe.configure({ mode: 'serial' });

const unique = (prefix) => `${prefix} ${Date.now()}-${Math.random().toString(16).slice(2)}`;

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('admin@memorias.test');
    await page.getByLabel('Senha').fill('password');
    await page.getByRole('button', { name: 'Entrar no admin' }).click();
    await expect(page).toHaveURL(/\/admin$/);
}

async function createCategory(page, title) {
    await page.goto('/admin/categorias/create');
    await page.getByLabel('Título').fill(title);
    await page.getByRole('button', { name: 'Cadastrar categoria' }).click();
    await expect(page).toHaveURL(/\/admin\/categorias$/);
    await expect(page.getByText(title, { exact: true })).toBeVisible();
}

test.beforeEach(async ({ page }) => {
    await login(page);
});

test('confirmation dialog preserves focus, cancellation, target form and single submission', async ({ page }) => {
    const title = unique('Categoria confirmação E2E');
    await createCategory(page, title);

    const row = page.locator('tr').filter({ hasText: title });
    const trigger = row.getByRole('button', { name: 'Excluir' });
    const dialog = page.getByRole('alertdialog');

    await trigger.click();
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('button', { name: 'Cancelar' })).toBeFocused();
    await expect(dialog).toContainText('As associações com os itens do acervo serão removidas.');

    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();

    await trigger.click();
    await dialog.getByRole('button', { name: 'Cancelar' }).click();
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(row).toBeVisible();

    let submissions = 0;
    page.on('request', (request) => {
        if (request.method() === 'POST' && request.url().includes('/admin/categorias/')) {
            submissions++;
        }
    });

    await trigger.click();
    await Promise.all([
        page.waitForURL(/\/admin\/categorias$/),
        dialog.getByRole('button', { name: 'Excluir categoria' }).evaluate((button) => {
            button.click();
            button.click();
        }),
    ]);

    await expect(page.getByText(title, { exact: true })).toHaveCount(0);
    expect(submissions).toBe(1);
});

test('quick catalogs, Alpine state and traditional photograph POST work together', async ({ page }) => {
    const existingCategory = unique('Categoria existente E2E');
    const newCategory = unique('Categoria rápida E2E');
    const newAuthor = unique('Autor rápido E2E');
    const photographTitle = unique('Fotografia híbrida E2E');

    await createCategory(page, existingCategory);
    await page.goto('/admin/fotografias/create');

    await page.getByText('Classificações', { exact: true }).click();
    const categoryPicker = page.locator('article.catalog-picker').filter({
        has: page.getByRole('heading', { name: 'Categorias', exact: true }),
    });
    const existingOption = categoryPicker.locator('label.catalog-option').filter({ hasText: existingCategory });
    await existingOption.getByRole('checkbox').check();
    await expect(categoryPicker.locator('.catalog-picker__count')).toHaveText('1 selecionado(s)');

    await categoryPicker.locator('details.quick-create summary').click();
    await categoryPicker.getByLabel('Título').fill(newCategory);
    await categoryPicker.getByRole('button', { name: 'Criar e selecionar' }).click();
    await expect(categoryPicker.getByText('Criado e selecionado.')).toHaveText('Criado e selecionado.');

    const newOption = categoryPicker.locator('label.catalog-option').filter({ hasText: newCategory });
    await expect(newOption).toBeVisible();
    await expect(newOption.getByRole('checkbox')).toBeChecked();
    await expect(existingOption.getByRole('checkbox')).toBeChecked();
    await expect(categoryPicker.locator('.catalog-picker__count')).toHaveText('2 selecionado(s)');

    const categorySearch = categoryPicker.getByPlaceholder('Buscar categoria...');
    await categorySearch.fill(newCategory);
    await expect(newOption).toBeVisible();
    await expect(existingOption).toBeHidden();
    await categorySearch.fill('');
    await expect(existingOption).toBeVisible();

    const author = page.locator('.catalog-author');
    await author.locator('details.quick-create summary').click();
    await author.getByLabel('Nome').fill(newAuthor);
    await author.getByLabel('Tipo').selectOption('pessoa');
    await author.getByRole('button', { name: 'Criar e selecionar' }).click();
    await expect(author.getByText('Criado e selecionado.')).toHaveText('Criado e selecionado.');
    await expect(author.locator('select[name="autor_id"] option:checked')).toContainText(newAuthor);

    await page.getByLabel(/Título obrigatório/).fill(photographTitle);
    await page.getByText('Data e contexto', { exact: true }).click();
    const precision = page.locator('#tipo_data');
    const day = page.locator('#dia');
    const month = page.locator('#mes');
    const year = page.locator('#ano');
    const decade = page.locator('#decada');

    await precision.selectOption('data_exata');
    await expect(day).toBeVisible();
    await expect(month).toBeVisible();
    await expect(year).toBeVisible();
    await expect(decade).toBeHidden();
    await expect(day).toBeEnabled();
    await expect(month).toBeEnabled();
    await expect(year).toBeEnabled();
    await expect(decade).toBeDisabled();
    await day.fill('15');
    await month.fill('8');
    await year.fill('1984');

    await precision.selectOption('decada');
    await expect(day).toBeHidden();
    await expect(month).toBeHidden();
    await expect(year).toBeHidden();
    await expect(decade).toBeVisible();
    await expect(day).toBeDisabled();
    await expect(month).toBeDisabled();
    await expect(year).toBeDisabled();
    await expect(decade).toBeEnabled();
    await decade.fill('1980');

    const photographRequest = page.waitForRequest((request) =>
        request.method() === 'POST' && new URL(request.url()).pathname === '/admin/fotografias',
    );

    await page.getByRole('button', { name: 'Salvar fotografia' }).click();
    const submitted = await photographRequest;
    const payload = submitted.postData() ?? '';

    expect(payload).toContain('name="decada"');
    expect(payload).not.toContain('name="dia"');
    expect(payload).not.toContain('name="mes"');
    expect(payload).not.toContain('name="ano"');

    await expect(page).toHaveURL(/\/admin\/fotografias$/);
    await expect(page.getByText(photographTitle, { exact: true })).toBeVisible();
});

test('local image preview updates when the selected file changes', async ({ page }) => {
    const firstImage = Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        'base64',
    );
    const secondImage = Buffer.from(
        'iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAQAAABFaP0WAAAADUlEQVR42mNk+M/wHwAEAQH/8E3hAAAAAElFTkSuQmCC',
        'base64',
    );

    await page.goto('/admin/fotografias/create');
    await page.getByText('Arquivo original', { exact: true }).click();

    const input = page.locator('input[name="arquivo_original"]');
    const preview = page.locator('.catalog-file-preview');
    const image = preview.locator('img');

    await input.setInputFiles({ name: 'primeira.png', mimeType: 'image/png', buffer: firstImage });
    await expect(preview.getByText('primeira.png', { exact: true })).toBeVisible();
    await expect(preview.getByText(/image\/png · .* MB/)).toBeVisible();
    await expect(image).toBeVisible();
    const firstUrl = await image.getAttribute('src');

    await input.setInputFiles({ name: 'segunda.png', mimeType: 'image/png', buffer: secondImage });
    await expect(preview.getByText('segunda.png', { exact: true })).toBeVisible();
    await expect(preview.getByText('primeira.png', { exact: true })).toHaveCount(0);
    await expect(image).toBeVisible();
    await expect(image).not.toHaveAttribute('src', firstUrl ?? '');
});
