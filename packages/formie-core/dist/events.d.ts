type EventCallback = (payload: unknown) => void;
export declare class ClientEventEmitter {
    private listeners;
    on(eventName: string, callback: EventCallback): () => void;
    emit(eventName: string, payload: unknown): void;
}
export {};
//# sourceMappingURL=events.d.ts.map