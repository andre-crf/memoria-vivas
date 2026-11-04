import { existsSync, renameSync } from 'node:fs';
import { hotFileBackupPath, hotFilePath, resetDatabase } from './environment.js';

export default function globalSetup() {
    // Com o Vite de desenvolvimento ligado, o Laravel serviria os assets pelo public/hot,
    // e o CORS do Vite bloqueia a origem dos testes. Os testes usam sempre o build.
    if (existsSync(hotFilePath) && !existsSync(hotFileBackupPath)) {
        renameSync(hotFilePath, hotFileBackupPath);
    }

    resetDatabase();
}
