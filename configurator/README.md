# MyBackup Configurer

A static page to create and edit backup plans for [MyBackup](https://github.com/juanparati/MyBackup), the MySQL/MariaDB backup tool.

MyBackup reads its YAML plan without validating it, so mistakes only show up when a backup runs. This page builds the plan from a form, shows the YAML as you type and points out problems before you run anything.

- Form for every option: connection, databases and tables, compression, encryption, rotation, dump text, Slack/e-mail notifications, filesystems (S3, GCS, FTP, SFTP, local) and post actions.
- **Import** an existing plan to edit it. Keys the form doesn't know are kept and stay editable as YAML.
- Any value can come from an **environment variable** (`%env(cast->NAME:default)%`).
- **Placeholder** menus (`{{datetime}}`, `{{snapshot_file}}`…) and a preview of the snapshot file name.
- **Validation** of the rules MyBackup doesn't check. Examples: a rotation of `0` also deletes the new snapshot, `"false"` in quotes counts as true, the name `local` is reserved, and mail needs `transport` and `from`.
- **Copy** or **download** `backup_plan.yaml`.

Everything runs in the browser and nothing is uploaded. A Content-Security-Policy blocks all network connections, so the page couldn't send a plan anywhere even if a dependency tried.

Credentials (passwords, keys, the Slack webhook, the GCS service account key, a pasted SFTP private key) are handled as secrets (`src/model/secrets.ts`):

- The YAML preview masks them until you press **Show secrets**. **Copy** and **Download** always use the real values.
- The draft is kept in `sessionStorage`, so it survives a reload and is gone when the tab closes. Secrets are left out of it, and after a reload the page lists the ones to enter again.
- Validation warns about each secret written in plain text, since an environment variable keeps it out of the file.

Values under **Extra keys** aren't recognised as secrets.

## Development

Requires Node 22.18+ (tests run TypeScript directly with Node's type stripping).

```sh
npm install
npm start           # dev server on http://localhost:1234 (no hot reload: the CSP blocks it)
npm test            # unit tests (node:test)
npm run typecheck   # tsc --noEmit
npm run build       # production build in dist/
```

`dist/` is a plain static site with relative paths, so any static host can serve it, under any path. Use a web server rather than opening `index.html` from disk, because browsers block module scripts on `file://`.

## Structure

```
src/
  model/        plan model, YAML import (parse.ts) and export (serialize.ts),
                validation, env references, placeholder engine, field definitions,
                secrets (secrets.ts)
  ui/           DOM helper, form fields, sections, preview and import dialog
  store.ts      current plan, change notifications, draft storage
  main.ts       page wiring
test/           unit tests and fixtures copied from MyBackup
```

The form edits a typed model (`src/model/types.ts`) rather than raw YAML. `parse.ts` and `serialize.ts` convert between the two, and validation issues carry the model path that is also the `data-path` of the form field.

The filesystem drivers, post actions and notification channels are described as data in `src/model/blocks.ts`. To support a new option, add a field definition there.

### Parcel alias for `yaml`

`package.json` aliases `yaml` to `yaml/browser/dist/index.js` for Parcel only. The package's default browser entry (`import * as YAML …; export default YAML`) is broken by Parcel's scope hoisting. TypeScript and Node keep resolving `yaml` normally.

## Checking a plan

The page checks what it can without a database. Before using a plan, still run:

```sh
mybackup backup backup_plan.yaml --dry
```
