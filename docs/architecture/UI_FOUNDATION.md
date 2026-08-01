# Gateway UI Foundation

Authenticated pages use a semantic skip link, dark top navigation, main content
region, and permanent footer. The route-aware navigation configuration contains
only real destinations. A permanent sidebar, decorative module links, and fake
workflows are prohibited.

Reusable components escape text by default and provide page headings,
breadcrumbs, summary cards, status badges, alerts, and empty states. Dashboard
facts come from application services and repository reads; views never query
the database or inspect environment variables.

Keyboard focus remains visible, active navigation uses `aria-current`, and
Bootstrap collapse/dropdown controls provide responsive navigation. Cards flow
from one column on narrow screens to multiple columns on wider screens without
requiring custom JavaScript.

Empty states describe the current verified condition and clearly identify
future tasks as unavailable. Charts, trends, health labels, or activity are
shown only when supported by an implemented source of truth.

## Operations-home patterns

The welcome banner is compact (approximately 150–190 pixels on desktop), uses
an owned CSS gradient, and presents the authenticated display name, employee
class, and job title with sufficient contrast. It is not a marketing hero.

A tool section owns a semantic heading, concise description, optional scope
label, and a collection of typed tool cards. It renders nothing for an empty
collection, allowing future Corporate, Manager, Property, and Administration
providers to be added without empty headings.

Each card contains a functional category, title, concise description, and
footer action or text status. Universal scope is established by the section;
cards do not repeat `GLOBAL`. Enabled cards use one accessible anchor and a
real approved internal route. Disabled cards use semantic non-interactive
markup, include a visible text status, and have no destination or tab stop.

The CSS grid presents four, three, two, then one column across large desktop,
medium desktop, tablet, and narrow mobile widths. Cards have no fixed desktop
width, transitions respect reduced-motion settings, and keyboard focus remains
visible. The authenticated shell has no sidebar. Fake data, decorative
metrics, fake activity, and unavailable navigation links are prohibited.

## Corporate navigation pattern

For authenticated users approved by the corporate-access service, page order is
Welcome, Universal Tools, Corporate Tools, then System Status. Employee class
and employee contact data do not control this presentation decision; access
membership uses the permanent Gateway username. The Corporate section reuses the standard
tool-section and tool-card components and contains four priority cards:
Finance, Human Resources, Marketing, and Admin. It is omitted entirely when its
filtered collection is empty.

The top navigation may contain a Corporate dropdown for the same authenticated
presentation context. Its trigger uses Bootstrap's existing accessible
dropdown behavior. Unavailable areas render as non-focusable text with a
visible `Planned` status—never as anchors, routes, buttons, or JavaScript
actions. The dropdown remains inside the collapsible mobile navigation and the
user menu remains separate. No sidebar is introduced.

## Read-only workspace pattern

A workspace uses breadcrumbs, one semantic page heading, a GET-only directory
toolbar, truthful result count, responsive results, and bounded pagination.
Empty datasets and filtered zero-results use
distinct messages and only real Clear filters links.

Directories use a responsive table with controlled local overflow. Missing
information is stated explicitly. Read-only modules do
not display disabled Add, Edit, Delete, Import, or Export controls and do not
ship employee datasets to client-side JavaScript.

The Company Directory is search-first: a prominent employee-name search
precedes secondary class, status, and sorting controls. Result information is
ordered around name, title, operational context, company phone, business
email, class, status, and Gateway access. `Not provided` is used for absent
company contact data without falling back to personal information.

On narrow screens, directory table rows become labeled employee cards rather
than compressed columns. The universal directory has no profile/detail
interaction. No photographs, avatars, image placeholders, or photo
dependencies are used.

Company Directory presents universal approved contact information. Restricted
complete employee records and detail workflows belong to the future Human
Resources module and are not linked from the directory.

## Properties and Human Resources workspaces

The universal and HR property directories reuse one query service, formatter,
and table partial. Both show PropID, Name, one complete selectable Address,
Phone, IVR, and Manager in that order. HR alone appends Action and Add Property.
Until editing is implemented, its edit glyph is non-interactive, unfocusable,
and labeled `Editing not yet enabled`.

The Human Resources landing page uses exactly three standard tool cards in
order: enabled Employees, enabled Properties, and disabled Credit Cards. It has
no Appliances card and does not add a fourth card for visual symmetry.

Major entity creation uses a dedicated authenticated page rather than an
overlay. HR property creation lives at `/human-resources/properties/create`,
with normal breadcrumbs, page context, sectioned responsive form cards, and
browser scrolling. Validation follows PRG back to that page with a summary,
field errors, and preserved safe input. Modals remain reserved for small,
bounded actions such as confirmations and status changes.
### Add Employee

Major employee creation uses a dedicated authenticated page, never a modal. It follows the Add Property card, section separator, responsive two-column grid, validation summary, field-error, and bottom action-footer conventions. Cancel is a normal link; Create Employee is the only submit action. Employee number and password are generated and therefore not inputs. Both phone fields use the shared `data-phone-mask` behavior, and the editable username suggestion begins with the normalized lowercase first name without overwriting manual changes.
## Restricted HR employee form

The Add Employee form collects required Date of Birth using a date input without a default. Personal phone, personal email, and Employee Notes are visibly optional. Personal phone retains progressive-enhancement masking, and the form remains usable without JavaScript. Date of Birth and optional private fields do not appear in directory, dashboard, or property UI.
