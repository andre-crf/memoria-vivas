import { defineConfig } from '@playwright/test';
import { e2eEnvironment } from './tests/Browser/environment.js';

export default defineConfig({
    testDir: './tests/Browser',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list'], ['html', { open: 'never' }]],
    globalSetup: './tests/Browser/global-setup.js',
    use: {
        baseURL: e2eEnvironment.APP_URL,
        locale: 'pt-BR',
        timezoneId: 'America/Sao_Paulo',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    webServer: {
        command: 'php artisan serve --host=127.0.0.1 --port=8011 --no-reload',
        url: e2eEnvironment.APP_URL,
        env: e2eEnvironment,
        reuseExistingServer: false,
        timeout: 30_000,
    },
});
