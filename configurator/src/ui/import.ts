import { fromYaml, type ImportResult } from '../model/parse.ts';
import { h } from './dom.ts';

let dialog: HTMLDialogElement | null = null;

/** Show the import dialog; `onImport` receives the parsed plan. */
export function openImport(onImport: (result: ImportResult) => void): void {
    dialog?.remove();

    const error = h('pre', { class: 'import-error', role: 'alert', hidden: true });
    const textarea = h('textarea', {
        rows: 16,
        spellcheck: 'false',
        placeholder: 'Paste a backup plan (YAML) here, or drop a file.',
        'aria-label': 'Backup plan YAML',
    });

    const load = async (file: File | undefined) => {
        if (file) {
            textarea.value = await file.text();
            error.hidden = true;
        }
    };

    textarea.addEventListener('dragover', (event) => {
        event.preventDefault();
        textarea.classList.add('dragging');
    });
    textarea.addEventListener('dragleave', () => textarea.classList.remove('dragging'));
    textarea.addEventListener('drop', (event) => {
        event.preventDefault();
        textarea.classList.remove('dragging');
        void load(event.dataTransfer?.files[0]);
    });

    const submit = () => {
        try {
            onImport(fromYaml(textarea.value));
            dialog?.close();
        } catch (e) {
            error.textContent = (e as Error).message;
            error.hidden = false;
        }
    };

    dialog = h('dialog', { class: 'import-dialog', 'aria-labelledby': 'import-title' },
        h('form', { method: 'dialog', onsubmit: (event: Event) => event.preventDefault() },
            h('h2', { id: 'import-title' }, 'Import a backup plan'),
            h('p', { class: 'hint' }, 'The form is filled from the file. Comments are not kept; keys the form doesn\'t know are kept as extra keys. Nothing leaves your browser.'),
            h('label', { class: 'file' },
                'Open a file: ',
                h('input', {
                    type: 'file',
                    accept: '.yaml,.yml,.json,text/yaml,text/plain',
                    onchange: (event: Event) => void load((event.target as HTMLInputElement).files?.[0]),
                }),
            ),
            textarea,
            error,
            h('div', { class: 'dialog-buttons' },
                h('button', { type: 'button', onclick: () => dialog?.close() }, 'Cancel'),
                h('button', { type: 'button', class: 'primary', onclick: submit }, 'Import'),
            ),
        ),
    );

    document.body.append(dialog);
    dialog.showModal();
    textarea.focus();
}
