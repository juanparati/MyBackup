import type { BackupPlan, Connection, Database, Table } from './types.ts';

/** The only method MyBackup's encrypted writer supports. */
export const ENCRYPTION_METHOD = 'AES-128-CBC';

export function emptyConnection(): Connection {
    return { driver: '', host: '', port: '', username: '', password: null, extra: {} };
}

/** A plan with nothing set: the base that imports are applied on. */
export function emptyPlan(): BackupPlan {
    return {
        name: '',
        catalog_file: '',
        snapshot_file: '',
        connection: emptyConnection(),
        connection_env: null,
        mysqldump_path: '',
        backup_rotation: null,
        compress: false,
        is_replica: false,
        databases: [],
        encryption: { enabled: false, key: '', extra: {} },
        dump: { begin: [], end: [] },
        notifications: { slack: null, mail: null, extra: {} },
        filesystems: [],
        post_actions: [],
        extra: {},
    };
}

/** Same defaults as `mybackup init` (Config::defaultConfig()). */
export function initPlan(): BackupPlan {
    const plan = emptyPlan();

    plan.name = 'Backup plan';
    plan.catalog_file = 'catalog.sqlite';
    plan.snapshot_file = 'snapshot_{{numeric:{{datetime}}}}.sql';
    plan.connection = { driver: 'mysql', host: 'localhost', port: '3306', username: 'root', password: '', extra: {} };
    plan.mysqldump_path = 'mysqldump';
    plan.backup_rotation = '1';

    return plan;
}

export function newDatabase(name = ''): Database {
    return { name, options: [], ignore: [], to: '', tables: [], extra: {} };
}

export function newTable(name = ''): Table {
    return { name, where: '', only_struct: false, extra: {} };
}
