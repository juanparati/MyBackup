import { ACTIONS, DRIVERS, MAIL_FIELDS, SLACK_FIELDS, type FieldDef } from './blocks.ts';
import { ENV_CASTS, hasEmbeddedEnv } from './env.ts';
import { expandPlaceholders, leftoverPlaceholders } from './placeholders.ts';
import { writtenSecrets } from './secrets.ts';
import { isEnv, isMap, type BackupPlan, type Block, type Value } from './types.ts';

export interface Issue {
    level: 'error' | 'warning';
    /** Model path, also the `data-path` of the form field. */
    path: string;
    message: string;
}

const PERIOD_UNITS = ['minute', 'minutes', 'hour', 'hours', 'day', 'days', 'week', 'weeks', 'month', 'months', 'year', 'years'];

const TIME_PLACEHOLDER = /{{(datetime|timestamp|uuid|date|date_calc|date_format)\b/;

/** Fields where MyBackup replaces {{placeholders}}. */
const PLACEHOLDER_PATHS = [
    /^snapshot_file$/,
    /^databases\.\d+\.tables\.\d+\.where$/,
    /^dump\.(begin|end)$/,
    /^post_actions\.\d+\.(source|destination|command)$/,
];

/** Stand-ins for the values MyBackup adds to the post action placeholders. */
const ACTION_DICTIONARY = { snapshot_file: '/backups/snapshot.sql.gz', crc: 'd41d8cd98f00b204e9800998ecf8427e' };

export function validate(plan: BackupPlan, now = new Date()): Issue[] {
    const issues: Issue[] = [];
    const error = (path: string, message: string) => issues.push({ level: 'error', path, message });
    const warning = (path: string, message: string) => issues.push({ level: 'warning', path, message });

    /** Check the placeholders of a value; returns the expanded value when it could be computed. */
    const checkPlaceholders = (path: string, label: string, value: string, dictionary: Record<string, string> = {}) => {
        if (/{{date_calc:\+/.test(value)) {
            warning(path, `${label}: {{date_calc:+…}} also goes back in time; MyBackup ignores the sign.`);
        }

        const expanded = expandPlaceholders(value, now, dictionary);

        if (expanded.error !== undefined) {
            error(path, `${label}: ${expanded.error}`);

            return;
        }

        for (const leftover of leftoverPlaceholders(expanded.value)) {
            warning(path, `${label}: ${leftover} isn't a placeholder MyBackup knows here; it is kept as text.`);
        }
    };

    // General
    if (plan.catalog_file === '') {
        error('catalog_file', 'Catalog file is required.');
    }

    if (plan.snapshot_file === '') {
        error('snapshot_file', 'Snapshot file is required.');
    } else if (!isEnv(plan.snapshot_file)) {
        const snapshot = plan.snapshot_file;

        if (snapshot.includes('{{snapshot_file}}')) {
            error('snapshot_file', 'Snapshot file: {{snapshot_file}} is only available in post actions.');
        } else {
            checkPlaceholders('snapshot_file', 'Snapshot file', snapshot);
        }

        if (!TIME_PLACEHOLDER.test(snapshot)) {
            warning('snapshot_file', 'Snapshot file has no date/time placeholder: MyBackup refuses to overwrite an existing snapshot, so a second run with the same name fails.');
        }

        const usesLocal = plan.post_actions.some((action) => action.values.filesystem === 'local');

        if (usesLocal && snapshot.slice(0, snapshot.lastIndexOf('/')).includes('{{')) {
            warning('snapshot_file', 'Snapshot file: the "local" filesystem uses the snapshot directory before placeholders are replaced; keep placeholders in the file name only.');
        }
    }

    if (typeof plan.mysqldump_path === 'string') {
        if (plan.mysqldump_path === '') {
            warning('mysqldump_path', 'mysqldump path is empty: MyBackup then runs /usr/bin/mysqldump.');
        } else if (/\s/.test(plan.mysqldump_path)) {
            warning('mysqldump_path', 'mysqldump path contains spaces; MyBackup doesn\'t quote it, so the command breaks.');
        }
    }

    // Connection
    if (plan.connection_env) {
        if (plan.connection_env.cast !== 'json') {
            warning('connection_env', 'Connection from env: use the json cast so the variable is decoded into connection settings.');
        }
    } else {
        const conn = plan.connection;

        if (typeof conn.driver === 'string' && !['', 'mysql', 'mariadb'].includes(conn.driver)) {
            error('connection.driver', `Connection driver "${conn.driver}" isn't supported; use mysql or mariadb.`);
        }

        if (conn.host === '') {
            error('connection.host', 'Connection host is required.');
        }

        if (conn.port === '') {
            error('connection.port', 'Connection port is required.');
        } else if (!isEnv(conn.port) && !/^\d+$/.test(conn.port.trim())) {
            error('connection.port', 'Connection port must be a number.');
        }
    }

    // Rotation and compression
    if (plan.backup_rotation !== null && !isEnv(plan.backup_rotation)) {
        const rotation = plan.backup_rotation.trim();

        if (/^\d+$/.test(rotation)) {
            if (Number(rotation) < 1) {
                error('backup_rotation', 'Backup rotation 0 also deletes the snapshot just created; keep at least 1.');
            }
        } else {
            checkPeriod('backup_rotation', 'Backup rotation', rotation, error, warning);
        }
    }

    if (typeof plan.compress === 'number' && !(Number.isInteger(plan.compress) && plan.compress >= 1 && plan.compress <= 9)) {
        error('compress', 'Compression level must be between 1 and 9.');
    }

    // Databases
    if (!plan.databases.length) {
        warning('databases', 'No databases selected: the snapshot only contains the dump begin/end text.');
    }

    const dbNames = new Set<string>();

    plan.databases.forEach((db, i) => {
        const path = `databases.${i}`;
        const label = `Database "${db.name}"`;

        if (db.name.trim() === '') {
            error(`${path}.name`, `Database #${i + 1}: name is required.`);
        } else if (dbNames.has(db.name)) {
            warning(`${path}.name`, `${label} is listed more than once.`);
        }

        dbNames.add(db.name);

        for (const option of db.options) {
            if (!option.startsWith('-')) {
                warning(`${path}.options`, `${label}: "${option}" doesn't look like a mysqldump option (one option per line).`);
            }
        }

        if (db.tables.length && db.ignore.length) {
            warning(`${path}.ignore`, `${label}: ignored tables only apply when the whole database is dumped; they're ignored when tables are listed.`);
        }

        const tableNames = new Set<string>();

        db.tables.forEach((table, j) => {
            const tablePath = `${path}.tables.${j}`;
            const tableLabel = `${label}, table "${table.name}"`;

            if (table.name.trim() === '') {
                error(`${tablePath}.name`, `${label}: table #${j + 1} needs a name.`);
            } else if (tableNames.has(table.name)) {
                warning(`${tablePath}.name`, `${tableLabel} is listed more than once.`);
            }

            tableNames.add(table.name);

            if (table.where !== '' && table.only_struct === true) {
                warning(`${tablePath}.where`, `${tableLabel}: the where condition is ignored for structure-only tables.`);
            }

            if (typeof table.where === 'string' && table.where !== '') {
                checkPlaceholders(`${tablePath}.where`, `${tableLabel} where`, table.where);
            }
        });
    });

    // Encryption and dump text
    if (plan.encryption.enabled && plan.encryption.key === '') {
        error('encryption.key', 'Encryption key is required when encryption is enabled.');
    }

    for (const part of ['begin', 'end'] as const) {
        for (const line of plan.dump[part]) {
            checkPlaceholders(`dump.${part}`, `Dump ${part} text`, line);
        }
    }

    // Notifications
    const { slack, mail } = plan.notifications;

    if (slack) {
        checkRequired(slack, SLACK_FIELDS, 'notifications.slack', 'Slack', error);

        const webhook = slack.values.webhook;

        if (typeof webhook === 'string' && webhook !== '' && !/^https?:\/\//.test(webhook)) {
            error('notifications.slack.webhook', 'Slack webhook must be an http(s) URL.');
        }
    }

    if (mail) {
        checkRequired(mail, MAIL_FIELDS, 'notifications.mail', 'Mail', error);

        if (mail.values.transport === 'smtp') {
            for (const key of ['host', 'port']) {
                if (isBlank(mail.values[key])) {
                    error(`notifications.mail.${key}`, `Mail: SMTP ${key} is required for the smtp transport.`);
                }
            }
        }

        checkNumbers(mail, MAIL_FIELDS, 'notifications.mail', 'Mail', error);
    }

    for (const key of Object.keys(plan.notifications.extra)) {
        warning('notifications.extra', `Notifications: "${key}" isn't a supported channel and makes MyBackup fail to send notifications.`);
    }

    // Filesystems
    const fsNames = new Set<string>();

    plan.filesystems.forEach((fs, i) => {
        const path = `filesystems.${i}`;
        const label = `Filesystem "${fs.name}"`;

        if (fs.name.trim() === '') {
            error(`${path}.name`, `Filesystem #${i + 1}: name is required.`);
        } else if (fs.name === 'local') {
            error(`${path}.name`, 'Filesystem name "local" is reserved: MyBackup always defines it as the snapshot directory.');
        } else if (fsNames.has(fs.name)) {
            error(`${path}.name`, `${label} is defined more than once.`);
        }

        fsNames.add(fs.name);

        const driver = DRIVERS[fs.driver];

        if (!driver) {
            warning(`${path}.driver`, `${label}: driver "${fs.driver}" isn't one of s3, gcs, ftp, sftp or local.`);

            return;
        }

        checkRequired(fs, driver.fields, path, label, error);
        checkNumbers(fs, driver.fields, path, label, error);

        if (fs.driver === 'gcs') {
            const keyFile = fs.values.key_file;

            if (typeof keyFile === 'string' && keyFile.trim() !== '') {
                try {
                    if (!isMap(JSON.parse(keyFile))) {
                        throw new Error();
                    }
                } catch {
                    error(`${path}.key_file`, `${label}: the service account key must be a JSON object.`);
                }
            } else if (isBlank(keyFile) && isBlank(fs.values.key_file_path)) {
                warning(`${path}.key_file`, `${label}: no key given, so Google's application default credentials are used.`);
            }
        }
    });

    // Post actions
    plan.post_actions.forEach((action, i) => {
        const path = `post_actions.${i}`;
        const definition = ACTIONS[action.type];
        const label = `Post action #${i + 1} (${definition?.label ?? action.type})`;

        if (!definition) {
            warning(`${path}.type`, `${label}: unknown action; MyBackup skips it.`);

            return;
        }

        checkRequired(action, definition.fields, path, label, error);

        const filesystem = action.values.filesystem;

        if (typeof filesystem === 'string' && filesystem !== '' && filesystem !== 'local' && !fsNames.has(filesystem)) {
            error(`${path}.filesystem`, `${label}: filesystem "${filesystem}" isn't defined.`);
        }

        const period = action.values.period;

        if (typeof period === 'string' && period !== '') {
            checkPeriod(`${path}.period`, `${label} period`, period.trim(), error, warning);
        }

        for (const key of ['source', 'destination', 'command']) {
            const value = action.values[key];

            if (typeof value === 'string' && value !== '') {
                checkPlaceholders(`${path}.${key}`, `${label} ${key}`, value, ACTION_DICTIONARY);
            }
        }
    });

    // Every value: env references and placeholders where they aren't replaced
    walk(plan, '', (path, value) => {
        if (isEnv(value)) {
            if (value.env.trim() === '') {
                error(path, `${pathLabel(path)}: environment variable name is required.`);
            } else if (/[\s:)]/.test(value.env)) {
                error(path, `${pathLabel(path)}: environment variable name "${value.env}" can't contain spaces, ":" or ")".`);
            }

            if (value.cast !== undefined && !(ENV_CASTS as readonly string[]).includes(value.cast) && value.cast !== 'integer') {
                warning(path, `${pathLabel(path)}: unknown cast "${value.cast}"; the value is used as is.`);
            }

            return;
        }

        if (hasEmbeddedEnv(value)) {
            warning(path, `${pathLabel(path)}: %env(…)% replaces the whole value, so the text around it is lost.`);
        }

        if (value.includes('{{') && !PLACEHOLDER_PATHS.some((pattern) => pattern.test(path))) {
            warning(path, `${pathLabel(path)}: placeholders aren't replaced in this field.`);
        }
    });

    // Credentials written in the file
    for (const secret of writtenSecrets(plan)) {
        warning(secret.path, secret.isDefault
            ? `${secret.label}: the default of the environment variable is written in plain text.`
            : `${secret.label} is written in plain text; an environment variable keeps it out of the file.`);
    }

    return issues;
}

type Report = (path: string, message: string) => void;

function checkPeriod(path: string, label: string, period: string, error: Report, warning: Report): void {
    const match = /^(\d+) (\S+)$/.exec(period);

    if (!match) {
        error(path, `${label}: use "N unit", e.g. "3 days" (units: minutes, hours, days, weeks, months, years).`);
    } else if (Number(match[1]) < 1) {
        error(path, `${label}: a period of 0 matches every file, including the newest backup.`);
    } else if (!PERIOD_UNITS.includes(match[2].toLowerCase())) {
        warning(path, `${label}: unit "${match[2]}" isn't recognised, so MyBackup counts days.`);
    }
}

function checkRequired(block: Block, fields: FieldDef[], path: string, label: string, error: Report): void {
    for (const field of fields) {
        if (field.required && isBlank(block.values[field.key])) {
            error(`${path}.${field.key}`, `${label}: ${field.label.toLowerCase()} is required.`);
        }
    }
}

function checkNumbers(block: Block, fields: FieldDef[], path: string, label: string, error: Report): void {
    for (const field of fields) {
        const value = block.values[field.key];

        if (field.kind === 'number' && typeof value === 'string' && value !== '' && !/^\d+$/.test(value.trim())) {
            error(`${path}.${field.key}`, `${label}: ${field.label.toLowerCase()} must be a number.`);
        }
    }
}

function isBlank(value: Value<string> | Value<boolean> | undefined): boolean {
    return value === undefined || (typeof value === 'string' && value.trim() === '');
}

/** Visit every text value and env reference with its model path (extra keys are skipped). */
function walk(node: unknown, path: string, visit: (path: string, value: string | Value<string>) => void): void {
    if (isEnv(node) || typeof node === 'string') {
        visit(path, node);
    } else if (Array.isArray(node)) {
        node.forEach((item, i) => walk(item, typeof item === 'string' ? path : `${path}.${i}`, visit));
    } else if (isMap(node)) {
        for (const [key, value] of Object.entries(node)) {
            if (key !== 'extra') {
                walk(value, key === 'values' ? path : path ? `${path}.${key}` : key, visit);
            }
        }
    }
}

/** "databases.0.tables.2.where" → "databases[0].tables[2].where" */
function pathLabel(path: string): string {
    return path.replace(/\.(\d+)/g, '[$1]');
}
