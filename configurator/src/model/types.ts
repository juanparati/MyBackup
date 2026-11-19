/**
 * Normalized backup plan model.
 *
 * Property names mirror the YAML keys, so a model path (e.g. "connection.host")
 * is also the `data-path` of the form field that edits it.
 */

/** A `%env([cast->]KEY[:default])%` reference. */
export interface EnvValue {
    env: string;
    cast?: string;
    default?: string;
}

/** A scalar that may be provided literally or read from an environment variable. */
export type Value<T> = T | EnvValue;

/** Keys the form doesn't know about, kept so imports round-trip. */
export type Extra = Record<string, unknown>;

/** Values of a block whose fields are described by `FieldDef`s (see fields.ts). */
export type FieldValues = Record<string, Value<string> | Value<boolean> | undefined>;

export interface Connection {
    driver: Value<string>;
    host: Value<string>;
    port: Value<string>;
    username: Value<string>;
    /** `null` omits the key, so MyBackup prompts for the password. */
    password: Value<string> | null;
    extra: Extra;
}

export interface Table {
    name: string;
    where: Value<string>;
    only_struct: Value<boolean>;
    extra: Extra;
}

export interface Database {
    name: string;
    options: string[];
    ignore: string[];
    to: Value<string>;
    tables: Table[];
    extra: Extra;
}

export interface Encryption {
    enabled: boolean;
    key: Value<string>;
    extra: Extra;
}

export interface Block {
    values: FieldValues;
    extra: Extra;
}

export interface Filesystem extends Block {
    name: string;
    driver: string;
}

export interface PostAction extends Block {
    type: string;
}

export interface BackupPlan {
    name: Value<string>;
    catalog_file: Value<string>;
    snapshot_file: Value<string>;
    connection: Connection;
    /** When set, the whole connection block is read from a JSON env variable. */
    connection_env: EnvValue | null;
    mysqldump_path: Value<string>;
    /** `null` disables rotation; digits keep the last N backups; "N unit" deletes by age. */
    backup_rotation: Value<string> | null;
    /** `false` = off, `true` = gzip level 6, 1-9 = gzip level. */
    compress: Value<boolean | number>;
    is_replica: Value<boolean>;
    databases: Database[];
    encryption: Encryption;
    dump: { begin: string[]; end: string[] };
    notifications: { slack: Block | null; mail: Block | null; extra: Extra };
    filesystems: Filesystem[];
    post_actions: PostAction[];
    extra: Extra;
}

export function isEnv(value: unknown): value is EnvValue {
    return typeof value === 'object' && value !== null && typeof (value as EnvValue).env === 'string';
}

export function isMap(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null && !Array.isArray(value);
}
