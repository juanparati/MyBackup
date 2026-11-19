import { PLACEHOLDERS } from '../../model/placeholders.ts';
import { isEnv, type BackupPlan } from '../../model/types.ts';
import { changed } from '../../store.ts';
import { h, idFor, nodes } from '../dom.ts';
import { extraField, field, selectField, textField } from '../fields.ts';
import { section } from './common.ts';

export function generalSection(plan: BackupPlan): HTMLElement {
    return section('General', 'general', null, h('div', { class: 'grid' },
        textField({
            label: 'Plan name',
            path: 'name',
            placeholder: 'Backup plan',
            hint: 'Shown in the console and in notifications.',
            get: () => plan.name,
            set: (value) => (plan.name = value),
        }),
        textField({
            label: 'mysqldump path',
            path: 'mysqldump_path',
            suggestions: ['mysqldump', 'mariadb-dump'],
            hint: 'Command name or absolute path of mysqldump or mariadb-dump.',
            get: () => plan.mysqldump_path,
            set: (value) => (plan.mysqldump_path = value),
        }),
        textField({
            label: 'Catalog file',
            path: 'catalog_file',
            required: true,
            placeholder: '/var/backups/mybackup/catalog.sqlite',
            hint: 'SQLite file with the backup history and the lock that prevents overlapping runs. Created when missing; use one per backup plan.',
            get: () => plan.catalog_file,
            set: (value) => (plan.catalog_file = value),
        }),
        textField({
            label: 'Snapshot file',
            path: 'snapshot_file',
            required: true,
            placeholder: '/var/backups/mybackup/snapshot_{{numeric:{{datetime}}}}.sql',
            placeholders: PLACEHOLDERS,
            hint: [
                'Path of the dump. ".gz" and ".aes" are appended when it is compressed or encrypted. ',
                h('output', { id: 'snapshot-preview', for: idFor('snapshot_file') }),
            ],
            get: () => plan.snapshot_file,
            set: (value) => (plan.snapshot_file = value),
        }),
    ), true);
}

export function connectionSection(plan: BackupPlan): HTMLElement {
    const body = h('div', { class: 'grid' });
    const conn = plan.connection;

    const render = () => {
        const fromEnv = plan.connection_env !== null;

        body.replaceChildren(
            h('label', { class: 'switch wide' },
                h('input', {
                    type: 'checkbox',
                    checked: fromEnv,
                    onchange: () => {
                        plan.connection_env = plan.connection_env ? null : { env: '', cast: 'json' };
                        changed();
                        render();
                    },
                }),
                ' Read the whole connection from a JSON environment variable'),
        );

        if (fromEnv) {
            body.append(textField({
                label: 'Environment variable',
                path: 'connection_env',
                env: false,
                hint: ['The variable holds the connection as JSON, e.g. ', h('code', {}, '{"driver":"mysql","host":"localhost","port":3306,"username":"root","password":"secret"}')],
                get: () => plan.connection_env!,
                set: (value) => isEnv(value) && (plan.connection_env = value),
            }));

            return;
        }

        body.append(...nodes([
            isEnv(conn.driver)
                ? textField({ label: 'Driver', path: 'connection.driver', get: () => conn.driver, set: (value) => (conn.driver = value) })
                : selectField({
                    label: 'Driver',
                    path: 'connection.driver',
                    options: [['', 'Default (mysql)'], ['mysql', 'MySQL'], ['mariadb', 'MariaDB']],
                    hint: 'MariaDB also works with the mysql driver.',
                    get: () => conn.driver as string,
                    set: (value) => (conn.driver = value),
                }),
            textField({
                label: 'Host',
                path: 'connection.host',
                required: true,
                placeholder: 'localhost',
                get: () => conn.host,
                set: (value) => (conn.host = value),
            }),
            textField({
                label: 'Port',
                path: 'connection.port',
                required: true,
                kind: 'number',
                cast: 'int',
                placeholder: '3306',
                get: () => conn.port,
                set: (value) => (conn.port = value),
            }),
            textField({
                label: 'Username',
                path: 'connection.username',
                placeholder: 'root',
                get: () => conn.username,
                set: (value) => (conn.username = value),
            }),
            field({ label: 'Password', path: 'connection.password-mode' }, h('select', {
                id: idFor('connection.password-mode'),
                onchange: (event: Event) => {
                    conn.password = (event.target as HTMLSelectElement).value === 'prompt' ? null : '';
                    changed();
                    render();
                },
            },
            h('option', { value: 'set' }, 'Set in the configuration'),
            h('option', { value: 'prompt', selected: conn.password === null }, 'Ask when the backup runs (interactive only)'))),
            conn.password !== null && textField({
                label: 'Password value',
                path: 'connection.password',
                kind: 'secret',
                hint: 'Leave empty for no password. An environment variable keeps it out of the file.',
                get: () => conn.password ?? '',
                set: (value) => (conn.password = value),
            }),
            extraField('connection.extra', () => conn.extra, (extra) => (conn.extra = extra),
                'Other Laravel connection keys (unix_socket, charset…). mysqldump itself only uses host, port, username and password.'),
        ]));
    };

    render();

    return section('Database connection', 'connection', null, body, true);
}

export function otherSection(plan: BackupPlan): HTMLElement {
    return section('Other keys', 'other', 'Top-level keys that the form doesn\'t cover. MyBackup ignores unknown keys.',
        extraField('extra', () => plan.extra, (extra) => (plan.extra = extra), 'Kept as written.'));
}
