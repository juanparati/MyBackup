import { parseDocument } from 'yaml';
import { ACTIONS, DRIVERS, MAIL_FIELDS, SLACK_FIELDS, type FieldDef } from './blocks.ts';
import { ENCRYPTION_METHOD, emptyConnection, emptyPlan, newDatabase, newTable } from './defaults.ts';
import { parseEnv } from './env.ts';
import { isMap, type BackupPlan, type Block, type Connection, type Database, type FieldValues, type Table, type Value } from './types.ts';

export class YamlError extends Error {}

export interface ImportResult {
    plan: BackupPlan;
    warnings: string[];
}

type Map = Record<string, unknown>;

const KNOWN_KEYS = [
    'name', 'catalog_file', 'snapshot_file', 'connection', 'mysqldump_path', 'backup_rotation', 'compress',
    'is_replica', 'databases', 'encryption', 'dump', 'notifications', 'filesystems', 'post_actions',
];

/** Parse a YAML backup plan into the form model. Throws YamlError when it can't be read. */
export function fromYaml(text: string): ImportResult {
    const doc = parseDocument(text, { merge: true });

    if (doc.errors.length) {
        throw new YamlError(doc.errors[0].message);
    }

    const data: unknown = doc.toJS({ maxAliasCount: -1 });

    if (!isMap(data)) {
        throw new YamlError('A backup plan must be a YAML mapping (key: value pairs).');
    }

    return new Importer().plan(data);
}

class Importer {
    warnings: string[] = [];

    plan(data: Map): ImportResult {
        const src = { ...data };
        const plan = emptyPlan();

        plan.name = this.scalar(src, 'name', 'name');
        plan.catalog_file = this.scalar(src, 'catalog_file', 'catalog_file');
        plan.snapshot_file = this.scalar(src, 'snapshot_file', 'snapshot_file');
        this.connection(src, plan);
        plan.mysqldump_path = this.scalar(src, 'mysqldump_path', 'mysqldump_path');
        plan.backup_rotation = this.rotation(src);
        plan.compress = this.compress(src);
        plan.is_replica = this.bool(src, 'is_replica', 'is_replica') ?? false;
        plan.databases = this.databases(take(src, 'databases'));
        plan.encryption = this.encryption(take(src, 'encryption'));
        plan.dump = this.dump(take(src, 'dump'));
        plan.notifications = this.notifications(take(src, 'notifications'));
        plan.filesystems = this.filesystems(take(src, 'filesystems'));
        plan.post_actions = this.postActions(take(src, 'post_actions'));
        plan.extra = src;

        for (const key of Object.keys(src).filter((k) => !KNOWN_KEYS.includes(k))) {
            this.warn(`Unknown key "${key}" is kept as is (MyBackup ignores it).`);
        }

        return { plan, warnings: this.warnings };
    }

    warn(message: string): void {
        this.warnings.push(message);
    }

    /**
     * Take a single value from `src`. Lists and maps can't be edited as text,
     * so they are put back into `src` (and end up in the block's extra keys).
     */
    scalar(src: Map, key: string, path: string): Value<string> {
        const value = take(src, key);

        if (value === undefined || value === null) {
            return '';
        }

        if (typeof value === 'string') {
            return parseEnv(value) ?? value;
        }

        if (typeof value === 'number' || typeof value === 'boolean') {
            return String(value);
        }

        src[key] = value;
        this.warn(`${path}: expected a single value but found a ${describe(value)}; it is kept as an extra key.`);

        return '';
    }

    /** Booleans follow PHP truthiness, since quoted strings like "false" are truthy for MyBackup. */
    bool(src: Map, key: string, path: string): Value<boolean> | undefined {
        const value = take(src, key);

        if (value === undefined || value === null) {
            return undefined;
        }

        if (typeof value === 'boolean') {
            return value;
        }

        if (typeof value === 'number') {
            return value !== 0;
        }

        if (typeof value === 'string') {
            const env = parseEnv(value);

            if (env) {
                return env;
            }

            const truthy = value !== '' && value !== '0';
            this.warn(`${path}: the string "${value}" counts as ${truthy}; it is exported as the boolean ${truthy}.`);

            return truthy;
        }

        src[key] = value;
        this.warn(`${path}: expected true or false but found a ${describe(value)}; it is kept as an extra key.`);

        return undefined;
    }

    connection(src: Map, plan: BackupPlan): void {
        const value = take(src, 'connection');

        if (value === undefined || value === null) {
            return;
        }

        const env = typeof value === 'string' ? parseEnv(value) : null;

        if (env) {
            plan.connection_env = env;

            return;
        }

        if (!isMap(value)) {
            src.connection = value;
            this.warn(`connection: expected a map but found a ${describe(value)}; it is kept as an extra key.`);

            return;
        }

        const conn = { ...value };
        const connection: Connection = emptyConnection();

        connection.driver = this.scalar(conn, 'driver', 'connection.driver');
        connection.host = this.scalar(conn, 'host', 'connection.host');
        connection.port = this.scalar(conn, 'port', 'connection.port');
        connection.username = this.scalar(conn, 'username', 'connection.username');

        if (conn.password === undefined || conn.password === null) {
            delete conn.password;
        } else {
            connection.password = this.scalar(conn, 'password', 'connection.password');
        }

        connection.extra = conn;
        plan.connection = connection;
    }

    rotation(src: Map): Value<string> | null {
        const value = src.backup_rotation;

        if (value === undefined || value === null) {
            delete src.backup_rotation;

            return null;
        }

        if (typeof value === 'boolean') {
            delete src.backup_rotation;
            this.warn(`backup_rotation: ${value} is not a valid rotation; it was converted to ${value ? 1 : 0}.`);

            return value ? '1' : '0';
        }

        return this.scalar(src, 'backup_rotation', 'backup_rotation');
    }

    compress(src: Map): Value<boolean | number> {
        const value = src.compress;

        if (typeof value === 'number') {
            delete src.compress;

            return value;
        }

        if (typeof value === 'string' && /^\d+$/.test(value) && value !== '0') {
            delete src.compress;
            this.warn(`compress: the quoted level "${value}" enables gzip at the default level 6; it is exported as true.`);

            return true;
        }

        return this.bool(src, 'compress', 'compress') ?? false;
    }

    databases(value: unknown): Database[] {
        if (value === undefined || value === null) {
            return [];
        }

        let items: unknown[];

        if (Array.isArray(value)) {
            items = value;
        } else if (isMap(value)) {
            items = Object.entries(value).map(([key, body]) => ({ [key]: body }));

            if (items.length) {
                this.warn('databases: expected a list; each key was converted to a list item.');
            }
        } else {
            this.warn(`databases: expected a list but found a ${describe(value)}; it was dropped.`);

            return [];
        }

        const databases: Database[] = [];

        items.forEach((item, i) => {
            const entry = this.namedEntry(item, `databases[${i}]`);

            if (!entry) {
                return;
            }

            const body = { ...entry.body };
            const db = newDatabase(entry.name);
            const path = `databases[${i}] "${entry.name}"`;

            db.options = this.strings(take(body, 'options'), `${path}.options`);
            db.ignore = this.strings(take(body, 'ignore'), `${path}.ignore`);
            db.to = this.scalar(body, 'to', `${path}.to`);
            db.tables = this.tables(take(body, 'tables'), path);
            db.extra = body;
            databases.push(db);
        });

        return databases;
    }

    tables(value: unknown, dbPath: string): Table[] {
        if (value === undefined || value === null) {
            return [];
        }

        if (!Array.isArray(value)) {
            this.warn(`${dbPath}.tables: expected a list but found a ${describe(value)}; it was dropped.`);

            return [];
        }

        const tables: Table[] = [];

        value.forEach((item, i) => {
            const entry = this.namedEntry(item, `${dbPath}.tables[${i}]`);

            if (!entry) {
                return;
            }

            const body = { ...entry.body };
            const table = newTable(entry.name);
            const path = `${dbPath}.tables "${entry.name}"`;

            table.where = this.scalar(body, 'where', `${path}.where`);
            table.only_struct = this.bool(body, 'only_struct', `${path}.only_struct`) ?? false;
            table.extra = body;
            tables.push(table);
        });

        return tables;
    }

    /** A list item that is either "name" or a single-key map "name: {options}". */
    namedEntry(item: unknown, path: string): { name: string; body: Map } | null {
        if (typeof item === 'string' || typeof item === 'number') {
            return { name: String(item), body: {} };
        }

        if (isMap(item) && Object.keys(item).length) {
            const [name, ...ignored] = Object.keys(item);
            const body = item[name];

            if (ignored.length) {
                this.warn(`${path}: only the first key "${name}" is used by MyBackup; ${ignored.map((k) => `"${k}"`).join(', ')} dropped.`);
            }

            if (body !== null && !isMap(body)) {
                this.warn(`${path} "${name}": options must be a map; ${describe(body)} dropped.`);

                return { name, body: {} };
            }

            return { name, body: body ?? {} };
        }

        this.warn(`${path}: unexpected ${describe(item)}; it was dropped.`);

        return null;
    }

    strings(value: unknown, path: string): string[] {
        if (value === undefined || value === null) {
            return [];
        }

        const list = Array.isArray(value) ? value : [value];
        const strings: string[] = [];

        for (const item of list) {
            if (typeof item === 'string' || typeof item === 'number' || typeof item === 'boolean') {
                strings.push(String(item));
            } else {
                this.warn(`${path}: unexpected ${describe(item)} dropped.`);
            }
        }

        if (!Array.isArray(value)) {
            this.warn(`${path}: expected a list; the single value was converted to a list.`);
        }

        return strings;
    }

    encryption(value: unknown): BackupPlan['encryption'] {
        if (value === undefined || value === null) {
            return { enabled: false, key: '', extra: {} };
        }

        if (!isMap(value)) {
            this.warn(`encryption: expected a map but found a ${describe(value)}; it was dropped.`);

            return { enabled: false, key: '', extra: {} };
        }

        const src = { ...value };
        const method = take(src, 'method');
        const key = this.scalar(src, 'key', 'encryption.key');

        if (method !== undefined && method !== null && method !== ENCRYPTION_METHOD) {
            this.warn(`encryption.method: "${String(method)}" isn't supported by MyBackup; it was replaced by ${ENCRYPTION_METHOD}.`);
        }

        return { enabled: key !== '', key, extra: src };
    }

    dump(value: unknown): BackupPlan['dump'] {
        if (value === undefined || value === null) {
            return { begin: [], end: [] };
        }

        if (!isMap(value)) {
            this.warn(`dump: expected a map but found a ${describe(value)}; it was dropped.`);

            return { begin: [], end: [] };
        }

        const src = { ...value };
        const dump = {
            begin: this.lines(take(src, 'begin'), 'dump.begin'),
            end: this.lines(take(src, 'end'), 'dump.end'),
        };

        for (const key of Object.keys(src)) {
            this.warn(`dump.${key}: unknown key dropped (only "begin" and "end" are supported).`);
        }

        return dump;
    }

    /** A text or a list of texts; each element is one line. */
    lines(value: unknown, path: string): string[] {
        if (value === undefined || value === null) {
            return [];
        }

        return Array.isArray(value) ? this.strings(value, path) : [String(value)];
    }

    notifications(value: unknown): BackupPlan['notifications'] {
        const notifications: BackupPlan['notifications'] = { slack: null, mail: null, extra: {} };

        if (value === undefined || value === null) {
            return notifications;
        }

        if (!isMap(value)) {
            this.warn(`notifications: expected a map but found a ${describe(value)}; it was dropped.`);

            return notifications;
        }

        const src = { ...value };

        for (const [key, fields] of [['slack', SLACK_FIELDS], ['mail', MAIL_FIELDS]] as const) {
            const channel = take(src, key);

            if (isMap(channel) && Object.keys(channel).length) {
                notifications[key] = this.block(channel, fields, `notifications.${key}`);
            } else if (channel !== undefined) {
                this.warn(`notifications.${key}: empty or invalid settings dropped.`);
            }
        }

        if (notifications.mail && notifications.mail.values.transport === undefined) {
            notifications.mail.values.transport = 'smtp';
            this.warn('notifications.mail: "transport: smtp" was added; mail is not sent without a transport.');
        }

        if (notifications.mail && isMap(notifications.mail.extra) && 'encryption' in notifications.mail.extra) {
            this.warn('notifications.mail.encryption is not used by the mailer; use "scheme" (smtps) or port 465 for TLS.');
        }

        notifications.extra = src;

        return notifications;
    }

    filesystems(value: unknown): BackupPlan['filesystems'] {
        if (value === undefined || value === null) {
            return [];
        }

        if (!isMap(value)) {
            this.warn(`filesystems: expected a map of named filesystems but found a ${describe(value)}; it was dropped.`);

            return [];
        }

        const filesystems: BackupPlan['filesystems'] = [];

        for (const [name, config] of Object.entries(value)) {
            const path = `filesystems.${name}`;

            if (!isMap(config)) {
                this.warn(`${path}: expected a map but found a ${describe(config)}; it was dropped.`);
                continue;
            }

            const src = { ...config };
            const driver = typeof src.driver === 'string' ? src.driver : '';
            delete src.driver;

            if (!DRIVERS[driver]) {
                this.warn(`${path}: driver "${driver}" isn't supported by the form; its settings are kept as extra keys.`);
            }

            filesystems.push({ name, driver, ...this.block(src, DRIVERS[driver]?.fields ?? [], path) });
        }

        return filesystems;
    }

    postActions(value: unknown): BackupPlan['post_actions'] {
        if (value === undefined || value === null) {
            return [];
        }

        if (!Array.isArray(value)) {
            this.warn(`post_actions: expected a list but found a ${describe(value)}; it was dropped.`);

            return [];
        }

        const actions: BackupPlan['post_actions'] = [];

        value.forEach((item, i) => {
            const entry = this.namedEntry(item, `post_actions[${i}]`);

            if (!entry) {
                return;
            }

            if (!ACTIONS[entry.name]) {
                this.warn(`post_actions[${i}]: unknown action "${entry.name}" is kept as is (MyBackup skips it).`);
            }

            actions.push({ type: entry.name, ...this.block(entry.body, ACTIONS[entry.name]?.fields ?? [], `post_actions[${i}]`) });
        });

        return actions;
    }

    /** Read the fields described by `fields`; everything else stays in `extra`. */
    block(value: Map, fields: FieldDef[], path: string): Block {
        const src = structuredClone(value);
        const values: FieldValues = {};

        for (const field of fields) {
            const parent = parentOf(src, field.key);

            if (!parent) {
                continue;
            }

            const key = leafOf(field.key);

            if (!(key in parent.map)) {
                continue;
            }

            if (field.kind === 'bool') {
                values[field.key] = this.bool(parent.map, key, `${path}.${field.key}`);
            } else if (field.kind === 'json' && isMap(parent.map[key])) {
                values[field.key] = JSON.stringify(take(parent.map, key), null, 4);
            } else {
                values[field.key] = this.scalar(parent.map, key, `${path}.${field.key}`);
            }

            parent.prune();
        }

        return { values, extra: src };
    }
}

function take(src: Map, key: string): unknown {
    const value = src[key];
    delete src[key];

    return value;
}

function leafOf(key: string): string {
    return key.slice(key.lastIndexOf('.') + 1);
}

/** The map holding a dotted key, and a callback that removes it once it's empty. */
function parentOf(src: Map, key: string): { map: Map; prune: () => void } | null {
    const parts = key.split('.');

    if (parts.length === 1) {
        return { map: src, prune: () => undefined };
    }

    const parentKey = parts[0];
    const map = src[parentKey];

    if (!isMap(map)) {
        return null;
    }

    return {
        map,
        prune: () => {
            if (!Object.keys(map).length) {
                delete src[parentKey];
            }
        },
    };
}

function describe(value: unknown): string {
    if (Array.isArray(value)) {
        return 'list';
    }

    return isMap(value) ? 'map' : `value "${String(value)}"`;
}
