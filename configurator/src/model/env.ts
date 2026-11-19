import type { EnvValue } from './types.ts';

/** Casts understood by MyBackup's Config::castEnvValueAs(). */
export const ENV_CASTS = ['string', 'int', 'float', 'bool', 'json'] as const;

const WHOLE_ENV = /^%env\((.*)\)%$/s;

/**
 * Parse a string that is entirely an env reference.
 *
 * Mirrors Config::parseEnv(): "[cast->]KEY[:default]", where the default may contain ":".
 */
export function parseEnv(value: string): EnvValue | null {
    const match = WHOLE_ENV.exec(value);

    if (!match) {
        return null;
    }

    const colon = match[1].indexOf(':');
    const keyPart = colon === -1 ? match[1] : match[1].slice(0, colon);
    const arrow = keyPart.indexOf('->');
    const env: EnvValue = { env: arrow === -1 ? keyPart : keyPart.slice(arrow + 2) };

    if (arrow !== -1) {
        env.cast = keyPart.slice(0, arrow);
    }

    if (colon !== -1) {
        env.default = match[1].slice(colon + 1);
    }

    return env;
}

export function formatEnv(value: EnvValue): string {
    const cast = value.cast ? `${value.cast}->` : '';
    const def = value.default !== undefined ? `:${value.default}` : '';

    return `%env(${cast}${value.env}${def})%`;
}

/** True when a plain string contains an env reference that isn't the whole value. */
export function hasEmbeddedEnv(value: string): boolean {
    return value.includes('%env(') && parseEnv(value) === null;
}
