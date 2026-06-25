# Synced Fields

Synced fields let you reuse the same field definition across multiple forms.

They are useful when a field should stay consistent everywhere it appears, instead of becoming a separate copy in each form. When a field is added as a synced field, Formie links it to the original field, so changes made to one instance are reflected everywhere that synced field is used.

This is especially useful for fields that appear repeatedly across a site, such as email addresses, phone numbers, consent checkboxes, and other common contact details.

## When to use synced fields

Synced fields are a good fit when:

- the field should behave the same way in every form
- you want one change to update every use of that field
- you are trying to avoid duplicated maintenance

They are a poor fit when a field only looks similar but needs to drift over time. In that case, a normal copied field is usually the better choice.

## Adding a synced field

In the form builder, add a field from the existing fields picker instead of creating a new one. When you choose an existing field, you can add it as a synced field so it stays linked to the original.

![Synced field badge in the builder](../_screenshots/forms/synced-field.png)

That same picker can also add a normal copied field instead. The difference is important:

- a copied field starts as a duplicate, then becomes independent
- a synced field stays linked

Because the field stays linked, editing it in one form means editing it everywhere. That is what makes synced fields useful, but it is also why they need a little more care.

## Synced fields inside groups

You can add existing or synced fields inside **Group** fields from the form builder. This is useful when you want independent group wrappers — such as delivery and invoice address blocks — that each contain the same reusable field definition.

Each group keeps its own label and settings, while synced nested fields stay linked to the shared definition. Submission values are namespaced by the group handle, for example `deliveryAddress.email` and `invoiceAddress.email`.

## Working with shared fields

Formie warns you about this in the builder, but it is still worth being deliberate. If you need an independent version later, the usual answer is to create a new field rather than trying to keep changing a shared one.

## Synced fields in stencils

You can add synced fields while building a [stencil](/forms/stencils). The stencil stores the shared definition by handle so new forms created from that stencil link to the same field definition.

Project stencils resolve synced fields by handle, so the shared definition should already exist on each environment (or Formie will create an independent copy from the stencil field settings).

## Synced fields and multi-site

Synced fields store their shared label, handle, and definition settings in one global field definition. Every placement on every form points at that same definition.

On multi-site projects, [content translation overrides](/forms/multi-site#content-translation) still apply per **field placement** — keyed by each placement’s reference, not by the shared definition id. That means:

- editing a synced field on the form’s **source site** updates the shared definition, which affects every form and every site that does not have a site override for that placement
- editing a synced field on a **secondary site** saves a site override for that placement only; the shared definition stays unchanged
- if the same synced field appears twice on one form — for example inside delivery and invoice groups — each placement has its own reference, so secondary sites can translate each placement independently even though they share one definition

Per-placement settings such as **required** remain local to each form field row. Site overrides can change translatable placement settings the same way as for normal fields.

If you need completely different labels or behaviour per site without sharing a definition, use a copied field instead of a synced one.
