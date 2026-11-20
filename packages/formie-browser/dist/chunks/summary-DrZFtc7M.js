import { t as e } from "./request-profile-DhwkeCpS.js";
import { t } from "./debug-rhzqP1j2.js";
import { a as n } from "./theme-classes-DAQuEqdP.js";
import { r } from "./http-B_uW5B9v.js";
import { t as i } from "./async-2GdXNFCE.js";
import { t as a } from "./styles-CBQ_fQem.js";
import { i as o, n as s } from "./shared-CJ1LIEh3.js";
//#region src/css/theme/fields/_summary.css?inline
var c = "@layer formie-theme{.formie-summary-container{padding:var(--formie-summary-padding);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-summary-heading{color:var(--formie-color-heading)}.formie-summary-blocks{gap:var(--formie-gap-summary);display:grid}.formie-summary-blocks[data-formie-loading=true]{min-height:calc(var(--formie-loading-size) + var(--formie-space-4));position:relative}.formie-summary-blocks[data-formie-loading=true]>*{opacity:0;pointer-events:none}.formie-summary-blocks[data-formie-loading=true]:before{content:\"\";background:var(--formie-color-bg);border-radius:inherit;z-index:1;display:block;position:absolute;inset:0}.formie-summary-blocks[data-formie-loading=true]:after{width:var(--formie-loading-size);height:var(--formie-loading-size);content:\"\";border:var(--formie-loading-border-width) solid var(--formie-loading-color);border-radius:var(--formie-radius-full);z-index:2;animation:formie-loading-spin var(--formie-loading-speed) linear infinite;border-top-color:#0000;border-right-color:#0000;display:block;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)}}", l = "[data-formie-summary-blocks]", u = "[data-formie-summary-container]", d = "formie/fields/get-summary-html", f = "summary", p = t("fields", "summary");
a(f, [c]);
function m(e) {
	let t = new URL(e || window.location.href);
	return t.hash = "", t.toString();
}
function h(e) {
	let t = e.querySelector("[data-formie-summary-token]");
	return { accessToken: (t instanceof HTMLInputElement ? t.value : t?.getAttribute("data-formie-summary-token"))?.trim() || null };
}
async function g(t, n, i, a) {
	if (!n.accessToken) throw Error("Summary field requires an access token.");
	let o = new FormData(t);
	return o.set("action", d), o.set("accessToken", n.accessToken), r(m(i), {
		...e(t),
		method: "POST",
		body: o,
		signal: a,
		headers: { Accept: "text/html" }
	});
}
function _(e, t, r) {
	let a = e.closest("form");
	if (!(a instanceof HTMLFormElement)) return p.warn("Missing form ancestor; skipping field."), () => {};
	let o = !1, c = !0, d = !1, m = 0, _ = 0, v = null, y = !1, b = h(e), x = b.accessToken, S = () => {
		let t = e.querySelector(l);
		return t instanceof HTMLElement ? t : null;
	}, C = () => {
		let t = e.querySelector(u);
		return t instanceof HTMLElement ? t : null;
	}, w = (e) => {
		let t = S();
		t && (e ? (t.setAttribute("data-formie-loading", "true"), t.setAttribute("aria-busy", "true"), n(t, a, "loading", !0)) : (t.removeAttribute("data-formie-loading"), t.removeAttribute("aria-busy"), n(t, a, "loading", !1)));
	};
	w(!!b.accessToken);
	let T = () => {
		y || !d || o && !c || (p.log("Queueing fetch."), E());
	}, E = i(async () => {
		if (y) return;
		let t = { accessToken: x };
		if (!S() || !t.accessToken) {
			p.warn("Missing state for fetch.", t), w(!1);
			return;
		}
		_ += 1;
		let n = _, i = m;
		v?.abort(), v = new AbortController(), w(!0);
		try {
			let l = await g(a, t, r, v.signal);
			if (n !== _) return;
			let d = C(), h = document.createElement("template");
			h.innerHTML = l.trim();
			let y = h.content.querySelector(u);
			d && y instanceof HTMLElement ? d.replaceWith(y) : d && (d.innerHTML = l), o = !0, c = m !== i, p.log("Fetch complete.", {
				isDirty: c,
				dirtyVersion: m,
				requestVersion: n
			}), s(e, f, "fetch-summary", {
				summary: e,
				html: l
			});
		} catch (e) {
			if (e instanceof DOMException && e.name === "AbortError") {
				p.log("Fetch aborted.");
				return;
			}
			console.error("[formie] Failed to load summary field HTML.", e);
		} finally {
			n === _ && (w(!1), v = null, c && m !== i && T());
		}
	}, 300), D = (t) => {
		let n = t?.target;
		n instanceof Node && e.contains(n) || (c = !0, m += 1, p.log("Marked dirty.", { dirtyVersion: m }));
	}, O = (e) => {
		D(e), T();
	}, k = (t) => {
		let n = t.detail?.meta?.summaryTokens, r = e.dataset.formieFieldUid;
		if (r && n && typeof n == "object") {
			let e = n[r];
			typeof e == "string" && e && (x = e, C()?.setAttribute("data-formie-summary-token", e));
		}
		D(), p.log("Submit result received; refreshing.", {
			uid: r,
			hasToken: !!x
		}), T();
	}, A = () => {
		D(), p.log("Page navigation received; refreshing."), T();
	}, j = (t) => {
		queueMicrotask(() => {
			t.defaultPrevented || y || (_ += 1, v?.abort(), v = null, x = null, C()?.removeAttribute("data-formie-summary-token"), e.querySelectorAll("input[data-formie-summary-token]").forEach((e) => e.remove()), o = !1, S()?.replaceChildren(), w(!1), D(), T());
		});
	}, M = new IntersectionObserver((t) => {
		d = !!t[0]?.isIntersecting, d && (p.log("Field became visible."), s(e, f, "field-visible", { summary: e }), T());
	}, {
		root: a,
		rootMargin: "50px"
	});
	return M.observe(e), a.addEventListener("input", O), a.addEventListener("change", O), a.addEventListener("reset", j), t.addEventListener("formie:page:navigate:after", A), t.addEventListener("formie:submit:result", k), () => {
		y = !0, _ += 1, v?.abort(), M.disconnect(), a.removeEventListener("input", O), a.removeEventListener("change", O), a.removeEventListener("reset", j), t.removeEventListener("formie:page:navigate:after", A), t.removeEventListener("formie:submit:result", k), p.log("Field destroyed.");
	};
}
var v = {
	moduleId: `formie:${f}`,
	version: 2,
	surfaces: [
		"server-rendered",
		"client-rendered",
		"cp-edit"
	],
	kind: "field",
	match: (e) => !!e.target.querySelector(l),
	setup: async (e) => {
		let t = e.options || {}, n = e.surface === "client-rendered" && typeof t.summaryUrl == "string" ? t.summaryUrl : null, r = o(e).map((t) => _(t, e.root, n));
		return p.log("Module setup.", { fieldCount: r.length }), await e.emit("formie:module:summary:init", { count: r.length }), { destroy: () => {
			r.forEach((e) => {
				e();
			}), p.log("Module destroy.", { fieldCount: r.length }), e.emit("formie:module:summary:destroy", {});
		} };
	}
};
//#endregion
export { v as summaryModule };
