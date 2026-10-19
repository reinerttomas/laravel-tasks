---
paths:
    - 'resources/js/**'
    - 'components.json'
    - 'package.json'
---

# Frontend

## Adding shadcn components

The project uses the `radix-ui` umbrella package, so registry components install as published. The shadcn CLI has repo-specific quirks; after every `shadcn add`, check `git diff package.json bun.lock` and grep the new files for `from "cn"`:

- It installs an unrelated `cn` npm package and writes `import { cn } from "cn"`. Revert package.json/bun.lock and import `cn` from `@/lib/utils`.
- It prompts before overwriting an existing file (`dialog.tsx`, `button.tsx`), `--yes` does not answer, and the run hangs. Keep the starter kit's `dialog.tsx` (its `DialogContent` has no `showCloseButton` prop).
- Fallback when the CLI hangs: fetch `https://ui.shadcn.com/r/styles/new-york-v4/<name>.json` and write the file by hand.
- `shadcn migrate` rewrites package.json with 2-space indent and `"latest"` versions; restore it and change dependencies with `bun add` / `bun remove`.

## Radix focus inside menus

A `DropdownMenuItem` that reveals an `autoFocus` input loses focus to the menu's focus trap. Focus the input from `DropdownMenuContent onCloseAutoFocus` instead (see `components/comment-thread.tsx`).
