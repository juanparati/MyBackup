/**
 * Declarative field definitions for the blocks whose keys depend on a type:
 * filesystem drivers, post actions and notification channels.
 */

export type FieldKind = 'text' | 'secret' | 'number' | 'bool' | 'json' | 'textarea';

export interface FieldDef {
    /** YAML key; dotted keys ("from.address") are nested maps. */
    key: string;
    label: string;
    kind?: FieldKind;
    required?: boolean;
    hint?: string;
    placeholder?: string;
    /** Value supports MyBackup {{placeholders}}. */
    placeholders?: boolean;
    /** Emit the key even when the value is empty. */
    keepEmpty?: boolean;
    /** Value for a newly added block. */
    initial?: string | boolean;
    /** Suggestions shown in a datalist. */
    suggestions?: string[];
    /** Id of a shared datalist (e.g. filesystem names). */
    list?: string;
    /**
     * Holds a credential, also implied by kind "secret" (see secrets.ts).
     * "content": only when the value is key material rather than a file path.
     */
    secret?: boolean | 'content';
}

export const FILESYSTEM_NAMES_LIST = 'filesystem-names';

const VISIBILITY = ['private', 'public'];

export const DRIVERS: Record<string, { label: string; fields: FieldDef[] }> = {
    s3: {
        label: 'Amazon S3 (or compatible)',
        fields: [
            { key: 'key', label: 'Access key ID' },
            { key: 'secret', label: 'Secret access key', kind: 'secret' },
            { key: 'region', label: 'Region', required: true, placeholder: 'eu-central-1' },
            { key: 'bucket', label: 'Bucket', required: true },
            { key: 'endpoint', label: 'Endpoint', hint: 'Only for S3-compatible services (MinIO, R2, Spaces…).', placeholder: 'https://…' },
            { key: 'use_path_style_endpoint', label: 'Path-style endpoint', kind: 'bool' },
            { key: 'root', label: 'Root directory', hint: 'Prefix inside the bucket.' },
        ],
    },
    gcs: {
        label: 'Google Cloud Storage',
        fields: [
            { key: 'project_id', label: 'Project ID' },
            { key: 'bucket', label: 'Bucket', required: true },
            { key: 'key_file', label: 'Service account key (JSON)', kind: 'json', secret: true, hint: 'Paste the content of the service account JSON key file.' },
            { key: 'key_file_path', label: 'Key file path', hint: 'Alternative to pasting the key: path to the JSON key file on the backup host.' },
            { key: 'path_prefix', label: 'Path prefix', hint: 'Prefix inside the bucket.' },
            { key: 'visibility', label: 'Visibility', suggestions: VISIBILITY },
        ],
    },
    ftp: {
        label: 'FTP',
        fields: [
            { key: 'host', label: 'Host', required: true },
            { key: 'port', label: 'Port', kind: 'number', placeholder: '21' },
            { key: 'username', label: 'Username', required: true },
            { key: 'password', label: 'Password', kind: 'secret', required: true },
            { key: 'root', label: 'Root directory' },
            { key: 'ssl', label: 'Use SSL', kind: 'bool' },
            { key: 'passive', label: 'Passive mode', kind: 'bool', hint: 'Default: yes.' },
            { key: 'timeout', label: 'Timeout (seconds)', kind: 'number', placeholder: '90' },
        ],
    },
    sftp: {
        label: 'SFTP',
        fields: [
            { key: 'host', label: 'Host', required: true },
            { key: 'port', label: 'Port', kind: 'number', placeholder: '22' },
            { key: 'username', label: 'Username', required: true },
            { key: 'password', label: 'Password', kind: 'secret' },
            { key: 'privateKey', label: 'Private key', secret: 'content', hint: 'Path to the key file or the key content.', placeholder: '/home/backup/.ssh/id_rsa' },
            { key: 'passphrase', label: 'Key passphrase', kind: 'secret' },
            { key: 'useAgent', label: 'Use SSH agent', kind: 'bool' },
            { key: 'hostFingerprint', label: 'Host fingerprint' },
            { key: 'root', label: 'Root directory' },
            { key: 'timeout', label: 'Timeout (seconds)', kind: 'number', placeholder: '10' },
            { key: 'visibility', label: 'File visibility', hint: 'private = 0600, public = 0644', suggestions: VISIBILITY },
            { key: 'directory_visibility', label: 'Directory visibility', hint: 'private = 0700, public = 0755', suggestions: VISIBILITY },
        ],
    },
    local: {
        label: 'Local directory',
        fields: [
            { key: 'root', label: 'Root directory', required: true, placeholder: '/mnt/backups' },
        ],
    },
};

export const ACTIONS: Record<string, { label: string; hint: string; fields: FieldDef[] }> = {
    copy: {
        label: 'Copy',
        hint: 'Copy a local file (usually the snapshot) to a filesystem.',
        fields: [
            { key: 'filesystem', label: 'Filesystem', required: true, list: FILESYSTEM_NAMES_LIST },
            { key: 'source', label: 'Source (local path)', required: true, placeholders: true, initial: '{{snapshot_file}}' },
            { key: 'destination', label: 'Destination (path on the filesystem)', required: true, placeholders: true, initial: '{{basename:{{snapshot_file}}}}' },
        ],
    },
    delete_old: {
        label: 'Delete old files',
        hint: 'Delete files older than a period from the top level of a filesystem (not recursive).',
        fields: [
            { key: 'filesystem', label: 'Filesystem', required: true, list: FILESYSTEM_NAMES_LIST },
            { key: 'pattern', label: 'File name pattern', keepEmpty: true, placeholder: 'snapshot_*', hint: 'Glob matched against file names. Empty matches every file.' },
            { key: 'period', label: 'Older than', required: true, placeholder: '3 days', hint: 'Format: "N unit", unit = minutes, hours, days, weeks, months or years.' },
        ],
    },
    run: {
        label: 'Run command',
        hint: 'Run a shell command. Exit code 0 counts as success.',
        fields: [
            { key: 'command', label: 'Command', required: true, placeholders: true, placeholder: 'echo "Finished"' },
        ],
    },
};

export const SLACK_FIELDS: FieldDef[] = [
    { key: 'username', label: 'Bot username', placeholder: 'BackupBot' },
    { key: 'channel', label: 'Channel' },
    { key: 'webhook', label: 'Webhook URL', kind: 'secret', required: true, placeholder: 'https://hooks.slack.com/services/…' },
];

export const MAIL_FIELDS: FieldDef[] = [
    { key: 'address', label: 'Recipient address', required: true, placeholder: 'ops@example.com' },
    { key: 'from.address', label: 'Sender address', required: true, placeholder: 'backup@example.com' },
    { key: 'from.name', label: 'Sender name', placeholder: 'MyBackup' },
    { key: 'transport', label: 'Transport', required: true, initial: 'smtp', suggestions: ['smtp', 'sendmail', 'log'] },
    { key: 'host', label: 'SMTP host', placeholder: 'smtp.example.com' },
    { key: 'port', label: 'SMTP port', kind: 'number', placeholder: '587' },
    { key: 'scheme', label: 'Scheme', suggestions: ['smtp', 'smtps'], hint: 'smtps = implicit TLS. When empty, port 465 uses smtps and anything else smtp (with STARTTLS when offered).' },
    { key: 'username', label: 'SMTP username' },
    { key: 'password', label: 'SMTP password', kind: 'secret' },
    { key: 'timeout', label: 'Timeout (seconds)', kind: 'number' },
];

/** Initial values for a new block. */
export function initialValues(fields: FieldDef[]): Record<string, string | boolean> {
    const values: Record<string, string | boolean> = {};

    for (const field of fields) {
        if (field.initial !== undefined) {
            values[field.key] = field.initial;
        }
    }

    return values;
}
