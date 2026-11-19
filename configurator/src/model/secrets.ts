import { ACTIONS, DRIVERS, MAIL_FIELDS, SLACK_FIELDS, type FieldDef } from './blocks.ts';
import { isEnv, type BackupPlan, type Block, type FieldValues, type Value } from './types.ts';

/** Shown in the preview instead of a secret. */
export const MASK = '••••••••';

/** A private key pasted as content rather than given as a file path. */
const KEY_CONTENT = /-----BEGIN |\n/;

/** A credential written in the plan file. */
export interface Secret {
    /** Model path, also the `data-path` of the form field. */
    path: string;
    label: string;
    /** The secret is the default of an environment variable reference. */
    isDefault: boolean;
    /** Replace the written text in the plan; `undefined` clears it. */
    replace: (text: string | undefined) => void;
}

/** Credentials written in the plan: literal values and defaults of env references (extra keys are not checked). */
export function writtenSecrets(plan: BackupPlan): Secret[] {
    const secrets: Secret[] = [];

    const add = (path: string, label: string, value: FieldValues[string] | null, set: (value: Value<string>) => void, applies = (_text: string) => true) => {
        if (typeof value === 'string' && value !== '' && applies(value)) {
            secrets.push({ path, label, isDefault: false, replace: (text) => set(text ?? '') });
        } else if (isEnv(value) && value.default && applies(value.default)) {
            secrets.push({ path, label, isDefault: true, replace: (text) => set({ ...value, default: text }) });
        }
    };

    const block = (target: Block, fields: FieldDef[], path: string, label: string) => {
        for (const field of fields) {
            const secret = field.kind === 'secret' || field.secret;

            if (secret) {
                add(`${path}.${field.key}`, `${label}: ${field.label.toLowerCase()}`, target.values[field.key],
                    (value) => (target.values[field.key] = value),
                    secret === 'content' ? (text) => KEY_CONTENT.test(text) : undefined);
            }
        }
    };

    if (plan.connection_env) {
        add('connection_env', 'Connection from env', plan.connection_env, (value) => isEnv(value) && (plan.connection_env = value));
    } else {
        add('connection.password', 'Connection password', plan.connection.password, (value) => (plan.connection.password = value));
    }

    if (plan.encryption.enabled) {
        add('encryption.key', 'Encryption key', plan.encryption.key, (value) => (plan.encryption.key = value));
    }

    if (plan.notifications.slack) {
        block(plan.notifications.slack, SLACK_FIELDS, 'notifications.slack', 'Slack');
    }

    if (plan.notifications.mail) {
        block(plan.notifications.mail, MAIL_FIELDS, 'notifications.mail', 'Mail');
    }

    plan.filesystems.forEach((fs, i) => {
        // Filesystems without a name aren't written to the file.
        if (fs.name !== '') {
            block(fs, DRIVERS[fs.driver]?.fields ?? [], `filesystems.${i}`, `Filesystem "${fs.name}"`);
        }
    });

    plan.post_actions.forEach((action, i) => block(action, ACTIONS[action.type]?.fields ?? [], `post_actions.${i}`, `Post action #${i + 1}`));

    return secrets;
}

/** A copy of the plan with every written secret replaced by `text`, or cleared when it is undefined. */
export function replaceSecrets(plan: BackupPlan, text?: string): BackupPlan {
    const copy = structuredClone(plan);

    writtenSecrets(copy).forEach((secret) => secret.replace(text));

    return copy;
}
