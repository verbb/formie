// Generated browser schema; the PHP-shipped schema is the canonical source.
import { copyFileSync } from 'node:fs';
copyFileSync(new URL('../src/conditions/schema.json', import.meta.url), new URL('../packages/formie-core/src/condition-schema.json', import.meta.url));
