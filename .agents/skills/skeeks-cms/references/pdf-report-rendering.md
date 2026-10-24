# HTML/PDF report rendering

Use this reference when a SkeekS CMS feature converts an HTML report to PDF or
shares report markup between the browser and a print renderer.

## Source of truth and options

- Treat document-template settings as the source of truth for orientation,
  cover, footer, page numbers, palette and backgrounds.
- Default to portrait only when the template does not specify orientation.
- Propagate options into the renderer API, not only into preview CSS. Enable
  background printing and reserve enough page margin for the footer.
- Selected report columns control both task content and summary metrics. If
  worked time is not selected, omit worked-time metadata and its title metric.
- Do not rely on database IDs for built-in templates. Seed and update defaults
  through idempotent semantic migrations keyed by stable attributes and flags.

## Rich task results

- A task may have several result comments. Render every comment as a separate
  result block in source order; do not concatenate them into one string.
- Preserve sanitized semantic HTML, including paragraphs, line breaks, lists,
  links and blockquotes.
- Keep the configured font size. Do not shrink long comments to force them onto
  one page.
- Render result attachments in both HTML and PDF. Preview resolvable images and
  list other files with their filename and link.
- If an image is missing or unreachable, suppress the broken image and show a
  file fallback instead.

## Pagination and visual continuity

- Long comments may fragment across pages. Do not apply `break-inside: avoid`
  to unbounded task or result bodies.
- Keep only small atomic pieces together, such as a task title and its metadata.
- Parent backgrounds, borders, radii and padding can disappear or restart when
  an element fragments. Put the visual surface and padding on fragmentable
  result blocks, or use renderer-supported decoration cloning; do not depend on
  one large parent rectangle.
- Avoid fixed heights and large minimum heights that cause overflow or blank
  pages.
- Keep the content width consistent on every page and continuation fragment.
- Reserve bottom space for the footer and leave a clear gap between its rule
  and text.
- Ensure page and card backgrounds are printed on every page.

## Verification

Verify the generated PDF, not only the HTML preview. Cover these cases:

- portrait and landscape orientation;
- footer, page numbers and backgrounds enabled and disabled;
- worked-time column selected and omitted;
- several result comments on one task;
- a long rich-text comment spanning pages;
- links, lists and blockquotes;
- image and non-image attachments;
- a missing image;
- a task without a result.

Render PDF pages to images and check for blank tasks, large accidental gaps,
clipped text, lost card surfaces, inconsistent widths, missing backgrounds and
missing footer or page numbers.
