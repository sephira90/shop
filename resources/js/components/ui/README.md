# UI Components Structure

`ui` components are organized by responsibility:

- `actions/`: action containers and actionable controls.
- `forms/`: form fields, semantic inputs, and form wrappers.
- `layout/`: layout primitives for cards and grids.
- `feedback/`: notices and empty-state messages.
- `data-display/`: chips, badges, metrics, details, and pagination.
- `table/`: table wrappers and table-specific utility cells.
- `typography/`: semantic text/title primitives.

Placement rule:

- If a component is generic and transport-agnostic, place it under `ui/*`.
- If a component contains domain semantics (orders/products/promotions/etc.), place it under the corresponding domain folder.
