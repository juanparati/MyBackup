import { FILESYSTEM_NAMES_LIST } from './model/blocks.ts';
import { initPlan } from './model/defaults.ts';
import { EXAMPLE_YAML } from './model/example.ts';
import { fromYaml } from './model/parse.ts';
import { MASK, replaceSecrets } from './model/secrets.ts';
import { toYaml } from './model/serialize.ts';
import type { BackupPlan } from './model/types.ts';
import { validate } from './model/validate.ts';
import { changed, clearedSecrets, onChange, onReset, plan, replacePlan } from './store.ts';
import { h } from './ui/dom.ts';
import { openImport } from './ui/import.ts';
import { markFields, renderIssues, renderYaml, snapshotExample } from './ui/preview.ts';
import { postActionsSection } from './ui/sections/actions.ts';
import { databasesSection } from './ui/sections/databases.ts';
import { filesystemsSection } from './ui/sections/filesystems.ts';
import { connectionSection, generalSection, otherSection } from './ui/sections/general.ts';
import { notificationsSection } from './ui/sections/notifications.ts';
import { dumpSection, outputSection } from './ui/sections/output.ts';

const FILE_NAME = 'backup_plan.yaml';

const $ = <T extends HTMLElement>(id: string) => document.getElementById(id) as T;

const form = $<HTMLFormElement>('form');
const yamlView = $<HTMLElement>('yaml');
const issuesView = $<HTMLElement>('issues');
const notice = $<HTMLElement>('notice');
const reveal = $<HTMLButtonElement>('reveal');

/** The preview masks secrets unless they are revealed; copy and download always use the real values. */
let showSecrets = false;

function renderForm(current: BackupPlan): void {
    form.replaceChildren(
        generalSection(current),
        connectionSection(current),
        databasesSection(current),
        outputSection(current),
        dumpSection(current),
        notificationsSection(current),
        filesystemsSection(current),
        postActionsSection(current),
        otherSection(current),
    );
}

function update(current: BackupPlan): void {
    const issues = validate(current);

    renderYaml(yamlView, toYaml(showSecrets ? current : replaceSecrets(current, MASK)));
    renderIssues(issuesView, issues);
    markFields(form, issues);

    const preview = document.getElementById('snapshot-preview');

    if (preview) {
        preview.textContent = snapshotExample(current);
    }

    $(FILESYSTEM_NAMES_LIST).replaceChildren(...['local', ...current.filesystems.map((fs) => fs.name)]
        .filter((name, i, names) => name !== '' && names.indexOf(name) === i)
        .map((name) => h('option', { value: name })));
}

function showNotice(title: string, items: string[]): void {
    notice.replaceChildren(
        h('div', { class: 'notice-head' },
            h('strong', {}, title),
            h('button', { type: 'button', class: 'icon', 'aria-label': 'Dismiss', onclick: () => (notice.hidden = true) }, '✕'),
        ),
        h('ul', {}, items.map((item) => h('li', {}, item))),
    );
    notice.hidden = items.length === 0;
}

function confirmReplace(): boolean {
    return confirm('Replace the current backup plan? Unsaved changes are lost.');
}

function setShowSecrets(show: boolean): void {
    showSecrets = show;
    reveal.setAttribute('aria-pressed', String(show));
    reveal.textContent = show ? 'Hide secrets' : 'Show secrets';
    update(plan());
}

reveal.addEventListener('click', () => setShowSecrets(!showSecrets));

$('new').addEventListener('click', () => {
    if (confirmReplace()) {
        notice.hidden = true;
        replacePlan(initPlan());
    }
});

$('example').addEventListener('click', () => {
    if (confirmReplace()) {
        notice.hidden = true;
        replacePlan(fromYaml(EXAMPLE_YAML).plan);
    }
});

$('import').addEventListener('click', () => openImport(({ plan: imported, warnings }) => {
    replacePlan(imported);
    showNotice('Imported with notes:', warnings);
}));

$('copy').addEventListener('click', async (event) => {
    const button = event.currentTarget as HTMLButtonElement;

    try {
        await navigator.clipboard.writeText(toYaml(plan()));
        button.textContent = 'Copied';
    } catch {
        // The selection is copied from the preview, so it has to show the real values.
        setShowSecrets(true);
        getSelection()?.selectAllChildren(yamlView);
        button.textContent = 'Press Ctrl+C';
    }

    setTimeout(() => (button.textContent = 'Copy'), 1500);
});

$('download').addEventListener('click', () => {
    const url = URL.createObjectURL(new Blob([toYaml(plan())], { type: 'application/yaml' }));
    const link = h('a', { href: url, download: FILE_NAME });
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
});

form.addEventListener('submit', (event) => event.preventDefault());

onReset(renderForm);
onChange(update);
renderForm(plan());
changed();
showNotice('Secrets are not kept when the page reloads. Enter them again:', clearedSecrets());
