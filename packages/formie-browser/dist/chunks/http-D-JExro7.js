//#region src/js/utils/http.ts
async function e(e, t = {}) {
	let n = {
		Accept: "application/json",
		...t.headers || {}
	};
	return delete n["X-Requested-With"], delete n["x-requested-with"], fetch(String(e), {
		method: t.method || "GET",
		body: t.body ?? null,
		signal: t.signal,
		cache: "no-store",
		headers: n,
		credentials: "same-origin"
	});
}
async function t(t, n = {}) {
	let r = await e(t, n);
	if (!r.ok) throw Error(`Request failed (${r.status}) for ${String(t)}`);
	return r.json();
}
async function n(t, n = {}) {
	let r = await e(t, n);
	if (!r.ok) throw Error(`Request failed (${r.status}) for ${String(t)}`);
	return r.text();
}
//#endregion
export { n, t };
