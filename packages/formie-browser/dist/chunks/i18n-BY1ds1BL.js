//#region src/js/utils/i18n.ts
function e() {
	return window.FormieTranslations || {};
}
function t() {
	if (typeof document > "u") return;
	let t = Array.from(document.querySelectorAll("script[type=\"application/json\"][data-formie-translations]:not([data-formie-translations-loaded=\"true\"])"));
	if (t.length === 0) return;
	let n = null;
	for (let r of t) {
		r.dataset.formieTranslationsLoaded = "true";
		let t = r.textContent?.trim();
		if (t) try {
			let r = JSON.parse(t);
			if (!r || Array.isArray(r) || typeof r != "object") continue;
			n = {
				...n ?? e(),
				...r
			};
		} catch {
			continue;
		}
	}
	n && (window.FormieTranslations = n);
}
function n() {
	return t(), e();
}
function r() {
	return { ...n() };
}
function i(e) {
	return window.FormieTranslations = { ...e }, r();
}
function a(e) {
	return window.FormieTranslations = {
		...n(),
		...e
	}, r();
}
function o(e) {
	let t = {}, n = 0;
	for (; n < e.length;) {
		for (; n < e.length && /\s/.test(e[n]);) n++;
		if (n >= e.length) break;
		let r = e.slice(n).match(/^(\w+|=\d+)\{/);
		if (!r) break;
		let i = r[1];
		n += r[0].length;
		let a = 1, o = n;
		for (; n < e.length && a > 0;) e[n] === "{" ? a++ : e[n] === "}" && a--, a > 0 && n++;
		t[i] = e.slice(o, n), n++;
	}
	return t;
}
function s(e, t) {
	let n = `=${e}`;
	if (Object.prototype.hasOwnProperty.call(t, n)) return t[n];
	if (typeof Intl < "u" && typeof Intl.PluralRules == "function") {
		let n = new Intl.PluralRules().select(e);
		if (Object.prototype.hasOwnProperty.call(t, n)) return t[n];
	}
	if (e === 1 && Object.prototype.hasOwnProperty.call(t, "one")) return t.one;
	if (Object.prototype.hasOwnProperty.call(t, "other")) return t.other;
	let r = Object.keys(t)[0];
	return r ? t[r] : "";
}
function c(e, t) {
	let n = e.slice(t).match(/^\{(\w+),\s*plural,\s*/);
	if (!n) return null;
	let r = n[1], i = t + n[0].length, a = i;
	for (; a < e.length;) {
		for (; a < e.length && /\s/.test(e[a]);) a++;
		if (a >= e.length || e[a] === "}") break;
		let t = e.slice(a).match(/^(\w+|=\d+)\{/);
		if (!t) return null;
		a += t[0].length;
		let n = 1;
		for (; a < e.length && n > 0;) e[a] === "{" ? n++ : e[a] === "}" && n--, n > 0 && a++;
		a++;
	}
	return a >= e.length || e[a] !== "}" ? null : {
		param: r,
		body: e.slice(i, a),
		endIndex: a
	};
}
function l(e, t) {
	let n = "", r = 0;
	for (; r < e.length;) {
		if (e[r] !== "{") {
			n += e[r], r++;
			continue;
		}
		let i = c(e, r);
		if (!i) {
			n += e[r], r++;
			continue;
		}
		let a = t[i.param], l = typeof a == "number" ? a : Number.parseInt(String(a ?? ""), 10) || 0, u = s(l, o(i.body));
		u = u.replace(/#/g, String(l)), n += u, r = i.endIndex + 1;
	}
	return n;
}
function u(e, t) {
	return e.replace(/\{(\w+),\s*number\}/g, (e, n) => {
		if (!Object.prototype.hasOwnProperty.call(t, n)) return e;
		let r = t[n];
		return typeof r == "number" ? r.toLocaleString() : String(r);
	});
}
function d(e, t) {
	return e.replace(/\{(\w+)\}/g, (e, n) => Object.prototype.hasOwnProperty.call(t, n) ? String(t[n]) : e);
}
function f(e, t = {}) {
	let r = n()[e] || e;
	return r = l(r, t), r = u(r, t), r = d(r, t), r;
}
var p = f;
//#endregion
export { p as a, f as i, a as n, i as r, r as t };
