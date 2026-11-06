import { expect, test } from '@playwright/test';

// Etapa 6C: erros ligados aos campos (nome e descrição acessíveis calculados pelo navegador), estado inválido
// e foco público nos formulários de fotografia, colaboração e download. A validação do navegador é
// desligada (noValidate) só para provocar as mensagens do servidor; nomes, rotas e envio são os de sempre.

test.describe.configure({ mode: 'serial' });

const viewports = { mobile: { width: 390, height: 844 }, desktop: { width: 1280, height: 800 } };

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('admin@memorias.test');
    await page.getByLabel('Senha').fill('password');
    await page.getByRole('button', { name: 'Entrar no admin' }).click();
    await expect(page).toHaveURL(/\/admin$/);
}

const cssColor = (page, className) => page.evaluate((name) => {
    const amostra = document.createElement('span');
    amostra.className = name;
    document.body.append(amostra);
    const cor = getComputedStyle(amostra).color;
    amostra.remove();

    return cor;
}, className);

async function expectNoHorizontalScroll(page) {
    expect(await page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(0);
}

for (const [nome, viewport] of Object.entries(viewports)) {
    test(`fotografia: a mensagem descreve o campo e fica fora do nome (${nome})`, async ({ page }) => {
        await page.setViewportSize(viewport);
        await login(page);
        await page.goto('/admin/fotografias/create');
        await page.locator('main form[enctype="multipart/form-data"]').evaluate((form) => { form.noValidate = true; });
        await page.locator('#codigo_referencia').fill('***');
        await page.locator('#legenda').fill('Legenda preservada');
        await page.getByRole('button', { name: 'Salvar fotografia' }).click();

        await expect(page.locator('main [role="alert"]:not([x-show])')).toContainText('Revise os campos destacados.');
        const titulo = page.locator('#titulo');
        await expect(titulo).toHaveAttribute('aria-invalid', 'true');
        await expect(titulo).toHaveAccessibleName('Título obrigatório');
        await expect(titulo).toHaveAccessibleDescription(/.+/);
        await expect(titulo).toHaveCSS('border-top-color', await cssColor(page, 'text-danger'));

        // A dica continua descrevendo o código de referência, seguida da mensagem.
        const codigo = page.locator('#codigo_referencia');
        await expect(codigo).toHaveAccessibleName('Código de referência');
        await expect(codigo).toHaveAccessibleDescription(/^Identificação utilizada pela instituição\..+/);
        await expect(codigo).toHaveValue('***');
        await expect(page.locator('#legenda')).toHaveValue('Legenda preservada');
        await expectNoHorizontalScroll(page);
    });
}

for (const [nome, viewport] of Object.entries(viewports)) {
    test(`colaboração: erros do servidor, borda inválida e foco público (${nome})`, async ({ page }) => {
        await page.setViewportSize(viewport);
        await page.goto('/colaborar');
        await expectNoHorizontalScroll(page);

        const historia = page.locator('#contribution-story');
        await historia.focus();
        await page.keyboard.press('Shift+Tab');
        await page.keyboard.press('Tab');
        await expect(historia).toHaveCSS('outline-width', '3px');
        await expect(historia).toHaveCSS('outline-offset', '3px');

        const form = page.locator('form[action$="/colaborar"]');
        await form.evaluate((elemento) => { elemento.noValidate = true; });
        await page.locator('#contribution-name').fill('Maria Silva');
        await page.locator('#contribution-email').fill('maria@example.com');
        await page.locator('#contribution-place').evaluate((campo) => { campo.removeAttribute('maxlength'); campo.value = 'a'.repeat(181); });
        await page.getByRole('button', { name: /Enviar fotografias/ }).click();

        await expect(page.getByRole('alert').filter({ hasText: 'Confira os campos indicados abaixo.' })).toBeVisible();
        for (const [id, rotulo] of [['contribution-title-input', 'Título ou assunto'], ['contribution-place', 'Local retratado opcional']]) {
            const campo = page.locator(`#${id}`);
            await expect(campo).toHaveAttribute('aria-invalid', 'true');
            await expect(campo).toHaveAccessibleName(rotulo);
            await expect(campo).toHaveAccessibleDescription(/.+/);
            await expect(campo).toHaveCSS('border-top-color', await cssColor(page, 'text-error-strong'));
        }
        await expect(page.locator('#contribution-name')).toHaveValue('Maria Silva');
        await expectNoHorizontalScroll(page);
    });
}

test('download: campos, foco no select e voltar à fotografia', async ({ page }) => {
    for (const [nome, viewport] of Object.entries(viewports)) {
        await page.setViewportSize(viewport);
        await page.goto('/catalogo');
        const fotografia = page.locator('main ul[aria-label="Fotografias do acervo"] a').first();
        test.skip(await fotografia.count() === 0, 'Sem fotografia pública no banco de e2e');
        const detalhe = await fotografia.getAttribute('href');
        await page.goto(detalhe);
        const baixar = page.getByRole('link', { name: /Baixar/ }).first();
        test.skip(await baixar.count() === 0, 'Fotografia pública sem versão para download');
        await baixar.click();

        await expect(page.getByRole('heading', { level: 1, name: 'Baixar fotografia' })).toBeVisible();
        await expectNoHorizontalScroll(page);
        const perfil = page.locator('#download-profile');
        await perfil.focus();
        await page.keyboard.press('Shift+Tab');
        await page.keyboard.press('Tab');
        await expect(perfil).toHaveCSS('outline-width', '3px');

        await page.locator('form[action$="/baixar"]').evaluate((form) => { form.noValidate = true; });
        await page.locator('#download-name').fill('Ana Silva');
        await page.getByRole('button', { name: /Confirmar e baixar/ }).click();
        await expect(page.getByRole('alert').filter({ hasText: 'Confira os campos indicados antes de continuar.' })).toBeVisible();
        await expect(page.locator('#download-email')).toHaveAttribute('aria-invalid', 'true');
        await expect(page.locator('#download-email')).toHaveAccessibleDescription(/.+/);
        await expect(page.locator('#download-name')).toHaveValue('Ana Silva');

        await page.getByRole('link', { name: 'Voltar à fotografia' }).click();
        await expect(page).toHaveURL(/\/fotografias\/\d+/);
        console.log(nome, 'download verificado');
    }
});

// Etapa 7: login no padrão do admin, contador da legenda, consentimento e títulos longos nas tabelas.

for (const [nome, viewport] of Object.entries(viewports)) {
    test(`login: erro em português ligado ao campo (${nome})`, async ({ page }) => {
        await page.setViewportSize(viewport);
        await page.goto('/login');
        await expectNoHorizontalScroll(page);
        await page.getByLabel('E-mail').fill('ninguem@example.test');
        await page.getByLabel('Senha').fill('senha-errada');
        await page.getByRole('button', { name: 'Entrar no admin' }).click();

        const email = page.locator('#email');
        await expect(email).toHaveAttribute('aria-invalid', 'true');
        await expect(email).toHaveAccessibleName('E-mail');
        await expect(email).toHaveAccessibleDescription('As credenciais informadas não conferem ou o usuário está inativo.');
        await expect(email).toHaveValue('ninguem@example.test');
        await expect(email).toHaveCSS('border-top-color', await cssColor(page, 'text-danger'));
        await expectNoHorizontalScroll(page);
    });
}

test('fotografia: contador da legenda fora do nome, como descrição que acompanha a digitação', async ({ page }) => {
    await login(page);
    await page.goto('/admin/fotografias/create');
    const legenda = page.locator('#legenda');
    await expect(legenda).toHaveAccessibleName('Legenda/descrição');
    await expect(legenda).toHaveAccessibleDescription('0/5.000');
    await legenda.fill('Praça');
    await expect(page.locator('#legenda-contador')).toHaveText('5/5.000');
    await expect(legenda).toHaveAccessibleDescription('5/5.000');
});

test('colaboração: mensagem do consentimento logo abaixo da caixa de seleção', async ({ page }) => {
    await page.goto('/colaborar');
    await page.locator('form[action$="/colaborar"]').evaluate((form) => { form.noValidate = true; });
    await page.getByRole('button', { name: /Enviar fotografias/ }).click();

    const caixa = page.locator('#contribution-consent');
    const mensagem = page.locator('#contribution-consent-error');
    await expect(caixa).toHaveAccessibleDescription('Confirme que você tem permissão para compartilhar as fotografias.');
    const distancia = (await mensagem.boundingBox()).y - ((await caixa.boundingBox()).y + (await caixa.boundingBox()).height);
    expect(distancia).toBeLessThan(40);
});

for (const [nome, viewport] of Object.entries(viewports)) {
    test(`tabela: título longo em uma linha, com reticências e sem rolagem da página (${nome})`, async ({ page }) => {
        await page.setViewportSize(viewport);
        await login(page);
        const titulo = `Inauguração da Praça Arthur Thomas com desfile das escolas municipais ${Date.now()}`;
        await page.goto('/admin/categorias/create');
        await page.getByLabel(/Título/).fill(titulo);
        await page.getByRole('button', { name: 'Cadastrar categoria' }).click();
        await page.goto(`/admin/categorias?q=${encodeURIComponent(titulo)}`);

        const celula = page.locator('tbody tr').first().locator('p.truncate');
        const medida = await celula.evaluate((p) => ({ cortado: p.scrollWidth > p.clientWidth, linhas: Math.round(p.getBoundingClientRect().height / parseFloat(getComputedStyle(p).lineHeight)) }));
        expect(medida).toEqual({ cortado: true, linhas: 1 });
        await expect(page.locator('tbody tr').first().getByRole('link', { name: titulo })).toBeVisible();
        await expectNoHorizontalScroll(page);
    });
}
