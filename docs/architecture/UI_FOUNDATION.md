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
