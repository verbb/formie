// Client-rendered forms keep the visitor's answers in the form instance rather than in named inputs. The form's mount
// registers a source here, so modules that read other fields, such as a Payment field's dynamic amount, can read them.
export type ClientValueSource = {
    read: (key: string) => { found: boolean; value: string | string[] };
    subscribe: (listener: () => void) => () => void;
};

const sources = new WeakMap<object, ClientValueSource>();

export function setClientValueSource(target: object, source: ClientValueSource | null): void {
    if (source) {
        sources.set(target, source);
    } else {
        sources.delete(target);
    }
}

export function getClientValueSource(...targets: Array<object | null | undefined>): ClientValueSource | null {
    for (const target of targets) {
        const source = target ? sources.get(target) : undefined;

        if (source) {
            return source;
        }
    }

    return null;
}
