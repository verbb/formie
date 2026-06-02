type EventCallback<T = unknown> = (payload: T) => void | Promise<void>;

export type EventEmitFailure = {
    index: number;
    error: unknown;
};

export type EventEmitReport = {
    eventName: string;
    total: number;
    succeeded: number;
    failed: EventEmitFailure[];
};

export class EventBus {
    private listeners = new Map<string, Set<EventCallback>>();

    on<T = unknown>(eventName: string, callback: EventCallback<T>): () => void {
        if (!this.listeners.has(eventName)) {
            this.listeners.set(eventName, new Set());
        }

        this.listeners.get(eventName)?.add(callback as EventCallback);

        return () => {
            this.listeners.get(eventName)?.delete(callback as EventCallback);
        };
    }

    async emit<T = unknown>(eventName: string, payload: T): Promise<void> {
        const callbacks = this.listeners.get(eventName);

        if (!callbacks || callbacks.size === 0) {
            return;
        }

        for (const callback of callbacks) {
            await callback(payload);
        }
    }

    async emitSafe<T = unknown>(eventName: string, payload: T): Promise<EventEmitReport> {
        const callbacks = this.listeners.get(eventName);
        const report: EventEmitReport = {
            eventName,
            total: callbacks?.size || 0,
            succeeded: 0,
            failed: [],
        };

        if (!callbacks || callbacks.size === 0) {
            return report;
        }

        let index = 0;

        for (const callback of callbacks) {
            try {
                await callback(payload);
                report.succeeded += 1;
            } catch (error) {
                report.failed.push({
                    index,
                    error,
                });
            }

            index += 1;
        }

        return report;
    }

    async emitParallelSafe<T = unknown>(eventName: string, payload: T): Promise<EventEmitReport> {
        const callbacks = this.listeners.get(eventName);
        const report: EventEmitReport = {
            eventName,
            total: callbacks?.size || 0,
            succeeded: 0,
            failed: [],
        };

        if (!callbacks || callbacks.size === 0) {
            return report;
        }

        const results = await Promise.allSettled(Array.from(callbacks).map(async(callback) => {
            return callback(payload);
        }));

        results.forEach((result, index) => {
            if (result.status === 'fulfilled') {
                report.succeeded += 1;
                return;
            }

            report.failed.push({
                index,
                error: result.reason,
            });
        });

        return report;
    }

    clear(): void {
        this.listeners.clear();
    }
}
