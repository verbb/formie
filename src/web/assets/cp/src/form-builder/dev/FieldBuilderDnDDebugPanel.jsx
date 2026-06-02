import { useEffect, useMemo, useState } from 'react';
import { useDragDropManager } from '@dnd-kit/react';

const buildExpectedTopLevelDropzoneIds = (pageIndex, page) => {
    const rows = page?.rows || [];
    const ids = [];

    rows.forEach((row, rowIndex) => {
        if (rowIndex === 0) {
            ids.push(`row-${pageIndex}-before-${row._id}`);
        }

        ids.push(`row-${pageIndex}-after-${row._id}`);

        const fields = row?.fields || [];
        fields.forEach((field, fieldIndex) => {
            if (fieldIndex === 0) {
                ids.push(`field-${pageIndex}-${row._id}-before-${field._id}`);
            }

            ids.push(`field-${pageIndex}-${row._id}-after-${field._id}`);
        });
    });

    return ids;
};

const buildExpectedTopLevelDraggableIds = (page) => {
    const rows = page?.rows || [];
    const ids = [];

    rows.forEach((row) => {
        const fields = row?.fields || [];

        fields.forEach((field) => {
            if (!field?._id) {
                return;
            }

            ids.push(`draggable-field-${field._id}`);
        });
    });

    return ids;
};

function FieldBuilderDnDDebugPanel({ pageIndex, page }) {
    const manager = useDragDropManager();
    const [snapshot, setSnapshot] = useState([]);
    const [draggableSnapshot, setDraggableSnapshot] = useState([]);
    const [duplicateDiagnostics, setDuplicateDiagnostics] = useState([]);

    useEffect(() => {
        const syncSnapshot = () => {
            const registry = manager?.registry?.droppables;
            const draggableRegistry = manager?.registry?.draggables;

            if (!registry) {
                setSnapshot([]);
                setDuplicateDiagnostics([]);
            } else {
                const containers = Array.from(registry);
                const ids = containers.map((container) => {
                    return String(container.id);
                }).sort();

                setSnapshot(ids);

                const stats = new Map();
                containers.forEach((container) => {
                    const id = String(container.id);
                    const element = container?.element ?? container?.node?.current ?? null;
                    const hasElement = Boolean(element);
                    const isConnected = Boolean(element?.isConnected);
                    const current = stats.get(id) || {
                        id,
                        total: 0,
                        withElement: 0,
                        connected: 0,
                    };
                    current.total += 1;

                    if (hasElement) {
                        current.withElement += 1;
                    }

                    if (isConnected) {
                        current.connected += 1;
                    }

                    stats.set(id, current);
                });

                setDuplicateDiagnostics(Array.from(stats.values()));
            }

            if (!draggableRegistry) {
                setDraggableSnapshot([]);
            } else {
                const draggableIds = Array.from(draggableRegistry).map((container) => {
                    return String(container.id);
                }).sort();

                setDraggableSnapshot(draggableIds);
            }
        };

        syncSnapshot();
        const timerId = window.setInterval(syncSnapshot, 200);

        return () => {
            window.clearInterval(timerId);
        };
    }, [manager]);

    const rowIds = snapshot.filter((id) => {
        return id.startsWith('row-');
    });
    const fieldIds = snapshot.filter((id) => {
        return id.startsWith('field-') && !id.startsWith('field-type-');
    });
    const expectedIds = useMemo(() => {
        return buildExpectedTopLevelDropzoneIds(pageIndex, page);
    }, [pageIndex, page]);
    const actualTopLevelIds = useMemo(() => {
        return snapshot.filter((id) => {
            return id.startsWith('row-') || id.startsWith('field-');
        });
    }, [snapshot]);
    const missingIds = useMemo(() => {
        return expectedIds.filter((id) => {
            return !actualTopLevelIds.includes(id);
        });
    }, [expectedIds, actualTopLevelIds]);
    const unexpectedIds = useMemo(() => {
        return actualTopLevelIds.filter((id) => {
            return !expectedIds.includes(id);
        });
    }, [actualTopLevelIds, expectedIds]);
    const expectedDraggableIds = useMemo(() => {
        return buildExpectedTopLevelDraggableIds(page);
    }, [page]);
    const actualTopLevelDraggableIds = useMemo(() => {
        return draggableSnapshot.filter((id) => {
            return id.startsWith('draggable-field-');
        });
    }, [draggableSnapshot]);
    const missingDraggableIds = useMemo(() => {
        return expectedDraggableIds.filter((id) => {
            return !actualTopLevelDraggableIds.includes(id);
        });
    }, [expectedDraggableIds, actualTopLevelDraggableIds]);
    const unexpectedDraggableIds = useMemo(() => {
        return actualTopLevelDraggableIds.filter((id) => {
            return !expectedDraggableIds.includes(id);
        });
    }, [actualTopLevelDraggableIds, expectedDraggableIds]);
    const duplicateRegistryIds = useMemo(() => {
        const counts = new Map();

        snapshot.forEach((id) => {
            counts.set(id, (counts.get(id) || 0) + 1);
        });

        return Array.from(counts.entries())
            .filter(([, count]) => {
                return count > 1;
            })
            .map(([id, count]) => {
                return { id, count };
            })
            .sort((a, b) => {
                return b.count - a.count;
            });
    }, [snapshot]);
    const connectedDuplicateRegistryIds = useMemo(() => {
        return duplicateDiagnostics
            .filter((entry) => {
                return entry.total > 1;
            })
            .filter((entry) => {
                return entry.connected > 1;
            })
            .sort((a, b) => {
                return b.connected - a.connected;
            });
    }, [duplicateDiagnostics]);

    return (
        <div className="fixed bottom-3 left-3 z-[9999] max-h-[40vh] w-[380px] overflow-y-auto rounded border border-slate-300 bg-white/95 p-2 text-[11px] text-slate-700 shadow">
            <div className="font-semibold">
                {`Droppables: ${snapshot.length} | row: ${rowIds.length} | field: ${fieldIds.length}`}
            </div>
            <div className="mt-1">
                {`Expected top-level IDs: ${expectedIds.length} | Missing: ${missingIds.length} | Unexpected: ${unexpectedIds.length}`}
            </div>
            <div className="mt-1">
                {`Draggables: ${actualTopLevelDraggableIds.length} | Expected: ${expectedDraggableIds.length} | Missing: ${missingDraggableIds.length} | Unexpected: ${unexpectedDraggableIds.length}`}
            </div>
            <div className="mt-1">
                {`Duplicate registry IDs: ${duplicateRegistryIds.length}`}
            </div>
            <div className="mt-1">
                {`Connected duplicates: ${connectedDuplicateRegistryIds.length}`}
            </div>

            {duplicateRegistryIds.length > 0 && (
                <div className="mt-1 border-t border-rose-200 pt-1 text-rose-700">
                    <div className="font-semibold">Duplicate Registry IDs</div>
                    {duplicateRegistryIds.map((entry) => {
                        return (
                            <div key={`dup-${entry.id}`} className="break-all whitespace-pre-wrap font-mono leading-4">
                                {`${entry.id}\n(count x${entry.count})`}
                            </div>
                        );
                    })}
                </div>
            )}

            {connectedDuplicateRegistryIds.length > 0 && (
                <div className="mt-1 border-t border-amber-200 pt-1 text-amber-700">
                    <div className="font-semibold">Connected Duplicate Registry IDs</div>
                    {connectedDuplicateRegistryIds.map((entry) => {
                        return (
                            <div key={`dup-live-${entry.id}`} className="break-all whitespace-pre-wrap font-mono leading-4">
                                {`${entry.id}\n(connected x${entry.connected} / withElement x${entry.withElement} / total x${entry.total})`}
                            </div>
                        );
                    })}
                </div>
            )}

            {missingDraggableIds.length > 0 && (
                <div className="mt-1 border-t border-rose-200 pt-1 text-rose-700">
                    <div className="font-semibold">Missing Draggable IDs</div>
                    {missingDraggableIds.map((id) => {
                        return (
                            <div key={`missing-draggable-${id}`} className="truncate font-mono leading-4">
                                {id}
                            </div>
                        );
                    })}
                </div>
            )}

            {unexpectedDraggableIds.length > 0 && (
                <div className="mt-1 border-t border-amber-200 pt-1 text-amber-700">
                    <div className="font-semibold">Unexpected Draggable IDs</div>
                    {unexpectedDraggableIds.map((id) => {
                        return (
                            <div key={`unexpected-draggable-${id}`} className="truncate font-mono leading-4">
                                {id}
                            </div>
                        );
                    })}
                </div>
            )}

            {missingIds.length > 0 && (
                <div className="mt-1 border-t border-rose-200 pt-1 text-rose-700">
                    <div className="font-semibold">Missing IDs</div>
                    {missingIds.map((id) => {
                        return (
                            <div key={`missing-${id}`} className="truncate font-mono leading-4">
                                {id}
                            </div>
                        );
                    })}
                </div>
            )}

            {unexpectedIds.length > 0 && (
                <div className="mt-1 border-t border-amber-200 pt-1 text-amber-700">
                    <div className="font-semibold">Unexpected IDs</div>
                    {unexpectedIds.map((id) => {
                        return (
                            <div key={`unexpected-${id}`} className="truncate font-mono leading-4">
                                {id}
                            </div>
                        );
                    })}
                </div>
            )}

            <div className="mt-1 border-t border-slate-200 pt-1">
                {snapshot.map((id, index) => {
                    return (
                        <div key={`${id}::${index}`} className="truncate font-mono leading-4">
                            {id}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export { FieldBuilderDnDDebugPanel };
