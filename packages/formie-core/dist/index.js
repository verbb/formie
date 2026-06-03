function Jt(n, a) {
  for (var h = 0; h < a.length; h++) {
    const i = a[h];
    if (typeof i != "string" && !Array.isArray(i)) {
      for (const m in i)
        if (m !== "default" && !(m in n)) {
          const p = Object.getOwnPropertyDescriptor(i, m);
          p && Object.defineProperty(n, m, p.get ? p : {
            enumerable: !0,
            get: () => i[m]
          });
        }
    }
  }
  return Object.freeze(Object.defineProperty(n, Symbol.toStringTag, { value: "Module" }));
}
function Kt(n) {
  return Array.isArray(n) ? n.map((a) => String(a ?? "")) : [String(n ?? "")];
}
function ft(n, a) {
  return n.some((h) => a.includes(h));
}
function mt(n, a) {
  return n.some((h) => a.some((i) => i === h || i.includes(h)));
}
function pt(n, a, h) {
  return a.some((i) => n.some((m) => h(m, i)));
}
function gt(n, a, h) {
  return a.some((i) => {
    const m = Number.parseFloat(i);
    return Number.isFinite(m) ? n.some((p) => {
      const N = Number.parseFloat(p);
      return Number.isFinite(N) ? h(N, m) : !1;
    }) : !1;
  });
}
function yt(n) {
  return n.length === 0 || n.every((a) => a.trim() === "");
}
function St(n, a, h = {}) {
  const i = String(n.condition || ""), m = Kt(n.value), p = h.visibility ?? null;
  switch (i) {
    case "=":
      return ft(m, a);
    case "!=":
      return !ft(m, a);
    case ">":
      return gt(a, m, (N, f) => N > f);
    case "<":
      return gt(a, m, (N, f) => N < f);
    case "contains":
      return mt(m, a);
    case "notContains":
      return !mt(m, a);
    case "startsWith":
      return pt(a, m, (N, f) => N.startsWith(f));
    case "endsWith":
      return pt(a, m, (N, f) => N.endsWith(f));
    case "empty":
      return yt(a);
    case "notEmpty":
      return !yt(a);
    case "visible":
      return p === !0;
    case "hidden":
      return p === !1;
    default:
      return !1;
  }
}
function At(n, a) {
  const h = n.conditionRule === "any" ? a.includes(!0) : a.every((m) => m === !0), i = h && n.showRule !== "show" || !h && n.showRule === "show";
  return {
    finalResult: h,
    shouldHide: i
  };
}
class Zt {
  constructor() {
    this.listeners = /* @__PURE__ */ new Map();
  }
  on(a, h) {
    const i = this.listeners.get(a) ?? /* @__PURE__ */ new Set();
    return i.add(h), this.listeners.set(a, i), () => {
      i.delete(h), i.size === 0 && this.listeners.delete(a);
    };
  }
  emit(a, h) {
    const i = this.listeners.get(a);
    i && i.forEach((m) => {
      m(h);
    });
  }
}
const Xt = /* @__PURE__ */ new Set([
  "single-line-text",
  "multi-line-text",
  "number",
  "email",
  "phone",
  "dropdown",
  "radio",
  "checkboxes",
  "agree",
  "date",
  "name",
  "address",
  "repeater",
  "signature",
  "file"
]);
function se(n) {
  return n.pages.flatMap((a) => a.rows.flatMap((h) => h.fields));
}
function de(n, a) {
  return se(n).find((h) => h.id === a);
}
function Ot(n, a) {
  return se(n).find((h) => h.handle === a);
}
function Pr(n, a) {
  return Object.fromEntries(Object.entries(a).map(([h, i]) => [de(n, h)?.handle ?? h, i]));
}
function Cr(n) {
  return Xt.has(n);
}
function Qt(n) {
  if (!n.runtime)
    throw new Error(`Field "${n.handle}" is missing field value metadata.`);
  return n.runtime;
}
function Pt(n) {
  return Qt(n).structure;
}
function fe(n) {
  return Pt(n) === "fixed-parent" && Me(n).length > 0;
}
function me(n) {
  return Pt(n) === "repeatable-parent";
}
function pe(n) {
  return n.type === "file" || n.input.fieldKind === "file";
}
function De(n) {
  const a = n.input;
  return pe(n) || n.type === "checkboxes" || n.type === "dropdown" && a.multiple === !0;
}
function er(n) {
  return n.type === "agree" || n.input.fieldKind === "boolean";
}
function tr(n) {
  return n.type === "number";
}
function rr(n) {
  return n.type === "email";
}
function Me(n) {
  const a = n.input;
  return Array.isArray(a.parts) ? a.parts.filter((h) => !!h && typeof h == "object" && "handle" in h && "type" in h) : [];
}
function sr(n) {
  const h = n.input.rowSchema;
  return !h || typeof h != "object" || !Array.isArray(h.rows) ? [] : h.rows;
}
function Se(n) {
  return sr(n).flatMap((a) => a.fields);
}
function Ye(n) {
  const a = n.input;
  if (n.type === "checkboxes")
    return (Array.isArray(a.options) ? a.options : []).filter((i) => i.selected === !0).map((i) => i.value ?? "");
  if (n.type === "radio" || n.type === "dropdown") {
    const h = Array.isArray(a.options) ? a.options : [];
    if (n.type === "dropdown" && a.multiple === !0)
      return h.filter((m) => m.selected === !0).map((m) => m.value ?? "");
    const i = h.find((m) => m.selected === !0);
    if (i)
      return i.value ?? "";
  }
  if (n.type === "agree")
    return a.defaultValue ?? !1;
  if (fe(n))
    return a.defaultValue && typeof a.defaultValue == "object" ? a.defaultValue : {};
  if (me(n)) {
    const h = Number(a.minRows ?? 0) || 0;
    return h <= 0 ? [] : Array.from({ length: h }, () => nr(n));
  }
  return pe(n) || De(n) ? [] : (n.type === "signature", a.defaultValue ?? "");
}
function nr(n) {
  return Object.fromEntries(Se(n).map((a) => [a.handle, Ye(a)]));
}
function re(n, a) {
  if (n.type === "checkboxes" || pe(n) || De(n))
    return Array.isArray(a) ? a.flatMap((h) => re(n, h)) : [];
  if (me(n)) {
    const h = Array.isArray(a) ? a : [], i = Se(n);
    return h.flatMap((m) => {
      if (!m || typeof m != "object")
        return [];
      const p = m;
      return i.flatMap((N) => re(N, p[N.handle]));
    });
  }
  return fe(n) && a && typeof a == "object" ? Object.values(a).flatMap((h) => re(n, h)) : a == null ? [] : typeof a == "boolean" ? a ? ["true"] : ["false"] : Array.isArray(a) ? a.flatMap((h) => re(n, h)) : [String(a)];
}
function ir(n) {
  return typeof Blob < "u" && n instanceof Blob;
}
async function ar(n) {
  return new Promise((a, h) => {
    const i = new FileReader();
    i.onerror = () => {
      h(i.error || new Error("Unable to read file."));
    }, i.onload = () => {
      a(typeof i.result == "string" ? i.result : "");
    }, i.readAsDataURL(n);
  });
}
async function or(n) {
  const a = Array.isArray(n) ? n : [];
  return (await Promise.all(a.map(async (i) => typeof i == "number" ? { assetId: i } : i && typeof i == "object" && "assetId" in i && typeof i.assetId == "number" ? {
    assetId: i.assetId,
    filename: typeof i.filename == "string" ? i.filename : void 0
  } : i && typeof i == "object" && "fileData" in i && typeof i.fileData == "string" ? {
    fileData: i.fileData,
    filename: typeof i.filename == "string" ? i.filename : void 0
  } : ir(i) ? {
    fileData: await ar(i),
    filename: "name" in i && typeof i.name == "string" ? i.name : "upload.bin"
  } : null))).filter((i) => i !== null);
}
async function Ct(n, a) {
  const h = a && typeof a == "object" ? a : {}, i = {
    ...h
  };
  return await Promise.all(n.map(async (m) => {
    i[m.handle] = await Rt(m, h[m.handle]);
  })), i;
}
async function ur(n, a) {
  const h = Se(n);
  return h.length === 0 || !Array.isArray(a) ? [] : Promise.all(a.map(async (i) => Ct(h, i)));
}
async function Rt(n, a) {
  return pe(n) ? or(a) : me(n) ? ur(n, a) : fe(n) ? Ct(Me(n), a) : a;
}
async function ke(n, a) {
  const h = await Promise.all(Object.entries(a).map(async ([i, m]) => {
    const p = de(n, i);
    return p ? [p.handle, await Rt(p, m)] : [i, m];
  }));
  return Object.fromEntries(h);
}
function $e(n) {
  return Array.isArray(n) ? n.map((a) => $e(a)) : !n || typeof n != "object" || typeof File < "u" && n instanceof File || typeof Blob < "u" && n instanceof Blob ? n : Object.fromEntries(Object.entries(n).map(([a, h]) => [a, $e(h)]));
}
function Ue(n) {
  return {
    ...n,
    session: {
      ...n.session,
      tokens: { ...n.session.tokens },
      continuation: n.session.continuation ? { ...n.session.continuation } : null
    },
    values: $e(n.values),
    errors: {
      form: [...n.errors.form],
      fields: Object.fromEntries(Object.entries(n.errors.fields).map(([a, h]) => [a, [...h]])),
      pages: Object.fromEntries(Object.entries(n.errors.pages).map(([a, h]) => [a, [...h]]))
    },
    fieldStates: Object.fromEntries(Object.entries(n.fieldStates).map(([a, h]) => [a, { ...h }])),
    pageStates: Object.fromEntries(Object.entries(n.pageStates).map(([a, h]) => [a, { ...h }])),
    lastSubmitResult: n.lastSubmitResult ? {
      ...n.lastSubmitResult,
      errors: {
        form: [...n.lastSubmitResult.errors.form],
        fields: Object.fromEntries(Object.entries(n.lastSubmitResult.errors.fields).map(([a, h]) => [a, [...h]])),
        pages: Object.fromEntries(Object.entries(n.lastSubmitResult.errors.pages).map(([a, h]) => [a, [...h]]))
      },
      messages: { ...n.lastSubmitResult.messages },
      session: n.lastSubmitResult.session ? {
        ...n.lastSubmitResult.session,
        tokens: { ...n.lastSubmitResult.session.tokens },
        continuation: n.lastSubmitResult.session.continuation ? { ...n.lastSubmitResult.session.continuation } : null
      } : null
    } : null
  };
}
function cr(n) {
  return Object.fromEntries(se(n.definition).map((a) => [a.id, Ye(a)]));
}
function It(n) {
  return Object.fromEntries(se(n).map((a) => [a.id, {
    hidden: a.meta?.hidden === !0,
    disabled: a.meta?.disabled === !0
  }]));
}
function lr(n) {
  return Object.fromEntries(n.pages.map((a) => [a.id, { hidden: !1 }]));
}
function hr(n, a) {
  const h = n.definition.pages.find((m) => m.id === a);
  if (!h)
    return [];
  const i = [];
  return h.rows.forEach((m) => {
    m.fields.forEach((p) => {
      i.push(p.id);
    });
  }), i;
}
function jt(n, a) {
  return de(n, a.fieldId) || Ot(n, a.fieldId);
}
function bt(n) {
  const a = It(n.definition);
  return se(n.definition).forEach((h) => {
    const i = h.condition;
    if (!i || i.rules.length === 0)
      return;
    const m = i.rules.map((N) => {
      const f = jt(n.definition, N), T = f ? a[f.id]?.hidden !== !0 : null;
      return St({
        condition: N.operator,
        value: N.value
      }, f ? re(f, n.values[f.id]) : [], {
        visibility: T
      });
    });
    if (i.effect === "show" || i.effect === "hide") {
      const { shouldHide: N } = At({
        conditionRule: i.mode,
        showRule: i.effect === "show" ? "show" : "hide"
      }, m);
      a[h.id] = {
        ...a[h.id],
        hidden: a[h.id].hidden || N
      };
      return;
    }
    const p = i.mode === "any" ? m.includes(!0) : m.every((N) => N === !0);
    a[h.id] = {
      ...a[h.id],
      disabled: a[h.id].disabled || (i.effect === "disable" ? p : !p)
    };
  }), a;
}
function dr(n) {
  return Ye(n);
}
function fr(n, a, h) {
  let i = n.values;
  return se(n.definition).forEach((m) => {
    const p = m.condition, N = a[m.id]?.hidden === !0, f = h[m.id]?.hidden === !0, T = p?.clearOnHide !== !1;
    if (!f || N || !T)
      return;
    const w = dr(m);
    i[m.id] !== w && (i = {
      ...i,
      [m.id]: w
    });
  }), i;
}
function wt(n, a) {
  return Object.fromEntries(n.definition.pages.map((h) => {
    const i = h.condition;
    if (!i || i.rules.length === 0)
      return [h.id, { hidden: !1 }];
    const m = i.rules.map((N) => {
      const f = jt(n.definition, N), T = f ? a[f.id]?.hidden !== !0 : null;
      return St({
        condition: N.operator,
        value: N.value
      }, f ? re(f, n.values[f.id]) : [], {
        visibility: T
      });
    }), { shouldHide: p } = At({
      conditionRule: i.mode,
      showRule: i.effect === "show" ? "show" : "hide"
    }, m);
    return [h.id, { hidden: p }];
  }));
}
function xt(n, a, h) {
  const i = n.pages[0]?.id || "", m = n.pages.find((p) => a[p.id]?.hidden !== !0)?.id || i;
  return h ? a[h]?.hidden === !0 ? m : h : m;
}
function G(n) {
  let a = n;
  for (let m = 0; m < 3; m += 1) {
    const p = bt(a), N = fr(a, a.fieldStates, p);
    if (N !== a.values) {
      a = {
        ...a,
        values: N,
        fieldStates: p
      };
      continue;
    }
    const f = wt(a, p);
    return {
      ...a,
      fieldStates: p,
      pageStates: f,
      currentPageId: xt(a.definition, f, a.currentPageId)
    };
  }
  const h = bt(a), i = wt(a, h);
  return {
    ...a,
    fieldStates: h,
    pageStates: i,
    currentPageId: xt(a.definition, i, a.currentPageId)
  };
}
function mr(n, a) {
  return n.type === "checkboxes" ? !Array.isArray(a) || a.length === 0 : er(n) ? a !== !0 : pe(n) || me(n) || De(n) ? !Array.isArray(a) || a.length === 0 : fe(n) && a && typeof a == "object" ? Object.values(a).every((h) => h == null || typeof h == "string" && h.trim() === "") : a == null ? !0 : typeof a == "string" ? a.trim() === "" : !1;
}
function Le(n, a, h, i, m) {
  const p = new Set(n.validation.map((T) => T.type)), N = n.input;
  if ((n.required || p.has("required")) && mr(n, a)) {
    m[i] = ["This field is required."];
    return;
  }
  if ((rr(n) || p.has("email")) && typeof a == "string" && a.trim() !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(a)) {
    m[i] = ["Please enter a valid email address."];
    return;
  }
  if ((tr(n) || p.has("number")) && typeof a == "string" && a.trim() !== "") {
    const T = Number.parseFloat(a);
    if (!Number.isFinite(T)) {
      m[i] = ["Please enter a valid number."];
      return;
    }
    const w = n.validation.find((R) => R.type === "number"), E = Number(N.min ?? w?.min ?? Number.NaN), S = Number(N.max ?? w?.max ?? Number.NaN);
    if (Number.isFinite(E) && T < E) {
      m[i] = [`Please enter a value greater than or equal to ${E}.`];
      return;
    }
    if (Number.isFinite(S) && T > S) {
      m[i] = [`Please enter a value less than or equal to ${S}.`];
      return;
    }
  }
  if (p.has("url") && typeof a == "string" && a.trim() !== "")
    try {
      new URL(a);
    } catch {
      m[i] = ["Please enter a valid URL."];
      return;
    }
  const f = n.validation.find((T) => T.type === "match");
  if (f && typeof a == "string" && a.trim() !== "") {
    const T = (f.fieldId ? de(h.definition, f.fieldId) : void 0) || (f.fieldHandle ? Ot(h.definition, f.fieldHandle) : void 0), w = T ? h.values[T.id] : void 0;
    if (typeof w == "string" && w !== a) {
      m[i] = ["This value must match the related field."];
      return;
    }
  }
  if (p.has("minmaxOptions") && Array.isArray(a)) {
    const T = n.validation.find((S) => S.type === "minmaxOptions"), w = Number(N.min ?? T?.min ?? Number.NaN), E = Number(N.max ?? T?.max ?? Number.NaN);
    if (Number.isFinite(w) && a.length < w) {
      m[i] = [`Please select at least ${w} option${w === 1 ? "" : "s"}.`];
      return;
    }
    if (Number.isFinite(E) && a.length > E) {
      m[i] = [`Please select no more than ${E} option${E === 1 ? "" : "s"}.`];
      return;
    }
  }
  if (fe(n)) {
    const T = Me(n), w = a && typeof a == "object" ? a : {};
    T.forEach((E) => {
      E.meta?.hidden !== !0 && Le(E, w[E.handle], h, `${i}.${E.handle}`, m);
    });
    return;
  }
  if (me(n)) {
    const T = Array.isArray(a) ? a : [], w = Se(n);
    T.forEach((E, S) => {
      const R = E && typeof E == "object" ? E : {};
      w.forEach((v) => {
        Le(
          v,
          R[v.handle],
          h,
          `${i}.${S}.${v.handle}`,
          m
        );
      });
    });
  }
}
function pr(n) {
  const a = {
    form: [],
    fields: {},
    pages: {}
  };
  return hr(n, n.currentPageId).forEach((h) => {
    const i = de(n.definition, h);
    !i || n.fieldStates[h]?.hidden === !0 || n.fieldStates[h]?.disabled === !0 || Le(i, n.values[h], n, h, a.fields);
  }), Object.keys(a.fields).length > 0 && (a.form = [n.definition.settings.validation.formErrorMessage || "Please correct the highlighted fields."]), a;
}
function Rr({ envelope: n, transport: a }) {
  const h = new Zt(), i = /* @__PURE__ */ new Set(), m = cr(n);
  let p = {
    status: "ready",
    definition: n.definition,
    session: n.session,
    values: m,
    errors: {
      form: [],
      fields: {},
      pages: {}
    },
    fieldStates: It(n.definition),
    pageStates: lr(n.definition),
    currentPageId: n.session.currentPageId || n.definition.settings.initialPageId,
    lastSubmitResult: null
  };
  p = G(p);
  const N = () => {
    const w = Ue(p);
    i.forEach((E) => {
      E(w);
    });
  }, f = (w) => {
    p = w(p), N();
  }, T = {
    id: n.session.id,
    getState() {
      return Ue(p);
    },
    subscribe(w) {
      return i.add(w), w(Ue(p)), () => {
        i.delete(w);
      };
    },
    setValue(w, E) {
      f((S) => {
        const R = Object.fromEntries(Object.entries(S.errors.fields).filter(([v]) => v !== w && !v.startsWith(`${w}.`)));
        return R[w] = [], G({
          ...S,
          values: {
            ...S.values,
            [w]: E
          },
          errors: {
            ...S.errors,
            fields: R
          }
        });
      });
    },
    patchValues(w) {
      f((E) => G({
        ...E,
        values: {
          ...E.values,
          ...w
        }
      }));
    },
    async submit(w) {
      const E = p.definition.pages.find((v) => v.id === p.currentPageId), S = w || E?.actions.primary.type || "submit", R = S === "next" ? "submit" : S;
      if (R !== "back" && R !== "save" && p.definition.settings.validation.onSubmit) {
        const v = pr(p);
        if (v.form.length > 0 || Object.keys(v.fields).length > 0) {
          const _ = {
            success: !1,
            isFinalPage: !1,
            errors: v,
            messages: {
              error: v.form[0] || null
            },
            session: p.session
          };
          return f((D) => ({
            ...D,
            errors: v,
            lastSubmitResult: _
          })), h.emit("formie:submit:result", _), _;
        }
      }
      f((v) => ({
        ...v,
        status: "submitting",
        errors: {
          form: [],
          fields: {},
          pages: {}
        }
      }));
      try {
        const v = await a.submit({
          definition: p.definition,
          session: p.session,
          values: p.values,
          action: R
        });
        return f((_) => G({
          ..._,
          status: "ready",
          session: v.session ?? _.session,
          currentPageId: v.session?.currentPageId || v.currentPageId || _.currentPageId,
          errors: v.errors,
          lastSubmitResult: v
        })), h.emit("formie:submit:result", v), (v.currentPageId || v.nextPageId) && h.emit("formie:page:navigate", {
          currentPageId: p.currentPageId,
          nextPageId: v.nextPageId || v.currentPageId
        }), v;
      } catch (v) {
        const _ = v instanceof Error ? v.message : "Submission failed.", D = {
          success: !1,
          isFinalPage: !1,
          errors: {
            form: [_],
            fields: {},
            pages: {}
          },
          messages: {
            error: _
          },
          session: p.session
        };
        return f((ge) => ({
          ...ge,
          status: "ready",
          errors: D.errors,
          lastSubmitResult: D
        })), h.emit("formie:submit:result", D), D;
      }
    },
    async setPage(w) {
      if (!a.setPage) {
        f((E) => G({
          ...E,
          currentPageId: w,
          session: {
            ...E.session,
            currentPageId: w
          }
        }));
        return;
      }
      f((E) => ({
        ...E,
        status: "refreshing"
      }));
      try {
        const E = await a.setPage({
          definition: p.definition,
          session: p.session,
          values: p.values,
          currentPageId: p.currentPageId,
          targetPageId: w
        });
        f((S) => G({
          ...S,
          status: "ready",
          session: E,
          currentPageId: E.currentPageId
        })), h.emit("formie:page:navigate", {
          currentPageId: p.currentPageId,
          nextPageId: w
        });
      } catch (E) {
        const S = E instanceof Error ? E.message : "Unable to change page.";
        f((R) => ({
          ...R,
          status: "ready"
        })), h.emit("formie:page:navigate:error", {
          currentPageId: p.currentPageId,
          nextPageId: w,
          error: S
        });
      }
    },
    async refreshSession() {
      f((w) => ({
        ...w,
        status: "refreshing"
      }));
      try {
        const w = await a.refreshSession({
          formHandle: p.definition.handle,
          siteId: p.definition.siteId ?? void 0,
          session: p.session
        });
        f((E) => G({
          ...E,
          status: "ready",
          session: w,
          currentPageId: w.currentPageId || E.currentPageId
        })), h.emit("formie:session:refreshed", w);
      } catch (w) {
        const E = w instanceof Error ? w.message : "Unable to refresh session.";
        f((S) => ({
          ...S,
          status: "ready"
        })), h.emit("formie:session:refresh:error", {
          error: E
        });
      }
    },
    reset() {
      f((w) => G({
        ...w,
        session: n.session,
        values: { ...m },
        errors: {
          form: [],
          fields: {},
          pages: {}
        },
        currentPageId: n.session.currentPageId || n.definition.settings.initialPageId,
        lastSubmitResult: null
      })), h.emit("formie:state:reset", null);
    },
    async destroy() {
      f((w) => ({
        ...w,
        status: "destroyed"
      })), i.clear();
    },
    on(w, E) {
      return h.on(w, E);
    }
  };
  return queueMicrotask(() => {
    h.emit("formie:client:ready", T.getState());
  }), T;
}
const Ir = [
  "formie:client:ready",
  "formie:submit:result",
  "formie:page:navigate",
  "formie:page:navigate:error",
  "formie:session:refreshed",
  "formie:session:refresh:error",
  "formie:state:reset"
];
var H = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : typeof self < "u" ? self : {};
function gr(n) {
  return n && n.__esModule && Object.prototype.hasOwnProperty.call(n, "default") ? n.default : n;
}
var he = { exports: {} }, Nt = he.exports, Et;
function yr() {
  return Et || (Et = 1, (function(n, a) {
    (function(h, i) {
      i(a);
    })(Nt, function(h) {
      function i(o, t, e) {
        return (t = (function(r) {
          var s = (function(u, c) {
            if (typeof u != "object" || !u) return u;
            var l = u[Symbol.toPrimitive];
            if (l !== void 0) {
              var d = l.call(u, c);
              if (typeof d != "object") return d;
              throw new TypeError("@@toPrimitive must return a primitive value.");
            }
            return (c === "string" ? String : Number)(u);
          })(r, "string");
          return typeof s == "symbol" ? s : s + "";
        })(t)) in o ? Object.defineProperty(o, t, { value: e, enumerable: !0, configurable: !0, writable: !0 }) : o[t] = e, o;
      }
      const m = function(o, t) {
        if (o.length === 0) return t.length;
        if (t.length === 0) return o.length;
        let e, r, s = [];
        for (e = 0; e <= t.length; e++) s[e] = [e];
        for (r = 0; r <= o.length; r++) s[0] === void 0 && (s[0] = []), s[0][r] = r;
        for (e = 1; e <= t.length; e++) for (r = 1; r <= o.length; r++) t.charAt(e - 1) === o.charAt(r - 1) ? s[e][r] = s[e - 1][r - 1] : s[e][r] = Math.min(s[e - 1][r - 1] + 1, Math.min(s[e][r - 1] + 1, s[e - 1][r] + 1));
        return s[t.length] === void 0 && (s[t.length] = []), s[t.length][o.length];
      };
      class p extends Error {
        constructor(t, e, r, s, u) {
          super(t), this.name = "SyntaxError", this.cursor = e, this.expression = r, this.subject = s, this.proposals = u;
        }
        toString() {
          let t = `${this.name}: ${this.message} around position ${this.cursor}`;
          if (this.expression && (t += ` for expression \`${this.expression}\``), t += ".", this.subject && this.proposals) {
            let e = Number.MAX_SAFE_INTEGER, r = null;
            for (let s of this.proposals) {
              let u = m(this.subject, s);
              u < e && (r = s, e = u);
            }
            r !== null && e < 3 && (t += ` Did you mean "${r}"?`);
          }
          return t;
        }
      }
      class N {
        constructor(t, e) {
          i(this, "next", () => {
            if (this.position += 1, this.tokens[this.position] === void 0) throw new p("Unexpected end of expression", this.last.cursor, this.expression);
          }), i(this, "expect", (r, s, u) => {
            let c = this.current;
            if (!c.test(r, s)) {
              let l = "";
              u && (l = u + ". ");
              let d = "";
              throw s && (d = ` with value "${s}"`), l += `Unexpected token "${c.type}" of value "${c.value}" ("${r}" expected${d})`, new p(l, c.cursor, this.expression);
            }
            this.next();
          }), i(this, "isEOF", () => f.EOF_TYPE === this.current.type), i(this, "isEqualTo", (r) => {
            if (r == null || !r instanceof N || r.tokens.length !== this.tokens.length) return !1;
            let s = r.position;
            r.position = 0;
            let u = !0;
            for (let c of this.tokens) {
              if (!r.current.isEqualTo(c)) {
                u = !1;
                break;
              }
              r.position < r.tokens.length - 1 && r.next();
            }
            return r.position = s, u;
          }), i(this, "diff", (r) => {
            let s = [];
            if (!this.isEqualTo(r)) {
              let u = 0, c = r.position;
              r.position = 0;
              for (let l of this.tokens) {
                let d = l.diff(r.current);
                d.length > 0 && s.push({ index: u, diff: d }), r.position < r.tokens.length - 1 && r.next();
              }
              r.position = c;
            }
            return s;
          }), this.expression = t, this.position = 0, this.tokens = e;
        }
        get current() {
          return this.tokens[this.position];
        }
        get last() {
          return this.tokens[this.position - 1];
        }
        toString() {
          return this.tokens.join(`
`);
        }
      }
      class f {
        constructor(t, e, r) {
          i(this, "test", (s, u = null) => this.type === s && (u === null || this.value === u)), i(this, "isEqualTo", (s) => !(s == null || !s instanceof f) && s.value == this.value && s.type === this.type && s.cursor === this.cursor), i(this, "diff", (s) => {
            let u = [];
            return this.isEqualTo(s) || (s.value !== this.value && u.push(`Value: ${s.value} != ${this.value}`), s.cursor !== this.cursor && u.push(`Cursor: ${s.cursor} != ${this.cursor}`), s.type !== this.type && u.push(`Type: ${s.type} != ${this.type}`)), u;
          }), this.value = e, this.type = t, this.cursor = r;
        }
        toString() {
          return `${this.cursor} [${this.type}] ${this.value}`;
        }
      }
      function T(o) {
        let t = 0, e = [], r = [], s = (o = o.replace(/\r|\n|\t|\v|\f/g, " ")).length;
        for (; t < s; ) {
          if (o[t] === " ") {
            ++t;
            continue;
          }
          if (o.substr(t, 2) === "/*") {
            const c = o.indexOf("*/", t + 2);
            if (c === -1) {
              t = s;
              break;
            }
            t = c + 2;
            continue;
          }
          let u = w(o.substr(t));
          if (u !== null) {
            const c = u.length, l = u.replace(/_/g, "");
            u = l.indexOf(".") === -1 && l.indexOf("e") === -1 && l.indexOf("E") === -1 ? parseInt(l, 10) : parseFloat(l), e.push(new f(f.NUMBER_TYPE, u, t + 1)), t += c;
          } else if ("([{".indexOf(o[t]) >= 0) r.push([o[t], t]), e.push(new f(f.PUNCTUATION_TYPE, o[t], t + 1)), ++t;
          else if (")]}".indexOf(o[t]) >= 0) {
            if (r.length === 0) throw new p(`Unexpected "${o[t]}"`, t, o);
            let [c, l] = r.pop(), d = c.replace("(", ")").replace("{", "}").replace("[", "]");
            if (o[t] !== d) throw new p(`Unclosed "${c}"`, l, o);
            e.push(new f(f.PUNCTUATION_TYPE, o[t], t + 1)), ++t;
          } else {
            let c = R(o.substr(t));
            if (c !== null) e.push(new f(f.STRING_TYPE, c.captured, t + 1)), t += c.length;
            else if (o.substr(t, 2) === "\\\\") e.push(new f(f.PUNCTUATION_TYPE, "\\", t + 1)), t += 2;
            else {
              const l = e.length > 0 ? e[e.length - 1] : null;
              if (l && l.type === f.PUNCTUATION_TYPE && (l.value === "." || l.value === "?.")) {
                let d = ge(o.substr(t));
                if (d) e.push(new f(f.NAME_TYPE, d, t + 1)), t += d.length;
                else {
                  let g = D(o.substr(t));
                  if (g) e.push(new f(f.OPERATOR_TYPE, g, t + 1)), t += g.length;
                  else if (o.substr(t, 2) === "?." || o.substr(t, 2) === "??") e.push(new f(f.PUNCTUATION_TYPE, o.substr(t, 2), t + 1)), t += 2;
                  else {
                    if (!(".,?:".indexOf(o[t]) >= 0)) throw new p(`Unexpected character "${o[t]}"`, t, o);
                    e.push(new f(f.PUNCTUATION_TYPE, o[t], t + 1)), ++t;
                  }
                }
              } else {
                let d = D(o.substr(t));
                if (d) e.push(new f(f.OPERATOR_TYPE, d, t + 1)), t += d.length;
                else if (o.substr(t, 2) === "?." || o.substr(t, 2) === "??") e.push(new f(f.PUNCTUATION_TYPE, o.substr(t, 2), t + 1)), t += 2;
                else if (".,?:".indexOf(o[t]) >= 0) e.push(new f(f.PUNCTUATION_TYPE, o[t], t + 1)), ++t;
                else {
                  let g = ge(o.substr(t));
                  if (!g) throw new p(`Unexpected character "${o[t]}"`, t, o);
                  e.push(new f(f.NAME_TYPE, g, t + 1)), t += g.length;
                }
              }
            }
          }
        }
        if (e.push(new f(f.EOF_TYPE, null, t + 1)), r.length > 0) {
          let [u, c] = r.pop();
          throw new p(`Unclosed "${u}"`, c, o);
        }
        return new N(o, e);
      }
      function w(o) {
        let t = null, e = o.match(/^(?:((?:\d(?:_?\d)*)\.(?:\d(?:_?\d)*)|\.(?:\d(?:_?\d)*)|(?:\d(?:_?\d)*))(?:[eE][+-]?\d(?:_?\d)*)?)/);
        return e && e.length > 0 && (t = e[0]), t;
      }
      i(f, "EOF_TYPE", "end of expression"), i(f, "NAME_TYPE", "name"), i(f, "NUMBER_TYPE", "number"), i(f, "STRING_TYPE", "string"), i(f, "OPERATOR_TYPE", "operator"), i(f, "PUNCTUATION_TYPE", "punctuation");
      const E = /^"([^"\\]*(?:\\.[^"\\]*)*)"|'([^'\\]*(?:\\.[^'\\]*)*)'/s;
      function S(o, t) {
        return t === '"' ? o = o.replace(/\\\"/g, '"') : t === "'" && (o = o.replace(/\\'/g, "'")), o = o.replace(/\\\\/g, "\\");
      }
      function R(o) {
        let t = null;
        if (["'", '"'].indexOf(o.substr(0, 1)) === -1) return t;
        let e = E.exec(o);
        return e !== null && e.length > 0 && (t = e[1] !== void 0 ? { captured: S(e[1], '"') } : { captured: S(e[2], "'") }, t.length = e[0].length), t;
      }
      const v = ["&&", "and", "||", "or", "+", "-", "**", "*", "/", "%", "&", "|", "^", ">>", "<<", "===", "!==", "!=", "==", "<=", ">=", "<", ">", "contains", "matches", "starts with", "ends with", "not in", "in", "not", "!", "xor", "~", ".."], _ = ["and", "or", "matches", "contains", "starts with", "ends with", "not in", "in", "not", "xor"];
      function D(o) {
        let t = null;
        for (let e of v) if (o.substr(0, e.length) === e) {
          _.indexOf(e) >= 0 ? o.substr(0, e.length + 1) === e + " " && (t = e) : t = e;
          break;
        }
        return t;
      }
      function ge(o) {
        let t = null, e = o.match(/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/);
        return e && e.length > 0 && (t = e[0]), t;
      }
      function Ut(o) {
        return /boolean|number|string/.test(typeof o);
      }
      function ze(o, t) {
        var e = "", r = [], s = 0, u = 0, c = "", l = "", d = "", g = "", y = "", x = 0, k = 0, B = 0, q = 0, ct = 0, we = [], Fe = "", lt = /%([\dA-Fa-f]+)/g, ht = function(xe, dt) {
          return (xe += "").length < dt ? new Array(++dt - xe.length).join("0") + xe : xe;
        };
        for (s = 0; s < t.length; s++) if (c = t.charAt(s), l = t.charAt(s + 1), c === "\\" && l && /\d/.test(l)) {
          if (q = s + (B = (d = t.slice(s + 1).match(/^\d+/)[0]).length) + 1, t.charAt(q) + t.charAt(q + 1) === "..") {
            if (x = d.charCodeAt(0), /\\\d/.test(t.charAt(q + 2) + t.charAt(q + 3))) g = t.slice(q + 3).match(/^\d+/)[0], s += 1;
            else {
              if (!t.charAt(q + 2)) throw new Error("Range with no end point");
              g = t.charAt(q + 2);
            }
            if ((k = g.charCodeAt(0)) > x) for (u = x; u <= k; u++) r.push(String.fromCharCode(u));
            else r.push(".", d, g);
            s += g.length + 2;
          } else y = String.fromCharCode(parseInt(d, 8)), r.push(y);
          s += B;
        } else if (l + t.charAt(s + 2) === "..") {
          if (x = (d = c).charCodeAt(0), /\\\d/.test(t.charAt(s + 3) + t.charAt(s + 4))) g = t.slice(s + 4).match(/^\d+/)[0], s += 1;
          else {
            if (!t.charAt(s + 3)) throw new Error("Range with no end point");
            g = t.charAt(s + 3);
          }
          if ((k = g.charCodeAt(0)) > x) for (u = x; u <= k; u++) r.push(String.fromCharCode(u));
          else r.push(".", d, g);
          s += g.length + 2;
        } else r.push(c);
        for (s = 0; s < o.length; s++) if (c = o.charAt(s), r.indexOf(c) !== -1) if (e += "\\", (ct = c.charCodeAt(0)) < 32 || ct > 126) switch (c) {
          case `
`:
            e += "n";
            break;
          case "	":
            e += "t";
            break;
          case "\r":
            e += "r";
            break;
          case "\x07":
            e += "a";
            break;
          case "\v":
            e += "v";
            break;
          case "\b":
            e += "b";
            break;
          case "\f":
            e += "f";
            break;
          default:
            for (Fe = encodeURIComponent(c), (we = lt.exec(Fe)) !== null && (e += ht(parseInt(we[1], 16).toString(8), 3)); (we = lt.exec(Fe)) !== null; ) e += "\\" + ht(parseInt(we[1], 16).toString(8), 3);
        }
        else e += c;
        else e += c;
        return e;
      }
      class I {
        constructor(t = {}, e = {}) {
          i(this, "compile", (r) => {
            for (let s of Object.values(this.nodes)) s.compile(r);
          }), i(this, "evaluate", (r, s) => {
            let u = [];
            for (let c of Object.values(this.nodes)) u.push(c.evaluate(r, s));
            return u;
          }), i(this, "toArray", () => {
            throw new Error(`Dumping a "${this.name}" instance is not supported yet.`);
          }), i(this, "dump", () => {
            let r = "";
            for (let s of this.toArray()) r += Ut(s) ? s : s.dump();
            return r;
          }), i(this, "dumpString", (r) => `"${ze(r, '\0	"\\')}"`), i(this, "isHash", (r) => {
            let s = 0;
            for (let u of Object.keys(r)) if (u = parseInt(u), u !== s++) return !0;
            return !1;
          }), this.name = "Node", this.nodes = t, this.attributes = e;
        }
        toString() {
          let t = [];
          for (let r of Object.keys(this.attributes)) {
            let s = "null";
            this.attributes[r] && (s = this.attributes[r].toString()), t.push(`${r}: '${s}'`);
          }
          let e = [this.name + "(" + t.join(", ")];
          if (this.nodes.length > 0) {
            for (let r of Object.values(this.nodes)) {
              let s = r.toString().split(`
`);
              for (let u of s) e.push("    " + u);
            }
            e.push(")");
          } else e[0] += ")";
          return e.join(`
`);
        }
      }
      class F extends I {
        constructor(t, e, r) {
          super({ left: e, right: r }, { operator: t }), i(this, "compile", (s) => {
            let u = this.attributes.operator;
            u !== "matches" ? u !== "contains" ? u !== "starts with" ? u !== "ends with" ? F.functions[u] === void 0 ? (F.operators[u] !== void 0 && (u = F.operators[u]), s.raw("(").compile(this.nodes.left).raw(" ").raw(u).raw(" ").compile(this.nodes.right).raw(")")) : s.raw(`${F.functions[u]}(`).compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")") : s.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().endsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())") : s.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().startsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())") : s.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().includes(").compile(this.nodes.right).raw(".toString().toLowerCase())") : s.compile(this.nodes.right).raw(".test(").compile(this.nodes.left).raw(")");
          }), i(this, "evaluate", (s, u) => {
            let c = this.attributes.operator, l = this.nodes.left.evaluate(s, u);
            if (F.functions[c] !== void 0) {
              let g = this.nodes.right.evaluate(s, u);
              switch (c) {
                case "not in":
                  return g.indexOf(l) === -1;
                case "in":
                  return g.indexOf(l) >= 0;
                case "..":
                  return (function(y, x) {
                    let k = [];
                    for (let B = y; B <= x; B++) k.push(B);
                    return k;
                  })(l, g);
                case "**":
                  return Math.pow(l, g);
              }
            }
            let d = null;
            switch (c) {
              case "or":
              case "||":
                return l || (d = this.nodes.right.evaluate(s, u)), l || d;
              case "and":
              case "&&":
                return l && (d = this.nodes.right.evaluate(s, u)), l && d;
              case "xor":
                return d = this.nodes.right.evaluate(s, u), d && !l || l && !d;
              case "<<":
                return d = this.nodes.right.evaluate(s, u), l << d;
              case ">>":
                return d = this.nodes.right.evaluate(s, u), l >> d;
            }
            switch (d = this.nodes.right.evaluate(s, u), c) {
              case "|":
                return l | d;
              case "^":
                return l ^ d;
              case "&":
                return l & d;
              case "==":
                return l == d;
              case "===":
                return l === d;
              case "!=":
                return l != d;
              case "!==":
                return l !== d;
              case "<":
                return l < d;
              case ">":
                return l > d;
              case ">=":
                return l >= d;
              case "<=":
                return l <= d;
              case "not in":
                return d.indexOf(l) === -1;
              case "in":
                return d.indexOf(l) >= 0;
              case "+":
                return l + d;
              case "-":
                return l - d;
              case "~":
                return l.toString() + d.toString();
              case "*":
                return l * d;
              case "/":
                return l / d;
              case "%":
                return l % d;
              case "matches":
                if (l == null) return !1;
                let g = d.match(F.regex_expression);
                return new RegExp(g[1], g[2]).test(l);
              case "contains":
                return l.toString().toLowerCase().includes(d.toString().toLowerCase());
              case "starts with":
                return l.toString().toLowerCase().startsWith(d.toString().toLowerCase());
              case "ends with":
                return l.toString().toLowerCase().endsWith(d.toString().toLowerCase());
            }
          }), i(this, "toArray", () => ["(", this.nodes.left, " " + this.attributes.operator + " ", this.nodes.right, ")"]), this.name = "BinaryNode";
        }
      }
      i(F, "regex_expression", /\/(.+)\/(.*)/), i(F, "operators", { "~": ".", and: "&&", or: "||", xor: "xor", "<<": "<<", ">>": ">>" }), i(F, "functions", { "**": "Math.pow", "..": "range", in: "includes", "not in": "!includes" });
      class ne extends I {
        constructor(t, e) {
          super({ node: e }, { operator: t }), i(this, "compile", (r) => {
            r.raw("(").raw(ne.operators[this.attributes.operator]).compile(this.nodes.node).raw(")");
          }), i(this, "evaluate", (r, s) => {
            let u = this.nodes.node.evaluate(r, s);
            switch (this.attributes.operator) {
              case "not":
              case "!":
                return !u;
              case "-":
                return -u;
              case "~":
                return ~u;
            }
            return u;
          }), i(this, "toArray", () => ["(", this.attributes.operator + " ", this.nodes.node, ")"]), this.name = "UnaryNode";
        }
      }
      i(ne, "operators", { "!": "!", not: "!", "+": "+", "-": "-", "~": "~" });
      class P extends I {
        constructor(t, e = !1, r = !1) {
          super({}, { value: t }), i(this, "compile", (s) => {
            s.repr(this.attributes.value, this.isIdentifier);
          }), i(this, "evaluate", (s, u) => this.attributes.value), i(this, "toArray", () => {
            let s = [], u = this.attributes.value;
            if (this.isIdentifier) s.push(u);
            else if (u === !0) s.push("true");
            else if (u === !1) s.push("false");
            else if (u === null) s.push("null");
            else if (typeof u == "number") s.push(u);
            else if (typeof u == "string") s.push(this.dumpString(u));
            else if (Array.isArray(u)) {
              for (let c of u) s.push(","), s.push(new P(c));
              s[0] = "[", s.push("]");
            } else if (this.isHash(u)) {
              for (let c of Object.keys(u)) s.push(", "), s.push(new P(c)), s.push(": "), s.push(new P(u[c]));
              s[0] = "{", s.push("}");
            }
            return s;
          }), this.isIdentifier = e, this.isNullSafe = r, this.name = "ConstantNode";
        }
      }
      class Ae extends I {
        constructor(t, e, r) {
          super({ expr1: t, expr2: e, expr3: r }), i(this, "compile", (s) => {
            s.raw("((").compile(this.nodes.expr1).raw(") ? (").compile(this.nodes.expr2).raw(") : (").compile(this.nodes.expr3).raw("))");
          }), i(this, "evaluate", (s, u) => this.nodes.expr1.evaluate(s, u) ? this.nodes.expr2.evaluate(s, u) : this.nodes.expr3.evaluate(s, u)), i(this, "toArray", () => ["(", this.nodes.expr1, " ? ", this.nodes.expr2, " : ", this.nodes.expr3, ")"]), this.name = "ConditionalNode";
        }
      }
      class Ve extends I {
        constructor(t, e) {
          super({ fnArguments: e }, { name: t }), i(this, "compile", (r) => {
            let s = [];
            for (let c of Object.values(this.nodes.fnArguments.nodes)) s.push(r.subcompile(c));
            let u = r.getFunction(this.attributes.name);
            r.raw(u.compiler.apply(null, s));
          }), i(this, "evaluate", (r, s) => {
            let u = [s];
            for (let c of Object.values(this.nodes.fnArguments.nodes)) u.push(c.evaluate(r, s));
            return r[this.attributes.name].evaluator.apply(null, u);
          }), i(this, "toArray", () => {
            let r = [];
            r.push(this.attributes.name);
            for (let s of Object.values(this.nodes.fnArguments.nodes)) r.push(", "), r.push(s);
            return r[1] = "(", r.push(")"), r;
          }), this.name = "FunctionNode";
        }
      }
      class He extends I {
        constructor(t) {
          super({}, { name: t }), i(this, "compile", (e) => {
            e.raw(this.attributes.name);
          }), i(this, "evaluate", (e, r) => r[this.attributes.name]), i(this, "toArray", () => [this.attributes.name]), this.name = "NameNode";
        }
      }
      class ye extends I {
        constructor() {
          super(), i(this, "addElement", (t, e = null) => {
            e === null ? e = new P(++this.index) : this.type === "Array" && (this.type = "Object"), this.nodes[(++this.keyIndex).toString()] = e, this.nodes[(++this.keyIndex).toString()] = t;
          }), i(this, "compile", (t) => {
            this.type === "Object" ? t.raw("{") : t.raw("["), this.compileArguments(t, this.type !== "Array"), this.type === "Object" ? t.raw("}") : t.raw("]");
          }), i(this, "evaluate", (t, e) => {
            let r;
            if (this.type === "Array") {
              r = [];
              for (let s of this.getKeyValuePairs()) r.push(s.value.evaluate(t, e));
            } else {
              r = {};
              for (let s of this.getKeyValuePairs()) r[s.key.evaluate(t, e)] = s.value.evaluate(t, e);
            }
            return r;
          }), i(this, "toArray", () => {
            let t = {};
            for (let r of this.getKeyValuePairs()) t[r.key.attributes.value] = r.value;
            let e = [];
            if (this.isHash(t)) {
              for (let r of Object.keys(t)) e.push(", "), e.push(new P(r)), e.push(": "), e.push(t[r]);
              e[0] = "{", e.push("}");
            } else {
              for (let r of Object.values(t)) e.push(", "), e.push(r);
              e[0] = "[", e.push("]");
            }
            return e;
          }), i(this, "getKeyValuePairs", () => {
            let t, e, r, s = [], u = Object.values(this.nodes);
            for (t = 0, e = u.length; t < e; t += 2) r = u.slice(t, t + 2), s.push({ key: r[0], value: r[1] });
            return s;
          }), i(this, "compileArguments", (t, e = !0) => {
            let r = !0;
            for (let s of this.getKeyValuePairs()) r || t.raw(", "), r = !1, e && t.compile(s.key).raw(": "), t.compile(s.value);
          }), this.name = "ArrayNode", this.type = "Array", this.index = -1, this.keyIndex = -1;
        }
      }
      class Oe extends ye {
        constructor() {
          super(), i(this, "compile", (t) => {
            this.compileArguments(t, !1);
          }), i(this, "toArray", () => {
            let t = [];
            for (let e of this.getKeyValuePairs()) t.push(e.value), t.push(", ");
            return t.pop(), t;
          }), this.name = "ArgumentsNode";
        }
      }
      class A extends I {
        constructor(t, e, r, s) {
          super({ node: t, attribute: e, fnArguments: r }, { type: s, is_null_coalesce: !1, is_short_circuited: !1 }), i(this, "compile", (u) => {
            const c = this.nodes.attribute instanceof P && this.nodes.attribute.isNullSafe;
            switch (this.attributes.type) {
              case A.PROPERTY_CALL:
                u.compile(this.nodes.node).raw(c ? "?." : ".").raw(this.nodes.attribute.attributes.value);
                break;
              case A.METHOD_CALL:
                u.compile(this.nodes.node).raw(c ? "?." : ".").raw(this.nodes.attribute.attributes.value).raw("(").compile(this.nodes.fnArguments).raw(")");
                break;
              case A.ARRAY_CALL:
                u.compile(this.nodes.node).raw("[").compile(this.nodes.attribute).raw("]");
            }
          }), i(this, "evaluate", (u, c) => {
            switch (this.attributes.type) {
              case A.PROPERTY_CALL:
                let l = this.nodes.node.evaluate(u, c);
                if (l === null && (this.nodes.attribute.isNullSafe || this.attributes.is_null_coalesce)) return this.attributes.is_short_circuited = !0, null;
                if (l === null && this.isShortCircuited()) return null;
                if (typeof l != "object") throw new Error(`Unable to get property "${d}" on a non-object: ` + typeof l);
                let d = this.nodes.attribute.attributes.value;
                return this.attributes.is_null_coalesce ? l[d] ?? null : l[d];
              case A.METHOD_CALL:
                let g = this.nodes.node.evaluate(u, c);
                if (g === null && this.nodes.attribute.isNullSafe) return this.attributes.is_short_circuited = !0, null;
                if (g === null && this.isShortCircuited()) return null;
                let y = this.nodes.attribute.attributes.value;
                if (typeof g != "object") throw new Error(`Unable to call method "${y}" on a non-object: ` + typeof g);
                if (g[y] === void 0) throw new Error(`Method "${y}" is undefined on object.`);
                if (typeof g[y] != "function") throw new Error(`Method "${y}" is not a function on object.`);
                let x = this.nodes.fnArguments.evaluate(u, c);
                return g[y].apply(null, x);
              case A.ARRAY_CALL:
                let k = this.nodes.node.evaluate(u, c);
                if (k === null && this.isShortCircuited()) return null;
                if (!(Array.isArray(k) || typeof k == "object" || k === null && this.attributes.is_null_coalesce)) throw new Error("Unable to get an item on a non-array: " + typeof k);
                return this.attributes.is_null_coalesce ? k ? k[this.nodes.attribute.evaluate(u, c)] ?? null : null : k[this.nodes.attribute.evaluate(u, c)];
            }
          }), i(this, "toArray", () => {
            const u = this.nodes.attribute instanceof P && this.nodes.attribute.isNullSafe;
            switch (this.attributes.type) {
              case A.PROPERTY_CALL:
                return [this.nodes.node, u ? "?." : ".", this.nodes.attribute];
              case A.METHOD_CALL:
                return [this.nodes.node, u ? "?." : ".", this.nodes.attribute, "(", this.nodes.fnArguments, ")"];
              case A.ARRAY_CALL:
                return [this.nodes.node, "[", this.nodes.attribute, "]"];
            }
          }), this.name = "GetAttrNode";
        }
        isShortCircuited() {
          return this.attributes.is_short_circuited || this.nodes.node instanceof A && this.nodes.node.isShortCircuited();
        }
      }
      i(A, "PROPERTY_CALL", 1), i(A, "METHOD_CALL", 2), i(A, "ARRAY_CALL", 3);
      class We extends I {
        constructor(t, e) {
          super({ expr1: t, expr2: e }), i(this, "compile", (r) => {
            r.raw("((").compile(this.nodes.expr1).raw(") ?? (").compile(this.nodes.expr2).raw("))");
          }), i(this, "evaluate", (r, s) => (this.nodes.expr1 instanceof A && this._addNullCoalesceAttributeToGetAttrNodes(this.nodes.expr1), this.nodes.expr1.evaluate(r, s) ?? this.nodes.expr2.evaluate(r, s))), i(this, "toArray", () => ["(", this.nodes.expr1, ") ?? (", this.nodes.expr2, ")"]), i(this, "_addNullCoalesceAttributeToGetAttrNodes", (r) => {
            if (!(!r instanceof A)) {
              r.attributes.is_null_coalesce = !0;
              for (let s of Object.values(r.nodes)) this._addNullCoalesceAttributeToGetAttrNodes(s);
            }
          }), this.name = "NullCoalesceNode";
        }
      }
      class Be extends I {
        constructor(t) {
          super({}, { name: t }), i(this, "compile", (e) => {
            e.raw(this.attributes.name + " ?? null");
          }), i(this, "evaluate", (e, r) => null), i(this, "toArray", () => [this.attributes.name + " ?? null"]), this.name = "NullCoalescedNameNode";
        }
      }
      class qe {
        constructor(t = {}) {
          i(this, "functions", {}), i(this, "unaryOperators", { not: { precedence: 50 }, "!": { precedence: 50 }, "-": { precedence: 500 }, "+": { precedence: 500 }, "~": { precedence: 500 } }), i(this, "binaryOperators", { or: { precedence: 10, associativity: 1 }, "||": { precedence: 10, associativity: 1 }, xor: { precedence: 12, associativity: 1 }, and: { precedence: 15, associativity: 1 }, "&&": { precedence: 15, associativity: 1 }, "|": { precedence: 16, associativity: 1 }, "^": { precedence: 17, associativity: 1 }, "&": { precedence: 18, associativity: 1 }, "==": { precedence: 20, associativity: 1 }, "===": { precedence: 20, associativity: 1 }, "!=": { precedence: 20, associativity: 1 }, "!==": { precedence: 20, associativity: 1 }, "<": { precedence: 20, associativity: 1 }, ">": { precedence: 20, associativity: 1 }, ">=": { precedence: 20, associativity: 1 }, "<=": { precedence: 20, associativity: 1 }, "not in": { precedence: 20, associativity: 1 }, in: { precedence: 20, associativity: 1 }, matches: { precedence: 20, associativity: 1 }, contains: { precedence: 20, associativity: 1 }, "starts with": { precedence: 20, associativity: 1 }, "ends with": { precedence: 20, associativity: 1 }, "..": { precedence: 25, associativity: 1 }, "<<": { precedence: 25, associativity: 1 }, ">>": { precedence: 25, associativity: 1 }, "+": { precedence: 30, associativity: 1 }, "-": { precedence: 30, associativity: 1 }, "~": { precedence: 40, associativity: 1 }, "*": { precedence: 60, associativity: 1 }, "/": { precedence: 60, associativity: 1 }, "%": { precedence: 60, associativity: 1 }, "**": { precedence: 200, associativity: 2 } }), i(this, "parse", (e, r = [], s = 0) => {
            this.tokenStream = e, this.names = r, this.objectMatches = {}, this.cachedNames = null, this.nestedExecutions = 0, this.flags = s;
            let u = this.parseExpression();
            if (!this.tokenStream.isEOF()) throw new p(`Unexpected token "${this.tokenStream.current.type}" of value "${this.tokenStream.current.value}"`, this.tokenStream.current.cursor, this.tokenStream.expression);
            return u;
          }), i(this, "lint", (e, r = [], s = 0) => {
            r === null && (console.log('Deprecated: passing "null" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument'), s |= 1, r = []), this.parse(e, r, s);
          }), i(this, "parseExpression", (e = 0) => {
            let r = this.getPrimary(), s = this.tokenStream.current;
            if (this.nestedExecutions++, this.nestedExecutions > 1e3) throw new Error("Way to many executions on '" + s.toString() + "' of '" + this.tokenStream.toString() + "'");
            for (; s.test(f.OPERATOR_TYPE) && this.binaryOperators[s.value] !== void 0 && this.binaryOperators[s.value] !== null && this.binaryOperators[s.value].precedence >= e; ) {
              let u = this.binaryOperators[s.value];
              this.tokenStream.next();
              let c = this.parseExpression(u.associativity === 1 ? u.precedence + 1 : u.precedence);
              r = new F(s.value, r, c), s = this.tokenStream.current;
            }
            return e === 0 ? this.parseConditionalExpression(r) : r;
          }), i(this, "getPrimary", () => {
            let e = this.tokenStream.current;
            if (e.test(f.OPERATOR_TYPE) && this.unaryOperators[e.value] !== void 0 && this.unaryOperators[e.value] !== null) {
              let r = this.unaryOperators[e.value];
              this.tokenStream.next();
              let s = this.parseExpression(r.precedence);
              return this.parsePostfixExpression(new ne(e.value, s));
            }
            if (e.test(f.PUNCTUATION_TYPE, "(")) {
              this.tokenStream.next();
              let r = this.parseExpression();
              return this.tokenStream.expect(f.PUNCTUATION_TYPE, ")", "An opened parenthesis is not properly closed"), this.parsePostfixExpression(r);
            }
            return this.parsePrimaryExpression();
          }), i(this, "hasVariable", (e) => this.getNames().indexOf(e) >= 0), i(this, "getNames", () => {
            if (this.cachedNames !== null) return this.cachedNames;
            if (this.names && this.names.length > 0) {
              let e = [], r = 0;
              this.objectMatches = {};
              for (let s of this.names) typeof s == "object" ? (this.objectMatches[Object.values(s)[0]] = r, e.push(Object.keys(s)[0]), e.push(Object.values(s)[0])) : e.push(s), r++;
              return this.cachedNames = e, e;
            }
            return [];
          }), i(this, "parseArrayExpression", () => {
            this.tokenStream.expect(f.PUNCTUATION_TYPE, "[", "An array element was expected");
            let e = new ye(), r = !0;
            for (; !this.tokenStream.current.test(f.PUNCTUATION_TYPE, "]") && (r || (this.tokenStream.expect(f.PUNCTUATION_TYPE, ",", "An array element must be followed by a comma"), !this.tokenStream.current.test(f.PUNCTUATION_TYPE, "]"))); ) r = !1, e.addElement(this.parseExpression());
            return this.tokenStream.expect(f.PUNCTUATION_TYPE, "]", "An opened array is not properly closed"), e;
          }), i(this, "parseHashExpression", () => {
            this.tokenStream.expect(f.PUNCTUATION_TYPE, "{", "A hash element was expected");
            let e = new ye(), r = !0;
            for (; !this.tokenStream.current.test(f.PUNCTUATION_TYPE, "}") && (r || (this.tokenStream.expect(f.PUNCTUATION_TYPE, ",", "A hash value must be followed by a comma"), !this.tokenStream.current.test(f.PUNCTUATION_TYPE, "}"))); ) {
              r = !1;
              let s = null;
              if (this.tokenStream.current.test(f.STRING_TYPE) || this.tokenStream.current.test(f.NAME_TYPE) || this.tokenStream.current.test(f.NUMBER_TYPE)) s = new P(this.tokenStream.current.value), this.tokenStream.next();
              else {
                if (!this.tokenStream.current.test(f.PUNCTUATION_TYPE, "(")) {
                  let c = this.tokenStream.current;
                  throw new p(`A hash key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "${c.type}" of value "${c.value}"`, c.cursor, this.tokenStream.expression);
                }
                s = this.parseExpression();
              }
              this.tokenStream.expect(f.PUNCTUATION_TYPE, ":", "A hash key must be followed by a colon (:)");
              let u = this.parseExpression();
              e.addElement(u, s);
            }
            return this.tokenStream.expect(f.PUNCTUATION_TYPE, "}", "An opened hash is not properly closed"), e;
          }), i(this, "parsePostfixExpression", (e) => {
            let r = this.tokenStream.current;
            for (; f.PUNCTUATION_TYPE === r.type; ) {
              if (r.value === "." || r.value === "?.") {
                const s = r.value === "?.";
                if (this.tokenStream.next(), r = this.tokenStream.current, this.tokenStream.next(), f.NAME_TYPE !== r.type && (f.OPERATOR_TYPE !== r.type || !/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/.test(r.value))) throw new p("Expected name", r.cursor, this.tokenStream.expression);
                let u = new P(r.value, !0, s), c = new Oe(), l = null;
                if (this.tokenStream.current.test(f.PUNCTUATION_TYPE, "(")) {
                  l = A.METHOD_CALL;
                  for (let d of Object.values(this.parseArguments().nodes)) c.addElement(d);
                } else l = A.PROPERTY_CALL;
                e = new A(e, u, c, l);
              } else {
                if (r.value !== "[") break;
                {
                  this.tokenStream.next();
                  let s = this.parseExpression();
                  this.tokenStream.expect(f.PUNCTUATION_TYPE, "]"), e = new A(e, s, new Oe(), A.ARRAY_CALL);
                }
              }
              r = this.tokenStream.current;
            }
            return e;
          }), i(this, "parseArguments", () => {
            let e = [];
            for (this.tokenStream.expect(f.PUNCTUATION_TYPE, "(", "A list of arguments must begin with an opening parenthesis"); !this.tokenStream.current.test(f.PUNCTUATION_TYPE, ")"); ) e.length !== 0 && this.tokenStream.expect(f.PUNCTUATION_TYPE, ",", "Arguments must be separated by a comma"), e.push(this.parseExpression());
            return this.tokenStream.expect(f.PUNCTUATION_TYPE, ")", "A list of arguments must be closed by a parenthesis"), new I(e);
          }), this.functions = t, this.tokenStream = null, this.names = null, this.objectMatches = {}, this.cachedNames = null, this.nestedExecutions = 0, this.flags = 0;
        }
        parseConditionalExpression(t) {
          for (; this.tokenStream.current.test(f.PUNCTUATION_TYPE, "??"); ) {
            this.tokenStream.next();
            let e = this.parseExpression();
            t = new We(t, e);
          }
          for (; this.tokenStream.current.test(f.PUNCTUATION_TYPE, "?"); ) {
            let e, r;
            this.tokenStream.next(), this.tokenStream.current.test(f.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), e = t, r = this.parseExpression()) : (e = this.parseExpression(), this.tokenStream.current.test(f.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), r = this.parseExpression()) : e instanceof P && typeof e.attributes?.value == "string" ? r = new P("") : e instanceof Ae ? (r = e.nodes.expr3, e = e.nodes.expr2) : (r = e, e = t)), t = new Ae(t, e, r);
          }
          return t;
        }
        parsePrimaryExpression() {
          let t = this.tokenStream.current, e = null;
          switch (t.type) {
            case f.NAME_TYPE:
              switch (this.tokenStream.next(), t.value) {
                case "true":
                case "TRUE":
                  return new P(!0);
                case "false":
                case "FALSE":
                  return new P(!1);
                case "null":
                case "NULL":
                  return new P(null);
                default:
                  if (this.tokenStream.current.value === "(") {
                    if (this.functions[t.value] === void 0 && !(2 & this.flags)) throw new p(`The function "${t.value}" does not exist`, t.cursor, this.tokenStream.expression, t.values, Object.keys(this.functions));
                    e = new Ve(t.value, this.parseArguments());
                  } else {
                    let r = null;
                    if (1 & this.flags) r = t.value;
                    else {
                      if (!this.hasVariable(t.value)) {
                        if (this.tokenStream.current.test(f.PUNCTUATION_TYPE, "??")) return new Be(t.value);
                        throw new p(`Variable "${t.value}" is not valid`, t.cursor, this.tokenStream.expression, t.value, this.getNames());
                      }
                      r = t.value, this.objectMatches[r] !== void 0 && (r = this.getNames()[this.objectMatches[r]]);
                    }
                    e = new He(r);
                  }
              }
              break;
            case f.NUMBER_TYPE:
            case f.STRING_TYPE:
              return this.tokenStream.next(), new P(t.value);
            default:
              if (t.test(f.PUNCTUATION_TYPE, "[")) e = this.parseArrayExpression();
              else {
                if (!t.test(f.PUNCTUATION_TYPE, "{")) throw new p(`Unexpected token "${t.type}" of value "${t.value}"`, t.cursor, this.tokenStream.expression);
                e = this.parseHashExpression();
              }
          }
          return this.parsePostfixExpression(e);
        }
      }
      class Ge {
        constructor(t) {
          i(this, "getFunction", (e) => this.functions[e]), i(this, "getSource", () => this.source), i(this, "reset", () => (this.source = "", this)), i(this, "compile", (e) => (e.compile(this), this)), i(this, "subcompile", (e) => {
            let r = this.source;
            this.source = "", e.compile(this);
            let s = this.source;
            return this.source = r, s;
          }), i(this, "raw", (e) => (this.source += e, this)), i(this, "string", (e) => (this.source += '"' + ze(e, '\0	"$\\') + '"', this)), i(this, "repr", (e, r = !1) => {
            if (r) this.raw(e);
            else if (Number.isInteger(e) || +e === e && (!isFinite(e) || e % 1)) this.raw(e);
            else if (e === null) this.raw("null");
            else if (typeof e == "boolean") this.raw(e ? "true" : "false");
            else if (typeof e == "object") {
              this.raw("{");
              let s = !0;
              for (let u of Object.keys(e)) s || this.raw(", "), s = !1, this.repr(u), this.raw(":"), this.repr(e[u]);
              this.raw("}");
            } else if (Array.isArray(e)) {
              this.raw("[");
              let s = !0;
              for (let u of e) s || this.raw(", "), s = !1, this.repr(u);
              this.raw("]");
            } else this.string(e);
            return this;
          }), this.source = "", this.functions = t;
        }
      }
      class $t {
        constructor(t) {
          this.expression = t;
        }
        toString() {
          return this.expression;
        }
      }
      class ie extends $t {
        constructor(t, e) {
          super(t), i(this, "getNodes", () => this.nodes), this.nodes = e;
        }
        static fromJSON(t) {
          const e = typeof t == "string" ? JSON.parse(t) : t, r = (c) => {
            if (c == null || c instanceof I || typeof c != "object" || !c.name) return c;
            switch (c.name) {
              case "ConstantNode":
                return new P(c.attributes?.value, !!c.isIdentifier, !!c.isNullSafe);
              case "NameNode":
                return new He(c.attributes?.name);
              case "NullCoalescedNameNode":
                return new Be(c.attributes?.name);
              case "UnaryNode":
                return new ne(c.attributes?.operator, r(c.nodes?.node));
              case "BinaryNode":
                return new F(c.attributes?.operator, r(c.nodes?.left), r(c.nodes?.right));
              case "ConditionalNode":
                return new Ae(r(c.nodes?.expr1), r(c.nodes?.expr2), r(c.nodes?.expr3));
              case "NullCoalesceNode":
                return new We(r(c.nodes?.expr1), r(c.nodes?.expr2));
              case "ArgumentsNode": {
                const l = new Oe();
                typeof c.type == "string" && (l.type = c.type), typeof c.index == "number" && (l.index = c.index), typeof c.keyIndex == "number" && (l.keyIndex = c.keyIndex), l.nodes = {};
                for (const d of Object.keys(c.nodes || {})) l.nodes[d] = r(c.nodes[d]);
                return l;
              }
              case "ArrayNode": {
                const l = new ye();
                typeof c.type == "string" && (l.type = c.type), typeof c.index == "number" && (l.index = c.index), typeof c.keyIndex == "number" && (l.keyIndex = c.keyIndex), l.nodes = {};
                for (const d of Object.keys(c.nodes || {})) l.nodes[d] = r(c.nodes[d]);
                return l;
              }
              case "FunctionNode": {
                const l = r(c.nodes?.arguments);
                return new Ve(c.attributes?.name, l);
              }
              case "GetAttrNode": {
                const l = new A(r(c.nodes?.node), r(c.nodes?.attribute), r(c.nodes?.fnArguments), c.attributes?.type);
                return c.attributes && typeof c.attributes.is_null_coalesce == "boolean" && (l.attributes.is_null_coalesce = c.attributes.is_null_coalesce), c.attributes && typeof c.attributes.is_short_circuited == "boolean" && (l.attributes.is_short_circuited = c.attributes.is_short_circuited), l;
              }
              case "Node": {
                const l = new I();
                if (Array.isArray(c.nodes)) l.nodes = c.nodes.map(r);
                else {
                  l.nodes = {};
                  for (const d of Object.keys(c.nodes || {})) l.nodes[d] = r(c.nodes[d]);
                }
                return l.attributes = c.attributes || {}, l;
              }
              default: {
                const l = new I();
                if (l.name = c.name, Array.isArray(c.nodes)) l.nodes = c.nodes.map(r);
                else {
                  l.nodes = {};
                  for (const d of Object.keys(c.nodes || {})) l.nodes[d] = r(c.nodes[d]);
                }
                return l.attributes = c.attributes || {}, l;
              }
            }
          }, s = e.expression, u = ((c) => {
            if (c == null) return c;
            if (c.name) return r(c);
            if (Array.isArray(c)) return c.map(r);
            if (typeof c == "object") {
              const l = {};
              for (const d of Object.keys(c)) l[d] = r(c[d]);
              return l;
            }
            return c;
          })(e.nodes);
          return new ie(s, u);
        }
      }
      var Je;
      class Ke {
        constructor(t = 0) {
          i(this, "createCacheItem", (e, r, s) => {
            let u = new M();
            return u.key = e, u.value = r, u.isHit = s, u.defaultLifetime = this.defaultLifetime, u;
          }), i(this, "get", (e, r, s = null, u = null) => {
            let c = this.getItem(e);
            return c.isHit || this.save(c.set(r(c, !0))), c.get();
          }), i(this, "getItem", (e) => {
            let r = this.hasItem(e), s = null;
            return r ? s = this.values[e] : this.values[e] = null, (0, this.createCacheItem)(e, s, r);
          }), i(this, "getItems", (e) => {
            for (let r of e) typeof r == "string" || this.expiries[r] || M.validateKey(r);
            return this.generateItems(e, (/* @__PURE__ */ new Date()).getTime() / 1e3, this.createCacheItem);
          }), i(this, "deleteItems", (e) => {
            for (let r of e) this.deleteItem(r);
            return !0;
          }), i(this, "save", (e) => !(!e instanceof M) && (e.expiry !== null && e.expiry <= (/* @__PURE__ */ new Date()).getTime() / 1e3 ? (this.deleteItem(e.key), !0) : (e.expiry === null && 0 < e.defaultLifetime && (e.expiry = (/* @__PURE__ */ new Date()).getTime() / 1e3 + e.defaultLifetime), this.values[e.key] = e.value, this.expiries[e.key] = e.expiry || Number.MAX_SAFE_INTEGER, !0))), i(this, "saveDeferred", (e) => this.save(e)), i(this, "commit", () => !0), i(this, "delete", (e) => this.deleteItem(e)), i(this, "getValues", () => this.values), i(this, "hasItem", (e) => !!(typeof e == "string" && this.expiries[e] && this.expiries[e] > (/* @__PURE__ */ new Date()).getTime() / 1e3) || (M.validateKey(e), !!this.expiries[e] && !this.deleteItem(e))), i(this, "clear", () => (this.values = {}, this.expiries = {}, !0)), i(this, "deleteItem", (e) => (typeof e == "string" && this.expiries[e] || M.validateKey(e), delete this.values[e], delete this.expiries[e], !0)), i(this, "reset", () => {
            this.clear();
          }), i(this, "generateItems", (e, r, s) => {
            let u = [];
            for (let c of e) {
              let l = null, d = !!this.expiries[c];
              d || !(this.expiries[c] > r) && this.deleteItem(c) ? l = this.values[c] : this.values[c] = null, u[c] = s(c, l, d);
            }
            return u;
          }), this.defaultLifetime = t, this.values = {}, this.expiries = {};
        }
      }
      class M {
        constructor() {
          i(this, "getKey", () => this.key), i(this, "get", () => this.value), i(this, "set", (t) => (this.value = t, this)), i(this, "expiresAt", (t) => {
            if (t === null) this.expiry = this.defaultLifetime > 0 ? Date.now() / 1e3 + this.defaultLifetime : null;
            else {
              if (!(t instanceof Date)) throw new Error(`Expiration date must be instance of Date or be null, "${t.name}" given`);
              this.expiry = t.getTime() / 1e3;
            }
            return this;
          }), i(this, "expiresAfter", (t) => {
            if (t === null) this.expiry = this.defaultLifetime > 0 ? Date.now() / 1e3 + this.defaultLifetime : null;
            else {
              if (!Number.isInteger(t)) throw new Error(`Expiration date must be an integer or be null, "${t.name}" given`);
              this.expiry = (/* @__PURE__ */ new Date()).getTime() / 1e3 + t;
            }
            return this;
          }), i(this, "tag", (t) => {
            if (!this.isTaggable) throw new Error(`Cache item "${this.key}" comes from a non tag-aware pool: you cannot tag it.`);
            Array.isArray(t) || (t = [t]);
            for (let e of t) {
              if (typeof e != "string") throw new Error(`Cache tag must by a string, "${typeof e}" given.`);
              if (this.newMetadata.tags[e] && e === "") throw new Error("Cache tag length must be greater than zero");
              this.newMetadata.tags[e] = e;
            }
            return this;
          }), i(this, "getMetadata", () => this.metadata), this.key = null, this.value = null, this.isHit = !1, this.expiry = null, this.defaultLifetime = null, this.metadata = {}, this.newMetadata = {}, this.innerItem = null, this.poolHash = null, this.isTaggable = !1;
        }
      }
      Je = M, i(M, "METADATA_EXPIRY_OFFSET", 1527506807), i(M, "RESERVED_CHARACTERS", ["{", "}", "(", ")", "/", "\\", "@", ":"]), i(M, "validateKey", (o) => {
        if (typeof o != "string") throw new Error(`Cache key must be string, "${typeof o}" given.`);
        if (o === "") throw new Error("Cache key length must be greater than zero");
        for (let t of Je.RESERVED_CHARACTERS) if (o.indexOf(t) >= 0) throw new Error(`Cache key "${o}" contains reserved character "${t}".`);
        return o;
      });
      class Lt extends Error {
        constructor(t) {
          super(t), this.name = "LogicException";
        }
        toString() {
          return `${this.name}: ${this.message}`;
        }
      }
      class O {
        constructor(t, e, r) {
          i(this, "getName", () => this.name), i(this, "getCompiler", () => this.compiler), i(this, "getEvaluator", () => this.evaluator), this.name = t, this.compiler = e, this.evaluator = r;
        }
        static fromJavascript(t, e = null) {
          if (typeof t != "string" || t.length === 0) throw new TypeError("A JavaScript function name (string) must be provided.");
          const r = t.replace(/^\/+/, ""), s = r.split(".");
          let u = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof H < "u" ? H : {};
          for (const c of s) {
            if (u == null) break;
            u = u[c];
          }
          if (typeof u != "function") throw new Error(`JavaScript function "${r}" does not exist.`);
          if (!e && s.length > 1) throw new Error(`An expression function name must be defined when JavaScript function "${r}" is namespaced.`);
          return new this(e || s[s.length - 1], (...c) => `${r}(${c.join(", ")})`, (c, ...l) => u(...l));
        }
      }
      class Ze {
        constructor(t = null, e = []) {
          i(this, "compile", (r, s = []) => this.getCompiler().compile(this.parse(r, s).getNodes()).getSource()), i(this, "evaluate", (r, s = {}) => this.parse(r, Object.keys(s)).getNodes().evaluate(this.functions, s)), i(this, "parse", (r, s, u = 0) => {
            if (r instanceof ie) return r;
            s.sort((g, y) => {
              let x = g, k = y;
              return typeof g == "object" && (x = Object.values(g)[0]), typeof y == "object" && (k = Object.values(y)[0]), x.localeCompare(k);
            });
            let c = [];
            for (let g of s) {
              let y = g;
              typeof g == "object" && (y = Object.keys(g)[0] + ":" + Object.values(g)[0]), c.push(y);
            }
            let l = this.cache.getItem(this.fixedEncodeURIComponent(r + "//" + c.join("|"))), d = l.get();
            if (d === null) {
              let g = this.getParser().parse(this.getLexer().tokenize(r), s, u);
              d = new ie(r, g), l.set(d), this.cache.save(l);
            }
            return d;
          }), i(this, "lint", (r, s = null, u = 0) => {
            s === null && (console.log('Deprecated: passing "null" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument'), u |= 1, s = []), r instanceof ie || this.getParser().lint(this.getLexer().tokenize(r), s, u);
          }), i(this, "fixedEncodeURIComponent", (r) => encodeURIComponent(r).replace(/[!'()*]/g, function(s) {
            return "%" + s.charCodeAt(0).toString(16);
          })), i(this, "register", (r, s, u) => {
            if (this.parser !== null) throw new Lt("Registering functions after calling evaluate(), compile(), or parse() is not supported.");
            this.functions[r] = { compiler: s, evaluator: u };
          }), i(this, "addFunction", (r) => {
            this.register(r.getName(), r.getCompiler(), r.getEvaluator());
          }), i(this, "registerProvider", (r) => {
            for (let s of r.getFunctions()) this.addFunction(s);
          }), i(this, "getLexer", () => (this.lexer === null && (this.lexer = { tokenize: T }), this.lexer)), i(this, "getParser", () => (this.parser === null && (this.parser = new qe(this.functions)), this.parser)), i(this, "getCompiler", () => (this.compiler === null && (this.compiler = new Ge(this.functions)), this.compiler.reset())), this.functions = [], this.lexer = null, this.parser = null, this.compiler = null, this.cache = t || new Ke(), this._registerBuiltinFunctions();
          for (let r of e) this.registerProvider(r);
        }
        _registerBuiltinFunctions() {
          const t = O.fromJavascript("Math.min", "min"), e = O.fromJavascript("Math.max", "max");
          this.addFunction(t), this.addFunction(e), this.addFunction(new O("constant", function(r) {
            return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));return __n.split('.').reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${r})`;
          }, function(r, s) {
            if (typeof s != "string" || !s) return;
            let u = (c = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof H < "u" ? H : {}, s.split(".").reduce((l, d) => l?.[d], c));
            var c;
            return u === void 0 && r && Object.prototype.hasOwnProperty.call(r, s) && (u = r[s]), u;
          })), this.addFunction(new O("enum", function(r) {
            return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));if(typeof __n!=='string'||!__n)return undefined;var s=String(__n);var keys=[],buf='';for(var i=0;i<s.length;i++){var c=s.charCodeAt(i);if(c===46||c===92){if(buf){keys.push(buf);buf='';}continue;}if(c===58){if(i+1<s.length&&s.charCodeAt(i+1)===58){if(buf){keys.push(buf);buf='';}i++;continue;}}buf+=s[i];}if(buf)keys.push(buf);return keys.reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${r})`;
          }, function(r, s) {
            if (typeof s != "string" || !s) return;
            const u = String(s).replace(/\\/g, ".").replace(/::/g, ".");
            var c;
            return u ? (c = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof H < "u" ? H : {}, u.split(".").reduce((l, d) => l?.[d], c)) : void 0;
          }));
        }
      }
      class ae {
        getFunctions() {
          throw new Error("getFunctions must be implemented by " + this.name);
        }
      }
      const Dt = new O("isset", function(o) {
        return `isset(${o})`;
      }, function(o, t) {
        if (typeof t != "string") return t != null;
        if (!(t.split(/[.\[]/)[0] in o)) return !0;
        let e = "", r = [], s = "", u = "";
        for (let c = 0; c < t.length; c++) {
          let l = t[c];
          if (l !== "]") if (l !== "[") {
            if (s === "object" && (!/[A-z0-9_]/.test(l) || c === t.length - 1)) {
              let d = !1;
              if (c === t.length - 1 && (u += l, d = !0), s = "", r.push({ type: "object", attribute: u }), u = "", d) continue;
            }
            l !== "." ? s ? u += l : e += l : (s = "object", u = "");
          } else s = "array", u = "";
          else s = "", r.push({ type: "array", index: u.replace(/"/g, "").replace(/'/g, "") }), u = "";
        }
        if (r.length > 0) {
          if (o[e] !== void 0) {
            let c = o[e];
            for (let l of r) {
              if (l.type === "array") {
                if (c[l.index] === void 0) return !1;
                c = c[l.index];
              }
              if (l.type === "object") {
                if (c[l.attribute] === void 0) return !1;
                c = c[l.attribute];
              }
            }
            return !0;
          }
          return !1;
        }
        return o[e] !== void 0;
      }), Xe = (o) => Object.entries(o);
      function Qe(o) {
        return typeof o == "object" && o !== null;
      }
      function Pe(o) {
        return Qe(o) && !(function(t) {
          return Array.isArray(t);
        })(o);
      }
      function et(o) {
        return (function(t) {
          return Qe(t);
        })(o) ? o : {};
      }
      const tt = typeof window == "object" && window !== null ? window : typeof H == "object" && H !== null ? H : {};
      function Mt() {
        const o = (() => {
          let y = tt.$locutus;
          typeof y == "object" && y !== null || (y = {}, tt.$locutus = y);
          let x = y.php;
          return typeof x == "object" && x !== null || (x = {}, y.php = x), x;
        })(), t = o.ini, e = o.locales, r = o.localeCategories, s = o.pointers, u = Pe(t) ? t : {}, c = ((y) => Pe(y))(e) ? e : {}, l = ((y) => Pe(y))(r) ? r : {}, d = Array.isArray(s) ? s : [];
        t !== u && (o.ini = u), e !== c && (o.locales = c), r !== l && (o.localeCategories = l), s !== d && (o.pointers = d);
        const g = o.locale_default;
        return { ini: u, locales: c, localeCategories: l, pointers: d, locale_default: typeof g == "string" ? g : void 0 };
      }
      function rt(o) {
        const t = Mt().ini[o];
        return t && t.local_value !== void 0 ? t.local_value === null ? "" : String(t.local_value) : "";
      }
      function Yt(o, t, e) {
        const r = (function(l) {
          if (typeof l == "boolean") return l ? "1" : "";
          if (typeof l == "string") return l;
          if (typeof l == "number") return isNaN(l) ? "NAN" : isFinite(l) ? l + "" : (l < 0 ? "-" : "") + "INF";
          if (l === void 0) return "";
          if (typeof l == "object") return Array.isArray(l) ? "Array" : l !== null ? "Object" : "";
          throw new Error("Unsupported value type");
        })(o), s = rt("unicode.semantics") === "on" ? r.match(/[\uD800-\uDBFF][\uDC00-\uDFFF]|[\s\S]/g) || [] : null, u = s ? s.length : r.length;
        let c = u;
        return t < 0 && (t += c), e !== void 0 && (c = e < 0 ? e + c : e + t), !(t > u || t < 0 || t > c) && (s ? s.slice(t, c).join("") : r.slice(t, c));
      }
      function zt(o, ...t) {
        const e = {};
        if (t.length < 1) return e;
        const r = et(o);
        e: for (const [s, u] of Xe(r)) {
          for (const c of t) {
            const l = et(c);
            let d = !1;
            for (const [, g] of Xe(l)) if (g === u) {
              d = !0;
              break;
            }
            if (!d) continue e;
          }
          e[s] = u;
        }
        return e;
      }
      const st = (o) => {
        if (!o || typeof o != "object") return !1;
        const t = Object.getPrototypeOf(o);
        return t === Array.prototype || t === Object.prototype;
      };
      function Ce(o, t = 0) {
        let e = 0;
        if (o == null) return 0;
        if (typeof o != "object") return 1;
        const r = Object.getPrototypeOf(o);
        if (r !== Array.prototype && r !== Object.prototype) return 1;
        const s = t === "COUNT_RECURSIVE" || t === 1;
        if (Array.isArray(o)) {
          for (const u of Object.keys(o)) {
            e++;
            const c = o[Number(u)];
            s && st(c) && (e += Ce(c, 1));
          }
          return e;
        }
        for (const u in o) if (Object.prototype.hasOwnProperty.call(o, u)) {
          e++;
          const c = o[u];
          s && st(c) && (e += Ce(c, 1));
        }
        return e;
      }
      const Vt = new O("implode", function(o, t) {
        return `implode(${o}, ${t})`;
      }, function(o, t, e) {
        return (function(...r) {
          let s, u = "", c = "", l = "";
          if (r.length === 1) {
            const [d] = r;
            s = d;
          } else {
            const [d, g] = r;
            l = String(d ?? ""), s = g;
          }
          if (typeof s == "object" && s !== null) {
            if (Array.isArray(s)) return s.join(l);
            for (const d in s) u += c + s[d], c = l;
            return u;
          }
          return String(s);
        })(t, e);
      }), Ht = new O("count", function(o, t) {
        let e = "";
        return t && (e = `, ${t}`), `count(${o}${e})`;
      }, function(o, t, e) {
        return Ce(t, e);
      }), Wt = new O("array_intersect", function(o, ...t) {
        let e = "";
        return t.length > 0 && (e = ", " + t.join(", ")), `array_intersect(${o}${e})`;
      }, function(o) {
        let t = [], e = !0;
        for (let s = 1; s < arguments.length; s++) t.push(arguments[s]), Array.isArray(arguments[s]) || (e = !1);
        let r = zt.apply(null, t);
        return e ? Object.values(r) : r;
      });
      function Bt(o, t) {
        let e, r = /* @__PURE__ */ new Date();
        const s = ["Sun", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"], u = /\\?(.?)/gi, c = function(y, x) {
          return k = y, Object.prototype.hasOwnProperty.call(e, k) ? String(e[y]()) : x;
          var k;
        }, l = function(y, x) {
          let k = String(y);
          for (; k.length < x; ) k = "0" + k;
          return k;
        };
        return e = { d: function() {
          return l(e.j(), 2);
        }, D: function() {
          return String(e.l()).slice(0, 3);
        }, j: function() {
          return r.getDate();
        }, l: function() {
          return (s[Number(e.w())] ?? "") + "day";
        }, N: function() {
          return Number(e.w()) || 7;
        }, S: function() {
          const y = Number(e.j());
          let x = y % 10;
          return x <= 3 && Number.parseInt(String(y % 100 / 10), 10) === 1 && (x = 0), ["st", "nd", "rd"][x - 1] || "th";
        }, w: function() {
          return r.getDay();
        }, z: function() {
          const y = new Date(Number(e.Y()), Number(e.n()) - 1, Number(e.j())), x = new Date(Number(e.Y()), 0, 1);
          return Math.round((y.getTime() - x.getTime()) / 864e5);
        }, W: function() {
          const y = new Date(Number(e.Y()), Number(e.n()) - 1, Number(e.j()) - Number(e.N()) + 3), x = new Date(y.getFullYear(), 0, 4);
          return l(1 + Math.round((y.getTime() - x.getTime()) / 864e5 / 7), 2);
        }, F: function() {
          return s[6 + Number(e.n())] ?? "";
        }, m: function() {
          return l(e.n(), 2);
        }, M: function() {
          return String(e.F()).slice(0, 3);
        }, n: function() {
          return r.getMonth() + 1;
        }, t: function() {
          return new Date(Number(e.Y()), Number(e.n()), 0).getDate();
        }, L: function() {
          const y = Number(e.Y());
          return y % 4 == 0 && y % 100 != 0 || y % 400 == 0 ? 1 : 0;
        }, o: function() {
          const y = Number(e.n()), x = Number(e.W());
          return Number(e.Y()) + (y === 12 && x < 9 ? 1 : y === 1 && x > 9 ? -1 : 0);
        }, Y: function() {
          return r.getFullYear();
        }, y: function() {
          return String(e.Y()).slice(-2);
        }, a: function() {
          return r.getHours() > 11 ? "pm" : "am";
        }, A: function() {
          return String(e.a()).toUpperCase();
        }, B: function() {
          const y = 3600 * r.getUTCHours(), x = 60 * r.getUTCMinutes(), k = r.getUTCSeconds();
          return l(Math.floor((y + x + k + 3600) / 86.4) % 1e3, 3);
        }, g: function() {
          return Number(e.G()) % 12 || 12;
        }, G: function() {
          return r.getHours();
        }, h: function() {
          return l(e.g(), 2);
        }, H: function() {
          return l(e.G(), 2);
        }, i: function() {
          return l(r.getMinutes(), 2);
        }, s: function() {
          return l(r.getSeconds(), 2);
        }, u: function() {
          return l(1e3 * r.getMilliseconds(), 6);
        }, e: function() {
          throw new Error("Not supported (see source code of date() for timezone on how to add support)");
        }, I: function() {
          const y = new Date(Number(e.Y()), 0), x = Date.UTC(Number(e.Y()), 0), k = new Date(Number(e.Y()), 6), B = Date.UTC(Number(e.Y()), 6);
          return y.getTime() - x !== k.getTime() - B ? 1 : 0;
        }, O: function() {
          const y = r.getTimezoneOffset(), x = Math.abs(y);
          return (y > 0 ? "-" : "+") + l(100 * Math.floor(x / 60) + x % 60, 4);
        }, P: function() {
          const y = String(e.O());
          return y.slice(0, 3) + ":" + y.slice(3, 5);
        }, T: function() {
          return "UTC";
        }, Z: function() {
          return 60 * -r.getTimezoneOffset();
        }, c: function() {
          return "Y-m-d\\TH:i:sP".replace(u, c);
        }, r: function() {
          return "D, d M Y H:i:s O".replace(u, c);
        }, U: function() {
          return r.getTime() / 1e3 | 0;
        } }, d = o, r = (g = t) === void 0 ? /* @__PURE__ */ new Date() : g instanceof Date ? new Date(g) : new Date(1e3 * Number(g)), d.replace(u, c);
        var d, g;
      }
      const Re = "[ \\t]+", J = "[ \\t]*", K = "(?:([ap])\\.?m\\.?([\\t ]|$))", $ = "(2[0-4]|[01]?[0-9])", oe = "([01][0-9]|2[0-4])", ee = "(0?[1-9]|1[0-2])", Y = "([0-5]?[0-9])", L = "([0-5][0-9])", be = "(60|[0-5]?[0-9])", z = "(60|[0-5][0-9])", nt = "(?:\\.([0-9]+))", it = "sunday|monday|tuesday|wednesday|thursday|friday|saturday|sun|mon|tue|wed|thu|fri|sat|weekdays?", at = "next|last|previous|this", ot = "(?:second|sec|minute|min|hour|day|fortnight|forthnight|month|year)s?|weeks|" + it, ue = "([0-9]{1,4})", C = "([0-9]{4})", W = "(1[0-2]|0?[0-9])", Z = "(0[0-9]|1[0-2])", U = "(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)", V = "(0[0-9]|[1-2][0-9]|3[01])", ut = "january|february|march|april|may|june|july|august|september|october|november|december", ce = "jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec", te = "(" + ut + "|" + ce + "|i[vx]|vi{0,3}|xi{0,2}|i{1,3})", Ie = "((?:GMT)?([+-])" + $ + ":?" + Y + "?)", le = te + "[ .\\t-]*" + U + "[,.stndrh\\t ]*";
      function X(o, t) {
        switch (t?.toLowerCase()) {
          case "a":
            o += o === 12 ? -12 : 0;
            break;
          case "p":
            o += o !== 12 ? 12 : 0;
        }
        return o;
      }
      function Q(o) {
        let t = +o;
        return o.length < 4 && t < 100 && (t += t < 70 ? 2e3 : 1900), t;
      }
      function j(o) {
        return { jan: 0, january: 0, i: 0, feb: 1, february: 1, ii: 1, mar: 2, march: 2, iii: 2, apr: 3, april: 3, iv: 3, may: 4, v: 4, jun: 5, june: 5, vi: 5, jul: 6, july: 6, vii: 6, aug: 7, august: 7, viii: 7, sep: 8, sept: 8, september: 8, ix: 8, oct: 9, october: 9, x: 9, nov: 10, november: 10, xi: 10, dec: 11, december: 11, xii: 11 }[o.toLowerCase()] ?? Number.NaN;
      }
      function je(o, t = 0) {
        return { mon: 1, monday: 1, tue: 2, tuesday: 2, wed: 3, wednesday: 3, thu: 4, thursday: 4, fri: 5, friday: 5, sat: 6, saturday: 6, sun: 0, sunday: 0 }[o.toLowerCase()] || t;
      }
      function _e(o, t = Number.NaN) {
        const e = o?.match(/(?:GMT)?([+-])(\d+)(:?)(\d{0,2})/i);
        if (!e) return t;
        const r = e[1] === "-" ? -1 : 1;
        let s = +(e[2] ?? 0), u = +(e[4] ?? 0);
        return e[4] || e[3] || (u = Math.floor(s % 100), s = Math.floor(s / 100)), r * (60 * s + u) * 60;
      }
      const qt = { acdt: 37800, acst: 34200, addt: -7200, adt: -10800, aedt: 39600, aest: 36e3, ahdt: -32400, ahst: -36e3, akdt: -28800, akst: -32400, amt: -13840, apt: -10800, ast: -14400, awdt: 32400, awst: 28800, awt: -10800, bdst: 7200, bdt: -36e3, bmt: -14309, bst: 3600, cast: 34200, cat: 7200, cddt: -14400, cdt: -18e3, cemt: 10800, cest: 7200, cet: 3600, cmt: -15408, cpt: -18e3, cst: -21600, cwt: -18e3, chst: 36e3, dmt: -1521, eat: 10800, eddt: -10800, edt: -14400, eest: 10800, eet: 7200, emt: -26248, ept: -14400, est: -18e3, ewt: -14400, ffmt: -14660, fmt: -4056, gdt: 39600, gmt: 0, gst: 36e3, hdt: -34200, hkst: 32400, hkt: 28800, hmt: -19776, hpt: -34200, hst: -36e3, hwt: -34200, iddt: 14400, idt: 10800, imt: 25025, ist: 7200, jdt: 36e3, jmt: 8440, jst: 32400, kdt: 36e3, kmt: 5736, kst: 30600, lst: 9394, mddt: -18e3, mdst: 16279, mdt: -21600, mest: 7200, met: 3600, mmt: 9017, mpt: -21600, msd: 14400, msk: 10800, mst: -25200, mwt: -21600, nddt: -5400, ndt: -9052, npt: -9e3, nst: -12600, nwt: -9e3, nzdt: 46800, nzmt: 41400, nzst: 43200, pddt: -21600, pdt: -25200, pkst: 21600, pkt: 18e3, plmt: 25590, pmt: -13236, ppmt: -17340, ppt: -25200, pst: -28800, pwt: -25200, qmt: -18840, rmt: 5794, sast: 7200, sdmt: -16800, sjmt: -20173, smt: -13884, sst: -39600, tbmt: 10751, tmt: 12344, uct: 0, utc: 0, wast: 7200, wat: 3600, wemt: 7200, west: 3600, wet: 0, wib: 25200, wita: 28800, wit: 32400, wmt: 5040, yddt: -25200, ydt: -28800, ypt: -28800, yst: -32400, ywt: -28800, a: 3600, b: 7200, c: 10800, d: 14400, e: 18e3, f: 21600, g: 25200, h: 28800, i: 32400, k: 36e3, l: 39600, m: 43200, n: -3600, o: -7200, p: -10800, q: -14400, r: -18e3, s: -21600, t: -25200, u: -28800, v: -32400, w: -36e3, x: -39600, y: -43200, z: 0 }, b = { yesterday: { regex: /^yesterday/i, name: "yesterday", callback() {
        return this.rd -= 1, this.resetTime();
      } }, now: { regex: /^now/i, name: "now" }, noon: { regex: /^noon/i, name: "noon", callback() {
        return this.resetTime() && this.time(12, 0, 0, 0);
      } }, midnightOrToday: { regex: /^(midnight|today)/i, name: "midnight | today", callback() {
        return this.resetTime();
      } }, tomorrow: { regex: /^tomorrow/i, name: "tomorrow", callback() {
        return this.rd += 1, this.resetTime();
      } }, timestamp: { regex: /^@(-?\d+)/i, name: "timestamp", callback(o, t) {
        return this.rs += +t, this.y = 1970, this.m = 0, this.d = 1, this.dates = 0, this.resetTime() && this.zone(0);
      } }, firstOrLastDay: { regex: /^(first|last) day of/i, name: "firstdayof | lastdayof", callback(o, t) {
        t.toLowerCase() === "first" ? this.firstOrLastDayOfMonth = 1 : this.firstOrLastDayOfMonth = -1;
      } }, backOrFrontOf: { regex: new RegExp("^(back|front) of " + $ + J + K + "?", "i"), name: "backof | frontof", callback(o, t, e, r) {
        let s = +e, u = 15;
        return t.toLowerCase() === "back" || (s -= 1, u = 45), s = X(s, r), this.resetTime() && this.time(s, u, 0, 0);
      } }, mssqltime: { regex: new RegExp("^" + ee + ":" + L + ":" + z + "[:.]([0-9]+)" + K, "i"), name: "mssqltime", callback(o, t, e, r, s, u) {
        return this.time(X(+t, u), +e, +r, +s.substr(0, 3));
      } }, oracledate: { regex: /^(\d{2})-([A-Z]{3})-(\d{2})$/i, name: "d-M-y", callback(o, t, e, r) {
        const s = { JAN: 0, FEB: 1, MAR: 2, APR: 3, MAY: 4, JUN: 5, JUL: 6, AUG: 7, SEP: 8, OCT: 9, NOV: 10, DEC: 11 }[e.toUpperCase()] ?? Number.NaN;
        return this.ymd(2e3 + parseInt(r, 10), s, parseInt(t, 10));
      } }, timeLong12: { regex: new RegExp("^" + ee + "[:.]" + Y + "[:.]" + z + J + K, "i"), name: "timelong12", callback(o, t, e, r, s) {
        return this.time(X(+t, s), +e, +r, 0);
      } }, timeShort12: { regex: new RegExp("^" + ee + "[:.]" + L + J + K, "i"), name: "timeshort12", callback(o, t, e, r) {
        return this.time(X(+t, r), +e, 0, 0);
      } }, timeTiny12: { regex: new RegExp("^" + ee + J + K, "i"), name: "timetiny12", callback(o, t, e) {
        return this.time(X(+t, e), 0, 0, 0);
      } }, soap: { regex: new RegExp("^" + C + "-" + Z + "-" + V + "T" + oe + ":" + L + ":" + z + nt + Ie + "?", "i"), name: "soap", callback(o, t, e, r, s, u, c, l, d) {
        return this.ymd(+t, +e - 1, +r) && this.time(+s, +u, +c, +l.substr(0, 3)) && this.zone(_e(d));
      } }, wddx: { regex: new RegExp("^" + C + "-" + W + "-" + U + "T" + $ + ":" + Y + ":" + be), name: "wddx", callback(o, t, e, r, s, u, c) {
        return this.ymd(+t, +e - 1, +r) && this.time(+s, +u, +c, 0);
      } }, exif: { regex: new RegExp("^" + C + ":" + Z + ":" + V + " " + oe + ":" + L + ":" + z, "i"), name: "exif", callback(o, t, e, r, s, u, c) {
        return this.ymd(+t, +e - 1, +r) && this.time(+s, +u, +c, 0);
      } }, xmlRpc: { regex: new RegExp("^" + C + Z + V + "T" + $ + ":" + L + ":" + z), name: "xmlrpc", callback(o, t, e, r, s, u, c) {
        return this.ymd(+t, +e - 1, +r) && this.time(+s, +u, +c, 0);
      } }, xmlRpcNoColon: { regex: new RegExp("^" + C + Z + V + "[Tt]" + $ + L + z), name: "xmlrpcnocolon", callback(o, t, e, r, s, u, c) {
        return this.ymd(+t, +e - 1, +r) && this.time(+s, +u, +c, 0);
      } }, clf: { regex: new RegExp("^" + U + "/(" + ce + ")/" + C + ":" + oe + ":" + L + ":" + z + Re + Ie, "i"), name: "clf", callback(o, t, e, r, s, u, c, l) {
        return this.ymd(+r, j(e), +t) && this.time(+s, +u, +c, 0) && this.zone(_e(l));
      } }, iso8601long: { regex: new RegExp("^t?" + $ + "[:.]" + Y + "[:.]" + be + nt, "i"), name: "iso8601long", callback(o, t, e, r, s) {
        return this.time(+t, +e, +r, +s.substr(0, 3));
      } }, dateTextual: { regex: new RegExp("^" + te + "[ .\\t-]*" + U + "[,.stndrh\\t ]+" + ue, "i"), name: "datetextual", callback(o, t, e, r) {
        return this.ymd(Q(r), j(t), +e);
      } }, pointedDate4: { regex: new RegExp("^" + U + "[.\\t-]" + W + "[.-]" + C), name: "pointeddate4", callback(o, t, e, r) {
        return this.ymd(+r, +e - 1, +t);
      } }, pointedDate2: { regex: new RegExp("^" + U + "[.\\t]" + W + "\\.([0-9]{2})"), name: "pointeddate2", callback(o, t, e, r) {
        return this.ymd(Q(r), +e - 1, +t);
      } }, timeLong24: { regex: new RegExp("^t?" + $ + "[:.]" + Y + "[:.]" + be), name: "timelong24", callback(o, t, e, r) {
        return this.time(+t, +e, +r, 0);
      } }, dateNoColon: { regex: new RegExp("^" + C + Z + V), name: "datenocolon", callback(o, t, e, r) {
        return this.ymd(+t, +e - 1, +r);
      } }, pgydotd: { regex: new RegExp("^" + C + "\\.?(00[1-9]|0[1-9][0-9]|[12][0-9][0-9]|3[0-5][0-9]|36[0-6])"), name: "pgydotd", callback(o, t, e) {
        return this.ymd(+t, 0, +e);
      } }, timeShort24: { regex: new RegExp("^t?" + $ + "[:.]" + Y, "i"), name: "timeshort24", callback(o, t, e) {
        return this.time(+t, +e, 0, 0);
      } }, iso8601noColon: { regex: new RegExp("^t?" + oe + L + z, "i"), name: "iso8601nocolon", callback(o, t, e, r) {
        return this.time(+t, +e, +r, 0);
      } }, iso8601dateSlash: { regex: new RegExp("^" + C + "/" + Z + "/" + V + "/"), name: "iso8601dateslash", callback(o, t, e, r) {
        return this.ymd(+t, +e - 1, +r);
      } }, dateSlash: { regex: new RegExp("^" + C + "/" + W + "/" + U), name: "dateslash", callback(o, t, e, r) {
        return this.ymd(+t, +e - 1, +r);
      } }, american: { regex: new RegExp("^" + W + "/" + U + "/" + ue), name: "american", callback(o, t, e, r) {
        return this.ymd(Q(r), +t - 1, +e);
      } }, americanShort: { regex: new RegExp("^" + W + "/" + U), name: "americanshort", callback(o, t, e) {
        return this.ymd(this.y, +t - 1, +e);
      } }, gnuDateShortOrIso8601date2: { regex: new RegExp("^" + ue + "-" + W + "-" + U), name: "gnudateshort | iso8601date2", callback(o, t, e, r) {
        return this.ymd(Q(t), +e - 1, +r);
      } }, iso8601date4: { regex: new RegExp("^([+-]?[0-9]{4})-" + Z + "-" + V), name: "iso8601date4", callback(o, t, e, r) {
        return this.ymd(+t, +e - 1, +r);
      } }, gnuNoColon: { regex: new RegExp("^t?" + oe + L, "i"), name: "gnunocolon", callback(o, t, e) {
        switch (this.times) {
          case 0:
            return this.time(+t, +e, 0, this.f);
          case 1:
            return this.y = 100 * +t + +e, this.times++, !0;
          default:
            return !1;
        }
      } }, gnuDateShorter: { regex: new RegExp("^" + C + "-" + W), name: "gnudateshorter", callback(o, t, e) {
        return this.ymd(+t, +e - 1, 1);
      } }, pgTextReverse: { regex: new RegExp("^(\\d{3,4}|[4-9]\\d|3[2-9])-(" + ce + ")-" + V, "i"), name: "pgtextreverse", callback(o, t, e, r) {
        return this.ymd(Q(t), j(e), +r);
      } }, dateFull: { regex: new RegExp("^" + U + "[ \\t.-]*" + te + "[ \\t.-]*" + ue, "i"), name: "datefull", callback(o, t, e, r) {
        return this.ymd(Q(r), j(e), +t);
      } }, dateNoDay: { regex: new RegExp("^" + te + "[ .\\t-]*" + C, "i"), name: "datenoday", callback(o, t, e) {
        return this.ymd(+e, j(t), 1);
      } }, dateNoDayRev: { regex: new RegExp("^" + C + "[ .\\t-]*" + te, "i"), name: "datenodayrev", callback(o, t, e) {
        return this.ymd(+t, j(e), 1);
      } }, pgTextShort: { regex: new RegExp("^(" + ce + ")-" + V + "-" + ue, "i"), name: "pgtextshort", callback(o, t, e, r) {
        return this.ymd(Q(r), j(t), +e);
      } }, dateNoYear: { regex: new RegExp("^" + le, "i"), name: "datenoyear", callback(o, t, e) {
        return this.ymd(this.y, j(t), +e);
      } }, dateNoYearRev: { regex: new RegExp("^" + U + "[ .\\t-]*" + te, "i"), name: "datenoyearrev", callback(o, t, e) {
        return this.ymd(this.y, j(e), +t);
      } }, isoWeekDay: { regex: new RegExp("^" + C + "-?W(0[1-9]|[1-4][0-9]|5[0-3])(?:-?([0-7]))?"), name: "isoweekday | isoweek", callback(o, t, e, r) {
        const s = r ? +r : 1;
        if (!this.ymd(+t, 0, 1)) return !1;
        let u = new Date(this.y, this.m, this.d).getDay();
        return u = 0 - (u > 4 ? u - 7 : u), this.rd += u + 7 * (+e - 1) + s, !0;
      } }, relativeText: { regex: new RegExp("^(first|second|third|fourth|fifth|sixth|seventh|eighth?|ninth|tenth|eleventh|twelfth|" + at + ")" + Re + "(" + ot + ")", "i"), name: "relativetext", callback(o, t, e) {
        const { amount: r } = (function(s) {
          const u = s.toLowerCase();
          return { amount: { last: -1, previous: -1, this: 0, first: 1, next: 1, second: 2, third: 3, fourth: 4, fifth: 5, sixth: 6, seventh: 7, eight: 8, eighth: 8, ninth: 9, tenth: 10, eleventh: 11, twelfth: 12 }[u] ?? 0, behavior: { this: 1 }[u] || 0 };
        })(t);
        switch (e.toLowerCase()) {
          case "sec":
          case "secs":
          case "second":
          case "seconds":
            this.rs += r;
            break;
          case "min":
          case "mins":
          case "minute":
          case "minutes":
            this.ri += r;
            break;
          case "hour":
          case "hours":
            this.rh += r;
            break;
          case "day":
          case "days":
            this.rd += r;
            break;
          case "fortnight":
          case "fortnights":
          case "forthnight":
          case "forthnights":
            this.rd += 14 * r;
            break;
          case "week":
          case "weeks":
            this.rd += 7 * r;
            break;
          case "month":
          case "months":
            this.rm += r;
            break;
          case "year":
          case "years":
            this.ry += r;
            break;
          case "mon":
          case "monday":
          case "tue":
          case "tuesday":
          case "wed":
          case "wednesday":
          case "thu":
          case "thursday":
          case "fri":
          case "friday":
          case "sat":
          case "saturday":
          case "sun":
          case "sunday":
            this.resetTime(), this.weekday = je(e, 7), this.weekdayBehavior = 1, this.rd += 7 * (r > 0 ? r - 1 : r);
        }
      } }, relative: { regex: new RegExp("^([+-]*)[ \\t]*(\\d+)" + J + "(" + ot + "|week)", "i"), name: "relative", callback(o, t, e, r) {
        const s = t.replace(/[^-]/g, "").length, u = +e * Math.pow(-1, s);
        switch (r.toLowerCase()) {
          case "sec":
          case "secs":
          case "second":
          case "seconds":
            this.rs += u;
            break;
          case "min":
          case "mins":
          case "minute":
          case "minutes":
            this.ri += u;
            break;
          case "hour":
          case "hours":
            this.rh += u;
            break;
          case "day":
          case "days":
            this.rd += u;
            break;
          case "fortnight":
          case "fortnights":
          case "forthnight":
          case "forthnights":
            this.rd += 14 * u;
            break;
          case "week":
          case "weeks":
            this.rd += 7 * u;
            break;
          case "month":
          case "months":
            this.rm += u;
            break;
          case "year":
          case "years":
            this.ry += u;
            break;
          case "mon":
          case "monday":
          case "tue":
          case "tuesday":
          case "wed":
          case "wednesday":
          case "thu":
          case "thursday":
          case "fri":
          case "friday":
          case "sat":
          case "saturday":
          case "sun":
          case "sunday":
            this.resetTime(), this.weekday = je(r, 7), this.weekdayBehavior = 1, this.rd += 7 * (u > 0 ? u - 1 : u);
        }
      } }, dayText: { regex: new RegExp("^(" + it + ")", "i"), name: "daytext", callback(o, t) {
        this.resetTime(), this.weekday = je(t, 0), this.weekdayBehavior !== 2 && (this.weekdayBehavior = 1);
      } }, relativeTextWeek: { regex: new RegExp("^(" + at + ")" + Re + "week", "i"), name: "relativetextweek", callback(o, t) {
        switch (this.weekdayBehavior = 2, t.toLowerCase()) {
          case "this":
            this.rd += 0;
            break;
          case "next":
            this.rd += 7;
            break;
          case "last":
          case "previous":
            this.rd -= 7;
        }
        isNaN(this.weekday) && (this.weekday = 1);
      } }, monthFullOrMonthAbbr: { regex: new RegExp("^(" + ut + "|" + ce + ")", "i"), name: "monthfull | monthabbr", callback(o, t) {
        return this.ymd(this.y, j(t), this.d);
      } }, tzCorrection: { regex: new RegExp("^" + Ie, "i"), name: "tzcorrection", callback(o) {
        return this.zone(_e(o));
      } }, tzAbbr: { regex: new RegExp("^\\(?([a-zA-Z]{1,6})\\)?"), name: "tzabbr", callback(o, t) {
        const e = qt[t.toLowerCase()];
        return e != null && !Number.isNaN(e) && this.zone(e);
      } }, ago: { regex: /^ago/i, name: "ago", callback() {
        this.ry = -this.ry, this.rm = -this.rm, this.rd = -this.rd, this.rh = -this.rh, this.ri = -this.ri, this.rs = -this.rs, this.rf = -this.rf;
      } }, year4: { regex: new RegExp("^" + C), name: "year4", callback(o, t) {
        return this.y = +t, !0;
      } }, whitespace: { regex: /^[ .,\t]+/, name: "whitespace" }, dateShortWithTimeLong: { regex: new RegExp("^" + le + "t?" + $ + "[:.]" + Y + "[:.]" + be, "i"), name: "dateshortwithtimelong", callback(o, t, e, r, s, u) {
        return this.ymd(this.y, j(t), +e) && this.time(+r, +s, +u, 0);
      } }, dateShortWithTimeLong12: { regex: new RegExp("^" + le + ee + "[:.]" + Y + "[:.]" + z + J + K, "i"), name: "dateshortwithtimelong12", callback(o, t, e, r, s, u, c) {
        return this.ymd(this.y, j(t), +e) && this.time(X(+r, c), +s, +u, 0);
      } }, dateShortWithTimeShort: { regex: new RegExp("^" + le + "t?" + $ + "[:.]" + Y, "i"), name: "dateshortwithtimeshort", callback(o, t, e, r, s) {
        return this.ymd(this.y, j(t), +e) && this.time(+r, +s, 0, 0);
      } }, dateShortWithTimeShort12: { regex: new RegExp("^" + le + ee + "[:.]" + L + J + K, "i"), name: "dateshortwithtimeshort12", callback(o, t, e, r, s, u) {
        return this.ymd(this.y, j(t), +e) && this.time(X(+r, u), +s, 0, 0);
      } } }, Gt = { y: NaN, m: NaN, d: NaN, h: NaN, i: NaN, s: NaN, f: NaN, ry: 0, rm: 0, rd: 0, rh: 0, ri: 0, rs: 0, rf: 0, weekday: NaN, weekdayBehavior: 0, firstOrLastDayOfMonth: 0, z: NaN, dates: 0, times: 0, zones: 0, ymd(o, t, e) {
        return !(this.dates > 0) && (this.dates++, this.y = o, this.m = t, this.d = e, !0);
      }, time(o, t, e, r) {
        return !(this.times > 0) && (this.times++, this.h = o, this.i = t, this.s = e, this.f = r, !0);
      }, resetTime() {
        return this.h = 0, this.i = 0, this.s = 0, this.f = 0, this.times = 0, !0;
      }, zone(o) {
        return this.zones <= 1 && (this.zones++, this.z = o, !0);
      }, toDate(o) {
        switch (this.dates && !this.times && (this.h = this.i = this.s = this.f = 0), isNaN(this.y) && (this.y = o.getFullYear()), isNaN(this.m) && (this.m = o.getMonth()), isNaN(this.d) && (this.d = o.getDate()), isNaN(this.h) && (this.h = o.getHours()), isNaN(this.i) && (this.i = o.getMinutes()), isNaN(this.s) && (this.s = o.getSeconds()), isNaN(this.f) && (this.f = o.getMilliseconds()), this.firstOrLastDayOfMonth) {
          case 1:
            this.d = 1;
            break;
          case -1:
            this.d = 0, this.m += 1;
        }
        if (!isNaN(this.weekday)) {
          const e = new Date(o.getTime());
          e.setFullYear(this.y, this.m, this.d), e.setHours(this.h, this.i, this.s, this.f);
          const r = e.getDay();
          if (this.weekdayBehavior === 2) r === 0 && this.weekday !== 0 && (this.weekday = -6), this.weekday === 0 && r !== 0 && (this.weekday = 7), this.d -= r, this.d += this.weekday;
          else {
            let s = this.weekday - r;
            (this.rd < 0 && s < 0 || this.rd >= 0 && s <= -this.weekdayBehavior) && (s += 7), this.weekday >= 0 ? this.d += s : this.d -= 7 - (Math.abs(this.weekday) - r), this.weekday = NaN;
          }
        }
        this.y += this.ry, this.m += this.rm, this.d += this.rd, this.h += this.rh, this.i += this.ri, this.s += this.rs, this.f += this.rf, this.ry = this.rm = this.rd = 0, this.rh = this.ri = this.rs = this.rf = 0;
        const t = new Date(o.getTime());
        switch (t.setFullYear(this.y, this.m, this.d), t.setHours(this.h, this.i, this.s, this.f), this.firstOrLastDayOfMonth) {
          case 1:
            t.setDate(1);
            break;
          case -1:
            t.setMonth(t.getMonth() + 1, 0);
        }
        return isNaN(this.z) || t.getTimezoneOffset() === this.z || (t.setUTCFullYear(t.getFullYear(), t.getMonth(), t.getDate()), t.setUTCHours(t.getHours(), t.getMinutes(), t.getSeconds() - this.z, t.getMilliseconds())), t;
      } };
      h.AbstractProvider = ae, h.ArrayAdapter = Ke, h.ArrayProvider = class extends ae {
        getFunctions() {
          return [Vt, Ht, Wt];
        }
      }, h.BasicProvider = class extends ae {
        getFunctions() {
          return [Dt];
        }
      }, h.Compiler = Ge, h.DateProvider = class extends ae {
        getFunctions() {
          return [new O("date", function(o, t) {
            let e = "";
            return t && (e = `, ${t}`), `date(${o}${e})`;
          }, function(o, t, e) {
            return Bt(t, e);
          }), new O("strtotime", function(o, t) {
            let e = "";
            return t && (e = `, ${t}`), `strtotime(${o}${e})`;
          }, function(o, t, e) {
            return (function(r, s) {
              const u = s ?? Math.floor(Date.now() / 1e3), c = [b.yesterday, b.now, b.noon, b.midnightOrToday, b.tomorrow, b.timestamp, b.firstOrLastDay, b.backOrFrontOf, b.timeTiny12, b.timeShort12, b.timeLong12, b.mssqltime, b.oracledate, b.timeShort24, b.timeLong24, b.iso8601long, b.gnuNoColon, b.iso8601noColon, b.americanShort, b.american, b.iso8601date4, b.iso8601dateSlash, b.dateSlash, b.gnuDateShortOrIso8601date2, b.gnuDateShorter, b.dateFull, b.pointedDate4, b.pointedDate2, b.dateNoDay, b.dateNoDayRev, b.dateTextual, b.dateNoYear, b.dateNoYearRev, b.dateNoColon, b.xmlRpc, b.xmlRpcNoColon, b.soap, b.wddx, b.exif, b.pgydotd, b.isoWeekDay, b.pgTextShort, b.pgTextReverse, b.clf, b.year4, b.ago, b.dayText, b.relativeTextWeek, b.relativeText, b.monthFullOrMonthAbbr, b.tzCorrection, b.tzAbbr, b.dateShortWithTimeShort12, b.dateShortWithTimeLong12, b.dateShortWithTimeShort, b.dateShortWithTimeLong, b.relative, b.whitespace], l = { ...Gt };
              for (; r.length; ) {
                let d = null, g = null;
                for (const y of c) {
                  const x = r.match(y.regex);
                  x && (!d || x[0].length > d[0].length) && (d = x, g = y);
                }
                if (!g || !d || g.callback && g.callback.apply(l, d) === !1) return !1;
                r = r.substr(d[0].length), g = null, d = null;
              }
              return Math.floor(l.toDate(new Date(1e3 * u)).getTime() / 1e3);
            })(t, e);
          })];
        }
      }, h.ExpressionFunction = O, h.ExpressionLanguage = Ze, h.IGNORE_UNKNOWN_FUNCTIONS = 2, h.IGNORE_UNKNOWN_VARIABLES = 1, h.Parser = qe, h.StringProvider = class extends ae {
        getFunctions() {
          return [new O("strtolower", (o) => "strtolower(" + o + ")", (o, t) => (function(e) {
            return (e + "").toLowerCase();
          })(t)), new O("strtoupper", (o) => "strtoupper(" + o + ")", (o, t) => (function(e) {
            return (e + "").toUpperCase();
          })(t)), new O("explode", (o, t, e = "null") => `explode(${o}, ${t}, ${e})`, (o, t, e, r = null) => (function(...s) {
            let [u, c, l] = s, d = u;
            const g = c;
            if (s.length < 2 || d === void 0 || g === void 0) return null;
            if (d === "" || d === !1 || d === null) return !1;
            if (typeof d == "function" || typeof d == "object" || typeof g == "function" || typeof g == "object") return { 0: "" };
            d === !0 && (d = "1");
            const y = d + "", x = (g + "").split(y);
            return l === void 0 ? x : (l === 0 && (l = 1), l > 0 ? l >= x.length ? x : x.slice(0, l - 1).concat([x.slice(l - 1).join(y)]) : -l >= x.length ? [] : (x.splice(x.length + l), x));
          })(t, e, r)), new O("strlen", function(o) {
            return `strlen(${o});`;
          }, function(o, t) {
            return (function(e) {
              const r = e + "";
              if ((rt("unicode.semantics") || "off") === "off") return r.length;
              let s = 0, u = 0;
              const c = function(l, d) {
                const g = l.charCodeAt(d);
                if (g >= 55296 && g <= 56319) {
                  if (l.length <= d + 1) throw new Error("High surrogate without following low surrogate");
                  const y = l.charCodeAt(d + 1);
                  if (y < 56320 || y > 57343) throw new Error("High surrogate without following low surrogate");
                  return l.charAt(d) + l.charAt(d + 1);
                }
                if (g >= 56320 && g <= 57343) {
                  if (d === 0) throw new Error("Low surrogate without preceding high surrogate");
                  const y = l.charCodeAt(d - 1);
                  if (y < 55296 || y > 56319) throw new Error("Low surrogate without preceding high surrogate");
                  return !1;
                }
                return l.charAt(d);
              };
              for (s = 0, u = 0; s < r.length; s++) c(r, s) !== !1 && u++;
              return u;
            })(t);
          }), new O("strstr", function(o, t, e) {
            let r = "";
            return e && (r = `, ${e}`), `strstr(${o}, ${t}${r});`;
          }, function(o, t, e, r) {
            return (function(s, u, c) {
              let l = 0;
              return l = (s += "").indexOf(u), l !== -1 && (c ? s.substr(0, l) : s.slice(l));
            })(t, e, r);
          }), new O("stristr", function(o, t, e) {
            let r = "";
            return e && (r = `, ${e}`), `stristr(${o}, ${t}${r});`;
          }, function(o, t, e, r) {
            return (function(s, u, c) {
              let l = 0;
              return l = (s += "").toLowerCase().indexOf((u + "").toLowerCase()), l !== -1 && (c ? s.substr(0, l) : s.slice(l));
            })(t, e, r);
          }), new O("substr", function(o, t, e) {
            let r = "";
            return e && (r = `, ${e}`), `substr(${o}, ${t}${r});`;
          }, function(o, t, e, r) {
            return Yt(t, e, r);
          })];
        }
      }, h.default = Ze, h.tokenize = T, Object.defineProperty(h, "__esModule", { value: !0 });
    }), (function(h) {
      var i = h.ExpressionLanguage;
      if (i && typeof i.ExpressionLanguage == "function") {
        var m = i.ExpressionLanguage;
        Object.keys(i).forEach(function(p) {
          p in m || (m[p] = i[p]);
        }), h.ExpressionLanguage = m;
      }
    })(typeof globalThis < "u" ? globalThis : typeof self < "u" ? self : Nt);
  })(he, he.exports)), he.exports;
}
var _t = yr();
const br = /* @__PURE__ */ gr(_t), vt = /* @__PURE__ */ Jt({
  __proto__: null,
  default: br
}, [_t]);
let kt = null;
function wr() {
  const n = vt, a = n.ExpressionLanguage || n.default || vt;
  if (typeof a != "function")
    throw new TypeError("Unable to resolve expression-language constructor.");
  return a;
}
function xr() {
  return kt ??= new (wr())(), kt;
}
function jr(n) {
  return (n.formula?.expression || n.formula?.formula || "").trim();
}
function _r(n) {
  return Object.entries(n.formula?.variables || {}).filter((a) => !!a[1]?.sourceKey);
}
function Fr(n, a) {
  return Object.entries(n).forEach(([h, i]) => {
    if (Array.isArray(i)) {
      n[h] = i.map((m) => typeof m == "string" && m.trim() !== "" && !Number.isNaN(Number(m)) ? Number(m) : m);
      return;
    }
    typeof i == "string" && i.trim() !== "" && !Number.isNaN(Number(i)) && (n[h] = Number(i));
  }), n;
}
function Nr(n, a) {
  if (a.formatting !== "number")
    return typeof n == "number" || typeof n == "string" ? n : "";
  let h = n;
  Array.isArray(h) && (h = h.reduce((p, N) => p + Number(N || 0), 0));
  const i = typeof a.decimals == "number" ? a.decimals : 0, m = Number(h || 0).toFixed(i);
  return `${a.prefix || ""}${m}${a.suffix || ""}`;
}
function Ur(n, a) {
  const h = n.type?.endsWith("\\Number");
  return n.type?.endsWith("\\Checkboxes") ? Array.isArray(a) ? a.length ? a : "" : a ? [a] : "" : Array.isArray(a) ? a.length ? h ? a.map((m) => Number(m || 0)) : a : "" : h ? Number(a || 0) : a;
}
function $r(n, a, h) {
  return Nr(xr().evaluate(n, a), h);
}
function Ne(n, a) {
  if (a.startsWith("http://") || a.startsWith("https://"))
    return a;
  if (n.startsWith("http://") || n.startsWith("https://"))
    return new URL(a, n).toString();
  const h = n.trim();
  return !h || h === "/" ? a : `${h.replace(/\/+$/, "")}${a}`;
}
async function Ee(n, a) {
  const h = await fetch(n, a);
  if (!h.ok)
    throw new Error(`Request failed with status ${h.status}.`);
  return h.json();
}
async function Lr(n) {
  const a = Ne(n.endpoint, "/actions/formie/client/forms/load"), h = JSON.stringify({
    handle: n.formHandle,
    siteId: n.siteId
  });
  return Ee(a, {
    method: "POST",
    credentials: n.credentials ?? "same-origin",
    headers: {
      "Content-Type": "application/json"
    },
    body: h
  });
}
function Dr(n) {
  return {
    async submit({ definition: a, session: h, values: i, action: m }) {
      const p = Ne(n.endpoint, "/actions/formie/client/submissions/submit"), N = await ke(a, i);
      return Ee(p, {
        method: "POST",
        credentials: n.credentials ?? "same-origin",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          handle: n.formHandle,
          siteId: n.siteId,
          action: m,
          session: h,
          values: N
        })
      });
    },
    async refreshSession({ session: a }) {
      const h = Ne(n.endpoint, "/actions/formie/client/sessions/refresh");
      return Ee(h, {
        method: "POST",
        credentials: n.credentials ?? "same-origin",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          handle: n.formHandle,
          siteId: n.siteId,
          session: a
        })
      });
    },
    async setPage({ definition: a, session: h, values: i, currentPageId: m, targetPageId: p }) {
      const N = Ne(n.endpoint, "/actions/formie/client/forms/page"), f = await ke(a, i);
      return Ee(N, {
        method: "POST",
        credentials: n.credentials ?? "same-origin",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify({
          handle: n.formHandle,
          siteId: n.siteId,
          currentPageId: m,
          targetPageId: p,
          session: h,
          values: f
        })
      });
    }
  };
}
const Te = `
    id
    currentPageId
    tokens
    continuation
`, Er = `
    success
    submissionUid
    currentPageId
    nextPageId
    previousPageId
    isFinalPage
    errors
    messages
    session {
        ${Te}
    }
`;
function vr(n) {
  if (n.startsWith("http://") || n.startsWith("https://"))
    return n;
  const a = n.trim();
  return !a || a === "/" ? "/api" : a;
}
async function ve(n, a, h) {
  const i = await fetch(vr(n.endpoint), {
    method: "POST",
    // Default `same-origin`: credentialed cross-origin + `Allow-Origin: *` is invalid in browsers.
    credentials: n.credentials ?? "same-origin",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json"
    },
    body: JSON.stringify({
      query: a,
      variables: h
    })
  });
  if (!i.ok)
    throw new Error(`Request failed with status ${i.status}.`);
  const m = await i.json();
  if (m.errors?.length)
    throw new Error(m.errors[0]?.message || "GraphQL returned an error.");
  if (!m.data)
    throw new Error("GraphQL returned no data.");
  return m.data;
}
async function Mr(n) {
  const a = await ve(
    n,
    `
            query ClientForm($handle: String!, $siteId: Int) {
                formieClientForm(handle: $handle, siteId: $siteId) {
                    schemaVersion
                    definition
                    session {
                        ${Te}
                    }
                }
            }
        `,
    {
      handle: n.formHandle,
      siteId: n.siteId
    }
  );
  if (!a.formieClientForm)
    throw new Error("No client form definition was returned.");
  return a.formieClientForm;
}
function Yr(n) {
  return {
    async submit({ definition: a, session: h, values: i, action: m }) {
      const p = await ke(a, i), N = await ve(
        n,
        `
                    mutation SubmitFormieClientForm(
                        $input: FormieClientSubmitInput!
                    ) {
                        submitFormieClientForm(input: $input) {
                            ${Er}
                        }
                    }
                `,
        {
          input: {
            handle: n.formHandle,
            siteId: n.siteId,
            action: m,
            session: h,
            values: p
          }
        }
      );
      if (!N.submitFormieClientForm)
        throw new Error("No client submit result was returned.");
      return N.submitFormieClientForm;
    },
    async refreshSession({ session: a }) {
      const h = await ve(
        n,
        `
                    mutation RefreshFormieClientSession(
                        $input: FormieClientSessionRefreshInput!
                    ) {
                        refreshFormieClientSession(input: $input) {
                            ${Te}
                        }
                    }
                `,
        {
          input: {
            handle: n.formHandle,
            siteId: n.siteId,
            session: a
          }
        }
      );
      if (!h.refreshFormieClientSession)
        throw new Error("No client session was returned.");
      return h.refreshFormieClientSession;
    },
    async setPage({ definition: a, session: h, values: i, currentPageId: m, targetPageId: p }) {
      const N = await ke(a, i), f = await ve(
        n,
        `
                    mutation SetFormieClientPage(
                        $input: FormieClientSetPageInput!
                    ) {
                        setFormieClientPage(input: $input) {
                            ${Te}
                        }
                    }
                `,
        {
          input: {
            handle: n.formHandle,
            siteId: n.siteId,
            currentPageId: m,
            targetPageId: p,
            session: h,
            values: N
          }
        }
      );
      if (!f.setFormieClientPage)
        throw new Error("No client page session was returned.");
      return f.setFormieClientPage;
    }
  };
}
const Tt = (() => {
  const n = Intl.Segmenter;
  return n ? new n(void 0, { granularity: "grapheme" }) : null;
})(), kr = /[\p{L}\p{N}\p{M}]+(?:['’._-][\p{L}\p{N}\p{M}]+)*/gu;
function Tr(n) {
  return typeof DOMParser < "u" ? new DOMParser().parseFromString(n, "text/html").body.textContent || "" : n.replace(/<[^>]*>/g, " ");
}
function Ft(n) {
  return Tr(n);
}
function Sr(n) {
  return Ft(n).replace(/[\s\t\n\r]+/g, " ").trim();
}
function Ar(n) {
  return Tt ? Array.from(Tt.segment(n)).length : Array.from(n).length;
}
function Or(n) {
  return n.match(kr)?.length || 0;
}
function zr(n) {
  const a = Ft(n), h = Sr(n);
  return {
    graphemeCount: Ar(a),
    wordCount: Or(h)
  };
}
export {
  Ir as FRONTEND_CLIENT_EVENT_NAMES,
  se as allFields,
  Fr as coerceCalculationVariables,
  Me as compositePartDefinitions,
  Ar as countGraphemes,
  Rr as createFrontendFormInstance,
  Yr as createGraphqlFrontendTransport,
  nr as createRepeaterRowValue,
  Dr as createRestFrontendTransport,
  Ye as defaultValueForField,
  $r as evaluateCalculationExpression,
  St as evaluateConditionDefinition,
  re as fieldValueAsStrings,
  Qt as fieldValueContract,
  Pt as fieldValueStructure,
  At as finalizeConditionEvaluation,
  Ot as findFieldByHandle,
  de as findFieldById,
  Nr as formatCalculationValue,
  jr as getCalculationFormula,
  _r as getCalculationVariableEntries,
  zr as getTextLimitMetrics,
  Or as getWordCount,
  er as isBooleanField,
  fe as isCompositeField,
  rr as isEmailField,
  pe as isFileField,
  Cr as isKnownFrontendFieldType,
  De as isMultiValueField,
  tr as isNumericField,
  me as isRepeatableField,
  Lr as loadFrontendEnvelope,
  Mr as loadGraphqlFrontendEnvelope,
  Sr as normalizeText,
  Ur as readCalculationVariableValue,
  Se as repeaterFieldDefinitions,
  sr as repeaterRowDefinitions,
  Pr as serializeFieldValues,
  ke as serializeTransportFieldValues
};
