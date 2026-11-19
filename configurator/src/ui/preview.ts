import { expandPlaceholders } from '../model/placeholders.ts';
import { isEnv, type BackupPlan } from '../model/types.ts';
import type { Issue } from '../model/validate.ts';
import { h } from './dom.ts';

const KEY_LINE = /^(\s*(?:- )*)([^\s"'#:{[][^:#]*|"(?:[^"\\]|\\.)*"):(?=\s|$)(.*)$/;
const VALUE_TOKENS = /("(?:[^"\\]|\\.)*")|(\b(?:true|false|null)\b)|((?<![\w.-])-?\d+(?:\.\d+)?(?![\w.-]))/g;

/** Render YAML with minimal syntax highlighting. */
export function renderYaml(target: HTMLElement, yaml: string): void {
    const fragment = document.createDocumentFragment();

    for (const line of yaml.split('\n')) {
        if (/^\s*#/.test(line)) {
            fragment.append(h('span', { class: 'tok-comment' }, line));
        } else {
            const match = KEY_LINE.exec(line);

            if (match) {
                fragment.append(match[1], h('span', { class: 'tok-key' }, match[2]), ':');
                appendValue(fragment, match[3]);
            } else {
                appendValue(fragment, line);
            }
        }

        fragment.append('\n');
    }

    target.replaceChildren(fragment);
}

function appendValue(target: DocumentFragment, text: string): void {
    let last = 0;

    for (const match of text.matchAll(VALUE_TOKENS)) {
        target.append(text.slice(last, match.index));
        target.append(h('span', { class: match[1] ? 'tok-string' : match[2] ? 'tok-literal' : 'tok-number' }, match[0]));
        last = match.index + match[0].length;
    }

    target.append(text.slice(last));
}

export function renderIssues(target: HTMLElement, issues: Issue[]): void {
    const errors = issues.filter((issue) => issue.level === 'error').length;
    const warnings = issues.length - errors;

    if (!issues.length) {
        target.replaceChildren(h('p', { class: 'issues-summary ok' }, '✓ No problems found'));

        return;
    }

    const counts = [errors && `${errors} error${errors > 1 ? 's' : ''}`, warnings && `${warnings} warning${warnings > 1 ? 's' : ''}`]
        .filter(Boolean)
        .join(', ');

    target.replaceChildren(h('details', { class: 'issues', open: true },
        h('summary', { class: errors ? 'issues-summary has-errors' : 'issues-summary' }, counts),
        h('ul', {}, issues.map((issue) => h('li', { class: `issue ${issue.level}` },
            h('button', { type: 'button', onclick: () => focusPath(issue.path) },
                h('span', { class: 'issue-level' }, issue.level === 'error' ? 'Error' : 'Warning'),
                ' ',
                issue.message,
            ),
        ))),
    ));
}

/** Flag the form fields that have issues (errors win over warnings). */
export function markFields(root: HTMLElement, issues: Issue[]): void {
    for (const el of root.querySelectorAll('[data-issue]')) {
        el.removeAttribute('data-issue');
        el.removeAttribute('aria-invalid');
    }

    const ordered = [...issues.filter((i) => i.level === 'warning'), ...issues.filter((i) => i.level === 'error')];

    for (const issue of ordered) {
        for (const el of root.querySelectorAll(`[data-path="${CSS.escape(issue.path)}"]`)) {
            el.setAttribute('data-issue', issue.level);

            if (issue.level === 'error') {
                el.setAttribute('aria-invalid', 'true');
            }
        }
    }
}

/** Open the sections around a field, then scroll to and focus it. */
export function focusPath(path: string): void {
    const target = document.querySelector<HTMLElement>(`[data-path="${CSS.escape(path)}"]`);

    if (!target) {
        return;
    }

    for (let el = target.parentElement; el; el = el.parentElement) {
        if (el instanceof HTMLDetailsElement) {
            el.open = true;
        }
    }

    target.scrollIntoView({ block: 'center', behavior: 'smooth' });
    target.focus({ preventScroll: true });
}

/** Example of the final snapshot name, as MyBackup would build it now. */
export function snapshotExample(plan: BackupPlan, now = new Date()): string {
    if (isEnv(plan.snapshot_file)) {
        return 'The name is read from an environment variable.';
    }

    if (plan.snapshot_file === '') {
        return '';
    }

    const expanded = expandPlaceholders(plan.snapshot_file, now);

    if (expanded.error !== undefined) {
        return expanded.error;
    }

    const gz = isEnv(plan.compress) ? '[.gz]' : plan.compress ? '.gz' : '';
    const aes = plan.encryption.enabled ? '.aes' : '';

    return `Now it would be: ${expanded.value}${gz}${aes} (dates in UTC).`;
}
