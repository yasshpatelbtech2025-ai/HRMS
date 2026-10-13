# Monthly attendance grid: design spec

Status: **designed, not built**. The interactive prototype runs locally at `/_ui/attendance`
(fictional data, nothing saved). Build the real screen from this when the Attendance module lands.

## Layout

- One row per employee, one column per day, then sticky totals: **P**, **A**, **L**, **Paid days**.
- Sticky: the header row (weekday initial + date), the employee column (code, name, department), and the totals.
- Sundays and other weekly offs are shaded. Today's column has a primary-colour top rule.
- Above the grid: month stepper (‹ October 2026 ›) and Export. The company comes from the top-bar switcher.
- Below the grid: a status line showing the focused cell, the keyboard help and the unsaved-changes count with **Save**.

## Status codes

Every status is a **letter plus a colour**, so the grid is readable without colour (colour-blind users, greyscale printouts).

| Key | Status        | Paid | Look                         |
|-----|---------------|------|------------------------------|
| P   | Present       | 1    | green tint                   |
| A   | Absent        | 0    | red tint                     |
| H   | Half day      | 0.5  | amber tint                   |
| L   | Leave         | 1    | blue tint                    |
| W   | Weekly off    | 1    | grey                         |
| F   | Holiday       | 1    | brand tint                   |
| M   | Missing punch | 0    | amber stripes (needs action) |

Paid-day weights are placeholders. The payroll rules decide the real values.

The legend chips above the grid are also buttons: clicking one marks the current selection.

## Keyboard (the main way to work)

| Keys                         | Action                                                      |
|------------------------------|-------------------------------------------------------------|
| Arrow keys                   | Move one cell                                               |
| Home / End                   | First / last day of the row                                 |
| PageUp / PageDown            | 10 rows up / down                                           |
| Shift + arrows               | Extend the selection rectangle                              |
| P A H L W F M                | Mark the selection. With one cell selected, focus moves right, so a row can be typed straight through: `P P P A P …` |
| Delete / Backspace           | Clear                                                       |
| Ctrl/Cmd + Z                 | Undo the last change (one bulk action = one undo step)      |
| Ctrl/Cmd + A                 | Select the whole month                                      |
| Esc                          | Collapse the selection to the focused cell                  |

Mouse: click a cell, drag or Shift-click to select a range, click a **date header** to select the column,
click an **employee name** to select the row. **Fill blanks as Present** marks every empty cell in the selection.

The grid uses `role="grid"` with a roving `tabindex` (one tab stop for the whole grid), and every cell has an
`aria-label` such as "14 Oct: Present".

## Performance (400–500 employees × 31 days ≈ 15,500 cells)

- Render the grid server-side as a plain table. **No per-cell Alpine bindings.** One delegated listener handles
  keyboard and mouse (`resources/js/ui/attendance-grid.js`). Cells are updated by setting `data-s` and the text.
- Status styling is pure CSS on `[data-s]`. Totals are recomputed only for rows that changed.
- Save sends only changed cells (`{employee_id, date, status}[]`) in one request. Optimistic UI, with a toast on
  failure that keeps the unsaved marks.
- If 500 rows feel slow on office PCs, paginate by department, or add row virtualisation. The API above stays the same.
- Warn before leaving with unsaved changes (same guard as the forms).

## Open questions for the module

1. Which statuses does payroll need: is "Half day" split into first and second half? Is "Comp-off" needed?
2. Are weekly offs per company or per shift?
3. Who can edit past months, and is a month "locked" after payroll runs?
