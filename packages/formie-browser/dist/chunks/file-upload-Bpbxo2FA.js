import { a as e, i as t } from "./event-names-BzJlD9YG.js";
import { t as n } from "./http-B8SNggtm.js";
import { t as r } from "./styles-BuYIxHcX.js";
import { c as i, i as a, s as o, t as s } from "./shared-ktsx_SHX.js";
//#region src/css/theme/fields/_file.css?inline
var c = "@layer formie-theme{.formie-file-input{padding:var(--formie-space-1);line-height:var(--formie-line-height-base);cursor:pointer}.formie-file-input::file-selector-button{appearance:none;padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-space-2);min-height:calc(var(--formie-control-height) - (var(--formie-space-1) * 2));border:var(--formie-border-width) solid var(--formie-color-border);border-radius:calc(var(--formie-radius-sm) - 1px);background:var(--formie-color-surface-subtle);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-normal);font-size:var(--formie-font-size-xs);white-space:nowrap;cursor:pointer;margin-inline-end:var(--formie-space-2);line-height:1.1;transition:border-color .15s,background-color .15s,color .15s,box-shadow .15s}.formie-file-input::-webkit-file-upload-button{appearance:none;padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-space-2);min-height:calc(var(--formie-control-height) - (var(--formie-space-1) * 2));border:var(--formie-border-width) solid var(--formie-color-border);border-radius:calc(var(--formie-radius-sm) - 1px);background:var(--formie-color-surface-subtle);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-normal);font-size:var(--formie-font-size-xs);white-space:nowrap;cursor:pointer;margin-inline-end:var(--formie-space-2);line-height:1.1;transition:border-color .15s,background-color .15s,color .15s,box-shadow .15s}.formie-file-input:hover::file-selector-button{border-color:color-mix(in srgb, var(--formie-color-border) 70%, var(--formie-color-heading) 30%);background:var(--formie-color-surface-muted)}.formie-file-input:hover::-webkit-file-upload-button{border-color:color-mix(in srgb, var(--formie-color-border) 70%, var(--formie-color-heading) 30%);background:var(--formie-color-surface-muted)}.formie-file-input:focus{outline:0}.formie-file-input:focus-visible::file-selector-button{border-color:var(--formie-color-focus-ring)}.formie-file-input:focus-visible::-webkit-file-upload-button{border-color:var(--formie-color-focus-ring)}.formie-field-has-error .formie-file-input::file-selector-button{border-color:var(--formie-color-danger)}.formie-field-has-error .formie-file-input::-webkit-file-upload-button{border-color:var(--formie-color-danger)}.formie-file-summary{padding:var(--formie-file-summary-padding);gap:var(--formie-gap-file-summary);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-file-summary-container{padding-left:var(--formie-list-indent);margin:0}}", l = "input[type=\"file\"][data-formie-file-input]", u = t("uploaded"), d = e("reset"), f = "data-formie-file-upload-key", p = "data-formie-file-upload-hydrate-endpoint", m = "data-formie-file-upload-anchor", h = "data-formie-file-upload-asset-id", g = [
	"fileLimit",
	"fileSizeMinLimit",
	"fileSizeMaxLimit"
], _ = "file-upload", v = "file-upload";
r(v, [c]);
function y(e) {
	return !!e && typeof e == "object" && !Array.isArray(e);
}
function b(e) {
	let t = Number(e);
	return Number.isInteger(t) && t > 0 ? t : null;
}
function x(e) {
	return typeof e == "string" ? e.trim() : "";
}
function S(e) {
	return e.getAttribute("data-formie-field-handle")?.trim() || "";
}
function C(e) {
	return e.name.endsWith("[]") ? e.name.slice(0, -2) : e.name;
}
function w(e) {
	return `${C(e)}[]`;
}
function T(e) {
	return e.getAttribute(f)?.trim() || e.getAttribute("data-formie-input-id")?.trim() || "";
}
function E(e) {
	return Array.from(e.querySelectorAll("input[type=\"hidden\"]")).filter((e) => e instanceof HTMLInputElement);
}
function D(e, t) {
	let n = w(t);
	return E(e).filter((e) => e.name === n && e.value.trim() !== "");
}
function O(e, t) {
	let n = C(t), r = E(e).find((e) => e.hasAttribute(m) || e.name === n && e.value === "");
	if (r) return r.setAttribute(m, "true"), r.name = n, r.value = "", r;
	let i = document.createElement("input");
	return i.type = "hidden", i.name = n, i.value = "", i.setAttribute(m, "true"), t.insertAdjacentElement("afterend", i), i;
}
function k(e) {
	if (Array.isArray(e)) return null;
	let t = b(e);
	if (t) return {
		assetId: t,
		filename: ""
	};
	if (!y(e)) return null;
	let n = b(e.assetId ?? e.id ?? e.value), r = x(e.filename ?? e.title ?? e.label ?? e.name), i = x(e.url) || null;
	return !n && !r ? null : {
		assetId: n,
		filename: r,
		url: i
	};
}
function A(e) {
	if (!Array.isArray(e)) {
		let t = k(e);
		return t ? [t] : [];
	}
	return e.flatMap((e) => {
		if (Array.isArray(e)) return A(e);
		let t = k(e);
		return t ? [t] : [];
	});
}
function j(e) {
	return e ? Array.from(e.querySelectorAll("[data-formie-file-summary-item]")).map((e) => ({
		assetId: null,
		filename: e.textContent?.trim() || ""
	})).filter((e) => e.filename !== "") : [];
}
function M(e, t) {
	return D(e, t).map((e) => ({
		assetId: b(e.value),
		filename: ""
	})).filter((e) => e.assetId !== null);
}
function N(e, t) {
	return e.length ? e.map((e, n) => ({
		assetId: e.assetId,
		filename: t[n]?.filename || ""
	})) : t;
}
function P(e) {
	return e.map((e) => e.trim().toLowerCase()).filter(Boolean);
}
function F(e, t) {
	let n = P(e.map((e) => e.filename)), r = P(t);
	return !n.length || !r.length ? !1 : n.every((e) => r.includes(e));
}
function I(e) {
	i(e, _, (e) => {
		e.addValidator("fileLimit", ({ input: e }) => {
			let t = parseInt(e.getAttribute("data-formie-file-limit") || "", 10);
			return e.type !== "file" || !t || !("files" in e) || !e.files?.length ? !0 : e.files.length <= t;
		}, ({ input: e, t }) => t("Choose up to {files} files.", { files: e.getAttribute("data-formie-file-limit") || "" })), e.addValidator("fileSizeMinLimit", ({ input: e }) => {
			let t = parseFloat(e.getAttribute("data-formie-size-min-limit") || "") * 1e3 * 1e3;
			return e.type !== "file" || !t || !("files" in e) || !e.files?.length ? !0 : Array.from(e.files).every((e) => e.size >= t);
		}, ({ input: e, t }) => t("File must be larger than {filesize} MB.", { filesize: e.getAttribute("data-formie-size-min-limit") || "" })), e.addValidator("fileSizeMaxLimit", ({ input: e }) => {
			let t = parseFloat(e.getAttribute("data-formie-size-max-limit") || "") * 1e3 * 1e3;
			return e.type !== "file" || !t || !("files" in e) || !e.files?.length ? !0 : Array.from(e.files).every((e) => e.size <= t);
		}, ({ input: e, t }) => t("File must be smaller than {filesize} MB.", { filesize: e.getAttribute("data-formie-size-max-limit") || "" }));
	});
}
function L(e) {
	o(e, _, g);
}
function R(e, t) {
	let n = e.querySelector("[data-formie-file-summary]");
	if (n) return n;
	let r = document.createElement("div");
	return r.className = "formie-file-summary", r.setAttribute("data-formie-file-summary", "true"), t.insertAdjacentElement("afterend", r), r;
}
function z(e) {
	let t = e.pendingFiles.length ? e.pendingFiles : e.uploadedAssets.map((e) => e.filename || (e.assetId ? `Asset #${e.assetId}` : "")).filter(Boolean), n = e.summaryRoot || e.field.querySelector("[data-formie-file-summary]");
	if (!t.length) {
		n && (n.replaceChildren(), n.hidden = !0), e.summaryRoot = n;
		return;
	}
	let r = n || R(e.field, e.input);
	if (!r) return;
	r.hidden = !1, e.summaryRoot = r;
	let i = document.createElement("ul");
	i.className = "formie-file-summary-container", i.setAttribute("data-formie-file-summary-container", "true"), t.forEach((e) => {
		let t = document.createElement("li");
		t.className = "formie-file-summary-item", t.setAttribute("data-formie-file-summary-item", "true"), t.textContent = e, i.appendChild(t);
	}), r.replaceChildren(i);
}
function B(e, t, n) {
	let r = O(e, t), i = w(t);
	D(e, t).forEach((e) => {
		e.remove();
	}), n.forEach((e) => {
		if (!e.assetId) return;
		let t = document.createElement("input");
		t.type = "hidden", t.name = i, t.value = String(e.assetId), t.setAttribute(h, "true"), r.insertAdjacentElement("afterend", t), r = t;
	}), t.value = "";
}
async function V(e, t, r) {
	let i = r.map((e) => e.assetId).filter((e) => e !== null);
	if (!i.length) return r;
	let a = t.getAttribute(p)?.trim() || "/actions/formie/file-upload/hydrate", o = new FormData();
	i.forEach((e) => {
		o.append("assetIds[]", String(e));
	});
	let s = A((await n(a, {
		method: "POST",
		body: o
	})).assets);
	if (!s.length) return r;
	let c = new Map(s.map((e) => [e.assetId, e]));
	return r.map((e) => {
		if (!e.assetId || !c.has(e.assetId)) return e;
		let t = c.get(e.assetId);
		return {
			assetId: e.assetId,
			filename: t.filename || e.filename,
			url: t.url ?? e.url
		};
	});
}
function H(e, t) {
	let n = e.detail;
	if (!y(n)) return [];
	let r = x(n.fieldHandle), i = x(n.inputKey), a = x(n.inputName ?? n.name), o = y(n.data) ? n.data : null, s = x(o?.fieldHandle), c = x(o?.inputKey), l = x(o?.inputName ?? o?.name), u = [], d = !1, f = !1, p = !1;
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
		y(e) && (t.inputKey && t.inputKey in e && (d = !0, u.push(e[t.inputKey])), t.inputName in e && (f = !0, u.push(e[t.inputName])), t.fieldHandle in e && u.push(e[t.fieldHandle]));
	});
	for (let e of u) {
		let n = A(e);
		if (n.length && (d || f || p || F(n, t.pendingFiles))) return n;
	}
	return [];
}
var U = {
	id: v,
	kind: "field",
	match: (e) => !!e.target.querySelector(l),
	setup: async (e) => {
		let t = a(e), n = t || e.target, r = Array.from(n.querySelectorAll(l)).filter((e) => e instanceof HTMLInputElement), i = e.form;
		I(i);
		let o = r.map((e) => {
			if (!(t instanceof HTMLElement)) return () => {};
			let n = {
				field: t,
				input: e,
				summaryRoot: R(t, e),
				uploadedAssets: N(M(t, e), j(t.querySelector("[data-formie-file-summary]"))),
				pendingFiles: [],
				hydrationToken: 0
			}, r = () => {
				n.pendingFiles = Array.from(e.files || []).map((e) => e.name), z(n);
			}, a = async (r) => {
				n.hydrationToken += 1;
				let i = n.hydrationToken;
				n.pendingFiles = [], n.uploadedAssets = r, B(t, e, r), z(n);
				try {
					let a = await V(t, e, r);
					if (i !== n.hydrationToken) return;
					n.uploadedAssets = a, z(n), s(t, v, "uploaded-assets-sync", {
						fileUpload: t,
						assets: a
					});
				} catch (e) {
					console.error("[formie] Failed to hydrate uploaded file details.", e);
				}
			}, o = () => {
				n.hydrationToken += 1, n.pendingFiles = [], n.uploadedAssets = [], B(t, e, []), z(n);
			}, c = (r) => {
				let i = S(t);
				if (!i) return;
				let o = H(r, {
					fieldHandle: i,
					inputKey: T(e),
					inputName: e.name,
					pendingFiles: n.pendingFiles
				});
				o.length && a(o);
			}, l = () => {
				o();
			};
			return e.addEventListener("change", r), i?.addEventListener(u, c), i?.addEventListener(d, l), z(n), () => {
				e.removeEventListener("change", r), i?.removeEventListener(u, c), i?.removeEventListener(d, l);
			};
		});
		return await e.emit("formie:module:file-upload:init", { count: r.length }), { destroy: () => {
			o.forEach((e) => {
				e();
			}), L(i), e.emit("formie:module:file-upload:destroy", {});
		} };
	}
};
//#endregion
export { U as fileUploadModule };
