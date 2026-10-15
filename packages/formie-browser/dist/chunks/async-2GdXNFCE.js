//#region src/js/utils/async.ts
async function e(e) {
	await new Promise((t) => {
		window.setTimeout(t, Math.max(e, 0));
	});
}
async function t(t, { timeoutMs: n = 5e3, intervalMs: r = 30 } = {}) {
	let i = Date.now();
	for (; Date.now() - i < n;) {
		let n = t();
		if (n) return n;
		await e(r);
	}
	throw Error("Timed out waiting for async condition.");
}
function n(e, t) {
	let n = null;
	return (...r) => {
		n !== null && window.clearTimeout(n), n = window.setTimeout(() => {
			e(...r);
		}, Math.max(t, 0));
	};
}
function r(e, t = document, n = 1e4) {
	return new Promise((r, i) => {
		if (!t.querySelector(e)) {
			r();
			return;
		}
		let a = null, o = new MutationObserver(() => {
			t.querySelector(e) || (o.disconnect(), a !== null && window.clearTimeout(a), r());
		});
		o.observe(t, {
			childList: !0,
			subtree: !0
		}), a = window.setTimeout(() => {
			o.disconnect(), i(/* @__PURE__ */ Error(`Timed out waiting for "${e}" to be removed.`));
		}, Math.max(n, 0));
	});
}
//#endregion
export { r as i, e as n, t as r, n as t };
