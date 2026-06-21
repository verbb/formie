import { a as e, i as t, r as n } from "./event-names-BCI2FLD8.js";
import { t as r } from "./http-D-JExro7.js";
import { t as i } from "./styles-BfoIZwJp.js";
import { c as a, l as o, t as s } from "./shared-Bx9s0i0P.js";
//#region src/css/theme/fields/_file.css?inline
var c = "@layer formie-theme{.formie-file-input{padding:var(--formie-space-1);line-height:var(--formie-line-height-base);cursor:pointer}.formie-file-input::file-selector-button{appearance:none;padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-space-2);min-height:calc(var(--formie-control-height) - (var(--formie-space-1) * 2));border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:calc(var(--formie-radius-sm) - 1px);background:var(--formie-color-surface-subtle);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-normal);font-size:var(--formie-font-size-xs);white-space:nowrap;cursor:pointer;margin-inline-end:var(--formie-space-2);line-height:1.1;transition:border-color .15s,background-color .15s,color .15s,box-shadow .15s}.formie-file-input::-webkit-file-upload-button{appearance:none;padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-space-2);min-height:calc(var(--formie-control-height) - (var(--formie-space-1) * 2));border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:calc(var(--formie-radius-sm) - 1px);background:var(--formie-color-surface-subtle);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-normal);font-size:var(--formie-font-size-xs);white-space:nowrap;cursor:pointer;margin-inline-end:var(--formie-space-2);line-height:1.1;transition:border-color .15s,background-color .15s,color .15s,box-shadow .15s}.formie-file-input:hover::file-selector-button{border-color:color-mix(in srgb, var(--formie-color-border-control) 70%, var(--formie-color-heading) 30%);background:var(--formie-color-surface-muted)}.formie-file-input:hover::-webkit-file-upload-button{border-color:color-mix(in srgb, var(--formie-color-border-control) 70%, var(--formie-color-heading) 30%);background:var(--formie-color-surface-muted)}.formie-file-input:focus{outline:0}.formie-file-input:focus-visible::file-selector-button{border-color:var(--formie-color-focus-ring)}.formie-file-input:focus-visible::-webkit-file-upload-button{border-color:var(--formie-color-focus-ring)}.formie-field-has-error .formie-file-input::file-selector-button{border-color:var(--formie-color-danger)}.formie-field-has-error .formie-file-input::-webkit-file-upload-button{border-color:var(--formie-color-danger)}.formie-file-summary{padding:var(--formie-file-summary-padding);gap:var(--formie-gap-file-summary);border:var(--formie-border-width) solid var(--formie-color-border-control);border-radius:var(--formie-radius-sm)}.formie-file-summary-container{padding-left:var(--formie-list-indent);margin:0}}", l = "input[type=\"file\"][data-formie-file-input]", u = t("uploaded"), d = e("reset"), f = "data-formie-file-upload-key", p = "data-formie-file-upload-hydrate-endpoint", m = "data-formie-file-upload-anchor", h = "data-formie-file-upload-asset-id", g = [
	"fileLimit",
	"fileSizeMinLimit",
	"fileSizeMaxLimit"
], _ = "file-upload", v = "file-upload", y = n("repeater", "init-row"), b = "[data-formie-field-handle]";
i(v, [c]);
function x(e) {
	return !!e && typeof e == "object" && !Array.isArray(e);
}
function S(e) {
	let t = Number(e);
	return Number.isInteger(t) && t > 0 ? t : null;
}
function C(e) {
	return typeof e == "string" ? e.trim() : "";
}
function w(e) {
	return e.getAttribute("data-formie-field-handle")?.trim() || "";
}
function T(e) {
	return e.name.endsWith("[]") ? e.name.slice(0, -2) : e.name;
}
function E(e) {
	return `${T(e)}[]`;
}
function D(e) {
	return e.getAttribute(f)?.trim() || e.getAttribute("data-formie-input-id")?.trim() || "";
}
function O(e) {
	return Array.from(e.querySelectorAll("input[type=\"hidden\"]")).filter((e) => e instanceof HTMLInputElement);
}
function k(e, t) {
	let n = E(t);
	return O(e).filter((e) => e.name === n && e.value.trim() !== "");
}
function A(e, t) {
	let n = T(t), r = O(e).find((e) => e.hasAttribute(m) || e.name === n && e.value === "");
	if (r) return r.setAttribute(m, "true"), r.name = n, r.value = "", r;
	let i = document.createElement("input");
	return i.type = "hidden", i.name = n, i.value = "", i.setAttribute(m, "true"), t.insertAdjacentElement("afterend", i), i;
}
function j(e) {
	if (Array.isArray(e)) return null;
	let t = S(e);
	if (t) return {
		assetId: t,
		filename: ""
	};
	if (!x(e)) return null;
	let n = S(e.assetId ?? e.id ?? e.value), r = C(e.filename ?? e.title ?? e.label ?? e.name), i = C(e.url) || null;
	return !n && !r ? null : {
		assetId: n,
		filename: r,
		url: i
	};
}
function M(e) {
	if (!Array.isArray(e)) {
		let t = j(e);
		return t ? [t] : [];
	}
	return e.flatMap((e) => {
		if (Array.isArray(e)) return M(e);
		let t = j(e);
		return t ? [t] : [];
	});
}
function N(e) {
	return e ? Array.from(e.querySelectorAll("[data-formie-file-summary-item]")).map((e) => ({
		assetId: null,
		filename: e.textContent?.trim() || ""
	})).filter((e) => e.filename !== "") : [];
}
function P(e, t) {
	return k(e, t).map((e) => ({
		assetId: S(e.value),
		filename: ""
	})).filter((e) => e.assetId !== null);
}
function F(e, t) {
	return e.length ? e.map((e, n) => ({
		assetId: e.assetId,
		filename: t[n]?.filename || ""
	})) : t;
}
function I(e) {
	return e.map((e) => e.trim().toLowerCase()).filter(Boolean);
}
function L(e, t) {
	let n = I(e.map((e) => e.filename)), r = I(t);
	return !n.length || !r.length ? !1 : n.every((e) => r.includes(e));
}
function R(e) {
	o(e, _, (e) => {
		e.addValidator("fileLimit", ({ input: e }) => {
			let t = parseInt(e.getAttribute("data-formie-file-limit") || "", 10);
			return e.type !== "file" || !t || !("files" in e) || !e.files?.length ? !0 : e.files.length <= t;
		}, ({ input: e, t }) => e.getAttribute("data-formie-validation-max-files-message") ?? t("Choose up to {files} files.", { files: e.getAttribute("data-formie-file-limit") || "" })), e.addValidator("fileSizeMinLimit", ({ input: e }) => {
			let t = parseFloat(e.getAttribute("data-formie-size-min-limit") || "") * 1e3 * 1e3;
			return e.type !== "file" || !t || !("files" in e) || !e.files?.length ? !0 : Array.from(e.files).every((e) => e.size >= t);
		}, ({ input: e, t }) => e.getAttribute("data-formie-validation-min-file-size-message") ?? t("File must be larger than {filesize} MB.", { filesize: e.getAttribute("data-formie-size-min-limit") || "" })), e.addValidator("fileSizeMaxLimit", ({ input: e }) => {
			let t = parseFloat(e.getAttribute("data-formie-size-max-limit") || "") * 1e3 * 1e3;
			return e.type !== "file" || !t || !("files" in e) || !e.files?.length ? !0 : Array.from(e.files).every((e) => e.size <= t);
		}, ({ input: e, t }) => e.getAttribute("data-formie-validation-max-file-size-message") ?? t("File must be smaller than {filesize} MB.", { filesize: e.getAttribute("data-formie-size-max-limit") || "" }));
	});
}
function z(e) {
	a(e, _, g);
}
function B(e, t) {
	let n = e.querySelector("[data-formie-file-summary]");
	if (n) return n;
	let r = document.createElement("div");
	return r.className = "formie-file-summary", r.setAttribute("data-formie-file-summary", "true"), t.insertAdjacentElement("afterend", r), r;
}
function V(e) {
	let t = e.pendingFiles.length ? e.pendingFiles : e.uploadedAssets.map((e) => e.filename || (e.assetId ? `Asset #${e.assetId}` : "")).filter(Boolean), n = e.summaryRoot || e.field.querySelector("[data-formie-file-summary]");
	if (!t.length) {
		n && (n.replaceChildren(), n.hidden = !0), e.summaryRoot = n;
		return;
	}
	let r = n || B(e.field, e.input);
	if (!r) return;
	r.hidden = !1, e.summaryRoot = r;
	let i = document.createElement("ul");
	i.className = "formie-file-summary-container", i.setAttribute("data-formie-file-summary-container", "true"), t.forEach((e) => {
		let t = document.createElement("li");
		t.className = "formie-file-summary-item", t.setAttribute("data-formie-file-summary-item", "true"), t.textContent = e, i.appendChild(t);
	}), r.replaceChildren(i);
}
function H(e, t, n) {
	let r = A(e, t), i = E(t);
	k(e, t).forEach((e) => {
		e.remove();
	}), n.forEach((e) => {
		if (!e.assetId) return;
		let t = document.createElement("input");
		t.type = "hidden", t.name = i, t.value = String(e.assetId), t.setAttribute(h, "true"), r.insertAdjacentElement("afterend", t), r = t;
	}), t.value = "";
}
function U(e) {
	if (!e) return "";
	let t = e.querySelector("input[name=\"handle\"]");
	return t instanceof HTMLInputElement && t.value.trim() ? t.value.trim() : e.getAttribute("data-formie-handle")?.trim() || "";
}
function W(e, t, n) {
	let r = t.form, i = new FormData(), a = U(r), o = w(e);
	a && i.append("handle", a), o && i.append("fieldHandle", o);
	let s = r?.querySelector("input[name=\"submissionUid\"]");
	return s instanceof HTMLInputElement && s.value.trim() && i.append("submissionUid", s.value.trim()), n.forEach((e) => {
		i.append("assetIds[]", String(e));
	}), i;
}
async function G(e, t, n) {
	let i = n.map((e) => e.assetId).filter((e) => e !== null);
	if (!i.length) return n;
	let a = M((await r(t.getAttribute(p)?.trim() || "/actions/formie/file-upload/hydrate", {
		method: "POST",
		body: W(e, t, i)
	})).assets);
	if (!a.length) return n;
	let o = new Map(a.map((e) => [e.assetId, e]));
	return n.map((e) => {
		if (!e.assetId || !o.has(e.assetId)) return e;
		let t = o.get(e.assetId);
		return {
			assetId: e.assetId,
			filename: t.filename || e.filename,
			url: t.url ?? e.url
		};
	});
}
function K(e, t) {
	let n = e.detail;
	if (!x(n)) return [];
	let r = C(n.fieldHandle), i = C(n.inputKey), a = C(n.inputName ?? n.name), o = x(n.data) ? n.data : null, s = C(o?.fieldHandle), c = C(o?.inputKey), l = C(o?.inputName ?? o?.name), u = [], d = !1, f = !1, p = !1;
	if (i !== "" && i === t.inputKey && (d = !0, u.push(n.assets, n.assetIds, n.data)), a !== "" && a === t.inputName && (f = !0, u.push(n.assets, n.assetIds, n.data)), r === t.fieldHandle && (p = !0, u.push(n.assets, n.assetIds, n.data)), o) {
		let e = [
			o.assets,
			o.assetIds,
			o.uploadedAssets,
			o.data
		];
		c !== "" && c === t.inputKey && (d = !0, u.push(...e)), l !== "" && l === t.inputName && (f = !0, u.push(...e)), s === t.fieldHandle && (p = !0, u.push(...e));
	}
	[
		n.assets,
		n.assetIds,
		n.data
	].forEach((e) => {
		x(e) && (t.inputKey && t.inputKey in e && (d = !0, u.push(e[t.inputKey])), t.inputName in e && (f = !0, u.push(e[t.inputName])), t.fieldHandle in e && u.push(e[t.fieldHandle]));
	});
	for (let e of u) {
		let n = M(e);
		if (n.length && (d || f || p || L(n, t.pendingFiles))) return n;
	}
	return [];
}
function q(e) {
	let t = e.closest(b);
	return t instanceof HTMLElement ? t : null;
}
function J(e) {
	return Array.from(e.querySelectorAll(l)).filter((e) => e instanceof HTMLInputElement);
}
function Y(e, t, n) {
	let r = {
		field: e,
		input: t,
		summaryRoot: B(e, t),
		uploadedAssets: F(P(e, t), N(e.querySelector("[data-formie-file-summary]"))),
		pendingFiles: [],
		hydrationToken: 0
	}, i = () => {
		r.pendingFiles = Array.from(t.files || []).map((e) => e.name), V(r);
	}, a = async (n) => {
		r.hydrationToken += 1;
		let i = r.hydrationToken;
		r.pendingFiles = [], r.uploadedAssets = n, H(e, t, n), V(r);
		try {
			let a = await G(e, t, n);
			if (i !== r.hydrationToken) return;
			r.uploadedAssets = a, V(r), s(e, v, "uploaded-assets-sync", {
				fileUpload: e,
				assets: a
			});
		} catch (e) {
			console.error("[formie] Failed to hydrate uploaded file details.", e);
		}
	}, o = () => {
		r.hydrationToken += 1, r.pendingFiles = [], r.uploadedAssets = [], H(e, t, []), V(r);
	}, c = (n) => {
		let i = w(e);
		if (!i) return;
		let o = K(n, {
			fieldHandle: i,
			inputKey: D(t),
			inputName: t.name,
			pendingFiles: r.pendingFiles
		});
		o.length && a(o);
	}, l = () => {
		o();
	};
	return t.addEventListener("change", i), n?.addEventListener(u, c), n?.addEventListener(d, l), (() => {
		if (r.uploadedAssets = F(P(e, t), N(r.summaryRoot)), r.uploadedAssets.some((e) => e.assetId && !e.filename)) {
			a(r.uploadedAssets);
			return;
		}
		V(r);
	})(), () => {
		t.removeEventListener("change", i), n?.removeEventListener(u, c), n?.removeEventListener(d, l);
	};
}
var X = {
	id: v,
	kind: "field",
	match: (e) => !!e.target.querySelector(l),
	setup: async (e) => {
		let t = e.form, n = /* @__PURE__ */ new WeakSet(), r = [], i = (e) => {
			J(e).forEach((e) => {
				if (n.has(e)) return;
				let i = q(e);
				i && (n.add(e), r.push(Y(i, e, t)));
			});
		};
		R(t), i(e.target);
		let a = (e) => {
			let t = e.detail;
			if (!x(t)) return;
			let n = t.row;
			n instanceof HTMLElement && i(n);
		};
		return t?.addEventListener(y, a), await e.emit("formie:module:file-upload:init", { count: r.length }), { destroy: () => {
			t?.removeEventListener(y, a), r.forEach((e) => {
				e();
			}), z(t), e.emit("formie:module:file-upload:destroy", {});
		} };
	}
};
//#endregion
export { X as fileUploadModule };
