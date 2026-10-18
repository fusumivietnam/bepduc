# Source-backed buying and installation metadata

Bepduc advisory metadata is optional WooCommerce **product post meta**, not a new product database. The schema lives in `wp-content/plugins/bepduc-core/src/Advisor/ProductFields.php`, and is available through the WooCommerce General product editor.

| Internal key | Editorial purpose |
| --- | --- |
| `_bd_buying_advice` | Expert buying advice |
| `_bd_suitable_for` | Intended user or kitchen needs |
| `_bd_installation_notes` | Model-specific installation cautions |
| `_bd_cutout_dimensions` | Manufacturer's cutout dimensions with units, NEVER inferred from shipping dimensions |
| `_bd_source_reference` | Document ID, revision or source identifier required before public display |

Editors must verify claims against **official manufacturer documentation** before setting the source reference. All public advice is hidden when the source is absent. Only non-empty values are displayed, escaped as plain text; the source reference itself is not published by the block. This mechanism is a **source-reference gate, not independent factual verification**.

The core plugin registers sanitized REST-visible strings. Editing is limited to users authorized to edit WooCommerce products. It does not require premium ACF; ACF Local JSON remains available for future editorial schemas.

No demo seed is changed and no product-specific installation claim is invented. The generic installation reminder remains even when sourced advisory metadata is absent. For variable products, parent-level advice must apply to all variants; variant-specific documentation will need a separate schema and user interface.

Operational QA: verify no notes appear without a source; verify note appears after source is saved; check for HTML escaping, editor authorization and UX on mobile. Do not use this as a warranty/service promise.
