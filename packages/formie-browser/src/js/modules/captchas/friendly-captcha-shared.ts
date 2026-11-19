import type { CaptchaHostServices } from '#modules/captchas/host';
import { CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS } from '#modules/captchas/constants';
import { sleep } from '#utils/async';

// Both Friendly Captcha SDKs keep a dot-prefixed state marker such as
// `.UNSTARTED`, `.FETCHING` or `.UNACTIVATED` in the solution field until the
// puzzle is solved, so a non-empty field isn't a solution on its own.
export function hasFriendlyCaptchaSolution(services: CaptchaHostServices): boolean {
    const value = services.tokens.read();

    return value !== '' && !value.startsWith('.');
}

// Solving a puzzle can take longer than the shared token wait, and v2 can ask
// the user to interact with the widget, so allow as long as other challenges
// started on submit.
export async function waitForFriendlyCaptchaSolution(
    services: CaptchaHostServices,
    timeoutMs: number = CAPTCHA_EXECUTE_WAIT_FOR_VALUE_MS,
): Promise<boolean> {
    const deadline = Date.now() + Math.max(timeoutMs, 0);

    while (!hasFriendlyCaptchaSolution(services)) {
        if (Date.now() >= deadline) {
            return false;
        }

        await sleep(120);
    }

    return true;
}
