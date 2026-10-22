import { readFileSync } from 'node:fs';
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

    const categoryPicker = page.getByTestId('catalog-picker-categoria');
    const categorySearch = categoryPicker.getByRole('combobox', { name: 'Categorias' });

    // Busca no servidor, escolha pelo teclado e chip do item escolhido.
    await categorySearch.fill(existingCategory);
    await expect(categoryPicker.getByRole('option', { name: existingCategory })).toHaveCount(1);
    await categorySearch.press('Enter');
    await expect(categoryPicker.getByRole('button', { name: `Remover ${existingCategory}` })).toBeVisible();

    // "Criar “termo”" abre o cadastro já preenchido com o que foi buscado.
    await page.getByLabel(/Título obrigatório/).fill(photographTitle);
    await categorySearch.fill(newCategory);
    await categoryPicker.getByRole('option', { name: `Criar categoria: “${newCategory}”` }).click();
    const categoryDialog = page.getByRole('dialog', { name: 'Criar categoria', exact: true });
    await expect(categoryDialog.getByLabel('Título')).toHaveValue(newCategory);
    await categoryDialog.getByRole('button', { name: 'Criar e selecionar' }).click();
    await expect(categoryDialog).toBeHidden();
    await expect(page.getByLabel(/Título obrigatório/)).toHaveValue(photographTitle);
    await expect(page.getByText(`Categoria “${newCategory}” criada e selecionada.`)).toBeVisible();
    await expect(categoryPicker.getByRole('button', { name: `Remover ${newCategory}` })).toBeVisible();
    await expect(categoryPicker.getByRole('button', { name: `Remover ${existingCategory}` })).toBeVisible();

    // Os já escolhidos não aparecem de novo nas sugestões.
    await categorySearch.fill(existingCategory);
    await expect(categoryPicker.getByText(`“${existingCategory}” já está selecionado.`)).toBeVisible();
    await expect(categoryPicker.getByRole('option')).toHaveCount(0);
    await categorySearch.fill('');

    const author = page.getByTestId('catalog-picker-autor');
    await author.getByRole('button', { name: 'Cadastrar novo autor' }).click();
    const authorDialog = page.getByRole('dialog', { name: 'Cadastrar novo autor', exact: true });
    await authorDialog.getByLabel('Nome').fill(newAuthor);
    await authorDialog.getByLabel('Tipo').selectOption('pessoa');
    await authorDialog.getByRole('button', { name: 'Criar e selecionar' }).click();
    await expect(authorDialog).toBeHidden();
    await expect(page.getByText(`Autor “${newAuthor}” cadastrado e selecionado.`)).toBeVisible();
    await expect(author.getByRole('button', { name: `Remover ${newAuthor}` })).toBeVisible();

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
    const input = page.locator('input[name="arquivo_original"]');
    const preview = page.getByTestId('file-preview');
    const image = preview.locator('img');

    await input.setInputFiles({ name: 'primeira.png', mimeType: 'image/png', buffer: firstImage });
    await expect(preview.getByText('primeira.png', { exact: true })).toBeVisible();
    await expect(preview.getByText(/^PNG · \d+ KB$/)).toBeVisible();
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

// Simula arrastar e soltar um arquivo criado na própria página sobre a área de upload.
async function dropFile(page, { name, type, size = null, fromPath = null }) {
    const base64 = fromPath ? readFileSync(fromPath).toString('base64') : null;

    await page.evaluate(({ name, type, size, base64 }) => {
        const content = base64 ? Uint8Array.from(atob(base64), (char) => char.charCodeAt(0)) : new Uint8Array(size ?? 16);
        const transfer = new DataTransfer();
        transfer.items.add(new File([content], name, { type }));
        const zone = document.querySelector('label[for="arquivo_original"]');
        zone.dispatchEvent(new DragEvent('dragenter', { dataTransfer: transfer, bubbles: true }));
        zone.dispatchEvent(new DragEvent('dragover', { dataTransfer: transfer, bubbles: true }));
        zone.dispatchEvent(new DragEvent('drop', { dataTransfer: transfer, bubbles: true }));
    }, { name, type, size, base64 });
}

test('dropped original file is previewed and sent with the traditional POST', async ({ page }) => {
    const title = unique('Fotografia arrastada');
    await page.goto('/admin/fotografias/create');
    await page.getByLabel(/Título obrigatório/).fill(title);

    const zone = page.locator('label[for="arquivo_original"]');
    await expect(zone).toContainText('Arraste e solte seu arquivo aqui');
    await page.evaluate(() => {
        const transfer = new DataTransfer();
        document.querySelector('label[for="arquivo_original"]').dispatchEvent(new DragEvent('dragenter', { dataTransfer: transfer, bubbles: true }));
    });
    await expect(zone).toHaveAttribute('data-dragging', 'true');
    await expect(zone).toContainText('Solte o arquivo para enviar');
    await page.evaluate(() => {
        const transfer = new DataTransfer();
        document.querySelector('label[for="arquivo_original"]').dispatchEvent(new DragEvent('dragleave', { dataTransfer: transfer, bubbles: true }));
    });
    await expect(zone).not.toHaveAttribute('data-dragging');

    // Imagem diferente das usadas em outros testes: o sistema recusa originais duplicados (mesmo hash).
    await dropFile(page, { name: 'praca-central.png', type: 'image/png', fromPath: 'public/images/umuarama-panorama-horizontal.png' });
    const preview = page.getByTestId('file-preview');
    await expect(preview.getByText('praca-central.png', { exact: true })).toBeVisible();
    await expect(preview.locator('img')).toBeVisible();
    await expect(zone).toBeHidden();
    expect(await page.locator('#arquivo_original').evaluate((input) => input.files[0]?.name)).toBe('praca-central.png');

    // O Playwright não expõe corpos de upload; a prova é o arquivo vinculado depois de salvar.
    await page.getByRole('button', { name: 'Salvar fotografia' }).click();
    await expect(page).toHaveURL(/\/admin\/fotografias$/);
    await page.getByRole('row').filter({ hasText: title }).getByRole('link', { name: 'Visualizar' }).click();
    await expect(page.getByText('praca-central.png').first()).toBeVisible();
});

test('original file can be removed and replaced, and invalid files are rejected', async ({ page }) => {
    await page.goto('/admin/fotografias/create');
    const input = page.locator('#arquivo_original');
    const preview = page.getByTestId('file-preview');
    const zone = page.locator('label[for="arquivo_original"]');
    const fileName = () => input.evaluate((element) => element.files[0]?.name ?? null);

    await input.setInputFiles({ name: 'primeira.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4') });
    await expect(preview.getByText('PDF', { exact: true })).toBeVisible();

    // Substituir abre o seletor; o novo arquivo toma o lugar do anterior.
    const chooser = page.waitForEvent('filechooser');
    await preview.getByRole('button', { name: 'Substituir' }).click();
    await (await chooser).setFiles({ name: 'segunda.jpg', mimeType: 'image/jpeg', buffer: readFileSync('public/images/memorias-vivas-logo.png') });
    await expect(preview.getByText('segunda.jpg', { exact: true })).toBeVisible();
    expect(await fileName()).toBe('segunda.jpg');

    // Formato inválido: mensagem acessível e o arquivo válido anterior é mantido.
    await dropFile(page, { name: 'notas.txt', type: 'text/plain' });
    await expect(page.getByRole('alert').filter({ hasText: 'Formato não aceito' })).toContainText('JPG, JPEG, PNG, WebP, TIF, TIFF ou PDF');
    expect(await fileName()).toBe('segunda.jpg');

    // Acima de 50 MB: rejeitado no navegador, sem chegar ao input.
    await dropFile(page, { name: 'grande.jpg', type: 'image/jpeg', size: 51 * 1024 * 1024 });
    await expect(page.getByRole('alert').filter({ hasText: 'o limite é 50 MB' })).toContainText('O arquivo tem 51 MB');
    expect(await fileName()).toBe('segunda.jpg');

    await preview.getByRole('button', { name: 'Remover' }).click();
    await expect(preview).toBeHidden();
    await expect(zone).toBeVisible();
    await expect(input).toBeFocused();
    expect(await fileName()).toBeNull();

    // Pelo teclado: com o campo focado, Enter abre o seletor do sistema.
    const keyboardChooser = page.waitForEvent('filechooser');
    await page.keyboard.press('Enter');
    await (await keyboardChooser).setFiles({ name: 'teclado.png', mimeType: 'image/png', buffer: readFileSync('public/images/memorias-vivas-logo.png') });
    await expect(preview.getByText('teclado.png', { exact: true })).toBeVisible();
});

async function createPhotographWithOriginal(page, title, file) {
    await page.goto('/admin/fotografias/create');
    await page.getByLabel(/Título obrigatório/).fill(title);
    await page.locator('#arquivo_original').setInputFiles(file);
    await page.getByRole('button', { name: 'Salvar fotografia' }).click();
    await expect(page).toHaveURL(/\/admin\/fotografias$/);
}

const pdf = (name, marker) => ({ name, mimeType: 'application/pdf', buffer: Buffer.from(`%PDF-1.4\n% ${marker}\n%%EOF`) });

test('original file is replaced through the actions menu and the drag and drop modal', async ({ page }) => {
    test.setTimeout(60_000);
    const marker = unique('substituicao');
    const duplicated = pdf('duplicado.pdf', `${marker}-a`);
    await createPhotographWithOriginal(page, unique('Fotografia já cadastrada'), duplicated);
    const title = unique('Fotografia a substituir');
    await createPhotographWithOriginal(page, title, pdf('atual.pdf', `${marker}-b`));
    await page.locator('tr').filter({ hasText: title }).getByRole('link', { name: 'Visualizar', exact: true }).click();

    const trigger = page.getByRole('button', { name: 'Ações do arquivo' });
    const option = page.getByRole('button', { name: 'Substituir imagem' });
    const dialog = page.getByRole('dialog', { name: 'Substituir imagem' });
    const confirm = dialog.getByRole('button', { name: 'Confirmar substituição' });
    const preview = dialog.getByTestId('file-preview');
    await expect(page.getByText('Substituir arquivo original')).toHaveCount(0);

    // Menu: abre e fecha no botão, fecha com Esc (foco volta) e com clique fora.
    await trigger.click();
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    await expect(option).toBeVisible();
    await trigger.click();
    await expect(option).toBeHidden();
    await trigger.press('Enter');
    await expect(option).toBeVisible();
    await page.keyboard.press('Tab');
    await expect(option).toBeFocused();
    await page.keyboard.press('Escape');
    await expect(option).toBeHidden();
    await expect(trigger).toBeFocused();
    await trigger.click();
    await page.getByRole('heading', { name: title }).click();
    await expect(option).toBeHidden();

    // Modal: começa vazio e sem confirmação possível.
    await trigger.click();
    await option.click();
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText('Selecione um novo arquivo para substituir a imagem original deste registro.');
    await expect(dialog).toContainText('JPG, JPEG, PNG, WebP, TIF, TIFF ou PDF · até 50 MB');
    await expect(confirm).toBeDisabled();

    await dropFile(page, { name: 'notas.txt', type: 'text/plain' });
    await expect(dialog.getByRole('alert').filter({ hasText: 'Formato não aceito' })).toBeVisible();
    await expect(confirm).toBeDisabled();

    await dropFile(page, { name: 'logo.png', type: 'image/png', fromPath: 'public/images/memorias-vivas-logo.png' });
    await expect(preview.getByRole('img', { name: 'Pré-visualização de logo.png' })).toBeVisible();
    await expect(confirm).toBeEnabled();
    await preview.getByRole('button', { name: 'Remover' }).click();
    await expect(confirm).toBeDisabled();

    // Cancelar fecha, descarta a seleção e devolve o foco ao menu.
    await dialog.locator('#arquivo_original').setInputFiles(pdf('descartado.pdf', `${marker}-c`));
    await dialog.getByRole('button', { name: 'Cancelar' }).click();
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();
    await expect(page.getByText('atual.pdf', { exact: true })).not.toHaveCount(0);
    await trigger.click();
    await option.click();
    await expect(preview).toBeHidden();
    await expect(confirm).toBeDisabled();

    // Erro do backend (arquivo de outra fotografia): o modal volta aberto com a mensagem e o original fica.
    await dialog.locator('#arquivo_original').setInputFiles(duplicated);
    await confirm.click();
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('alert')).not.toHaveCount(0);
    await expect(page.getByText('Arquivo original substituído com sucesso.')).toHaveCount(0);
    await expect(page.getByText('atual.pdf', { exact: true })).not.toHaveCount(0);

    // Sucesso: toast e metadados do novo arquivo.
    await dialog.locator('#arquivo_original').setInputFiles(pdf('novo.pdf', `${marker}-d`));
    await confirm.click();
    await expect(page.getByText('Arquivo original substituído com sucesso.')).toBeVisible();
    await expect(dialog).toBeHidden();
    await expect(page.getByText('novo.pdf', { exact: true })).toBeVisible();
    await expect(page.getByText('Substituição · novo.pdf')).toBeVisible();
    await expect(page.getByText('atual.pdf', { exact: true })).toHaveCount(0);

    // O atalho do formulário de edição abre o modal direto.
    await page.getByRole('link', { name: 'Editar', exact: true }).click();
    await page.getByRole('link', { name: 'Substituir na página da fotografia' }).click();
    await expect(dialog).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(dialog).toBeHidden();

    // No celular, o modal cabe na tela.
    await page.setViewportSize({ width: 390, height: 844 });
    await trigger.click();
    await option.click();
    const box = await dialog.boundingBox();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(390);
    expect(await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(0);
});

for (const mode of ['cadastro', 'edição']) {
    test(`modals preserve photograph fields and select all five catalogs during ${mode}`, async ({ page }) => {
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
        for (const [catalog, field, triggerName] of [
            ['autor', 'Nome', 'Cadastrar novo autor'],
            ['pessoa', 'Nome', 'Cadastrar pessoa'],
            ['categoria', 'Título', 'Criar categoria'],
            ['assunto', 'Título', 'Criar assunto'],
            ['palavra-chave', 'Termo', 'Criar palavra-chave'],
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
            await expect(page.getByTestId(`catalog-picker-${catalog}`).getByRole('button', { name: `Remover ${name}` })).toBeVisible();
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
    await page.goto('/catalogo');
    await page.getByRole('link').filter({ hasText: title }).click();
    const trigger = page.getByRole('button', { name: `Ampliar fotografia: ${title}`, exact: true });
    const viewer = page.getByRole('dialog', { name: title, exact: true });
    await trigger.press('Enter');
    await expect(viewer).toBeVisible();
    await expect(viewer.getByRole('button', { name: 'Fechar visualização' })).toBeFocused();
    await expect.poll(() => viewer.locator('img').evaluate((img) => img.complete && img.naturalWidth > 0)).toBe(true);
    // Zoom pelo teclado (alternativa à roda do mouse): + amplia, 0 volta ao ajuste.
    const zoom = () => viewer.locator('img').evaluate((img) => new DOMMatrix(getComputedStyle(img).transform).a);
    await page.keyboard.press('+');
    await expect.poll(zoom).toBeGreaterThan(1);
    await page.keyboard.press('0');
    await expect.poll(zoom).toBeCloseTo(1, 2);
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
