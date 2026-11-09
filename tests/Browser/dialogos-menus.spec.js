import { expect, test } from '@playwright/test';

// Diálogos e menus do admin (Etapa 6B): apresentação comum (.admin-dialog, .admin-menu) com o funcionamento
// de antes. Complementa hybrid-flows.spec.js, que cobre o envio da confirmação, o cadastro rápido com
// sucesso e a substituição do original de ponta a ponta. Cada teste cria os próprios dados.

test.describe.configure({ mode: 'serial' });

const unique = (prefix) => `${prefix} ${Date.now()}-${Math.random().toString(16).slice(2)}`;
const pdf = (name, marker) => ({ name, mimeType: 'application/pdf', buffer: Buffer.from(`%PDF-1.4\n% ${marker}\n%%EOF`) });
const viewports = { mobile: { width: 390, height: 844 }, desktop: { width: 1280, height: 800 } };

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('admin@memorias.test');
    await page.getByLabel('Senha', { exact: true }).fill('password');
    await page.getByRole('button', { name: 'Entrar no admin' }).click();
    await expect(page).toHaveURL(/\/admin$/);
}

async function createCategory(page, title) {
    await page.goto('/admin/categorias/create');
    await page.getByLabel(/Título/).fill(title);
    await page.getByRole('button', { name: 'Cadastrar categoria' }).click();
    await expect(page).toHaveURL(/\/admin\/categorias$/);
}

async function createPhotographWithOriginal(page, title, file) {
    await page.goto('/admin/fotografias/create');
    await page.getByLabel(/Título obrigatório/).fill(title);
    await page.locator('#arquivo_original').setInputFiles(file);
    await page.getByRole('button', { name: 'Salvar fotografia' }).click();
    await expect(page).toHaveURL(/\/admin\/fotografias$/);
}

const cssColor = (page, className) => page.evaluate((name) => {
    const amostra = document.createElement('span');
    amostra.className = name;
    document.body.append(amostra);
    const cor = getComputedStyle(amostra).color;
    amostra.remove();

    return cor;
}, className);

// O diálogo cabe na tela, com a mesma casca, e não cria rolagem horizontal na página.
async function expectDialogShell(page, dialog) {
    const caixa = await dialog.boundingBox();
    const largura = page.viewportSize().width;
    expect(caixa.x).toBeGreaterThanOrEqual(0);
    expect(caixa.x + caixa.width).toBeLessThanOrEqual(largura);
    await expect(dialog).toHaveClass(/admin-dialog/);
    expect(await dialog.evaluate((el) => getComputedStyle(el).boxShadow)).not.toBe('none');
    expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(0);
}

test.beforeEach(async ({ page }) => {
    await login(page);
});

for (const [nome, viewport] of Object.entries(viewports)) {
    test(`confirmação: variantes, Escape, Tab e foco de volta (${nome})`, async ({ page }) => {
        test.setTimeout(60_000);
        await page.setViewportSize(viewport);
        const categoria = unique('Categoria diálogo E2E');
        await createCategory(page, categoria);
        await page.goto(`/admin/categorias?q=${encodeURIComponent(categoria)}`);

        const excluir = page.locator('tbody tr').filter({ hasText: categoria }).getByRole('button', { name: 'Excluir' });
        const dialog = page.getByRole('alertdialog');
        await excluir.click();
        await expect(dialog).toBeVisible();
        await expectDialogShell(page, dialog);
        await expect(dialog.getByRole('heading', { name: 'Confirmar exclusão' })).toBeVisible();

        // Destrutiva: vermelho cheio; a variante primária fica oculta e fora da árvore de acessibilidade.
        const confirmar = dialog.getByRole('button', { name: 'Excluir categoria' });
        await expect(confirmar).toHaveCount(1);
        await expect(confirmar).toHaveCSS('background-color', await cssColor(page, 'text-danger'));
        await expect(dialog.getByRole('button', { name: 'Cancelar' })).toBeFocused();

        // Tab e Shift+Tab entre as duas ações, com contorno de foco visível.
        await page.keyboard.press('Tab');
        await expect(confirmar).toBeFocused();
        await expect(confirmar).toHaveCSS('outline-style', 'solid');
        await page.keyboard.press('Shift+Tab');
        await expect(dialog.getByRole('button', { name: 'Cancelar' })).toBeFocused();

        // Escape fecha sem enviar e devolve o foco a quem abriu.
        await page.keyboard.press('Escape');
        await expect(dialog).toBeHidden();
        await expect(excluir).toBeFocused();
        await expect(page.locator('tbody tr').filter({ hasText: categoria })).toHaveCount(1);
    });
}

test('confirmação primária na lixeira usa o botão institucional e restaura de verdade', async ({ page }) => {
    test.setTimeout(60_000);
    const titulo = unique('Fotografia lixeira E2E');
    await createPhotographWithOriginal(page, titulo, pdf('lixeira.pdf', titulo));

    // Excluir pela listagem (confirmação destrutiva) e restaurar pela lixeira (confirmação primária).
    await page.goto(`/admin/fotografias?q=${encodeURIComponent(titulo)}`);
    await page.locator('tbody tr').filter({ hasText: titulo }).getByRole('button', { name: 'Excluir' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Excluir fotografia' }).click();
    await expect(page.locator('[data-toast-type="success"]')).toBeVisible();

    await page.goto(`/admin/fotografias/lixeira?q=${encodeURIComponent(titulo)}`);
    await page.locator('tbody tr').filter({ hasText: titulo }).getByRole('button', { name: 'Restaurar' }).click();
    const dialog = page.getByRole('alertdialog');
    const restaurar = dialog.getByRole('button', { name: 'Restaurar fotografia' });
    await expect(restaurar).toHaveCount(1);
    await expect(restaurar).toHaveCSS('background-color', await cssColor(page, 'text-institutional'));
    await restaurar.click();
    await expect(page).toHaveURL(/\/admin\/fotografias\/lixeira/);
    await expect(page.locator('tbody tr').filter({ hasText: titulo })).toHaveCount(0);
});

for (const [nome, viewport] of Object.entries(viewports)) {
    test(`cadastro rápido: erro de validação ligado ao campo, X e Escape devolvem o foco (${nome})`, async ({ page }) => {
        test.setTimeout(60_000);
        await page.setViewportSize(viewport);
        await page.goto('/admin/fotografias/create');

        const picker = page.getByTestId('catalog-picker-categoria');
        const abrir = picker.getByRole('button', { name: 'Criar categoria' });
        const dialog = page.getByRole('dialog', { name: 'Criar categoria', exact: true });
        await abrir.click();
        await expect(dialog).toBeVisible();
        await expectDialogShell(page, dialog);
        const campo = dialog.getByLabel('Título');
        await expect(campo).toBeFocused();

        // Envio vazio: o Livewire devolve o erro, que marca o campo e é a descrição dele.
        await campo.fill('');
        await dialog.getByRole('button', { name: 'Criar e selecionar' }).click();
        const erro = dialog.getByRole('alert');
        await expect(erro).toBeVisible();
        await expect(campo).toHaveAttribute('aria-invalid', 'true');
        await expect(campo).toHaveAccessibleDescription((await erro.textContent()).trim());
        await expect(campo).toHaveCSS('border-top-color', await cssColor(page, 'text-danger'));
        await expect(dialog).toBeVisible();

        // Fechar pelo X (nome acessível próprio) devolve o foco ao botão que abriu.
        await dialog.getByRole('button', { name: 'Fechar cadastro' }).click();
        await expect(dialog).toBeHidden();
        await expect(abrir).toBeFocused();

        // Reabrir e fechar com Escape também devolve o foco; o diálogo continua teleportado para o body.
        await abrir.click();
        await expect(dialog).toBeVisible();
        expect(await dialog.evaluate((el) => el.parentElement === document.body)).toBe(true);
        await page.keyboard.press('Escape');
        await expect(dialog).toBeHidden();
        await expect(abrir).toBeFocused();
    });
}

for (const [nome, viewport] of Object.entries(viewports)) {
    test(`menu do usuário: aria-expanded, Tab, Escape e clique fora (${nome})`, async ({ page }) => {
        await page.setViewportSize(viewport);
        await page.goto('/admin/categorias');

        const gatilho = page.locator('button[aria-controls="admin-user-menu"]');
        const menu = page.locator('#admin-user-menu');
        await expect(gatilho).toHaveAttribute('aria-expanded', 'false');
        await gatilho.click();
        await expect(menu).toBeVisible();
        await expect(gatilho).toHaveAttribute('aria-expanded', 'true');
        await expect(menu).toHaveClass(/admin-menu/);
        const caixa = await menu.boundingBox();
        expect(caixa.x).toBeGreaterThanOrEqual(0);
        expect(caixa.x + caixa.width).toBeLessThanOrEqual(viewport.width);

        await page.keyboard.press('Tab');
        const perfil = menu.getByRole('link', { name: 'Acessar meu perfil' });
        await expect(perfil).toBeFocused();
        await expect(perfil).toHaveCSS('outline-style', 'solid');
        await page.keyboard.press('Escape');
        await expect(menu).toBeHidden();
        await expect(gatilho).toBeFocused();
        await expect(gatilho).toHaveAttribute('aria-expanded', 'false');

        // Clique fora: no canto inferior esquerdo, longe do menu (no celular ele cobre o título da página).
        await gatilho.click();
        await page.mouse.click(4, viewport.height - 4);
        await expect(menu).toBeHidden();

        // Sair continua sendo o envio do formulário de logout.
        await gatilho.click();
        await menu.getByRole('button', { name: 'Sair' }).click();
        await expect(page).toHaveURL(/\/login$/);
    });
}

test('substituição do original: X descarta a seleção, foco volta ao menu e o modal reabre vazio', async ({ page }) => {
    test.setTimeout(60_000);
    for (const [nome, viewport] of Object.entries(viewports)) {
        await page.setViewportSize(viewport);
        const titulo = unique(`Fotografia modal ${nome}`);
        await createPhotographWithOriginal(page, titulo, pdf('atual.pdf', titulo));
        await page.locator('tr').filter({ hasText: titulo }).getByRole('link', { name: titulo }).click();

        const gatilho = page.getByRole('button', { name: 'Ações do arquivo' });
        const opcao = page.getByRole('button', { name: 'Substituir imagem' });
        const dialog = page.getByRole('dialog', { name: 'Substituir imagem' });
        const confirmar = dialog.getByRole('button', { name: 'Confirmar substituição' });

        // Menu com a superfície comum; Escape fecha e devolve o foco.
        await gatilho.click();
        await expect(page.locator('#arquivo-acoes')).toHaveClass(/admin-menu/);
        await expect(gatilho).toHaveAttribute('aria-expanded', 'true');
        await page.keyboard.press('Escape');
        await expect(gatilho).toBeFocused();
        await expect(gatilho).toHaveAttribute('aria-expanded', 'false');

        await gatilho.click();
        await opcao.click();
        await expect(dialog).toBeVisible();
        await expectDialogShell(page, dialog);
        await dialog.locator('#arquivo_original').setInputFiles(pdf('novo.pdf', `${titulo}-novo`));
        await expect(dialog.getByTestId('file-preview')).toBeVisible();
        await expect(confirmar).toBeEnabled();

        // O botão X é o mesmo close() do Alpine: descarta a seleção (clear() do [data-dropzone]).
        await dialog.getByRole('button', { name: 'Fechar' }).click();
        await expect(dialog).toBeHidden();
        await expect(gatilho).toBeFocused();

        await gatilho.click();
        await opcao.click();
        await expect(dialog).toBeVisible();
        await expect(dialog.getByTestId('file-preview')).toBeHidden();
        await expect(confirmar).toBeDisabled();
        await page.keyboard.press('Escape');
        await expect(dialog).toBeHidden();
    }
});
