Before adding any new colors make sure they are added to the bricks color palette.

Every font has to be added to the bricks font palette.

If you're instructed to add an element that doesn't fit existing elements, you need to add a new element to the bricks element palette. From there you can use the element to build your page. Always check the bricks element palette to see if the element you're adding already exists or if any could be modified to fit your needs. Do not repeat yourself.

Respect the local typografie.dc.html file for typography.

Respect the local Rozestupy.dc.html file for spacing. It is the locked layout: bedrock page background, square full-bleed sections, the 1056px content column, the clamp gutter (24–48px), and 96px service-hero padding. New sections use the global classes named in that file. Keep the Novamira design `velocity-gen-z` in sync when that standard changes.

Build on the live development site. Update Typografie.dc.html, Rozestupy.dc.html, and this file when the user changes the standard.

Use the browser to verify the changes you're making. If you don't have access to the browser, ask for help and refuse to do anything until you have access to the browser.

## Bricks element ids

Every Bricks element id is exactly 6 characters from `a-z` and `0-9`. That is what `Bricks\Helpers::generate_random_id` and Novamira produce. Omit the id when creating an element and let the builder generate it.

Hand-written ids of any other length cannot be selected or deleted in the Bricks builder. The front end still renders them, because PHP prints the stored id as `brxe-{id}`. A previous build saved sequential ids such as `sp003` (5), `p04` (3), `ncd1` (4), and `oshbtnc` (7). On Obsahová strategie that left 89 of 103 elements undeletable. The sentence "A nebo ne. Kdo ví :)." was element `sp003`. Služby, Reference, O nás, the header, and the footer stayed editable because every id there was already 6 characters. A later element with a valid id (`hkvzgt`) could be added beside the broken ones, which is why creating worked and deleting did not.

When copying a tree, generate a new 6-character id per element and rewrite `id`, `parent`, `children`, and whole `brxe-` tokens (`#brxe-`, `.brxe-`, `brxe-` attribute values). Rewrite component property `connections` keys in the same change; they are element ids. Leave the component root id equal to the component id. Match tokens with a boundary so a shorter id is not cut out of a longer one (`ncd1` inside `ncd1k`). Leave prose untouched: a short id can appear inside real copy.
