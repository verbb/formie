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
export declare class EventBus {
    private listeners;
    on<T = unknown>(eventName: string, callback: EventCallback<T>): () => void;
    emit<T = unknown>(eventName: string, payload: T): Promise<void>;
    emitSafe<T = unknown>(eventName: string, payload: T): Promise<EventEmitReport>;
    emitParallelSafe<T = unknown>(eventName: string, payload: T): Promise<EventEmitReport>;
    clear(): void;
}
export {};
//# sourceMappingURL=event-bus.d.ts.map