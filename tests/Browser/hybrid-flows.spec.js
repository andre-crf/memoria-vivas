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
    const categoryPicker = page.getByTestId('relationship-picker').filter({
        has: page.getByRole('heading', { name: 'Categorias', exact: true }),
    });
    const existingOption = categoryPicker.locator('[data-option]').filter({ hasText: existingCategory });
    await existingOption.getByRole('checkbox').check();
    await expect(categoryPicker.getByTestId('relationship-picker-count')).toHaveText('1 selecionado(s)');

    await page.getByLabel(/Título obrigatório/).fill(photographTitle);
    await categoryPicker.getByRole('button', { name: '+ Criar categoria' }).click();
    const categoryDialog = page.getByRole('dialog', { name: 'Criar categoria', exact: true });
    await categoryDialog.getByLabel('Título').fill(newCategory);
    await categoryDialog.getByRole('button', { name: 'Criar e selecionar' }).click();
    await expect(categoryDialog).toBeHidden();
    await expect(page.getByLabel(/Título obrigatório/)).toHaveValue(photographTitle);
    await expect(categoryPicker.getByText('Criado e selecionado.')).toHaveText('Criado e selecionado.');

    const newOption = categoryPicker.locator('[data-option]').filter({ hasText: newCategory });
    await expect(newOption).toBeVisible();
    await expect(newOption.getByRole('checkbox')).toBeChecked();
    await expect(existingOption.getByRole('checkbox')).toBeChecked();
    await expect(categoryPicker.getByTestId('relationship-picker-count')).toHaveText('2 selecionado(s)');

    const categorySearch = categoryPicker.getByPlaceholder('Buscar categoria...');
    await categorySearch.fill(newCategory);
    await expect(newOption).toBeVisible();
    await expect(existingOption).toBeHidden();
    await categorySearch.fill('');
    await expect(existingOption).toBeVisible();

    const author = page.getByTestId('quick-author');
    await author.getByRole('button', { name: '+ Cadastrar novo autor' }).click();
    const authorDialog = page.getByRole('dialog', { name: 'Cadastrar novo autor', exact: true });
    await authorDialog.getByLabel('Nome').fill(newAuthor);
    await authorDialog.getByLabel('Tipo').selectOption('pessoa');
    await authorDialog.getByRole('button', { name: 'Criar e selecionar' }).click();
    await expect(authorDialog).toBeHidden();
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
    await expect(day).toHaveValue('');
    await expect(month).toHaveValue('');
    await expect(year).toHaveValue('');
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
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        'base64',
    );

    await page.goto('/admin/fotografias/create');
    await page.getByText('Arquivo original', { exact: true }).click();

    const input = page.locator('input[name="arquivo_original"]');
    const preview = page.getByTestId('file-preview');
    const image = preview.locator('img');

    await input.setInputFiles({ name: 'primeira.png', mimeType: 'image/png', buffer: firstImage });
    await expect(preview.getByText('primeira.png', { exact: true })).toBeVisible();
    await expect(preview.getByText(/image\/png · .* MB/)).toBeVisible();
    await expect(image).toBeVisible();
    await expect.poll(() => image.evaluate((img) => img.naturalWidth)).toBeGreaterThan(0);
    const firstUrl = await image.getAttribute('src');

    await input.setInputFiles({ name: 'segunda.png', mimeType: 'image/png', buffer: secondImage });
    await expect(preview.getByText('segunda.png', { exact: true })).toBeVisible();
    await expect(preview.getByText('primeira.png', { exact: true })).toHaveCount(0);
    await expect(image).toBeVisible();
    await expect(image).not.toHaveAttribute('src', firstUrl ?? '');
    await expect.poll(() => image.evaluate((img) => img.naturalWidth)).toBeGreaterThan(0);
    await input.setInputFiles([]);
    await expect(preview).toBeHidden();
});

for (const mode of ['cadastro', 'edição']) {
    test(`modals preserve photograph fields and select all four catalogs during ${mode}`, async ({ page }) => {
        test.setTimeout(60_000);
        const title = unique(`Fotografia ${mode}`);
        await page.goto('/admin/fotografias/create');
        await page.getByLabel(/Título obrigatório/).fill(title);
        if (mode === 'edição') {
            await page.getByRole('button', { name: 'Salvar fotografia' }).click();
            const row = page.locator('tr').filter({ hasText: title });
            await row.getByRole('link', { name: 'Editar', exact: true }).click();
        }
        const errors = [];
        page.on('pageerror', (error) => errors.push(error.message));
        await page.getByText('Classificações', { exact: true }).click();
        for (const [catalog, field, triggerName] of [
            ['autor', 'Nome', '+ Cadastrar novo autor'],
            ['categoria', 'Título', '+ Criar categoria'],
            ['assunto', 'Título', '+ Criar assunto'],
            ['palavra-chave', 'Termo', '+ Criar palavra-chave'],
        ]) {
            const trigger = page.getByRole('button', { name: triggerName, exact: true });
            await trigger.click();
            const dialog = page.getByRole('dialog');
            await expect(dialog).toBeVisible();
            await page.keyboard.press('Escape');
            await expect(dialog).toBeHidden();
            await expect(trigger).toBeFocused();
            await trigger.click();
            await dialog.getByRole('button', { name: 'Criar e selecionar' }).click();
            await expect(dialog.getByRole('alert').first()).toBeVisible();
            const name = unique(`${catalog} ${mode}`);
            await dialog.getByLabel(field).fill(name);
            await dialog.getByRole('button', { name: 'Criar e selecionar' }).click();
            await expect(dialog).toBeHidden();
            await expect(page.getByLabel(/Título obrigatório/)).toHaveValue(title);
            if (catalog === 'autor') {
                await expect(page.locator('#autor_id option:checked')).toContainText(name);
            } else {
                await expect(page.locator('[data-option]').filter({ hasText: name }).getByRole('checkbox')).toBeChecked();
            }
        }
        await page.getByRole('button', { name: mode === 'cadastro' ? 'Salvar fotografia' : 'Salvar alterações' }).click();
        await expect(page).not.toHaveURL(/\/(create|edit)$/);
        expect(errors).toEqual([]);
    });
}

test('public photograph viewer supports keyboard, zoom and mobile fullscreen', async ({ page }, testInfo) => {
    const title = unique('Fotografia pública ampliada');
    await page.goto('/admin/fotografias/create');
    await page.getByLabel(/Título obrigatório/).fill(title);
    await page.locator('#status').selectOption('publicado');
    await page.locator('#visibilidade').selectOption('publico');
    await page.getByText('Arquivo original', { exact: true }).click();
    await page.locator('#arquivo_original').setInputFiles('public/images/memorias-vivas-logo.png');
    await page.getByRole('button', { name: 'Salvar fotografia' }).click();
    await expect(page).toHaveURL(/\/admin\/fotografias$/);
    await page.goto('/publico/catalogo');
    await page.getByRole('link', { name: `Ver detalhes de ${title}`, exact: true }).click();
    const trigger = page.getByRole('button', { name: `Ampliar fotografia: ${title}`, exact: true });
    const viewer = page.getByRole('dialog', { name: title, exact: true });
    await trigger.press('Enter');
    await expect(viewer).toBeVisible();
    await expect(viewer.getByRole('button', { name: 'Fechar visualização' })).toBeFocused();
    await expect.poll(() => viewer.locator('img').evaluate((img) => img.complete && img.naturalWidth > 0)).toBe(true);
    await viewer.getByRole('button', { name: 'Ver imagem no tamanho original' }).click();
    await expect(viewer.getByRole('button', { name: 'Ajustar imagem à tela' })).toHaveAttribute('aria-pressed', 'true');
    await page.keyboard.press('Escape');
    await expect(viewer).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(page.locator('html')).not.toHaveCSS('overflow', 'hidden');

    await page.setViewportSize({ width: 390, height: 844 });
    await trigger.click();
    await expect(viewer).toBeVisible();
    const bounds = await viewer.boundingBox();
    expect(bounds?.width).toBe(390);
    expect(bounds?.height).toBe(844);
    await page.screenshot({ path: testInfo.outputPath('viewer-mobile.png') });
    await viewer.getByRole('button', { name: 'Fechar visualização' }).click();
    await expect(viewer).toBeHidden();
    await expect(trigger).toBeFocused();
});
