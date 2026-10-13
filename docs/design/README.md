# Design system ("Ledger")

A calm, paper-like work tool: dense, high-contrast tables, with colour reserved for meaning (status).
Local reference page: **`/_ui`**, available when `APP_ENV=local`.

## Where things live

| What                       | Where                                                    |
|----------------------------|----------------------------------------------------------|
| Colour, radius, size tokens| `resources/css/app.css` (`:root` and `[data-theme=dark]`) |
| Tailwind mapping           | `tailwind.config.js` (`bg-surface`, `text-ink-2`, `bg-primary`, …) |
| Components                 | `resources/views/components/ui/*` (`<x-ui.button>`, `<x-ui.data-table>`, …) |
| Icons                      | `resources/views/components/icon.blade.php` (Heroicons outline) |
| Behaviour                  | `resources/js/ui/*` (Alpine components)                  |
| Menu, breadcrumbs, palette | `config/navigation.php`                                  |
| Product name and logo mark | `config/branding.php` or `BRAND_NAME` / `BRAND_MARK` / `BRAND_TAGLINE` in `.env` |

## Rules of thumb

- Use tokens (`text-ink-2`, `border-line`), never raw Tailwind greys or hex values, so dark mode keeps working.
- Numbers, IDs, PAN and Aadhaar use `font-mono`. It has tabular figures, so columns line up.
- Every list page: `x-ui.page-header` (title + primary action), then `x-ui.data-table`.
- Destructive actions go through the shared confirm dialog (`x-ui.menu-item :confirm="[...]"`) and flash `undo`
  with a restore URL, so the toast offers Undo.
- Flash `status` / `error` from controllers as before. They appear as toasts.
- Numbers on the dashboard must come from real data. Unbuilt modules use `<x-ui.stat-card pending />`.

See `attendance-grid.md` for the attendance screen spec.
