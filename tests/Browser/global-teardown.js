import { existsSync, renameSync } from 'node:fs';
import { hotFileBackupPath, hotFilePath } from './environment.js';

export default function globalTeardown() {
    if (existsSync(hotFileBackupPath) && !existsSync(hotFilePath)) {
        renameSync(hotFileBackupPath, hotFilePath);
    }
}
