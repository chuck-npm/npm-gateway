# Gateway workflow email framework

Gateway workflow emails use `GatewayEmailMessage` as their structured content contract and `GatewayEmailRenderer` for the shared NPM Gateway presentation. Workflow senders remain responsible for recipients, subjects, approved route URLs, transport, and failure handling. They must not construct independent standalone HTML documents.

The message contract supports a safe preheader, eyebrow, title, subtitle, readable status and tone, ordered label/value summary rows, titled plain-text sections, a primary action and fallback URL, and an optional footer note. All values are escaped by the renderer; user content is plain text and multiline sections preserve line breaks. The renderer creates both the HTML document and equivalent plain-text alternative.

The HTML framework uses the established navy NPM Gateway header, restrained gold accent, white 640-pixel content surface, Arial/system-safe typography, email-safe presentation tables, inline styles, responsive narrow-screen rules, a VML Outlook button fallback, a visible raw URL, and the standard automated-message footer. It requires no external stylesheet, web font, script, image, or tracking resource.

## Application Review examples

`ApplicationReviewEmailSender` builds an “Application Review Submitted” message with Pending Review status, submission summary, optional Comments for Reviewer section, and Review Application action. Decision messages use Approved or Denied status, reviewer summary, Reviewer Comments, and View Application Review action. Recipient selection remains fail-closed test mode and is not part of the renderer.

## Future workflow examples

- Renovation Request Submitted: title and property subtitle, Pending status, request summary, manager comments, and Review Renovation Request action.
- HVAC Service Request: title and property subtitle, informational status, equipment/service summary, issue-description section, and View Service Request action.
- Credit Card Purchase: title and employee subtitle, pending or completed status, merchant/amount/date summary, business-purpose section, and View Purchase action.

These examples are illustrative only. A future module supplies structured strings and approved URLs to the shared contract; it must not add its own `<html>`, header, footer, arbitrary HTML, recipient lookup, database access, or authorization to the renderer.
