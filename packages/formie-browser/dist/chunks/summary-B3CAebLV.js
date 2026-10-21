import { t as e } from "./debug-rhzqP1j2.js";
import { a as t } from "./theme-classes-DAQuEqdP.js";
import { r as n } from "./http-Dj_skqaa.js";
import { t as r } from "./async-2GdXNFCE.js";
import { t as i } from "./styles-BfoIZwJp.js";
import { i as a, n as o } from "./shared-CJ1LIEh3.js";
//#region src/css/theme/fields/_summary.css?inline
var s = "@layer formie-theme{.formie-summary-container{padding:var(--formie-summary-padding);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-summary-heading{color:var(--formie-color-heading)}.formie-summary-blocks{gap:var(--formie-gap-summary);display:grid}.formie-summary-blocks[data-formie-loading=true]{min-height:calc(var(--formie-loading-size) + var(--formie-space-4));position:relative}.formie-summary-blocks[data-formie-loading=true]>*{opacity:0;pointer-events:none}.formie-summary-blocks[data-formie-loading=true]:before{content:\"\";background:var(--formie-color-bg);border-radius:inherit;z-index:1;display:block;position:absolute;inset:0}.formie-summary-blocks[data-formie-loading=true]:after{width:var(--formie-loading-size);height:var(--formie-loading-size);content:\"\";border:var(--formie-loading-border-width) solid var(--formie-loading-color);border-radius:var(--formie-radius-full);z-index:2;animation:formie-loading-spin var(--formie-loading-speed) linear infinite;border-top-color:#0000;border-right-color:#0000;display:block;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)}}", c = "[data-formie-summary-blocks]", l = "[data-formie-summary-container]", u = "formie/fields/get-summary-html", d = "summary", f = e("fields", "summary");
i(d, [s]);
function p() {
	let e = new URL(window.location.href);
	return e.hash = "", e.toString();
}
function m(e) {
	let t = e.querySelector("[data-formie-summary-token]");
	return { accessToken: (t instanceof HTMLInputElement ? t.value : t?.getAttribute("data-formie-summary-token"))?.trim() || null };
}
async function h(e, t, r) {
	if (!t.accessToken) throw Error("Summary field requires an access token.");
	let i = new FormData(e);
	return i.set("action", u), i.set("accessToken", t.accessToken), n(p(), {
		method: "POST",
		body: i,
		signal: r,
		headers: { Accept: "text/html" }
	});
}
function g(e, n) {
	let i = e.closest("form");
	if (!(i instanceof HTMLFormElement)) return f.warn("Missing form ancestor; skipping field."), () => {};
	let a = !1, s = !0, u = !1, p = 0, g = 0, _ = null, v = !1, y = m(e), b = y.accessToken, x = () => {
		let t = e.querySelector(c);
		return t instanceof HTMLElement ? t : null;
	}, S = () => {
		let t = e.querySelector(l);
		return t instanceof HTMLElement ? t : null;
	}, C = (e) => {
		let n = x();
		n && (e ? (n.setAttribute("data-formie-loading", "true"), n.setAttribute("aria-busy", "true"), t(n, i, "loading", !0)) : (n.removeAttribute("data-formie-loading"), n.removeAttribute("aria-busy"), t(n, i, "loading", !1)));
	};
	C(!!y.accessToken);
	let w = () => {
		v || !u || a && !s || (f.log("Queueing fetch."), T());
	}, T = r(async () => {
		if (v) return;
		let t = { accessToken: b };
		if (!x() || !t.accessToken) {
			f.warn("Missing state for fetch.", t), C(!1);
			return;
		}
		g += 1;
		let n = g, r = p;
		_?.abort(), _ = new AbortController(), C(!0);
		try {
			let c = await h(i, t, _.signal);
			if (n !== g) return;
			let u = S(), m = document.createElement("template");
			m.innerHTML = c.trim();
			let v = m.content.querySelector(l);
			u && v instanceof HTMLElement ? u.replaceWith(v) : u && (u.innerHTML = c), a = !0, s = p !== r, f.log("Fetch complete.", {
				isDirty: s,
				dirtyVersion: p,
				requestVersion: n
			}), o(e, d, "fetch-summary", {
				summary: e,
				html: c
			});
		} catch (e) {
			if (e instanceof DOMException && e.name === "AbortError") {
				f.log("Fetch aborted.");
				return;
			}
			console.error("[formie] Failed to load summary field HTML.", e);
		} finally {
			n === g && (C(!1), _ = null, s && p !== r && w());
		}
	}, 300), E = (t) => {
		let n = t?.target;
		n instanceof Node && e.contains(n) || (s = !0, p += 1, f.log("Marked dirty.", { dirtyVersion: p }));
	}, D = (e) => {
		E(e), w();
	}, O = (t) => {
		let n = t.detail?.meta?.summaryTokens, r = e.dataset.formieFieldUid;
		if (r && n && typeof n == "object") {
			let e = n[r];
			typeof e == "string" && e && (b = e, S()?.setAttribute("data-formie-summary-token", e));
		}
		E(), f.log("Submit result received; refreshing.", {
			uid: r,
			hasToken: !!b
		}), w();
	}, k = () => {
		E(), f.log("Page navigation received; refreshing."), w();
	}, A = (t) => {
		queueMicrotask(() => {
			t.defaultPrevented || v || (g += 1, _?.abort(), _ = null, b = null, S()?.removeAttribute("data-formie-summary-token"), e.querySelectorAll("input[data-formie-summary-token]").forEach((e) => e.remove()), a = !1, x()?.replaceChildren(), C(!1), E(), w());
		});
	}, j = new IntersectionObserver((t) => {
		u = !!t[0]?.isIntersecting, u && (f.log("Field became visible."), o(e, d, "field-visible", { summary: e }), w());
	}, {
		root: i,
		rootMargin: "50px"
	});
	return j.observe(e), i.addEventListener("input", D), i.addEventListener("change", D), i.addEventListener("reset", A), n.addEventListener("formie:page:navigate:after", k), n.addEventListener("formie:submit:result", O), () => {
		v = !0, g += 1, _?.abort(), j.disconnect(), i.removeEventListener("input", D), i.removeEventListener("change", D), i.removeEventListener("reset", A), n.removeEventListener("formie:page:navigate:after", k), n.removeEventListener("formie:submit:result", O), f.log("Field destroyed.");
	};
}
var _ = {
	moduleId: `formie:${d}`,
	version: 2,
	surfaces: [
		"server-rendered",
		"client-rendered",
		"cp-edit"
	],
	kind: "field",
	match: (e) => !!e.target.querySelector(c),
	setup: async (e) => {
		let t = a(e).map((t) => g(t, e.root));
		return f.log("Module setup.", { fieldCount: t.length }), await e.emit("formie:module:summary:init", { count: t.length }), { destroy: () => {
			t.forEach((e) => {
				e();
			}), f.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:summary:destroy", {});
		} };
	}
};
//#endregion
export { _ as summaryModule };
