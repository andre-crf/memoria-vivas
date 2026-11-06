import { expect, test } from '@playwright/test';
import { resetDatabase } from './environment.js';

// Referência visual do Style Guide (/admin/_style-guide). Detecta mudanças nos tokens e nos
// componentes reais exibidos nele. Atualizar só depois de revisar as diferenças:
// npx playwright test tests/Browser/style-guide.spec.js --update-snapshots

test.describe.configure({ mode: 'serial' });

const viewports = {
    mobile: { width: 390, height: 844 },
    desktop: { width: 1280, height: 800 },
};

async function login(page) {
    await page.goto('/login');
    await page.getByLabel('E-mail').fill('admin@memorias.test');
    await page.getByLabel('Senha').fill('password');
    await page.getByRole('button', { name: 'Entrar no admin' }).click();
    await expect(page).toHaveURL(/\/admin$/);
}

async function capture(page, name) {
    await page.evaluate(() => document.fonts.ready);
    await page.waitForLoadState('networkidle');
    // Datas e horas mudam a cada execução e, com dígitos de largura variável, mudariam até o tamanho da máscara.
    await page.locator('time, [data-data-hora]').evaluateAll((elements) => elements.forEach((element) => {
        element.textContent = '00/00/0000 00:00';
    }));
    await page.evaluate(() => window.scrollTo(0, 0));
    await expect(page).toHaveScreenshot(`${name}.png`, {
        fullPage: true,
        animations: 'disabled',
        caret: 'hide',
        // Datas e horas mudam a cada execução.
        mask: [page.locator('time, [data-data-hora]')],
    });
}

async function expectNoHorizontalScroll(page) {
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(0);
}

test.beforeAll(() => {
    resetDatabase();
});

for (const [viewportName, viewport] of Object.entries(viewports)) {
    test.describe(viewportName, () => {
        test.use({ viewport });

        test('style guide do admin e do público', async ({ page }) => {
            await login(page);

            await page.goto('/admin/_style-guide');
            await expect(page.getByRole('heading', { level: 1, name: 'Style Guide' })).toBeVisible();
            // Os valores dos tokens são lidos do CSS gerado; "não gerado" indicaria token sem classe.
            await expect(page.getByText('não gerado')).toHaveCount(0);
            await expectNoHorizontalScroll(page);
            await capture(page, `style-guide-admin-${viewportName}`);

            await page.goto('/admin/_style-guide/publico');
            await expect(page.getByRole('heading', { level: 1, name: 'Componentes do site público' })).toBeVisible();
            await expectNoHorizontalScroll(page);
            await capture(page, `style-guide-publico-${viewportName}`);
        });
    });
}

test('foco por teclado, notificações e confirmação usam os componentes reais', async ({ page }) => {
    await page.setViewportSize(viewports.desktop);
    await login(page);
    await page.goto('/admin/_style-guide');

    // Tab a partir do botão do cabeçalho chega ao índice com o contorno de foco padrão do admin.
    await page.getByRole('link', { name: 'Componentes públicos' }).focus();
    await page.keyboard.press('Tab');
    const firstSection = page.getByRole('navigation', { name: 'Seções do Style Guide' }).getByRole('link').first();
    await expect(firstSection).toBeFocused();
    const outline = await firstSection.evaluate((element) => {
        const style = getComputedStyle(element);

        return { style: style.outlineStyle, width: style.outlineWidth };
    });
    expect(outline).toEqual({ style: 'solid', width: '2px' });

    // Enter no índice leva à seção.
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/#cores$/);

    await page.getByRole('button', { name: 'Aviso' }).click();
    await expect(page.locator('[data-toast-type="warning"]')).toContainText('uma versão não foi gerada');

    await page.getByRole('button', { name: 'Variante danger (padrão)' }).click();
    const dialog = page.getByRole('alertdialog');
    await expect(dialog).toBeVisible();
    await expect(dialog.getByRole('button', { name: 'Cancelar' })).toBeFocused();
    await dialog.getByRole('button', { name: 'Excluir registro' }).click();
    await expect(page).toHaveURL(/confirmado=perigo/);
    await expect(page.getByRole('status').filter({ hasText: 'Confirmação recebida (danger).' })).toBeVisible();
});

test('estados dos componentes da Etapa 3 seguem o CSS real', async ({ page }) => {
    await page.setViewportSize(viewports.desktop);
    await login(page);
    await page.goto('/admin/_style-guide');

    const corDanger = await page.evaluate(() => {
        const amostra = document.createElement('span');
        amostra.className = 'text-danger';
        document.body.append(amostra);
        const cor = getComputedStyle(amostra).color;
        amostra.remove();

        return cor;
    });

    // Foco por teclado no botão danger usa a cor de perigo, não a institucional.
    const linhaDanger = page.locator('#botoes tbody tr').filter({ has: page.locator('code', { hasText: /^danger$/ }) });
    await linhaDanger.getByRole('button', { name: 'Salvar' }).first().focus();
    await page.keyboard.press('Shift+Tab');
    await page.keyboard.press('Tab');
    const focoDanger = await page.evaluate(() => {
        const estilo = getComputedStyle(document.activeElement);

        return { cor: estilo.outlineColor, largura: estilo.outlineWidth };
    });
    expect(focoDanger).toEqual({ cor: corDanger, largura: '2px' });

    // Carregamento estático: desabilitado, ocupado, com spinner visível e texto para leitores de tela.
    const carregando = linhaDanger.getByRole('button', { name: /Salvando/ });
    await expect(carregando).toBeDisabled();
    await expect(carregando).toHaveAttribute('aria-busy', 'true');
    await expect(carregando.locator('svg.animate-spin')).toBeVisible();
    await expect(carregando).toHaveAccessibleName(/\(processando\)/);

    // Carregamento dinâmico via x-bind:aria-busy: o spinner só aparece enquanto o valor for true.
    const enviar = page.getByRole('button', { name: /Enviar \(simula 2 s\)/ });
    await expect(enviar.locator('svg.animate-spin')).toBeHidden();
    await enviar.click();
    const enviando = page.getByRole('button', { name: /Enviando/ });
    await expect(enviando).toHaveAttribute('aria-busy', 'true');
    await expect(enviando).toBeDisabled();
    await expect(enviando.locator('svg.animate-spin')).toBeVisible();
    await expect(page.getByRole('button', { name: /Enviar \(simula 2 s\)/ })).toBeEnabled({ timeout: 4000 });

    // Link desabilitado não navega.
    const linkDesabilitado = page.getByRole('link', { name: 'Link desabilitado' });
    await expect(linkDesabilitado).toHaveAttribute('aria-disabled', 'true');
    await expect(linkDesabilitado).not.toHaveAttribute('href', /.*/);

    // Campo inválido: borda de perigo, inclusive com foco.
    const invalido = page.locator('#sg-erro');
    await expect(invalido).toHaveCSS('border-top-color', corDanger);
    await invalido.focus();
    await expect(invalido).toHaveCSS('border-top-color', corDanger);
    await expect(page.locator('#sg-desabilitado')).toBeDisabled();
    await expect(page.locator('#sg-desabilitado')).toHaveCSS('cursor', 'not-allowed');
});
