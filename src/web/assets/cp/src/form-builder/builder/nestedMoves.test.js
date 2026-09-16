import { it } from 'vitest';

// Run the existing model-based move checks through Vite so builder aliases resolve.
it('preserves field placement and nested-container constraints', async () => {
    await import('../../../scripts/test-nested-moves.mjs');
});
