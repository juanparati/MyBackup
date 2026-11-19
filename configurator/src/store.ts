import { initPlan } from './model/defaults.ts';
import { fromYaml } from './model/parse.ts';
import { replaceSecrets, writtenSecrets } from './model/secrets.ts';
import { toYaml } from './model/serialize.ts';
import type { BackupPlan } from './model/types.ts';

type Listener = (plan: BackupPlan) => void;

interface Draft {
    yaml: string;
    /** Labels of the secrets left out of `yaml`. */
    cleared: string[];
}

/** Session storage keeps the draft across reloads, but not after the tab is closed. Secrets are left out. */
const DRAFT_KEY = 'mybackup-configurer:draft';
const DEBOUNCE_MS = 120;

let cleared: string[] = [];
let current: BackupPlan = loadDraft() ?? initPlan();
let timer: ReturnType<typeof setTimeout> | undefined;
const changeListeners: Listener[] = [];
const resetListeners: Listener[] = [];

export function plan(): BackupPlan {
    return current;
}

/** Secrets the plan had before the page was reloaded, which the draft doesn't keep. */
export function clearedSecrets(): string[] {
    return cleared;
}

/** Called after the form edits the plan in place. */
export function changed(): void {
    clearTimeout(timer);
    timer = setTimeout(notify, DEBOUNCE_MS);
}

/** Replace the whole plan (new, example, import): the form is rebuilt. */
export function replacePlan(next: BackupPlan): void {
    current = next;
    resetListeners.forEach((listener) => listener(current));
    clearTimeout(timer);
    notify();
}

export function onChange(listener: Listener): void {
    changeListeners.push(listener);
}

export function onReset(listener: Listener): void {
    resetListeners.push(listener);
}

function notify(): void {
    changeListeners.forEach((listener) => listener(current));
    saveDraft();
}

function loadDraft(): BackupPlan | null {
    try {
        const stored = sessionStorage.getItem(DRAFT_KEY);

        if (!stored) {
            return null;
        }

        const draft = JSON.parse(stored) as Draft;
        const loaded = fromYaml(draft.yaml).plan;
        cleared = draft.cleared;

        return loaded;
    } catch {
        return null;
    }
}

function saveDraft(): void {
    const draft: Draft = {
        yaml: toYaml(replaceSecrets(current)),
        cleared: writtenSecrets(current).map((secret) => secret.label),
    };

    try {
        sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
    } catch {
        // Storage can be unavailable (private mode, blocked site data); the draft just isn't kept.
    }
}
