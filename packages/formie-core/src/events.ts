type EventCallback = (payload: unknown) => void;

export class FrontendEventEmitter {
    private listeners = new Map<string, Set<EventCallback>>();

    on(eventName: string, callback: EventCallback): () => void {
        const callbacks = this.listeners.get(eventName) ?? new Set<EventCallback>();
        callbacks.add(callback);
        this.listeners.set(eventName, callbacks);

        return () => {
            callbacks.delete(callback);

            if (callbacks.size === 0) {
                this.listeners.delete(eventName);
            }
        };
    }

    emit(eventName: string, payload: unknown): void {
        const callbacks = this.listeners.get(eventName);

        if (!callbacks) {
            return;
        }

        callbacks.forEach((callback) => {
            callback(payload);
        });
    }
}
