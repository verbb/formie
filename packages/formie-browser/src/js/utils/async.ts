export async function sleep(ms: number): Promise<void> {
    await new Promise((resolve) => {
        window.setTimeout(resolve, Math.max(ms, 0));
    });
}

export async function waitFor<T>(
    callback: () => T | null | undefined | false,
    {
        timeoutMs = 5000,
        intervalMs = 30,
    }: {
        timeoutMs?: number;
        intervalMs?: number;
    } = {},
): Promise<T> {
    const startedAt = Date.now();

    // Polling is sufficient here because these waits are short-lived bridges to
    // third-party SDK readiness or DOM insertion, not long-running job orchestration.
    while ((Date.now() - startedAt) < timeoutMs) {
        const value = callback();

        if (value) {
            return value;
        }

        await sleep(intervalMs);
    }

    throw new Error('Timed out waiting for async condition.');
}

export function debounce<TArgs extends unknown[]>(
    callback: (...args: TArgs) => void,
    delayMs: number,
): (...args: TArgs) => void {
    let timeoutId: number | null = null;

    return (...args: TArgs) => {
        if (timeoutId !== null) {
            window.clearTimeout(timeoutId);
        }

        timeoutId = window.setTimeout(() => {
            callback(...args);
        }, Math.max(delayMs, 0));
    };
}

export function waitForElement(
    selector: string,
    root: ParentNode = document,
): Promise<Element> {
    return new Promise((resolve) => {
        const existing = root.querySelector(selector);

        if (existing) {
            resolve(existing);
            return;
        }

        // Use DOM observation instead of polling when the dependency is structural,
        // such as late-rendered provider placeholders or dynamically added rows.
        const observer = new MutationObserver(() => {
            const match = root.querySelector(selector);

            if (!match) {
                return;
            }

            observer.disconnect();
            resolve(match);
        });

        observer.observe(root, {
            childList: true,
            subtree: true,
        });
    });
}
