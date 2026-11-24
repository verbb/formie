export type ClientValueSource = {
    read: (key: string) => {
        found: boolean;
        value: string | string[];
    };
    subscribe: (listener: () => void) => () => void;
};
export declare function setClientValueSource(target: object, source: ClientValueSource | null): void;
export declare function getClientValueSource(...targets: Array<object | null | undefined>): ClientValueSource | null;
//# sourceMappingURL=client-values.d.ts.map