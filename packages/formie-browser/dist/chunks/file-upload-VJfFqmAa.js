import { b as C, r as x, d as O, a as q } from "./shared-8s7yzxo7.js";
import { e as B } from "./styles-C3aqgtek.js";
import { k as j, l as Y, m as $ } from "./index-BqkORC7E.js";
const J = "@layer formie-theme{.formie-file-input{padding:var(--formie-space-1);line-height:var(--formie-line-height-base);cursor:pointer}.formie-file-input::file-selector-button,.formie-file-input::-webkit-file-upload-button{appearance:none;-webkit-appearance:none;margin-inline-end:var(--formie-space-2);padding:calc(var(--formie-control-padding-y) - 1px) var(--formie-space-2);min-height:calc(var(--formie-control-height) - (var(--formie-space-1) * 2));border:var(--formie-border-width) solid var(--formie-color-border);border-radius:calc(var(--formie-radius-sm) - 1px);background:var(--formie-color-surface-subtle);color:var(--formie-color-heading);font-weight:var(--formie-font-weight-normal);font-size:var(--formie-font-size-xs);line-height:1.1;white-space:nowrap;cursor:pointer;transition:border-color .15s ease,background-color .15s ease,color .15s ease,box-shadow .15s ease}.formie-file-input:hover::file-selector-button,.formie-file-input:hover::-webkit-file-upload-button{border-color:color-mix(in srgb,var(--formie-color-border) 70%,var(--formie-color-heading) 30%);background:var(--formie-color-surface-muted)}.formie-file-input:focus{outline:0}.formie-file-input:focus-visible::file-selector-button,.formie-file-input:focus-visible::-webkit-file-upload-button{border-color:var(--formie-color-focus-ring)}.formie-field-has-error .formie-file-input::file-selector-button,.formie-field-has-error .formie-file-input::-webkit-file-upload-button{border-color:var(--formie-color-danger)}.formie-file-summary{padding:var(--formie-file-summary-padding);gap:var(--formie-gap-file-summary);border:var(--formie-border-width) solid var(--formie-color-border);border-radius:var(--formie-radius-sm)}.formie-file-summary-container{margin:0;padding-left:var(--formie-list-indent)}}", L = 'input[type="file"][data-formie-file-input]', T = j("uploaded"), N = Y("reset"), W = "data-formie-file-upload-key", G = "data-formie-file-upload-hydrate-endpoint", I = "data-formie-file-upload-anchor", Q = "data-formie-file-upload-asset-id", X = [
  "fileLimit",
  "fileSizeMinLimit",
  "fileSizeMaxLimit"
], w = "file-upload", v = "file-upload";
B(v, [J]);
function b(e) {
  return !!e && typeof e == "object" && !Array.isArray(e);
}
function E(e) {
  const t = Number(e);
  return Number.isInteger(t) && t > 0 ? t : null;
}
function p(e) {
  return typeof e == "string" ? e.trim() : "";
}
function R(e) {
  return e.getAttribute("data-formie-field-handle")?.trim() || "";
}
function k(e) {
  return e.name.endsWith("[]") ? e.name.slice(0, -2) : e.name;
}
function D(e) {
  return `${k(e)}[]`;
}
function Z(e) {
  return e.getAttribute(W)?.trim() || e.getAttribute("data-formie-input-id")?.trim() || "";
}
function K(e) {
  return Array.from(e.querySelectorAll('input[type="hidden"]')).filter((t) => t instanceof HTMLInputElement);
}
function P(e, t) {
  const r = D(t);
  return K(e).filter((i) => i.name === r && i.value.trim() !== "");
}
function ee(e, t) {
  const r = k(t), i = K(e).find((a) => a.hasAttribute(I) || a.name === r && a.value === "");
  if (i)
    return i.setAttribute(I, "true"), i.name = r, i.value = "", i;
  const n = document.createElement("input");
  return n.type = "hidden", n.name = r, n.value = "", n.setAttribute(I, "true"), t.insertAdjacentElement("afterend", n), n;
}
function S(e) {
  if (Array.isArray(e))
    return null;
  const t = E(e);
  if (t)
    return {
      assetId: t,
      filename: ""
    };
  if (!b(e))
    return null;
  const r = E(e.assetId ?? e.id ?? e.value), i = p(e.filename ?? e.title ?? e.label ?? e.name), n = p(e.url) || null;
  return !r && !i ? null : {
    assetId: r,
    filename: i,
    url: n
  };
}
function F(e) {
  if (!Array.isArray(e)) {
    const t = S(e);
    return t ? [t] : [];
  }
  return e.flatMap((t) => {
    if (Array.isArray(t))
      return F(t);
    const r = S(t);
    return r ? [r] : [];
  });
}
function H(e) {
  return e ? Array.from(e.querySelectorAll("[data-formie-file-summary-item]")).map((t) => ({
    assetId: null,
    filename: t.textContent?.trim() || ""
  })).filter((t) => t.filename !== "") : [];
}
function U(e, t) {
  return P(e, t).map((r) => ({
    assetId: E(r.value),
    filename: ""
  })).filter((r) => r.assetId !== null);
}
function z(e, t) {
  return e.length ? e.map((r, i) => ({
    assetId: r.assetId,
    filename: t[i]?.filename || ""
  })) : t;
}
function M(e) {
  return e.map((t) => t.trim().toLowerCase()).filter(Boolean);
}
function te(e, t) {
  const r = M(e.map((n) => n.filename)), i = M(t);
  return !r.length || !i.length ? !1 : r.every((n) => i.includes(n));
}
function re(e) {
  x(e, w, (t) => {
    t.addValidator("fileLimit", ({ input: r }) => {
      const i = parseInt(r.getAttribute("data-formie-file-limit") || "", 10);
      return r.type !== "file" || !i || !("files" in r) || !r.files?.length ? !0 : r.files.length <= i;
    }, ({ input: r, t: i }) => i("Choose up to {files} files.", {
      files: r.getAttribute("data-formie-file-limit") || ""
    })), t.addValidator("fileSizeMinLimit", ({ input: r }) => {
      const n = parseFloat(r.getAttribute("data-formie-size-min-limit") || "") * 1e3 * 1e3;
      return r.type !== "file" || !n || !("files" in r) || !r.files?.length ? !0 : Array.from(r.files).every((a) => a.size >= n);
    }, ({ input: r, t: i }) => i("File must be larger than {filesize} MB.", {
      filesize: r.getAttribute("data-formie-size-min-limit") || ""
    })), t.addValidator("fileSizeMaxLimit", ({ input: r }) => {
      const n = parseFloat(r.getAttribute("data-formie-size-max-limit") || "") * 1e3 * 1e3;
      return r.type !== "file" || !n || !("files" in r) || !r.files?.length ? !0 : Array.from(r.files).every((a) => a.size <= n);
    }, ({ input: r, t: i }) => i("File must be smaller than {filesize} MB.", {
      filesize: r.getAttribute("data-formie-size-max-limit") || ""
    }));
  });
}
function ie(e) {
  q(e, w, X);
}
function V(e, t) {
  const r = e.querySelector("[data-formie-file-summary]");
  if (r)
    return r;
  const i = document.createElement("div");
  return i.className = "formie-file-summary", i.setAttribute("data-formie-file-summary", "true"), t.insertAdjacentElement("afterend", i), i;
}
function h(e) {
  const t = e.pendingFiles.length ? e.pendingFiles : e.uploadedAssets.map((a) => a.filename || (a.assetId ? `Asset #${a.assetId}` : "")).filter(Boolean), r = e.summaryRoot || e.field.querySelector("[data-formie-file-summary]");
  if (!t.length) {
    r && (r.replaceChildren(), r.hidden = !0), e.summaryRoot = r;
    return;
  }
  const i = r || V(e.field, e.input);
  if (!i)
    return;
  i.hidden = !1, e.summaryRoot = i;
  const n = document.createElement("ul");
  n.className = "formie-file-summary-container", n.setAttribute("data-formie-file-summary-container", "true"), t.forEach((a) => {
    const o = document.createElement("li");
    o.className = "formie-file-summary-item", o.setAttribute("data-formie-file-summary-item", "true"), o.textContent = a, n.appendChild(o);
  }), i.replaceChildren(n);
}
function _(e, t, r) {
  let n = ee(e, t);
  const a = D(t);
  P(e, t).forEach((o) => {
    o.remove();
  }), r.forEach((o) => {
    if (!o.assetId)
      return;
    const s = document.createElement("input");
    s.type = "hidden", s.name = a, s.value = String(o.assetId), s.setAttribute(Q, "true"), n.insertAdjacentElement("afterend", s), n = s;
  }), t.value = "";
}
function ne(e) {
  if (!e)
    return "";
  const t = e.querySelector('input[name="handle"]');
  return t instanceof HTMLInputElement && t.value.trim() ? t.value.trim() : e.getAttribute("data-formie-handle")?.trim() || "";
}
function oe(e, t, r) {
  const i = t.form, n = new FormData(), a = ne(i), o = R(e);
  a && n.append("handle", a), o && n.append("fieldHandle", o);
  const s = i?.querySelector('input[name="submissionUid"]');
  return s instanceof HTMLInputElement && s.value.trim() && n.append("submissionUid", s.value.trim()), r.forEach((d) => {
    n.append("assetIds[]", String(d));
  }), n;
}
async function se(e, t, r) {
  const i = r.map((d) => d.assetId).filter((d) => d !== null);
  if (!i.length)
    return r;
  const n = t.getAttribute(G)?.trim() || "/actions/formie/file-upload/hydrate", a = await $(n, {
    method: "POST",
    body: oe(e, t, i)
  }), o = F(a.assets);
  if (!o.length)
    return r;
  const s = new Map(o.map((d) => [d.assetId, d]));
  return r.map((d) => {
    if (!d.assetId || !s.has(d.assetId))
      return d;
    const c = s.get(d.assetId);
    return {
      assetId: d.assetId,
      filename: c.filename || d.filename,
      url: c.url ?? d.url
    };
  });
}
function ae(e, t) {
  const r = e.detail;
  if (!b(r))
    return [];
  const i = p(r.fieldHandle), n = p(r.inputKey), a = p(r.inputName ?? r.name), o = b(r.data) ? r.data : null, s = p(o?.fieldHandle), d = p(o?.inputKey), c = p(o?.inputName ?? o?.name), u = [];
  let y = !1, g = !1, A = !1;
  if (n !== "" && n === t.inputKey && (y = !0, u.push(r.assets, r.assetIds, r.data)), a !== "" && a === t.inputName && (g = !0, u.push(r.assets, r.assetIds, r.data)), i === t.fieldHandle && (A = !0, u.push(r.assets, r.assetIds, r.data)), o) {
    const l = [
      o.assets,
      o.assetIds,
      o.uploadedAssets,
      o.data
    ];
    d !== "" && d === t.inputKey && (y = !0, u.push(...l)), c !== "" && c === t.inputName && (g = !0, u.push(...l)), s === t.fieldHandle && (A = !0, u.push(...l));
  }
  [r.assets, r.assetIds, r.data].forEach((l) => {
    b(l) && (t.inputKey && t.inputKey in l && (y = !0, u.push(l[t.inputKey])), t.inputName in l && (g = !0, u.push(l[t.inputName])), t.fieldHandle in l && u.push(l[t.fieldHandle]));
  });
  for (const l of u) {
    const f = F(l);
    if (f.length && (y || g || A || te(f, t.pendingFiles)))
      return f;
  }
  return [];
}
const fe = {
  id: v,
  kind: "field",
  match: (e) => !!e.target.querySelector(L),
  setup: async (e) => {
    const t = C(e), r = t || e.target, i = Array.from(r.querySelectorAll(L)).filter((o) => o instanceof HTMLInputElement), n = e.form;
    re(n);
    const a = i.map((o) => {
      if (!(t instanceof HTMLElement))
        return () => {
        };
      const s = {
        field: t,
        input: o,
        summaryRoot: V(t, o),
        uploadedAssets: z(
          U(t, o),
          H(t.querySelector("[data-formie-file-summary]"))
        ),
        pendingFiles: [],
        hydrationToken: 0
      }, d = () => {
        s.pendingFiles = Array.from(o.files || []).map((l) => l.name), h(s);
      }, c = async (l) => {
        s.hydrationToken += 1;
        const f = s.hydrationToken;
        s.pendingFiles = [], s.uploadedAssets = l, _(t, o, l), h(s);
        try {
          const m = await se(t, o, l);
          if (f !== s.hydrationToken)
            return;
          s.uploadedAssets = m, h(s), O(t, v, "uploaded-assets-sync", {
            fileUpload: t,
            assets: m
          });
        } catch (m) {
          console.error("[formie] Failed to hydrate uploaded file details.", m);
        }
      }, u = () => {
        s.hydrationToken += 1, s.pendingFiles = [], s.uploadedAssets = [], _(t, o, []), h(s);
      }, y = (l) => {
        const f = R(t);
        if (!f)
          return;
        const m = ae(l, {
          fieldHandle: f,
          inputKey: Z(o),
          inputName: o.name,
          pendingFiles: s.pendingFiles
        });
        m.length && c(m);
      }, g = () => {
        u();
      };
      return o.addEventListener("change", d), n?.addEventListener(T, y), n?.addEventListener(N, g), (() => {
        const l = U(t, o), f = H(s.summaryRoot);
        if (s.uploadedAssets = z(l, f), s.uploadedAssets.some((m) => m.assetId && !m.filename)) {
          c(s.uploadedAssets);
          return;
        }
        h(s);
      })(), () => {
        o.removeEventListener("change", d), n?.removeEventListener(T, y), n?.removeEventListener(N, g);
      };
    });
    return await e.emit("formie:module:file-upload:init", {
      count: i.length
    }), {
      destroy: () => {
        a.forEach((o) => {
          o();
        }), ie(n), e.emit("formie:module:file-upload:destroy", {});
      }
    };
  }
};
export {
  fe as fileUploadModule
};
