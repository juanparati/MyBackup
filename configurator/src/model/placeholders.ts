/**
 * Port of MyBackup's Placeholder::replace(), used to preview file names and to
 * spot placeholders that won't work. MyBackup runs in UTC (Laravel's default).
 */

export interface PlaceholderInfo {
    text: string;
    label: string;
}

export const PLACEHOLDERS: PlaceholderInfo[] = [
    { text: '{{numeric:{{datetime}}}}', label: 'Date and time, digits only (YmdHis)' },
    { text: '{{datetime}}', label: 'Date and time (Y-m-d H:i:s)' },
    { text: '{{date}}', label: 'Date (Y-m-d)' },
    { text: '{{timestamp}}', label: 'UNIX timestamp' },
    { text: '{{uuid}}', label: 'UUID v7' },
    { text: '{{date:{{date_calc:-7days}}}}', label: 'Date 7 days ago' },
    { text: '{{date_calc:-1day}}', label: 'Date and time 1 day ago' },
    { text: '{{date_format:{{date}}|Y}}', label: 'Custom date format (must be the whole value)' },
];

/** Only available in post actions. */
export const ACTION_PLACEHOLDERS: PlaceholderInfo[] = [
    { text: '{{snapshot_file}}', label: 'Snapshot file (full path, with extensions)' },
    { text: '{{basename:{{snapshot_file}}}}', label: 'Snapshot file name' },
    { text: '{{crc}}', label: 'Snapshot MD5 checksum' },
];

export type Expansion = { value: string; error?: undefined } | { value?: undefined; error: string };

const DATE_ARG = '(?::(\\d{4}-\\d{2}-\\d{2}(?: \\d{2}:\\d{2}:\\d{2})?))?';

export function expandPlaceholders(input: string, now: Date, dictionary: Record<string, string> = {}): Expansion {
    let s = input;

    for (const [key, value] of Object.entries(dictionary)) {
        s = s.replaceAll(`{{${key}}}`, value);
    }

    s = s.replace(/{{basename:(.*?)}}/g, (_, path: string) => basename(path));

    s = s.replace(
        /{{date_calc:[-+](\d+)(minutes?|hours?|days?|weeks?|months?|years?)?}}/g,
        (_, amount: string, unit: string | undefined) => dateTimeString(subtract(now, Number(amount), unit ?? 'hour')),
    );

    const dateArg = (arg: string | undefined) => (arg ? parseDate(arg) : now);

    s = s.replace(new RegExp(`{{date${DATE_ARG}}}`, 'g'), (_, arg?: string) => dateTimeString(dateArg(arg)).slice(0, 10));
    s = s.replace(new RegExp(`{{datetime${DATE_ARG}}}`, 'g'), (_, arg?: string) => dateTimeString(dateArg(arg)));
    s = s.replace(new RegExp(`{{timestamp${DATE_ARG}}}`, 'g'), (_, arg?: string) => String(unix(dateArg(arg))));

    const dateFormat = new RegExp(`{{date_format${DATE_ARG}\\|(.*?)}}`, 'g');
    const formats = [...s.matchAll(dateFormat)];

    if (formats.length) {
        // MyBackup replaces the whole value with the formatted date of the first match.
        const replaced = s.replace(dateFormat, (_, arg?: string) => String(unix(dateArg(arg))));

        if (!/^\d+$/.test(replaced)) {
            return { error: '{{date_format:…}} only works when it is the whole value.' };
        }

        s = formatPhpDate(new Date(Number(replaced) * 1000), formats[0][2]);
    }

    s = s.replace(new RegExp(`{{uuid${DATE_ARG}}}`, 'g'), (_, arg?: string) => uuid7(dateArg(arg)));
    s = s.replace(/{{numeric:(.*?)}}/g, (_, value: string) => value.replace(/\D/g, ''));

    return { value: s };
}

/** Placeholders left in an expanded value (unknown names or unsupported syntax). */
export function leftoverPlaceholders(expanded: string): string[] {
    return expanded.match(/{{[^{}]*}}/g) ?? [];
}

function basename(path: string): string {
    const trimmed = path.replace(/\/+$/, '');

    return trimmed.slice(trimmed.lastIndexOf('/') + 1);
}

function parseDate(arg: string): Date {
    const [date, time = '00:00:00'] = arg.split(' ');

    return new Date(`${date}T${time}Z`);
}

function subtract(date: Date, amount: number, unit: string): Date {
    const d = new Date(date);

    switch (unit.replace(/s$/, '')) {
        case 'minute':
            d.setUTCMinutes(d.getUTCMinutes() - amount);
            break;
        case 'hour':
            d.setUTCHours(d.getUTCHours() - amount);
            break;
        case 'day':
            d.setUTCDate(d.getUTCDate() - amount);
            break;
        case 'week':
            d.setUTCDate(d.getUTCDate() - amount * 7);
            break;
        case 'month':
            d.setUTCMonth(d.getUTCMonth() - amount);
            break;
        case 'year':
            d.setUTCFullYear(d.getUTCFullYear() - amount);
            break;
    }

    return d;
}

function unix(date: Date): number {
    return Math.floor(date.getTime() / 1000);
}

function pad(value: number, length = 2): string {
    return String(value).padStart(length, '0');
}

function dateTimeString(date: Date): string {
    return formatPhpDate(date, 'Y-m-d H:i:s');
}

function uuid7(date: Date): string {
    const random = crypto.getRandomValues(new Uint8Array(10));
    const hex = date.getTime().toString(16).padStart(12, '0')
        + [...random].map((b) => b.toString(16).padStart(2, '0')).join('');
    const variant = ((parseInt(hex[16], 16) & 0x3) | 0x8).toString(16);

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-7${hex.slice(13, 16)}-${variant}${hex.slice(17, 20)}-${hex.slice(20, 32)}`;
}

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

/** The common subset of PHP's date() format characters, in UTC. */
export function formatPhpDate(date: Date, format: string): string {
    const Y = date.getUTCFullYear();
    const m = date.getUTCMonth();
    const d = date.getUTCDate();
    const H = date.getUTCHours();
    const w = date.getUTCDay();
    const dayOfYear = Math.floor((Date.UTC(Y, m, d) - Date.UTC(Y, 0, 1)) / 86400000);
    const leap = new Date(Date.UTC(Y, 1, 29)).getUTCMonth() === 1;
    const iso = isoWeek(date);

    const chars: Record<string, () => string> = {
        d: () => pad(d),
        D: () => DAYS[w].slice(0, 3),
        j: () => String(d),
        l: () => DAYS[w],
        N: () => String(w || 7),
        S: () => (d % 10 === 1 && d !== 11 ? 'st' : d % 10 === 2 && d !== 12 ? 'nd' : d % 10 === 3 && d !== 13 ? 'rd' : 'th'),
        w: () => String(w),
        z: () => String(dayOfYear),
        W: () => pad(iso.week),
        o: () => String(iso.year),
        F: () => MONTHS[m],
        m: () => pad(m + 1),
        M: () => MONTHS[m].slice(0, 3),
        n: () => String(m + 1),
        t: () => String(new Date(Date.UTC(Y, m + 1, 0)).getUTCDate()),
        L: () => (leap ? '1' : '0'),
        Y: () => String(Y),
        y: () => pad(Y % 100),
        a: () => (H < 12 ? 'am' : 'pm'),
        A: () => (H < 12 ? 'AM' : 'PM'),
        g: () => String(H % 12 || 12),
        G: () => String(H),
        h: () => pad(H % 12 || 12),
        H: () => pad(H),
        i: () => pad(date.getUTCMinutes()),
        s: () => pad(date.getUTCSeconds()),
        u: () => pad(date.getUTCMilliseconds() * 1000, 6),
        v: () => pad(date.getUTCMilliseconds(), 3),
        e: () => 'UTC',
        T: () => 'UTC',
        P: () => '+00:00',
        O: () => '+0000',
        Z: () => '0',
        U: () => String(unix(date)),
        c: () => formatPhpDate(date, 'Y-m-d\\TH:i:sP'),
    };

    let out = '';

    for (let i = 0; i < format.length; i++) {
        const char = format[i];

        if (char === '\\' && i + 1 < format.length) {
            out += format[++i];
        } else {
            out += chars[char]?.() ?? char;
        }
    }

    return out;
}

function isoWeek(date: Date): { year: number; week: number } {
    const d = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate()));
    d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
    const yearStart = Date.UTC(d.getUTCFullYear(), 0, 1);

    return { year: d.getUTCFullYear(), week: Math.ceil(((d.getTime() - yearStart) / 86400000 + 1) / 7) };
}
