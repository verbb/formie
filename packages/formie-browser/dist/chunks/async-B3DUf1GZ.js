//#region src/js/utils/async.ts
async function sleep(ms) {
	await new Promise((resolve) => {
		window.setTimeout(resolve, Math.max(ms, 0));
	});
}
async function waitFor(callback, { timeoutMs = 5e3, intervalMs = 30 } = {}) {
	const startedAt = Date.now();
	while (Date.now() - startedAt < timeoutMs) {
		const value = callback();
		if (value) return value;
		await sleep(intervalMs);
	}
	throw new Error("Timed out waiting for async condition.");
}
function debounce(callback, delayMs) {
	let timeoutId = null;
	return (...args) => {
		if (timeoutId !== null) window.clearTimeout(timeoutId);
		timeoutId = window.setTimeout(() => {
			callback(...args);
		}, Math.max(delayMs, 0));
	};
}
//#endregion
export { sleep as n, waitFor as r, debounce as t };
