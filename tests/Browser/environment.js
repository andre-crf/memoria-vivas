import os from 'node:os';
import path from 'node:path';

export const databasePath = path.join(os.tmpdir(), 'memorias-vivas-e2e.sqlite');

export const e2eEnvironment = {
    APP_ENV: 'e2e',
    APP_KEY: 'base64:BwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwcHBwc=',
    APP_URL: 'http://127.0.0.1:8011',
    APP_DEBUG: 'true',
    APP_DISPLAY_TIMEZONE: 'America/Sao_Paulo',
    BCRYPT_ROUNDS: '4',
    CACHE_STORE: 'array',
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: databasePath,
    DB_URL: '',
    FILESYSTEM_DISK: 'local',
    LOG_CHANNEL: 'stderr',
    MAIL_MAILER: 'array',
    QUEUE_CONNECTION: 'sync',
    SESSION_DRIVER: 'file',
};
