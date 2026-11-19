import { Document, isScalar, isSeq, visit } from 'yaml';
import { ACTIONS, DRIVERS, MAIL_FIELDS, SLACK_FIELDS, type FieldDef } from './blocks.ts';
import { formatEnv } from './env.ts';
import { ENCRYPTION_METHOD } from './defaults.ts';
import { isEnv, isMap, type BackupPlan, type Block, type Connection, type Database, type Extra, type Table, type Value } from './types.ts';

type Map = Record<string, unknown>;

/** Keys whose lists are written inline, as in the MyBackup README. */
const FLOW_LISTS = ['options', 'ignore'];

export function toYaml(plan: BackupPlan): string {
    const doc = new Document(toObject(plan));

    visit(doc, {
        Pair(_, pair) {
            if (isScalar(pair.key) && FLOW_LISTS.includes(String(pair.key.value)) && isSeq(pair.value)) {
                pair.value.flow = true;
            }
        },
    });

    doc.commentBefore = ' MyBackup configuration';

    return doc.toString({
        indent: 4,
        lineWidth: 0,
        defaultKeyType: 'PLAIN',
        defaultStringType: 'QUOTE_DOUBLE',
        // Keeps multi-line values such as private keys on one line, with \n escapes.
        doubleQuotedAsJSON: true,
    });
}

/** The plan as plain data, in the key order of the MyBackup README. */
export function toObject(plan: BackupPlan): Map {
    const out: Map = {};

    setText(out, 'name', plan.name);
    setText(out, 'catalog_file', plan.catalog_file);
    setText(out, 'snapshot_file', plan.snapshot_file);

    const connection = plan.connection_env ? formatEnv(plan.connection_env) : connectionToObject(plan.connection);

    if (typeof connection === 'string' || Object.keys(connection).length) {
        out.connection = connection;
    }

    setText(out, 'mysqldump_path', plan.mysqldump_path);

    if (plan.backup_rotation !== null) {
        out.backup_rotation = number(plan.backup_rotation);
    }

    out.compress = isEnv(plan.compress) ? formatEnv(plan.compress) : plan.compress;
    out.is_replica = bool(plan.is_replica);
    out.databases = plan.databases.map(databaseToYaml);

    if (plan.encryption.enabled) {
        out.encryption = withExtra({ method: ENCRYPTION_METHOD, key: text(plan.encryption.key) }, plan.encryption.extra);
    }

    const dump: Map = {};
    setLines(dump, 'begin', plan.dump.begin);
    setLines(dump, 'end', plan.dump.end);

    if (Object.keys(dump).length) {
        out.dump = dump;
    }

    const notifications: Map = {};

    if (plan.notifications.slack) {
        notifications.slack = blockToObject(plan.notifications.slack, SLACK_FIELDS);
    }

    if (plan.notifications.mail) {
        notifications.mail = blockToObject(plan.notifications.mail, MAIL_FIELDS);
    }

    withExtra(notifications, plan.notifications.extra);

    if (Object.keys(notifications).length) {
        out.notifications = notifications;
    }

    const filesystems: Map = {};

    for (const fs of plan.filesystems) {
        if (fs.name !== '') {
            filesystems[fs.name] = { driver: fs.driver, ...blockToObject(fs, DRIVERS[fs.driver]?.fields ?? []) };
        }
    }

    if (Object.keys(filesystems).length) {
        out.filesystems = filesystems;
    }

    if (plan.post_actions.length) {
        out.post_actions = plan.post_actions.map((action) => ({
            [action.type]: blockToObject(action, ACTIONS[action.type]?.fields ?? []),
        }));
    }

    return withExtra(out, plan.extra);
}

function connectionToObject(connection: Connection): Map {
    const out: Map = {};

    setText(out, 'driver', connection.driver);
    setText(out, 'host', connection.host);

    if (connection.port !== '') {
        out.port = number(connection.port);
    }

    setText(out, 'username', connection.username);

    if (connection.password !== null) {
        out.password = text(connection.password);
    }

    return withExtra(out, connection.extra);
}

function databaseToYaml(db: Database): unknown {
    const body: Map = {};

    if (db.options.length) {
        body.options = db.options;
    }

    if (db.ignore.length) {
        body.ignore = db.ignore;
    }

    setText(body, 'to', db.to);

    if (db.tables.length) {
        body.tables = db.tables.map(tableToYaml);
    }

    withExtra(body, db.extra);

    return Object.keys(body).length ? { [db.name]: body } : db.name;
}

function tableToYaml(table: Table): unknown {
    const body: Map = {};

    setText(body, 'where', table.where);

    if (table.only_struct !== false) {
        body.only_struct = bool(table.only_struct);
    }

    withExtra(body, table.extra);

    return Object.keys(body).length ? { [table.name]: body } : table.name;
}

export function blockToObject(block: Block, fields: FieldDef[]): Map {
    const out: Map = {};

    for (const field of fields) {
        const value = block.values[field.key];

        if (value === undefined || value === '') {
            if (field.keepEmpty) {
                setPath(out, field.key, '');
            }

            continue;
        }

        if (isEnv(value)) {
            setPath(out, field.key, formatEnv(value));
        } else if (typeof value === 'boolean') {
            setPath(out, field.key, value);
        } else if (field.kind === 'number') {
            setPath(out, field.key, number(value));
        } else if (field.kind === 'json') {
            setPath(out, field.key, parseJson(value));
        } else {
            setPath(out, field.key, value);
        }
    }

    return withExtra(out, block.extra);
}

function text(value: Value<string>): string {
    return isEnv(value) ? formatEnv(value) : value;
}

/** Digits are written as integers; anything else as text (validation reports it). */
function number(value: Value<string>): string | number {
    if (isEnv(value)) {
        return formatEnv(value);
    }

    return /^\d+$/.test(value.trim()) ? Number(value.trim()) : value;
}

function bool(value: Value<boolean>): string | boolean {
    return isEnv(value) ? formatEnv(value) : value;
}

function setText(out: Map, key: string, value: Value<string>): void {
    if (value !== '') {
        out[key] = text(value);
    }
}

/** A single line is written as a text, several as a list. */
function setLines(out: Map, key: string, lines: string[]): void {
    if (lines.length === 1) {
        out[key] = lines[0];
    } else if (lines.length > 1) {
        out[key] = lines;
    }
}

function setPath(out: Map, path: string, value: unknown): void {
    const [parent, key] = path.includes('.') ? path.split('.', 2) : [null, path];

    if (parent === null) {
        out[key] = value;

        return;
    }

    if (!isMap(out[parent])) {
        out[parent] = {};
    }

    (out[parent] as Map)[key] = value;
}

function parseJson(value: string): unknown {
    try {
        return JSON.parse(value);
    } catch {
        return value;
    }
}

/** Add the extra keys that the known fields didn't already set (nested maps are merged). */
function withExtra(out: Map, extra: Extra): Map {
    for (const [key, value] of Object.entries(extra)) {
        if (!(key in out)) {
            out[key] = value;
        } else if (isMap(out[key]) && isMap(value)) {
            withExtra(out[key] as Map, value);
        }
    }

    return out;
}
