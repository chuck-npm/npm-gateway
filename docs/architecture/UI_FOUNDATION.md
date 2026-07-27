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
