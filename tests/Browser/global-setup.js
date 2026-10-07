import { execFileSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';
import { databasePath, e2eEnvironment } from './environment.js';

export default function globalSetup() {
    writeFileSync(databasePath, '');

    execFileSync('php', ['artisan', 'migrate:fresh', '--force', '--seed'], {
        cwd: process.cwd(),
        env: { ...process.env, ...e2eEnvironment },
        stdio: 'inherit',
    });
}
