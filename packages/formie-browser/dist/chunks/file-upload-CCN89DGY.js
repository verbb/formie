import { b as R, r as D, d as K, a as P } from "./shared-DGn4SKv5.js";
import { e as V } from "./styles-C3aqgtek.js";
import { k as C, l as x, m as O } from "./index-7j3Qw3EW.js";
const B = "@layer formie-theme{.formie-file-input{padding:var(--formie-space-1);line-height:var(--formie-line-height-base);cursor:pointer}.formie-file-input::file-selector-button,.formie-file-input::-webkit-file-upload-button{appearance:none;-webkit-appearance:none;margin-inline-end:var(--formie-space-2);padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-space-2);min-height:calc(var(--formie-control-height) - (var(--formie-space-1) * 2));border:var(--formie-border-width) solid var(--formie-color-border);border-radius:calc(var(--formie-radius-sm) - 1px);background:var(--formie-color-surface-subtle);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-normal);font-size:var(--formie-font-size-xs);line-height:1.1;white-space:nowrap;cursor:pointer;transition:border-color .15s ease,background-color .15s ease,color .15s ease,box-shadow .15s ease}.formie-file-input:hover::file-selector-button,.formie-file-input:hover::-webkit-file-upload-button{border-color:color-mix(in srgb,var(--formie-color-border) 70%,var(--formie-color-heading) 30%);background:var(--formie-color-surface-muted)}.formie-file-input:focus{outline:0}.formie-file-input:focus-visible::file-selector-button,.formie-file-input:focus-visible::-webkit-file-upload-button{border-color:var(--formie-color-focus-ring)}.formie-field-has-error .formie-file-input::file-selector-button,.formie-field-has-error .formie-file-input::-webkit-file-upload-button{border-color:var(--formie-color-danger)}.formie-file-summary{padding:var(--formie-file-summary-padding);gap:var(--formie-gap-file-summary);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-file-summary-container{margin:0;padding-left:var(--formie-list-indent)}}", F = 'input[type="file"][data-formie-file-input]', N = C("uploaded"), L = x("reset"), q = "data-formie-file-upload-key", j = "data-formie-file-upload-hydrate-endpoint", b = "data-formie-file-upload-anchor", Y = "data-formie-file-upload-asset-id", $ = [
  "fileLimit",
  "fileSizeMinLimit",
  "fileSizeMaxLimit"
], U = "file-upload", I = "file-upload";
V(I, [B]);
function A(e) {
  return !!e && typeof e == "object" && !Array.isArray(e);
}
function v(e) {
  const r = Number(e);
  return Number.isInteger(r) && r > 0 ? r : null;
}
function p(e) {
  return typeof e == "string" ? e.trim() : "";
}
function J(e) {
  return e.getAttribute("data-formie-field-handle")?.trim() || "";
}
function H(e) {
  return e.name.endsWith("[]") ? e.name.slice(0, -2) : e.name;
}
function _(e) {
  return `${H(e)}[]`;
}
function W(e) {
  return e.getAttribute(q)?.trim() || e.getAttribute("data-formie-input-id")?.trim() || "";
}
function w(e) {
  return Array.from(e.querySelectorAll('input[type="hidden"]')).filter((r) => r instanceof HTMLInputElement);
}
function k(e, r) {
  const t = _(r);
  return w(e).filter((i) => i.name === t && i.value.trim() !== "");
}
function G(e, r) {
  const t = H(r), i = w(e).find((a) => a.hasAttribute(b) || a.name === t && a.value === "");
  if (i)
    return i.setAttribute(b, "true"), i.name = t, i.value = "", i;
  const n = document.createElement("input");
  return n.type = "hidden", n.name = t, n.value = "", n.setAttribute(b, "true"), r.insertAdjacentElement("afterend", n), n;
}
function T(e) {
  if (Array.isArray(e))
    return null;
  const r = v(e);
  if (r)
    return {
      assetId: r,
      filename: ""
    };
  if (!A(e))
    return null;
  const t = v(e.assetId ?? e.id ?? e.value), i = p(e.filename ?? e.title ?? e.label ?? e.name), n = p(e.url) || null;
  return !t && !i ? null : {
    assetId: t,
    filename: i,
    url: n
  };
}
function E(e) {
  if (!Array.isArray(e)) {
    const r = T(e);
    return r ? [r] : [];
  }
  return e.flatMap((r) => {
    if (Array.isArray(r))
      return E(r);
    const t = T(r);
    return t ? [t] : [];
  });
}
function Q(e) {
  return e ? Array.from(e.querySelectorAll("[data-formie-file-summary-item]")).map((r) => ({
    assetId: null,
    filename: r.textContent?.trim() || ""
  })).filter((r) => r.filename !== "") : [];
}
function X(e, r) {
  return k(e, r).map((t) => ({
    assetId: v(t.value),
    filename: ""
  })).filter((t) => t.assetId !== null);
}
function Z(e, r) {
  return e.length ? e.map((t, i) => ({
    assetId: t.assetId,
    filename: r[i]?.filename || ""
  })) : r;
}
function z(e) {
  return e.map((r) => r.trim().toLowerCase()).filter(Boolean);
}
function ee(e, r) {
  const t = z(e.map((n) => n.filename)), i = z(r);
  return !t.length || !i.length ? !1 : t.every((n) => i.includes(n));
}
function te(e) {
  D(e, U, (r) => {
    r.addValidator("fileLimit", ({ input: t }) => {
      const i = parseInt(t.getAttribute("data-formie-file-limit") || "", 10);
      return t.type !== "file" || !i || !("files" in t) || !t.files?.length ? !0 : t.files.length <= i;
    }, ({ input: t, t: i }) => i("Choose up to {files} files.", {
      files: t.getAttribute("data-formie-file-limit") || ""
    })), r.addValidator("fileSizeMinLimit", ({ input: t }) => {
      const n = parseFloat(t.getAttribute("data-formie-size-min-limit") || "") * 1e3 * 1e3;
      return t.type !== "file" || !n || !("files" in t) || !t.files?.length ? !0 : Array.from(t.files).every((a) => a.size >= n);
    }, ({ input: t, t: i }) => i("File must be larger than {filesize} MB.", {
      filesize: t.getAttribute("data-formie-size-min-limit") || ""
    })), r.addValidator("fileSizeMaxLimit", ({ input: t }) => {
      const n = parseFloat(t.getAttribute("data-formie-size-max-limit") || "") * 1e3 * 1e3;
      return t.type !== "file" || !n || !("files" in t) || !t.files?.length ? !0 : Array.from(t.files).every((a) => a.size <= n);
    }, ({ input: t, t: i }) => i("File must be smaller than {filesize} MB.", {
      filesize: t.getAttribute("data-formie-size-max-limit") || ""
    }));
  });
}
function re(e) {
  P(e, U, $);
}
function M(e, r) {
  const t = e.querySelector("[data-formie-file-summary]");
  if (t)
    return t;
  const i = document.createElement("div");
  return i.className = "formie-file-summary", i.setAttribute("data-formie-file-summary", "true"), r.insertAdjacentElement("afterend", i), i;
}
function h(e) {
  const r = e.pendingFiles.length ? e.pendingFiles : e.uploadedAssets.map((a) => a.filename || (a.assetId ? `Asset #${a.assetId}` : "")).filter(Boolean), t = e.summaryRoot || e.field.querySelector("[data-formie-file-summary]");
  if (!r.length) {
    t && (t.replaceChildren(), t.hidden = !0), e.summaryRoot = t;
    return;
  }
  const i = t || M(e.field, e.input);
  if (!i)
    return;
  i.hidden = !1, e.summaryRoot = i;
  const n = document.createElement("ul");
  n.className = "formie-file-summary-container", n.setAttribute("data-formie-file-summary-container", "true"), r.forEach((a) => {
    const o = document.createElement("li");
    o.className = "formie-file-summary-item", o.setAttribute("data-formie-file-summary-item", "true"), o.textContent = a, n.appendChild(o);
  }), i.replaceChildren(n);
}
function S(e, r, t) {
  let n = G(e, r);
  const a = _(r);
  k(e, r).forEach((o) => {
    o.remove();
  }), t.forEach((o) => {
    if (!o.assetId)
      return;
    const s = document.createElement("input");
    s.type = "hidden", s.name = a, s.value = String(o.assetId), s.setAttribute(Y, "true"), n.insertAdjacentElement("afterend", s), n = s;
  }), r.value = "";
}
async function ie(e, r, t) {
  const i = t.map((l) => l.assetId).filter((l) => l !== null);
  if (!i.length)
    return t;
  const n = r.getAttribute(j)?.trim() || "/actions/formie/file-upload/hydrate", a = new FormData();
  i.forEach((l) => {
    a.append("assetIds[]", String(l));
  });
  const o = await O(n, {
    method: "POST",
    body: a
  }), s = E(o.assets);
  if (!s.length)
    return t;
  const c = new Map(s.map((l) => [l.assetId, l]));
  return t.map((l) => {
    if (!l.assetId || !c.has(l.assetId))
      return l;
    const f = c.get(l.assetId);
    return {
      assetId: l.assetId,
      filename: f.filename || l.filename,
      url: f.url ?? l.url
    };
  });
}
function ne(e, r) {
  const t = e.detail;
  if (!A(t))
    return [];
  const i = p(t.fieldHandle), n = p(t.inputKey), a = p(t.inputName ?? t.name), o = A(t.data) ? t.data : null, s = p(o?.fieldHandle), c = p(o?.inputKey), l = p(o?.inputName ?? o?.name), f = [];
  let y = !1, g = !1, u = !1;
  if (n !== "" && n === r.inputKey && (y = !0, f.push(t.assets, t.assetIds, t.data)), a !== "" && a === r.inputName && (g = !0, f.push(t.assets, t.assetIds, t.data)), i === r.fieldHandle && (u = !0, f.push(t.assets, t.assetIds, t.data)), o) {
    const d = [
      o.assets,
      o.assetIds,
      o.uploadedAssets,
      o.data
    ];
    c !== "" && c === r.inputKey && (y = !0, f.push(...d)), l !== "" && l === r.inputName && (g = !0, f.push(...d)), s === r.fieldHandle && (u = !0, f.push(...d));
  }
  [t.assets, t.assetIds, t.data].forEach((d) => {
    A(d) && (r.inputKey && r.inputKey in d && (y = !0, f.push(d[r.inputKey])), r.inputName in d && (g = !0, f.push(d[r.inputName])), r.fieldHandle in d && f.push(d[r.fieldHandle]));
  });
  for (const d of f) {
    const m = E(d);
    if (m.length && (y || g || u || ee(m, r.pendingFiles)))
      return m;
  }
  return [];
}
const le = {
  id: I,
  kind: "field",
  match: (e) => !!e.target.querySelector(F),
  setup: async (e) => {
    const r = R(e), t = r || e.target, i = Array.from(t.querySelectorAll(F)).filter((o) => o instanceof HTMLInputElement), n = e.form;
    te(n);
    const a = i.map((o) => {
      if (!(r instanceof HTMLElement))
        return () => {
        };
      const s = {
        field: r,
        input: o,
        summaryRoot: M(r, o),
        uploadedAssets: Z(
          X(r, o),
          Q(r.querySelector("[data-formie-file-summary]"))
        ),
        pendingFiles: [],
        hydrationToken: 0
      }, c = () => {
        s.pendingFiles = Array.from(o.files || []).map((u) => u.name), h(s);
      }, l = async (u) => {
        s.hydrationToken += 1;
        const d = s.hydrationToken;
        s.pendingFiles = [], s.uploadedAssets = u, S(r, o, u), h(s);
        try {
          const m = await ie(r, o, u);
          if (d !== s.hydrationToken)
            return;
          s.uploadedAssets = m, h(s), K(r, I, "uploaded-assets-sync", {
            fileUpload: r,
            assets: m
          });
        } catch (m) {
          console.error("[formie] Failed to hydrate uploaded file details.", m);
        }
      }, f = () => {
        s.hydrationToken += 1, s.pendingFiles = [], s.uploadedAssets = [], S(r, o, []), h(s);
      }, y = (u) => {
        const d = J(r);
        if (!d)
          return;
        const m = ne(u, {
          fieldHandle: d,
          inputKey: W(o),
          inputName: o.name,
          pendingFiles: s.pendingFiles
        });
        m.length && l(m);
      }, g = () => {
        f();
      };
      return o.addEventListener("change", c), n?.addEventListener(N, y), n?.addEventListener(L, g), h(s), () => {
        o.removeEventListener("change", c), n?.removeEventListener(N, y), n?.removeEventListener(L, g);
      };
    });
    return await e.emit("formie:module:file-upload:init", {
      count: i.length
    }), {
      destroy: () => {
        a.forEach((o) => {
          o();
        }), re(n), e.emit("formie:module:file-upload:destroy", {});
      }
    };
  }
};
export {
  le as fileUploadModule
};
