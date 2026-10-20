import { expect, test } from '@playwright/test';
import { resetDatabase } from './environment.js';

// Capturas de referência para a migração do CSS para Tailwind.
// Atualizar com: npx playwright test tests/Browser/visual.spec.js --update-snapshots
// (só depois de revisar as diferenças no relatório).

test.describe.configure({ mode: 'serial' });

const viewports = {
    mobile: { width: 390, height: 844 },
    desktop: { width: 1280, height: 800 },
};

const publicPages = {
    'publico-home': '/publico',
    'publico-catalogo': '/publico/catalogo',
    'publico-fotografia': '/publico/fotografias/1',
    'publico-colaborar': '/publico/colaborar',
    'publico-sobre': '/publico/sobre',
    'publico-contato': '/publico/contato',
    'publico-404': '/publico/nao-existe',
    login: '/login',
};

const adminPages = {
    'admin-dashboard': '/admin',
    'admin-categorias': '/admin/categorias',
    'admin-categorias-create': '/admin/categorias/create',
    'admin-autores': '/admin/autores',
    'admin-usuarios-edit': '/admin/usuarios/1/edit',
    'admin-fotografias': '/admin/fotografias',
    'admin-fotografias-show': '/admin/fotografias/4',
    'admin-fotografias-edit': '/admin/fotografias/4/edit',
    'admin-fotografias-lixeira': '/admin/fotografias/lixeira',
    'admin-contribuicoes': '/admin/contribuicoes',
    'admin-contribuicoes-show': '/admin/contribuicoes/1',
    'admin-auditoria': '/admin/auditoria',
    'admin-auditoria-show': '/admin/auditoria/1',
    'admin-perfil': '/admin/perfil',
};

async function capture(page, name) {
    await page.evaluate(() => document.fonts.ready);
    // Espera as imagens terminarem (ou falharem) para o fallback "Imagem indisponível" já estar na tela.
    await page.waitForLoadState('networkidle');
    await page.waitForFunction(() => [...document.images].every((image) => {
        // Imagens lazy fora da tela ou escondidas (ex.: dentro de um <dialog> fechado) nunca carregam.
        const outsideViewport = image.getBoundingClientRect().top > window.innerHeight;

        return image.complete || (image.loading === 'lazy' && (outsideViewport || !image.checkVisibility()));
    }));
    await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
    // O header do admin é fixo: com a página rolada, ele apareceria no meio da captura.
    await page.evaluate(() => window.scrollTo(0, 0));
    await expect(page).toHaveScreenshot(`${name}.png`, {
        fullPage: true,
        animations: 'disabled',
        caret: 'hide',
        // Datas e horas mudam a cada execução.
        mask: [page.locator('time, [data-data-hora]')],
    });
}

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('admin@memorias.test');
    await page.getByLabel('Senha').fill('password');
    await page.getByRole('button', { name: 'Entrar no admin' }).click();
    await expect(page).toHaveURL(/\/admin$/);
}

test.beforeAll(() => {
    resetDatabase(['E2eVisualSeeder']);
});

for (const [viewportName, viewport] of Object.entries(viewports)) {
    test.describe(viewportName, () => {
        test.use({ viewport });

        test('páginas públicas', async ({ page }) => {
            for (const [name, url] of Object.entries(publicPages)) {
                await page.goto(url);
                await capture(page, `${name}-${viewportName}`);
            }

            await page.goto('/publico/catalogo?q=inexistente');
            await capture(page, `publico-catalogo-vazio-${viewportName}`);

            await page.goto(`/publico/catalogo?q=${'a'.repeat(201)}`);
            await capture(page, `publico-busca-erro-${viewportName}`);

            await page.goto('/publico/contato');
            if (viewportName === 'mobile') {
                await page.getByTestId('public-menu').locator('summary').click();
                await capture(page, `publico-menu-aberto-${viewportName}`);
            }
        });

        test('páginas do admin', async ({ page }) => {
            await login(page);

            for (const [name, url] of Object.entries(adminPages)) {
                await page.goto(url);
                await capture(page, `${name}-${viewportName}`);
            }

            if (viewportName === 'mobile') {
                await page.goto('/admin/categorias');
                await page.getByText('Menu', { exact: true }).click();
                await capture(page, `admin-menu-aberto-${viewportName}`);
            }
        });

        test('formulário de fotografia aberto', async ({ page }) => {
            await login(page);
            await page.goto('/admin/fotografias/create');

            await page.locator('form details').evaluateAll((sections) => sections.forEach((section) => {
                section.open = true;
            }));
            await capture(page, `admin-fotografias-create-aberto-${viewportName}`);

            // Sem a validação nativa, o envio chega ao servidor e os erros do Laravel aparecem.
            await page.locator('form:has(button:text("Salvar fotografia"))').evaluate((form) => {
                form.noValidate = true;
            });
            await page.getByRole('button', { name: 'Salvar fotografia' }).click();
            await expect(page.getByText(/obrigatório|required/i).first()).toBeVisible();
            await capture(page, `admin-fotografias-create-erros-${viewportName}`);

            await page.goto('/admin/fotografias/create');
            await page.getByText('Classificações', { exact: true }).click();
            await page.getByRole('button', { name: '+ Criar categoria', exact: true }).click();
            const dialog = page.getByRole('dialog');
            await expect(dialog).toBeVisible();
            await dialog.getByRole('button', { name: 'Criar e selecionar' }).click();
            await expect(dialog.getByRole('alert').first()).toBeVisible();
            await expect(page).toHaveScreenshot(`admin-cadastro-rapido-${viewportName}.png`, {
                animations: 'disabled',
                caret: 'hide',
            });
        });
    });
}
