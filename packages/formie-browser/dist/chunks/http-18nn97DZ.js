//#region src/js/utils/http.ts
async function request(url, options = {}) {
	const headers = {
		Accept: "application/json",
		...options.headers || {}
	};
	delete headers["X-Requested-With"];
	delete headers["x-requested-with"];
	return fetch(String(url), {
		method: options.method || "GET",
		body: options.body ?? null,
		signal: options.signal,
		cache: "no-store",
		headers,
		credentials: "same-origin"
	});
}
async function requestJson(url, options = {}) {
	const response = await request(url, options);
	if (!response.ok) throw new Error(`Request failed (${response.status}) for ${String(url)}`);
	return response.json();
}
async function requestText(url, options = {}) {
	const response = await request(url, options);
	if (!response.ok) throw new Error(`Request failed (${response.status}) for ${String(url)}`);
	return response.text();
}
//#endregion
export { requestText as n, requestJson as t };
