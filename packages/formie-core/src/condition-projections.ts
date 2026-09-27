/** Row scopes consume normalized rows; selectors and row identity remain explicit. */
export function selectConditionRows(rows: unknown[], params: Record<string, string>, current?: number): { value?: unknown; diagnostic?: string } {
    const scope = params.scope;
    if (scope === 'count') return { value: rows.length };
    if (scope === 'all') return { value: rows };
    if (scope === 'first') return { value: rows[0] ?? null };
    if (scope === 'last') return { value: rows[rows.length - 1] ?? null };
    if (scope === 'current' || scope === 'index') {
        const index = scope === 'current' ? current : /^[0-9]+$/.test(params.index ?? '') ? Number(params.index) : undefined;
        return index !== undefined && index >= 0 && index < rows.length ? { value: rows[index] } : { diagnostic: 'invalidRowScope' };
    }
    if (scope === 'rows') {
        const selection = params.rows ?? '';
        if (!/^(?:even|odd|every:[1-9]\d*|[1-9]\d*(?:\s*-\s*[1-9]\d*)?(?:\s*,\s*[1-9]\d*(?:\s*-\s*[1-9]\d*)?)*)$/.test(selection)) return { diagnostic: 'invalidRowScope' };
        const selected = rows.filter((_, index) => {
            if (selection === 'even') return index % 2 === 1;
            if (selection === 'odd') return index % 2 === 0;
            if (selection.startsWith('every:')) return index % Number(selection.slice(6)) === 0;
            return selection.split(',').some((range) => { const [start, end = start] = range.split('-').map(Number); return index + 1 >= Math.min(start, end) && index + 1 <= Math.max(start, end); });
        });
        return { value: selected.length === 1 ? selected[0] : selected };
    }
    return { diagnostic: 'missingRowScope' };
}
