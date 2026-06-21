import { t as e } from "./debug-BV0DvdHx.js";
import { i as t } from "./theme-classes-Tv7q7ToE.js";
import { n } from "./http-D-JExro7.js";
import { t as r } from "./async-nPFRNQ06.js";
import { t as i } from "./styles-BfoIZwJp.js";
import { r as a, t as o } from "./shared-Bx9s0i0P.js";
//#region src/css/theme/fields/_summary.css?inline
var s = "@layer formie-theme{.formie-summary-container{padding:var(--formie-summary-padding);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-summary-heading{color:var(--formie-color-heading)}.formie-summary-blocks{gap:var(--formie-gap-summary);display:grid}.formie-summary-blocks[data-formie-loading=true]{min-height:calc(var(--formie-loading-size) + var(--formie-space-4));position:relative}.formie-summary-blocks[data-formie-loading=true]>*{opacity:0;pointer-events:none}.formie-summary-blocks[data-formie-loading=true]:before{content:\"\";background:var(--formie-color-bg);border-radius:inherit;z-index:1;display:block;position:absolute;inset:0}.formie-summary-blocks[data-formie-loading=true]:after{width:var(--formie-loading-size);height:var(--formie-loading-size);content:\"\";border:var(--formie-loading-border-width) solid var(--formie-loading-color);border-radius:var(--formie-radius-full);z-index:2;animation:formie-loading-spin var(--formie-loading-speed) linear infinite;border-top-color:#0000;border-right-color:#0000;display:block;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)}}", c = "[data-formie-summary-blocks]", l = "[data-formie-summary-container]", u = "formie/fields/get-summary-html", d = "data-formie-theme-config", f = "data-formie-frontend-theme", p = "summary", m = e("fields", "summary");
i(p, [s]);
function h() {
	let e = new URL(window.location.href);
	return e.hash = "", e.toString();
}
function g(e, t) {
	return {
		accessToken: e.querySelector("[data-formie-summary-token]")?.value?.trim() || null,
		themeConfig: t.getAttribute(d)?.trim() || null,
		frontendTheme: t.getAttribute(f)?.trim() || null
	};
}
async function _(e, t, r) {
	if (!t.accessToken) throw Error("Summary field requires an access token.");
	let i = new FormData(e);
	return i.set("action", u), i.set("accessToken", t.accessToken), t.themeConfig && i.set("themeConfig", t.themeConfig), t.frontendTheme && i.set("frontendTheme", t.frontendTheme), n(h(), {
		method: "POST",
		body: i,
		signal: r,
		headers: { Accept: "text/html" }
	});
}
function v(e, n) {
	let i = e.closest("form");
	if (!(i instanceof HTMLFormElement)) return m.warn("Missing form ancestor; skipping field."), () => {};
	let a = !1, s = !0, u = !1, d = 0, f = 0, h = null, v = () => {
		let t = e.querySelector(c);
		return t instanceof HTMLElement ? t : null;
	}, y = () => {
		let t = e.querySelector(l);
		return t instanceof HTMLElement ? t : null;
	}, b = (e) => {
		let n = v();
		if (n) {
			if (e) {
				n.setAttribute("data-formie-loading", "true"), n.setAttribute("aria-busy", "true"), t(n, i, "loading", !0);
				return;
			}
			n.removeAttribute("data-formie-loading"), n.removeAttribute("aria-busy"), t(n, i, "loading", !1);
		}
	};
	b(!!g(e, i).accessToken);
	let x = () => {
		!u || a && !s || (m.log("Queueing fetch."), S());
	}, S = r(async () => {
		let t = g(e, i);
		if (!v() || !t.accessToken) {
			m.warn("Missing state for fetch.", t), b(!1);
			return;
		}
		f += 1;
		let n = f, r = d;
		h?.abort(), h = new AbortController(), b(!0);
		try {
			let c = await _(i, t, h.signal);
			if (n !== f) return;
			let u = y(), g = document.createElement("template");
			g.innerHTML = c.trim();
			let v = g.content.querySelector(l);
			u && v instanceof HTMLElement ? u.replaceWith(v) : u && (u.innerHTML = c), a = !0, s = d !== r, m.log("Fetch complete.", {
				isDirty: s,
				dirtyVersion: d,
				requestVersion: n
			}), o(e, p, "fetch-summary", {
				summary: e,
				html: c
			});
		} catch (e) {
			if (e instanceof DOMException && e.name === "AbortError") {
				m.log("Fetch aborted.");
				return;
			}
			console.error("[formie] Failed to load summary field HTML.", e);
		} finally {
			n === f && (b(!1), h = null, s && x());
		}
	}, 300), C = (t) => {
		let n = t?.target;
		n instanceof Node && e.contains(n) || (s = !0, d += 1, m.log("Marked dirty.", { dirtyVersion: d }));
	}, w = (e) => {
		C(e), x();
	}, T = () => {
		s = !0, m.log("Submit result received; refreshing."), x();
	}, E = () => {
		s = !0, m.log("Page navigation received; refreshing."), x();
	}, D = new IntersectionObserver((t) => {
		u = !!t[0]?.isIntersecting, u && (m.log("Field became visible."), o(e, p, "field-visible", { summary: e }), x());
	}, {
		root: i,
		rootMargin: "50px"
	});
	return D.observe(e), i.addEventListener("input", w), i.addEventListener("change", w), n.addEventListener("formie:page:navigate:after", E), n.addEventListener("formie:submit:result", T), () => {
		h?.abort(), D.disconnect(), i.removeEventListener("input", w), i.removeEventListener("change", w), n.removeEventListener("formie:page:navigate:after", E), n.removeEventListener("formie:submit:result", T), m.log("Field destroyed.");
	};
}
var y = {
	id: p,
	kind: "field",
	match: (e) => !!e.target.querySelector(c),
	setup: async (e) => {
		let t = a(e).map((t) => v(t, e.root));
		return m.log("Module setup.", { fieldCount: t.length }), await e.emit("formie:module:summary:init", { count: t.length }), { destroy: () => {
			t.forEach((e) => {
				e();
			}), m.log("Module destroy.", { fieldCount: t.length }), e.emit("formie:module:summary:destroy", {});
		} };
	}
};
//#endregion
export { y as summaryModule };
