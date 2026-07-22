function Xt(s, a) {
  for (var l = 0; l < a.length; l++) {
    const i = a[l];
    if (typeof i != "string" && !Array.isArray(i)) {
      for (const m in i)
        if (m !== "default" && !(m in s)) {
          const p = Object.getOwnPropertyDescriptor(i, m);
          p && Object.defineProperty(s, m, p.get ? p : {
            enumerable: !0,
            get: () => i[m]
          });
        }
    }
  }
  return Object.freeze(Object.defineProperty(s, Symbol.toStringTag, { value: "Module" }));
}
function Qt(s) {
  return Array.isArray(s) ? s.map((a) => String(a ?? "")) : [String(s ?? "")];
}
function gt(s, a) {
  return s.some((l) => a.includes(l));
}
function yt(s, a) {
  return s.some((l) => a.some((i) => i === l || i.includes(l)));
}
function bt(s, a, l) {
  return a.some((i) => s.some((m) => l(m, i)));
}
function wt(s, a, l) {
  return a.some((i) => {
    const m = Number.parseFloat(i);
    return Number.isFinite(m) ? s.some((p) => {
      const b = Number.parseFloat(p);
      return Number.isFinite(b) ? l(b, m) : !1;
    }) : !1;
  });
}
function xt(s) {
  return s.length === 0 || s.every((a) => a.trim() === "");
}
function Pt(s, a, l = {}) {
  const i = String(s.condition || ""), m = Qt(s.value), p = l.visibility ?? null;
  switch (i) {
    case "=":
      return gt(m, a);
    case "!=":
      return !gt(m, a);
    case ">":
      return wt(a, m, (b, f) => b > f);
    case "<":
      return wt(a, m, (b, f) => b < f);
    case "contains":
      return yt(m, a);
    case "notContains":
      return !yt(m, a);
    case "startsWith":
      return bt(a, m, (b, f) => b.startsWith(f));
    case "endsWith":
      return bt(a, m, (b, f) => b.endsWith(f));
    case "empty":
      return xt(a);
    case "notEmpty":
      return !xt(a);
    case "visible":
      return p === !0;
    case "hidden":
      return p === !1;
    default:
      return !1;
  }
}
function Ct(s, a) {
  const l = s.conditionRule === "any" ? a.includes(!0) : a.every((m) => m === !0), i = l && s.showRule !== "show" || !l && s.showRule === "show";
  return {
    finalResult: l,
    shouldHide: i
  };
}
const er = /* @__PURE__ */ new Set([
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
function ie(s) {
  return s.pages.flatMap((a) => a.rows.flatMap((l) => l.fields));
}
function me(s, a) {
  return ie(s).find((l) => l.id === a);
}
function It(s, a) {
  return ie(s).find((l) => l.handle === a);
}
function Ur(s, a) {
  return Object.fromEntries(Object.entries(a).map(([l, i]) => [me(s, l)?.handle ?? l, i]));
}
function Lr(s) {
  return er.has(s);
}
function tr(s) {
  if (!s.runtime)
    throw new Error(`Field "${s.handle}" is missing field value metadata.`);
  return s.runtime;
}
function Rt(s) {
  return tr(s).structure;
}
function pe(s) {
  return Rt(s) === "fixed-parent" && Oe(s).length > 0;
}
function ge(s) {
  return Rt(s) === "repeatable-parent";
}
function ye(s) {
  return s.type === "file" || s.input.fieldKind === "file";
}
function ze(s) {
  const a = s.input;
  return ye(s) || s.type === "checkboxes" || s.type === "dropdown" && a.multiple === !0;
}
function rr(s) {
  return s.type === "agree" || s.input.fieldKind === "boolean";
}
function sr(s) {
  return s.type === "number";
}
function nr(s) {
  return s.type === "email";
}
function Oe(s) {
  const a = s.input;
  return Array.isArray(a.parts) ? a.parts.filter((l) => !!l && typeof l == "object" && "handle" in l && "type" in l) : [];
}
function ir(s) {
  const l = s.input.rowSchema;
  return !l || typeof l != "object" || !Array.isArray(l.rows) ? [] : l.rows;
}
function Pe(s) {
  return ir(s).flatMap((a) => a.fields);
}
function He(s) {
  const a = s.input;
  if (s.type === "checkboxes")
    return (Array.isArray(a.options) ? a.options : []).filter((i) => i.selected === !0).map((i) => i.value ?? "");
  if (s.type === "radio" || s.type === "dropdown") {
    const l = Array.isArray(a.options) ? a.options : [];
    if (s.type === "dropdown" && a.multiple === !0)
      return l.filter((m) => m.selected === !0).map((m) => m.value ?? "");
    const i = l.find((m) => m.selected === !0);
    if (i)
      return i.value ?? "";
  }
  if (s.type === "agree")
    return a.defaultValue ?? !1;
  if (pe(s))
    return a.defaultValue && typeof a.defaultValue == "object" ? a.defaultValue : {};
  if (ge(s)) {
    const l = Number(a.minRows ?? 0) || 0;
    return l <= 0 ? [] : Array.from({ length: l }, () => ar(s));
  }
  return ye(s) || ze(s) ? [] : (s.type === "signature", a.defaultValue ?? "");
}
function ar(s) {
  return Object.fromEntries(Pe(s).map((a) => [a.handle, He(a)]));
}
function ne(s, a) {
  if (s.type === "checkboxes" || ye(s) || ze(s))
    return Array.isArray(a) ? a.flatMap((l) => ne(s, l)) : [];
  if (ge(s)) {
    const l = Array.isArray(a) ? a : [], i = Pe(s);
    return l.flatMap((m) => {
      if (!m || typeof m != "object")
        return [];
      const p = m;
      return i.flatMap((b) => ne(b, p[b.handle]));
    });
  }
  return pe(s) && a && typeof a == "object" ? Object.values(a).flatMap((l) => ne(s, l)) : a == null ? [] : typeof a == "boolean" ? a ? ["true"] : ["false"] : Array.isArray(a) ? a.flatMap((l) => ne(s, l)) : [String(a)];
}
function or(s) {
  return typeof Blob < "u" && s instanceof Blob;
}
async function ur(s) {
  return new Promise((a, l) => {
    const i = new FileReader();
    i.onerror = () => {
      l(i.error || new Error("Unable to read file."));
    }, i.onload = () => {
      a(typeof i.result == "string" ? i.result : "");
    }, i.readAsDataURL(s);
  });
}
async function cr(s) {
  const a = Array.isArray(s) ? s : [];
  return (await Promise.all(a.map(async (i) => typeof i == "number" ? { assetId: i } : i && typeof i == "object" && "assetId" in i && typeof i.assetId == "number" ? {
    assetId: i.assetId,
    filename: typeof i.filename == "string" ? i.filename : void 0
  } : i && typeof i == "object" && "fileData" in i && typeof i.fileData == "string" ? {
    fileData: i.fileData,
    filename: typeof i.filename == "string" ? i.filename : void 0
  } : or(i) ? {
    fileData: await ur(i),
    filename: "name" in i && typeof i.name == "string" ? i.name : "upload.bin"
  } : null))).filter((i) => i !== null);
}
async function jt(s, a) {
  const l = a && typeof a == "object" ? a : {}, i = {
    ...l
  };
  return await Promise.all(s.map(async (m) => {
    i[m.handle] = await _t(m, l[m.handle]);
  })), i;
}
async function lr(s, a) {
  const l = Pe(s);
  return l.length === 0 || !Array.isArray(a) ? [] : Promise.all(a.map(async (i) => jt(l, i)));
}
async function _t(s, a) {
  return ye(s) ? cr(a) : ge(s) ? lr(s, a) : pe(s) ? jt(Oe(s), a) : a;
}
async function Se(s, a) {
  const l = await Promise.all(Object.entries(a).map(async ([i, m]) => {
    const p = me(s, i);
    return p ? [p.handle, await _t(p, m)] : [i, m];
  }));
  return Object.fromEntries(l);
}
function _(s) {
  return s == null ? "" : String(s).trim();
}
function hr(s) {
  return Oe(s).filter((a) => a.meta?.hidden !== !0).map((a) => a.handle).filter((a) => ["year", "month", "day"].includes(a));
}
function dr(s, a) {
  const l = hr(a);
  return l.length === 0 ? !1 : l.every((i) => _(s[i]) !== "");
}
function fr(s, a, l) {
  if (!Number.isInteger(s) || !Number.isInteger(a) || !Number.isInteger(l))
    return !1;
  const i = new Date(s, a - 1, l);
  return i.getFullYear() === s && i.getMonth() === a - 1 && i.getDate() === l;
}
function mr(s) {
  const a = Number.parseInt(_(s.year), 10), l = Number.parseInt(_(s.month), 10), i = Number.parseInt(_(s.day), 10), m = _(s.hour) !== "" ? Number.parseInt(_(s.hour), 10) : 0, p = _(s.minute) !== "" ? Number.parseInt(_(s.minute), 10) : 0, b = _(s.second) !== "" ? Number.parseInt(_(s.second), 10) : 0;
  return new Date(a, l - 1, i, m, p, b);
}
function pr(s, a, l, i) {
  const m = s.validation.find((x) => x.type === "dateParts");
  if (!m || s.input.dateEnabled === !1)
    return;
  const p = a && typeof a == "object" ? a : {};
  if (!dr(p, s))
    return;
  const b = Number.parseInt(_(p.year), 10), f = Number.parseInt(_(p.month), 10), T = Number.parseInt(_(p.day), 10);
  if (!fr(b, f, T)) {
    const x = `${l}.day`;
    i[x] || (i[x] = ["Day is invalid."]);
    return;
  }
  const w = mr(p);
  if (m.minDate) {
    const x = new Date(m.minDate);
    if (Number.isFinite(x.getTime()) && w < x) {
      i[l] = [`The date must be on or after ${x.toLocaleDateString()}.`];
      return;
    }
  }
  if (m.maxDate) {
    const x = new Date(m.maxDate);
    Number.isFinite(x.getTime()) && w > x && (i[l] = [`The date must be on or before ${x.toLocaleDateString()}.`]);
  }
}
class gr {
  constructor() {
    this.listeners = /* @__PURE__ */ new Map();
  }
  on(a, l) {
    const i = this.listeners.get(a) ?? /* @__PURE__ */ new Set();
    return i.add(l), this.listeners.set(a, i), () => {
      i.delete(l), i.size === 0 && this.listeners.delete(a);
    };
  }
  emit(a, l) {
    const i = this.listeners.get(a);
    i && i.forEach((m) => {
      m(l);
    });
  }
}
function Ye(s) {
  return Array.isArray(s) ? s.map((a) => Ye(a)) : !s || typeof s != "object" || typeof File < "u" && s instanceof File || typeof Blob < "u" && s instanceof Blob ? s : Object.fromEntries(Object.entries(s).map(([a, l]) => [a, Ye(l)]));
}
function De(s) {
  return {
    ...s,
    session: {
      ...s.session,
      tokens: { ...s.session.tokens },
      continuation: s.session.continuation ? { ...s.session.continuation } : null
    },
    values: Ye(s.values),
    errors: {
      form: [...s.errors.form],
      fields: Object.fromEntries(Object.entries(s.errors.fields).map(([a, l]) => [a, [...l]])),
      pages: Object.fromEntries(Object.entries(s.errors.pages).map(([a, l]) => [a, [...l]]))
    },
    fieldStates: Object.fromEntries(Object.entries(s.fieldStates).map(([a, l]) => [a, { ...l }])),
    pageStates: Object.fromEntries(Object.entries(s.pageStates).map(([a, l]) => [a, { ...l }])),
    lastSubmitResult: s.lastSubmitResult ? {
      ...s.lastSubmitResult,
      errors: {
        form: [...s.lastSubmitResult.errors.form],
        fields: Object.fromEntries(Object.entries(s.lastSubmitResult.errors.fields).map(([a, l]) => [a, [...l]])),
        pages: Object.fromEntries(Object.entries(s.lastSubmitResult.errors.pages).map(([a, l]) => [a, [...l]]))
      },
      messages: { ...s.lastSubmitResult.messages },
      session: s.lastSubmitResult.session ? {
        ...s.lastSubmitResult.session,
        tokens: { ...s.lastSubmitResult.session.tokens },
        continuation: s.lastSubmitResult.session.continuation ? { ...s.lastSubmitResult.session.continuation } : null
      } : null
    } : null
  };
}
function yr(s) {
  return Object.fromEntries(ie(s.definition).map((a) => [a.id, He(a)]));
}
function Ft(s) {
  return Object.fromEntries(ie(s).map((a) => [a.id, {
    hidden: a.meta?.hidden === !0,
    disabled: a.meta?.disabled === !0
  }]));
}
function br(s) {
  return Object.fromEntries(s.pages.map((a) => [a.id, { hidden: !1 }]));
}
function wr(s, a) {
  const l = s.definition.pages.find((m) => m.id === a);
  if (!l)
    return [];
  const i = [];
  return l.rows.forEach((m) => {
    m.fields.forEach((p) => {
      i.push(p.id);
    });
  }), i;
}
function $t(s, a) {
  return me(s, a.fieldId) || It(s, a.fieldId);
}
function Nt(s) {
  const a = Ft(s.definition);
  return ie(s.definition).forEach((l) => {
    const i = l.condition;
    if (!i || i.rules.length === 0)
      return;
    const m = i.rules.map((b) => {
      const f = $t(s.definition, b), T = f ? a[f.id]?.hidden !== !0 : null;
      return Pt({
        condition: b.operator,
        value: b.value
      }, f ? ne(f, s.values[f.id]) : [], {
        visibility: T
      });
    });
    if (i.effect === "show" || i.effect === "hide") {
      const { shouldHide: b } = Ct({
        conditionRule: i.mode,
        showRule: i.effect === "show" ? "show" : "hide"
      }, m);
      a[l.id] = {
        ...a[l.id],
        hidden: a[l.id].hidden || b
      };
      return;
    }
    const p = i.mode === "any" ? m.includes(!0) : m.every((b) => b === !0);
    a[l.id] = {
      ...a[l.id],
      disabled: a[l.id].disabled || (i.effect === "disable" ? p : !p)
    };
  }), a;
}
function xr(s) {
  return He(s);
}
function Nr(s, a, l) {
  let i = s.values;
  return ie(s.definition).forEach((m) => {
    const p = m.condition, b = a[m.id]?.hidden === !0, f = l[m.id]?.hidden === !0, T = p?.clearOnHide !== !1;
    if (!f || b || !T)
      return;
    const w = xr(m);
    i[m.id] !== w && (i = {
      ...i,
      [m.id]: w
    });
  }), i;
}
function Et(s, a) {
  return Object.fromEntries(s.definition.pages.map((l) => {
    const i = l.condition;
    if (!i || i.rules.length === 0)
      return [l.id, { hidden: !1 }];
    const m = i.rules.map((b) => {
      const f = $t(s.definition, b), T = f ? a[f.id]?.hidden !== !0 : null;
      return Pt({
        condition: b.operator,
        value: b.value
      }, f ? ne(f, s.values[f.id]) : [], {
        visibility: T
      });
    }), { shouldHide: p } = Ct({
      conditionRule: i.mode,
      showRule: i.effect === "show" ? "show" : "hide"
    }, m);
    return [l.id, { hidden: p }];
  }));
}
function Tt(s, a, l) {
  const i = s.pages[0]?.id || "", m = s.pages.find((p) => a[p.id]?.hidden !== !0)?.id || i;
  return l ? a[l]?.hidden === !0 ? m : l : m;
}
function J(s) {
  let a = s;
  for (let m = 0; m < 3; m += 1) {
    const p = Nt(a), b = Nr(a, a.fieldStates, p);
    if (b !== a.values) {
      a = {
        ...a,
        values: b,
        fieldStates: p
      };
      continue;
    }
    const f = Et(a, p);
    return {
      ...a,
      fieldStates: p,
      pageStates: f,
      currentPageId: Tt(a.definition, f, a.currentPageId)
    };
  }
  const l = Nt(a), i = Et(a, l);
  return {
    ...a,
    fieldStates: l,
    pageStates: i,
    currentPageId: Tt(a.definition, i, a.currentPageId)
  };
}
function Er(s, a) {
  return s.type === "checkboxes" ? !Array.isArray(a) || a.length === 0 : rr(s) ? a !== !0 : ye(s) || ge(s) || ze(s) ? !Array.isArray(a) || a.length === 0 : pe(s) && a && typeof a == "object" ? Object.values(a).every((l) => l == null || typeof l == "string" && l.trim() === "") : a == null ? !0 : typeof a == "string" ? a.trim() === "" : !1;
}
function K(s) {
  return s.label?.trim() || s.handle;
}
function Ve(s, a, l, i, m) {
  const p = new Set(s.validation.map((T) => T.type)), b = s.input;
  if ((s.required || p.has("required")) && Er(s, a)) {
    m[i] = [`${K(s)} cannot be blank.`];
    return;
  }
  if ((nr(s) || p.has("email")) && typeof a == "string" && a.trim() !== "" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(a)) {
    m[i] = [`${K(s)} is not a valid email address.`];
    return;
  }
  if ((sr(s) || p.has("number")) && typeof a == "string" && a.trim() !== "") {
    const T = Number.parseFloat(a);
    if (!Number.isFinite(T)) {
      m[i] = [`${K(s)} is not a valid number.`];
      return;
    }
    const w = s.validation.find((I) => I.type === "number"), x = Number(b.min ?? w?.min ?? Number.NaN), S = Number(b.max ?? w?.max ?? Number.NaN);
    if (Number.isFinite(x) && T < x) {
      m[i] = [`${K(s)} must be no less than ${x}.`];
      return;
    }
    if (Number.isFinite(S) && T > S) {
      m[i] = [`${K(s)} must be no greater than ${S}.`];
      return;
    }
  }
  if (p.has("url") && typeof a == "string" && a.trim() !== "")
    try {
      new URL(a);
    } catch {
      m[i] = [`${K(s)} is not a valid URL.`];
      return;
    }
  const f = s.validation.find((T) => T.type === "match");
  if (f && typeof a == "string" && a.trim() !== "") {
    const T = (f.fieldId ? me(l.definition, f.fieldId) : void 0) || (f.fieldHandle ? It(l.definition, f.fieldHandle) : void 0), w = T ? l.values[T.id] : void 0;
    if (typeof w == "string" && w !== a) {
      const x = T ? K(T) : s.handle;
      m[i] = [`${K(s)} must match ${x}.`];
      return;
    }
  }
  if (p.has("minmaxOptions") && Array.isArray(a)) {
    const T = s.validation.find((S) => S.type === "minmaxOptions"), w = Number(b.min ?? T?.min ?? Number.NaN), x = Number(b.max ?? T?.max ?? Number.NaN);
    if (Number.isFinite(w) && a.length < w) {
      m[i] = [`Please select at least ${w} option${w === 1 ? "" : "s"}.`];
      return;
    }
    if (Number.isFinite(x) && a.length > x) {
      m[i] = [`Please select no more than ${x} option${x === 1 ? "" : "s"}.`];
      return;
    }
  }
  if (pe(s)) {
    const T = Oe(s), w = a && typeof a == "object" ? a : {};
    T.forEach((x) => {
      x.meta?.hidden !== !0 && Ve(x, w[x.handle], l, `${i}.${x.handle}`, m);
    }), pr(s, w, i, m);
    return;
  }
  if (ge(s)) {
    const T = Array.isArray(a) ? a : [], w = Pe(s);
    T.forEach((x, S) => {
      const I = x && typeof x == "object" ? x : {};
      w.forEach((k) => {
        Ve(
          k,
          I[k.handle],
          l,
          `${i}.${S}.${k.handle}`,
          m
        );
      });
    });
  }
}
function Tr(s) {
  const a = {
    form: [],
    fields: {},
    pages: {}
  };
  return wr(s, s.currentPageId).forEach((l) => {
    const i = me(s.definition, l);
    !i || s.fieldStates[l]?.hidden === !0 || s.fieldStates[l]?.disabled === !0 || Ve(i, s.values[l], s, l, a.fields);
  }), Object.keys(a.fields).length > 0 && (a.form = [s.definition.settings.validation.formErrorMessage || "Please correct the highlighted fields."]), a;
}
function Dr({ envelope: s, transport: a }) {
  const l = new gr(), i = /* @__PURE__ */ new Set(), m = yr(s);
  let p = {
    status: "ready",
    definition: s.definition,
    session: s.session,
    values: m,
    errors: {
      form: [],
      fields: {},
      pages: {}
    },
    fieldStates: Ft(s.definition),
    pageStates: br(s.definition),
    currentPageId: s.session.currentPageId || s.definition.settings.initialPageId,
    lastSubmitResult: null
  };
  p = J(p);
  const b = () => {
    const w = De(p);
    i.forEach((x) => {
      x(w);
    });
  }, f = (w) => {
    p = w(p), b();
  }, T = {
    id: s.session.id,
    getState() {
      return De(p);
    },
    subscribe(w) {
      return i.add(w), w(De(p)), () => {
        i.delete(w);
      };
    },
    setValue(w, x) {
      f((S) => {
        const I = Object.fromEntries(Object.entries(S.errors.fields).filter(([k]) => k !== w && !k.startsWith(`${w}.`)));
        return I[w] = [], J({
          ...S,
          values: {
            ...S.values,
            [w]: x
          },
          errors: {
            ...S.errors,
            fields: I
          }
        });
      });
    },
    patchValues(w) {
      f((x) => J({
        ...x,
        values: {
          ...x.values,
          ...w
        }
      }));
    },
    async submit(w) {
      const x = p.definition.pages.find((k) => k.id === p.currentPageId), S = w || x?.actions.primary.type || "submit", I = S === "next" ? "submit" : S;
      if (I !== "back" && I !== "save" && p.definition.settings.validation.onSubmit) {
        const k = Tr(p);
        if (k.form.length > 0 || Object.keys(k.fields).length > 0) {
          const F = {
            success: !1,
            isFinalPage: !1,
            errors: k,
            messages: {
              error: k.form[0] || null
            },
            session: p.session
          };
          return f((M) => ({
            ...M,
            errors: k,
            lastSubmitResult: F
          })), l.emit("formie:submit:result", F), F;
        }
      }
      f((k) => ({
        ...k,
        status: "submitting",
        errors: {
          form: [],
          fields: {},
          pages: {}
        }
      }));
      try {
        const k = await a.submit({
          definition: p.definition,
          session: p.session,
          values: p.values,
          action: I
        });
        return f((F) => J({
          ...F,
          status: "ready",
          session: k.session ?? F.session,
          currentPageId: k.session?.currentPageId || k.currentPageId || F.currentPageId,
          errors: k.errors,
          lastSubmitResult: k
        })), l.emit("formie:submit:result", k), (k.currentPageId || k.nextPageId) && l.emit("formie:page:navigate", {
          currentPageId: p.currentPageId,
          nextPageId: k.nextPageId || k.currentPageId
        }), k;
      } catch (k) {
        const F = k instanceof Error ? k.message : "Submission failed.", M = {
          success: !1,
          isFinalPage: !1,
          errors: {
            form: [F],
            fields: {},
            pages: {}
          },
          messages: {
            error: F
          },
          session: p.session
        };
        return f((be) => ({
          ...be,
          status: "ready",
          errors: M.errors,
          lastSubmitResult: M
        })), l.emit("formie:submit:result", M), M;
      }
    },
    async setPage(w) {
      if (!a.setPage) {
        f((x) => J({
          ...x,
          currentPageId: w,
          session: {
            ...x.session,
            currentPageId: w
          }
        }));
        return;
      }
      f((x) => ({
        ...x,
        status: "refreshing"
      }));
      try {
        const x = await a.setPage({
          definition: p.definition,
          session: p.session,
          values: p.values,
          currentPageId: p.currentPageId,
          targetPageId: w
        });
        f((S) => J({
          ...S,
          status: "ready",
          session: x,
          currentPageId: x.currentPageId
        })), l.emit("formie:page:navigate", {
          currentPageId: p.currentPageId,
          nextPageId: w
        });
      } catch (x) {
        const S = x instanceof Error ? x.message : "Unable to change page.";
        f((I) => ({
          ...I,
          status: "ready"
        })), l.emit("formie:page:navigate:error", {
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
        f((x) => J({
          ...x,
          status: "ready",
          session: w,
          currentPageId: w.currentPageId || x.currentPageId
        })), l.emit("formie:session:refreshed", w);
      } catch (w) {
        const x = w instanceof Error ? w.message : "Unable to refresh session.";
        f((S) => ({
          ...S,
          status: "ready"
        })), l.emit("formie:session:refresh:error", {
          error: x
        });
      }
    },
    reset() {
      f((w) => J({
        ...w,
        session: s.session,
        values: { ...m },
        errors: {
          form: [],
          fields: {},
          pages: {}
        },
        currentPageId: s.session.currentPageId || s.definition.settings.initialPageId,
        lastSubmitResult: null
      })), l.emit("formie:state:reset", null);
    },
    async destroy() {
      f((w) => ({
        ...w,
        status: "destroyed"
      })), i.clear();
    },
    on(w, x) {
      return l.on(w, x);
    }
  };
  return queueMicrotask(() => {
    l.emit("formie:client:ready", T.getState());
  }), T;
}
const Mr = [
  "formie:client:ready",
  "formie:submit:result",
  "formie:page:navigate",
  "formie:page:navigate:error",
  "formie:session:refreshed",
  "formie:session:refresh:error",
  "formie:state:reset"
];
var W = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof global < "u" ? global : typeof self < "u" ? self : {};
function kr(s) {
  return s && s.__esModule && Object.prototype.hasOwnProperty.call(s, "default") ? s.default : s;
}
var fe = { exports: {} }, kt = fe.exports, vt;
function vr() {
  return vt || (vt = 1, (function(s, a) {
    (function(l, i) {
      i(a);
    })(kt, function(l) {
      function i(o, t, e) {
        return (t = (function(r) {
          var n = (function(u, c) {
            if (typeof u != "object" || !u) return u;
            var h = u[Symbol.toPrimitive];
            if (h !== void 0) {
              var d = h.call(u, c);
              if (typeof d != "object") return d;
              throw new TypeError("@@toPrimitive must return a primitive value.");
            }
            return (c === "string" ? String : Number)(u);
          })(r, "string");
          return typeof n == "symbol" ? n : n + "";
        })(t)) in o ? Object.defineProperty(o, t, { value: e, enumerable: !0, configurable: !0, writable: !0 }) : o[t] = e, o;
      }
      const m = function(o, t) {
        if (o.length === 0) return t.length;
        if (t.length === 0) return o.length;
        let e, r, n = [];
        for (e = 0; e <= t.length; e++) n[e] = [e];
        for (r = 0; r <= o.length; r++) n[0] === void 0 && (n[0] = []), n[0][r] = r;
        for (e = 1; e <= t.length; e++) for (r = 1; r <= o.length; r++) t.charAt(e - 1) === o.charAt(r - 1) ? n[e][r] = n[e - 1][r - 1] : n[e][r] = Math.min(n[e - 1][r - 1] + 1, Math.min(n[e][r - 1] + 1, n[e - 1][r] + 1));
        return n[t.length] === void 0 && (n[t.length] = []), n[t.length][o.length];
      };
      class p extends Error {
        constructor(t, e, r, n, u) {
          super(t), this.name = "SyntaxError", this.cursor = e, this.expression = r, this.subject = n, this.proposals = u;
        }
        toString() {
          let t = `${this.name}: ${this.message} around position ${this.cursor}`;
          if (this.expression && (t += ` for expression \`${this.expression}\``), t += ".", this.subject && this.proposals) {
            let e = Number.MAX_SAFE_INTEGER, r = null;
            for (let n of this.proposals) {
              let u = m(this.subject, n);
              u < e && (r = n, e = u);
            }
            r !== null && e < 3 && (t += ` Did you mean "${r}"?`);
          }
          return t;
        }
      }
      class b {
        constructor(t, e) {
          i(this, "next", () => {
            if (this.position += 1, this.tokens[this.position] === void 0) throw new p("Unexpected end of expression", this.last.cursor, this.expression);
          }), i(this, "expect", (r, n, u) => {
            let c = this.current;
            if (!c.test(r, n)) {
              let h = "";
              u && (h = u + ". ");
              let d = "";
              throw n && (d = ` with value "${n}"`), h += `Unexpected token "${c.type}" of value "${c.value}" ("${r}" expected${d})`, new p(h, c.cursor, this.expression);
            }
            this.next();
          }), i(this, "isEOF", () => f.EOF_TYPE === this.current.type), i(this, "isEqualTo", (r) => {
            if (r == null || !r instanceof b || r.tokens.length !== this.tokens.length) return !1;
            let n = r.position;
            r.position = 0;
            let u = !0;
            for (let c of this.tokens) {
              if (!r.current.isEqualTo(c)) {
                u = !1;
                break;
              }
              r.position < r.tokens.length - 1 && r.next();
            }
            return r.position = n, u;
          }), i(this, "diff", (r) => {
            let n = [];
            if (!this.isEqualTo(r)) {
              let u = 0, c = r.position;
              r.position = 0;
              for (let h of this.tokens) {
                let d = h.diff(r.current);
                d.length > 0 && n.push({ index: u, diff: d }), r.position < r.tokens.length - 1 && r.next();
              }
              r.position = c;
            }
            return n;
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
          i(this, "test", (n, u = null) => this.type === n && (u === null || this.value === u)), i(this, "isEqualTo", (n) => !(n == null || !n instanceof f) && n.value == this.value && n.type === this.type && n.cursor === this.cursor), i(this, "diff", (n) => {
            let u = [];
            return this.isEqualTo(n) || (n.value !== this.value && u.push(`Value: ${n.value} != ${this.value}`), n.cursor !== this.cursor && u.push(`Cursor: ${n.cursor} != ${this.cursor}`), n.type !== this.type && u.push(`Type: ${n.type} != ${this.type}`)), u;
          }), this.value = e, this.type = t, this.cursor = r;
        }
        toString() {
          return `${this.cursor} [${this.type}] ${this.value}`;
        }
      }
      function T(o) {
        let t = 0, e = [], r = [], n = (o = o.replace(/\r|\n|\t|\v|\f/g, " ")).length;
        for (; t < n; ) {
          if (o[t] === " ") {
            ++t;
            continue;
          }
          if (o.substr(t, 2) === "/*") {
            const c = o.indexOf("*/", t + 2);
            if (c === -1) {
              t = n;
              break;
            }
            t = c + 2;
            continue;
          }
          let u = w(o.substr(t));
          if (u !== null) {
            const c = u.length, h = u.replace(/_/g, "");
            u = h.indexOf(".") === -1 && h.indexOf("e") === -1 && h.indexOf("E") === -1 ? parseInt(h, 10) : parseFloat(h), e.push(new f(f.NUMBER_TYPE, u, t + 1)), t += c;
          } else if ("([{".indexOf(o[t]) >= 0) r.push([o[t], t]), e.push(new f(f.PUNCTUATION_TYPE, o[t], t + 1)), ++t;
          else if (")]}".indexOf(o[t]) >= 0) {
            if (r.length === 0) throw new p(`Unexpected "${o[t]}"`, t, o);
            let [c, h] = r.pop(), d = c.replace("(", ")").replace("{", "}").replace("[", "]");
            if (o[t] !== d) throw new p(`Unclosed "${c}"`, h, o);
            e.push(new f(f.PUNCTUATION_TYPE, o[t], t + 1)), ++t;
          } else {
            let c = I(o.substr(t));
            if (c !== null) e.push(new f(f.STRING_TYPE, c.captured, t + 1)), t += c.length;
            else if (o.substr(t, 2) === "\\\\") e.push(new f(f.PUNCTUATION_TYPE, "\\", t + 1)), t += 2;
            else {
              const h = e.length > 0 ? e[e.length - 1] : null;
              if (h && h.type === f.PUNCTUATION_TYPE && (h.value === "." || h.value === "?.")) {
                let d = be(o.substr(t));
                if (d) e.push(new f(f.NAME_TYPE, d, t + 1)), t += d.length;
                else {
                  let g = M(o.substr(t));
                  if (g) e.push(new f(f.OPERATOR_TYPE, g, t + 1)), t += g.length;
                  else if (o.substr(t, 2) === "?." || o.substr(t, 2) === "??") e.push(new f(f.PUNCTUATION_TYPE, o.substr(t, 2), t + 1)), t += 2;
                  else {
                    if (!(".,?:".indexOf(o[t]) >= 0)) throw new p(`Unexpected character "${o[t]}"`, t, o);
                    e.push(new f(f.PUNCTUATION_TYPE, o[t], t + 1)), ++t;
                  }
                }
              } else {
                let d = M(o.substr(t));
                if (d) e.push(new f(f.OPERATOR_TYPE, d, t + 1)), t += d.length;
                else if (o.substr(t, 2) === "?." || o.substr(t, 2) === "??") e.push(new f(f.PUNCTUATION_TYPE, o.substr(t, 2), t + 1)), t += 2;
                else if (".,?:".indexOf(o[t]) >= 0) e.push(new f(f.PUNCTUATION_TYPE, o[t], t + 1)), ++t;
                else {
                  let g = be(o.substr(t));
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
        return new b(o, e);
      }
      function w(o) {
        let t = null, e = o.match(/^(?:((?:\d(?:_?\d)*)\.(?:\d(?:_?\d)*)|\.(?:\d(?:_?\d)*)|(?:\d(?:_?\d)*))(?:[eE][+-]?\d(?:_?\d)*)?)/);
        return e && e.length > 0 && (t = e[0]), t;
      }
      i(f, "EOF_TYPE", "end of expression"), i(f, "NAME_TYPE", "name"), i(f, "NUMBER_TYPE", "number"), i(f, "STRING_TYPE", "string"), i(f, "OPERATOR_TYPE", "operator"), i(f, "PUNCTUATION_TYPE", "punctuation");
      const x = /^"([^"\\]*(?:\\.[^"\\]*)*)"|'([^'\\]*(?:\\.[^'\\]*)*)'/s;
      function S(o, t) {
        return t === '"' ? o = o.replace(/\\\"/g, '"') : t === "'" && (o = o.replace(/\\'/g, "'")), o = o.replace(/\\\\/g, "\\");
      }
      function I(o) {
        let t = null;
        if (["'", '"'].indexOf(o.substr(0, 1)) === -1) return t;
        let e = x.exec(o);
        return e !== null && e.length > 0 && (t = e[1] !== void 0 ? { captured: S(e[1], '"') } : { captured: S(e[2], "'") }, t.length = e[0].length), t;
      }
      const k = ["&&", "and", "||", "or", "+", "-", "**", "*", "/", "%", "&", "|", "^", ">>", "<<", "===", "!==", "!=", "==", "<=", ">=", "<", ">", "contains", "matches", "starts with", "ends with", "not in", "in", "not", "!", "xor", "~", ".."], F = ["and", "or", "matches", "contains", "starts with", "ends with", "not in", "in", "not", "xor"];
      function M(o) {
        let t = null;
        for (let e of k) if (o.substr(0, e.length) === e) {
          F.indexOf(e) >= 0 ? o.substr(0, e.length + 1) === e + " " && (t = e) : t = e;
          break;
        }
        return t;
      }
      function be(o) {
        let t = null, e = o.match(/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/);
        return e && e.length > 0 && (t = e[0]), t;
      }
      function Dt(o) {
        return /boolean|number|string/.test(typeof o);
      }
      function We(o, t) {
        var e = "", r = [], n = 0, u = 0, c = "", h = "", d = "", g = "", y = "", E = 0, v = 0, G = 0, q = 0, dt = 0, Ne = [], Le = "", ft = /%([\dA-Fa-f]+)/g, mt = function(Ee, pt) {
          return (Ee += "").length < pt ? new Array(++pt - Ee.length).join("0") + Ee : Ee;
        };
        for (n = 0; n < t.length; n++) if (c = t.charAt(n), h = t.charAt(n + 1), c === "\\" && h && /\d/.test(h)) {
          if (q = n + (G = (d = t.slice(n + 1).match(/^\d+/)[0]).length) + 1, t.charAt(q) + t.charAt(q + 1) === "..") {
            if (E = d.charCodeAt(0), /\\\d/.test(t.charAt(q + 2) + t.charAt(q + 3))) g = t.slice(q + 3).match(/^\d+/)[0], n += 1;
            else {
              if (!t.charAt(q + 2)) throw new Error("Range with no end point");
              g = t.charAt(q + 2);
            }
            if ((v = g.charCodeAt(0)) > E) for (u = E; u <= v; u++) r.push(String.fromCharCode(u));
            else r.push(".", d, g);
            n += g.length + 2;
          } else y = String.fromCharCode(parseInt(d, 8)), r.push(y);
          n += G;
        } else if (h + t.charAt(n + 2) === "..") {
          if (E = (d = c).charCodeAt(0), /\\\d/.test(t.charAt(n + 3) + t.charAt(n + 4))) g = t.slice(n + 4).match(/^\d+/)[0], n += 1;
          else {
            if (!t.charAt(n + 3)) throw new Error("Range with no end point");
            g = t.charAt(n + 3);
          }
          if ((v = g.charCodeAt(0)) > E) for (u = E; u <= v; u++) r.push(String.fromCharCode(u));
          else r.push(".", d, g);
          n += g.length + 2;
        } else r.push(c);
        for (n = 0; n < o.length; n++) if (c = o.charAt(n), r.indexOf(c) !== -1) if (e += "\\", (dt = c.charCodeAt(0)) < 32 || dt > 126) switch (c) {
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
            for (Le = encodeURIComponent(c), (Ne = ft.exec(Le)) !== null && (e += mt(parseInt(Ne[1], 16).toString(8), 3)); (Ne = ft.exec(Le)) !== null; ) e += "\\" + mt(parseInt(Ne[1], 16).toString(8), 3);
        }
        else e += c;
        else e += c;
        return e;
      }
      class R {
        constructor(t = {}, e = {}) {
          i(this, "compile", (r) => {
            for (let n of Object.values(this.nodes)) n.compile(r);
          }), i(this, "evaluate", (r, n) => {
            let u = [];
            for (let c of Object.values(this.nodes)) u.push(c.evaluate(r, n));
            return u;
          }), i(this, "toArray", () => {
            throw new Error(`Dumping a "${this.name}" instance is not supported yet.`);
          }), i(this, "dump", () => {
            let r = "";
            for (let n of this.toArray()) r += Dt(n) ? n : n.dump();
            return r;
          }), i(this, "dumpString", (r) => `"${We(r, '\0	"\\')}"`), i(this, "isHash", (r) => {
            let n = 0;
            for (let u of Object.keys(r)) if (u = parseInt(u), u !== n++) return !0;
            return !1;
          }), this.name = "Node", this.nodes = t, this.attributes = e;
        }
        toString() {
          let t = [];
          for (let r of Object.keys(this.attributes)) {
            let n = "null";
            this.attributes[r] && (n = this.attributes[r].toString()), t.push(`${r}: '${n}'`);
          }
          let e = [this.name + "(" + t.join(", ")];
          if (this.nodes.length > 0) {
            for (let r of Object.values(this.nodes)) {
              let n = r.toString().split(`
`);
              for (let u of n) e.push("    " + u);
            }
            e.push(")");
          } else e[0] += ")";
          return e.join(`
`);
        }
      }
      class $ extends R {
        constructor(t, e, r) {
          super({ left: e, right: r }, { operator: t }), i(this, "compile", (n) => {
            let u = this.attributes.operator;
            u !== "matches" ? u !== "contains" ? u !== "starts with" ? u !== "ends with" ? $.functions[u] === void 0 ? ($.operators[u] !== void 0 && (u = $.operators[u]), n.raw("(").compile(this.nodes.left).raw(" ").raw(u).raw(" ").compile(this.nodes.right).raw(")")) : n.raw(`${$.functions[u]}(`).compile(this.nodes.left).raw(", ").compile(this.nodes.right).raw(")") : n.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().endsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())") : n.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().startsWith(").compile(this.nodes.right).raw(".toString().toLowerCase())") : n.raw("(").compile(this.nodes.left).raw(".toString().toLowerCase().includes(").compile(this.nodes.right).raw(".toString().toLowerCase())") : n.compile(this.nodes.right).raw(".test(").compile(this.nodes.left).raw(")");
          }), i(this, "evaluate", (n, u) => {
            let c = this.attributes.operator, h = this.nodes.left.evaluate(n, u);
            if ($.functions[c] !== void 0) {
              let g = this.nodes.right.evaluate(n, u);
              switch (c) {
                case "not in":
                  return g.indexOf(h) === -1;
                case "in":
                  return g.indexOf(h) >= 0;
                case "..":
                  return (function(y, E) {
                    let v = [];
                    for (let G = y; G <= E; G++) v.push(G);
                    return v;
                  })(h, g);
                case "**":
                  return Math.pow(h, g);
              }
            }
            let d = null;
            switch (c) {
              case "or":
              case "||":
                return h || (d = this.nodes.right.evaluate(n, u)), h || d;
              case "and":
              case "&&":
                return h && (d = this.nodes.right.evaluate(n, u)), h && d;
              case "xor":
                return d = this.nodes.right.evaluate(n, u), d && !h || h && !d;
              case "<<":
                return d = this.nodes.right.evaluate(n, u), h << d;
              case ">>":
                return d = this.nodes.right.evaluate(n, u), h >> d;
            }
            switch (d = this.nodes.right.evaluate(n, u), c) {
              case "|":
                return h | d;
              case "^":
                return h ^ d;
              case "&":
                return h & d;
              case "==":
                return h == d;
              case "===":
                return h === d;
              case "!=":
                return h != d;
              case "!==":
                return h !== d;
              case "<":
                return h < d;
              case ">":
                return h > d;
              case ">=":
                return h >= d;
              case "<=":
                return h <= d;
              case "not in":
                return d.indexOf(h) === -1;
              case "in":
                return d.indexOf(h) >= 0;
              case "+":
                return h + d;
              case "-":
                return h - d;
              case "~":
                return h.toString() + d.toString();
              case "*":
                return h * d;
              case "/":
                return h / d;
              case "%":
                return h % d;
              case "matches":
                if (h == null) return !1;
                let g = d.match($.regex_expression);
                return new RegExp(g[1], g[2]).test(h);
              case "contains":
                return h.toString().toLowerCase().includes(d.toString().toLowerCase());
              case "starts with":
                return h.toString().toLowerCase().startsWith(d.toString().toLowerCase());
              case "ends with":
                return h.toString().toLowerCase().endsWith(d.toString().toLowerCase());
            }
          }), i(this, "toArray", () => ["(", this.nodes.left, " " + this.attributes.operator + " ", this.nodes.right, ")"]), this.name = "BinaryNode";
        }
      }
      i($, "regex_expression", /\/(.+)\/(.*)/), i($, "operators", { "~": ".", and: "&&", or: "||", xor: "xor", "<<": "<<", ">>": ">>" }), i($, "functions", { "**": "Math.pow", "..": "range", in: "includes", "not in": "!includes" });
      class ae extends R {
        constructor(t, e) {
          super({ node: e }, { operator: t }), i(this, "compile", (r) => {
            r.raw("(").raw(ae.operators[this.attributes.operator]).compile(this.nodes.node).raw(")");
          }), i(this, "evaluate", (r, n) => {
            let u = this.nodes.node.evaluate(r, n);
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
      i(ae, "operators", { "!": "!", not: "!", "+": "+", "-": "-", "~": "~" });
      class P extends R {
        constructor(t, e = !1, r = !1) {
          super({}, { value: t }), i(this, "compile", (n) => {
            n.repr(this.attributes.value, this.isIdentifier);
          }), i(this, "evaluate", (n, u) => this.attributes.value), i(this, "toArray", () => {
            let n = [], u = this.attributes.value;
            if (this.isIdentifier) n.push(u);
            else if (u === !0) n.push("true");
            else if (u === !1) n.push("false");
            else if (u === null) n.push("null");
            else if (typeof u == "number") n.push(u);
            else if (typeof u == "string") n.push(this.dumpString(u));
            else if (Array.isArray(u)) {
              for (let c of u) n.push(","), n.push(new P(c));
              n[0] = "[", n.push("]");
            } else if (this.isHash(u)) {
              for (let c of Object.keys(u)) n.push(", "), n.push(new P(c)), n.push(": "), n.push(new P(u[c]));
              n[0] = "{", n.push("}");
            }
            return n;
          }), this.isIdentifier = e, this.isNullSafe = r, this.name = "ConstantNode";
        }
      }
      class Ce extends R {
        constructor(t, e, r) {
          super({ expr1: t, expr2: e, expr3: r }), i(this, "compile", (n) => {
            n.raw("((").compile(this.nodes.expr1).raw(") ? (").compile(this.nodes.expr2).raw(") : (").compile(this.nodes.expr3).raw("))");
          }), i(this, "evaluate", (n, u) => this.nodes.expr1.evaluate(n, u) ? this.nodes.expr2.evaluate(n, u) : this.nodes.expr3.evaluate(n, u)), i(this, "toArray", () => ["(", this.nodes.expr1, " ? ", this.nodes.expr2, " : ", this.nodes.expr3, ")"]), this.name = "ConditionalNode";
        }
      }
      class Be extends R {
        constructor(t, e) {
          super({ fnArguments: e }, { name: t }), i(this, "compile", (r) => {
            let n = [];
            for (let c of Object.values(this.nodes.fnArguments.nodes)) n.push(r.subcompile(c));
            let u = r.getFunction(this.attributes.name);
            r.raw(u.compiler.apply(null, n));
          }), i(this, "evaluate", (r, n) => {
            let u = [n];
            for (let c of Object.values(this.nodes.fnArguments.nodes)) u.push(c.evaluate(r, n));
            return r[this.attributes.name].evaluator.apply(null, u);
          }), i(this, "toArray", () => {
            let r = [];
            r.push(this.attributes.name);
            for (let n of Object.values(this.nodes.fnArguments.nodes)) r.push(", "), r.push(n);
            return r[1] = "(", r.push(")"), r;
          }), this.name = "FunctionNode";
        }
      }
      class Ge extends R {
        constructor(t) {
          super({}, { name: t }), i(this, "compile", (e) => {
            e.raw(this.attributes.name);
          }), i(this, "evaluate", (e, r) => r[this.attributes.name]), i(this, "toArray", () => [this.attributes.name]), this.name = "NameNode";
        }
      }
      class we extends R {
        constructor() {
          super(), i(this, "addElement", (t, e = null) => {
            e === null ? e = new P(++this.index) : this.type === "Array" && (this.type = "Object"), this.nodes[(++this.keyIndex).toString()] = e, this.nodes[(++this.keyIndex).toString()] = t;
          }), i(this, "compile", (t) => {
            this.type === "Object" ? t.raw("{") : t.raw("["), this.compileArguments(t, this.type !== "Array"), this.type === "Object" ? t.raw("}") : t.raw("]");
          }), i(this, "evaluate", (t, e) => {
            let r;
            if (this.type === "Array") {
              r = [];
              for (let n of this.getKeyValuePairs()) r.push(n.value.evaluate(t, e));
            } else {
              r = {};
              for (let n of this.getKeyValuePairs()) r[n.key.evaluate(t, e)] = n.value.evaluate(t, e);
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
            let t, e, r, n = [], u = Object.values(this.nodes);
            for (t = 0, e = u.length; t < e; t += 2) r = u.slice(t, t + 2), n.push({ key: r[0], value: r[1] });
            return n;
          }), i(this, "compileArguments", (t, e = !0) => {
            let r = !0;
            for (let n of this.getKeyValuePairs()) r || t.raw(", "), r = !1, e && t.compile(n.key).raw(": "), t.compile(n.value);
          }), this.name = "ArrayNode", this.type = "Array", this.index = -1, this.keyIndex = -1;
        }
      }
      class Ie extends we {
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
      class A extends R {
        constructor(t, e, r, n) {
          super({ node: t, attribute: e, fnArguments: r }, { type: n, is_null_coalesce: !1, is_short_circuited: !1 }), i(this, "compile", (u) => {
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
                let h = this.nodes.node.evaluate(u, c);
                if (h === null && (this.nodes.attribute.isNullSafe || this.attributes.is_null_coalesce)) return this.attributes.is_short_circuited = !0, null;
                if (h === null && this.isShortCircuited()) return null;
                if (typeof h != "object") throw new Error(`Unable to get property "${d}" on a non-object: ` + typeof h);
                let d = this.nodes.attribute.attributes.value;
                return this.attributes.is_null_coalesce ? h[d] ?? null : h[d];
              case A.METHOD_CALL:
                let g = this.nodes.node.evaluate(u, c);
                if (g === null && this.nodes.attribute.isNullSafe) return this.attributes.is_short_circuited = !0, null;
                if (g === null && this.isShortCircuited()) return null;
                let y = this.nodes.attribute.attributes.value;
                if (typeof g != "object") throw new Error(`Unable to call method "${y}" on a non-object: ` + typeof g);
                if (g[y] === void 0) throw new Error(`Method "${y}" is undefined on object.`);
                if (typeof g[y] != "function") throw new Error(`Method "${y}" is not a function on object.`);
                let E = this.nodes.fnArguments.evaluate(u, c);
                return g[y].apply(null, E);
              case A.ARRAY_CALL:
                let v = this.nodes.node.evaluate(u, c);
                if (v === null && this.isShortCircuited()) return null;
                if (!(Array.isArray(v) || typeof v == "object" || v === null && this.attributes.is_null_coalesce)) throw new Error("Unable to get an item on a non-array: " + typeof v);
                return this.attributes.is_null_coalesce ? v ? v[this.nodes.attribute.evaluate(u, c)] ?? null : null : v[this.nodes.attribute.evaluate(u, c)];
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
      class qe extends R {
        constructor(t, e) {
          super({ expr1: t, expr2: e }), i(this, "compile", (r) => {
            r.raw("((").compile(this.nodes.expr1).raw(") ?? (").compile(this.nodes.expr2).raw("))");
          }), i(this, "evaluate", (r, n) => (this.nodes.expr1 instanceof A && this._addNullCoalesceAttributeToGetAttrNodes(this.nodes.expr1), this.nodes.expr1.evaluate(r, n) ?? this.nodes.expr2.evaluate(r, n))), i(this, "toArray", () => ["(", this.nodes.expr1, ") ?? (", this.nodes.expr2, ")"]), i(this, "_addNullCoalesceAttributeToGetAttrNodes", (r) => {
            if (!(!r instanceof A)) {
              r.attributes.is_null_coalesce = !0;
              for (let n of Object.values(r.nodes)) this._addNullCoalesceAttributeToGetAttrNodes(n);
            }
          }), this.name = "NullCoalesceNode";
        }
      }
      class Je extends R {
        constructor(t) {
          super({}, { name: t }), i(this, "compile", (e) => {
            e.raw(this.attributes.name + " ?? null");
          }), i(this, "evaluate", (e, r) => null), i(this, "toArray", () => [this.attributes.name + " ?? null"]), this.name = "NullCoalescedNameNode";
        }
      }
      class Ke {
        constructor(t = {}) {
          i(this, "functions", {}), i(this, "unaryOperators", { not: { precedence: 50 }, "!": { precedence: 50 }, "-": { precedence: 500 }, "+": { precedence: 500 }, "~": { precedence: 500 } }), i(this, "binaryOperators", { or: { precedence: 10, associativity: 1 }, "||": { precedence: 10, associativity: 1 }, xor: { precedence: 12, associativity: 1 }, and: { precedence: 15, associativity: 1 }, "&&": { precedence: 15, associativity: 1 }, "|": { precedence: 16, associativity: 1 }, "^": { precedence: 17, associativity: 1 }, "&": { precedence: 18, associativity: 1 }, "==": { precedence: 20, associativity: 1 }, "===": { precedence: 20, associativity: 1 }, "!=": { precedence: 20, associativity: 1 }, "!==": { precedence: 20, associativity: 1 }, "<": { precedence: 20, associativity: 1 }, ">": { precedence: 20, associativity: 1 }, ">=": { precedence: 20, associativity: 1 }, "<=": { precedence: 20, associativity: 1 }, "not in": { precedence: 20, associativity: 1 }, in: { precedence: 20, associativity: 1 }, matches: { precedence: 20, associativity: 1 }, contains: { precedence: 20, associativity: 1 }, "starts with": { precedence: 20, associativity: 1 }, "ends with": { precedence: 20, associativity: 1 }, "..": { precedence: 25, associativity: 1 }, "<<": { precedence: 25, associativity: 1 }, ">>": { precedence: 25, associativity: 1 }, "+": { precedence: 30, associativity: 1 }, "-": { precedence: 30, associativity: 1 }, "~": { precedence: 40, associativity: 1 }, "*": { precedence: 60, associativity: 1 }, "/": { precedence: 60, associativity: 1 }, "%": { precedence: 60, associativity: 1 }, "**": { precedence: 200, associativity: 2 } }), i(this, "parse", (e, r = [], n = 0) => {
            this.tokenStream = e, this.names = r, this.objectMatches = {}, this.cachedNames = null, this.nestedExecutions = 0, this.flags = n;
            let u = this.parseExpression();
            if (!this.tokenStream.isEOF()) throw new p(`Unexpected token "${this.tokenStream.current.type}" of value "${this.tokenStream.current.value}"`, this.tokenStream.current.cursor, this.tokenStream.expression);
            return u;
          }), i(this, "lint", (e, r = [], n = 0) => {
            r === null && (console.log('Deprecated: passing "null" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument'), n |= 1, r = []), this.parse(e, r, n);
          }), i(this, "parseExpression", (e = 0) => {
            let r = this.getPrimary(), n = this.tokenStream.current;
            if (this.nestedExecutions++, this.nestedExecutions > 1e3) throw new Error("Way to many executions on '" + n.toString() + "' of '" + this.tokenStream.toString() + "'");
            for (; n.test(f.OPERATOR_TYPE) && this.binaryOperators[n.value] !== void 0 && this.binaryOperators[n.value] !== null && this.binaryOperators[n.value].precedence >= e; ) {
              let u = this.binaryOperators[n.value];
              this.tokenStream.next();
              let c = this.parseExpression(u.associativity === 1 ? u.precedence + 1 : u.precedence);
              r = new $(n.value, r, c), n = this.tokenStream.current;
            }
            return e === 0 ? this.parseConditionalExpression(r) : r;
          }), i(this, "getPrimary", () => {
            let e = this.tokenStream.current;
            if (e.test(f.OPERATOR_TYPE) && this.unaryOperators[e.value] !== void 0 && this.unaryOperators[e.value] !== null) {
              let r = this.unaryOperators[e.value];
              this.tokenStream.next();
              let n = this.parseExpression(r.precedence);
              return this.parsePostfixExpression(new ae(e.value, n));
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
              for (let n of this.names) typeof n == "object" ? (this.objectMatches[Object.values(n)[0]] = r, e.push(Object.keys(n)[0]), e.push(Object.values(n)[0])) : e.push(n), r++;
              return this.cachedNames = e, e;
            }
            return [];
          }), i(this, "parseArrayExpression", () => {
            this.tokenStream.expect(f.PUNCTUATION_TYPE, "[", "An array element was expected");
            let e = new we(), r = !0;
            for (; !this.tokenStream.current.test(f.PUNCTUATION_TYPE, "]") && (r || (this.tokenStream.expect(f.PUNCTUATION_TYPE, ",", "An array element must be followed by a comma"), !this.tokenStream.current.test(f.PUNCTUATION_TYPE, "]"))); ) r = !1, e.addElement(this.parseExpression());
            return this.tokenStream.expect(f.PUNCTUATION_TYPE, "]", "An opened array is not properly closed"), e;
          }), i(this, "parseHashExpression", () => {
            this.tokenStream.expect(f.PUNCTUATION_TYPE, "{", "A hash element was expected");
            let e = new we(), r = !0;
            for (; !this.tokenStream.current.test(f.PUNCTUATION_TYPE, "}") && (r || (this.tokenStream.expect(f.PUNCTUATION_TYPE, ",", "A hash value must be followed by a comma"), !this.tokenStream.current.test(f.PUNCTUATION_TYPE, "}"))); ) {
              r = !1;
              let n = null;
              if (this.tokenStream.current.test(f.STRING_TYPE) || this.tokenStream.current.test(f.NAME_TYPE) || this.tokenStream.current.test(f.NUMBER_TYPE)) n = new P(this.tokenStream.current.value), this.tokenStream.next();
              else {
                if (!this.tokenStream.current.test(f.PUNCTUATION_TYPE, "(")) {
                  let c = this.tokenStream.current;
                  throw new p(`A hash key must be a quoted string, a number, a name, or an expression enclosed in parentheses (unexpected token "${c.type}" of value "${c.value}"`, c.cursor, this.tokenStream.expression);
                }
                n = this.parseExpression();
              }
              this.tokenStream.expect(f.PUNCTUATION_TYPE, ":", "A hash key must be followed by a colon (:)");
              let u = this.parseExpression();
              e.addElement(u, n);
            }
            return this.tokenStream.expect(f.PUNCTUATION_TYPE, "}", "An opened hash is not properly closed"), e;
          }), i(this, "parsePostfixExpression", (e) => {
            let r = this.tokenStream.current;
            for (; f.PUNCTUATION_TYPE === r.type; ) {
              if (r.value === "." || r.value === "?.") {
                const n = r.value === "?.";
                if (this.tokenStream.next(), r = this.tokenStream.current, this.tokenStream.next(), f.NAME_TYPE !== r.type && (f.OPERATOR_TYPE !== r.type || !/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/.test(r.value))) throw new p("Expected name", r.cursor, this.tokenStream.expression);
                let u = new P(r.value, !0, n), c = new Ie(), h = null;
                if (this.tokenStream.current.test(f.PUNCTUATION_TYPE, "(")) {
                  h = A.METHOD_CALL;
                  for (let d of Object.values(this.parseArguments().nodes)) c.addElement(d);
                } else h = A.PROPERTY_CALL;
                e = new A(e, u, c, h);
              } else {
                if (r.value !== "[") break;
                {
                  this.tokenStream.next();
                  let n = this.parseExpression();
                  this.tokenStream.expect(f.PUNCTUATION_TYPE, "]"), e = new A(e, n, new Ie(), A.ARRAY_CALL);
                }
              }
              r = this.tokenStream.current;
            }
            return e;
          }), i(this, "parseArguments", () => {
            let e = [];
            for (this.tokenStream.expect(f.PUNCTUATION_TYPE, "(", "A list of arguments must begin with an opening parenthesis"); !this.tokenStream.current.test(f.PUNCTUATION_TYPE, ")"); ) e.length !== 0 && this.tokenStream.expect(f.PUNCTUATION_TYPE, ",", "Arguments must be separated by a comma"), e.push(this.parseExpression());
            return this.tokenStream.expect(f.PUNCTUATION_TYPE, ")", "A list of arguments must be closed by a parenthesis"), new R(e);
          }), this.functions = t, this.tokenStream = null, this.names = null, this.objectMatches = {}, this.cachedNames = null, this.nestedExecutions = 0, this.flags = 0;
        }
        parseConditionalExpression(t) {
          for (; this.tokenStream.current.test(f.PUNCTUATION_TYPE, "??"); ) {
            this.tokenStream.next();
            let e = this.parseExpression();
            t = new qe(t, e);
          }
          for (; this.tokenStream.current.test(f.PUNCTUATION_TYPE, "?"); ) {
            let e, r;
            this.tokenStream.next(), this.tokenStream.current.test(f.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), e = t, r = this.parseExpression()) : (e = this.parseExpression(), this.tokenStream.current.test(f.PUNCTUATION_TYPE, ":") ? (this.tokenStream.next(), r = this.parseExpression()) : e instanceof P && typeof e.attributes?.value == "string" ? r = new P("") : e instanceof Ce ? (r = e.nodes.expr3, e = e.nodes.expr2) : (r = e, e = t)), t = new Ce(t, e, r);
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
                    e = new Be(t.value, this.parseArguments());
                  } else {
                    let r = null;
                    if (1 & this.flags) r = t.value;
                    else {
                      if (!this.hasVariable(t.value)) {
                        if (this.tokenStream.current.test(f.PUNCTUATION_TYPE, "??")) return new Je(t.value);
                        throw new p(`Variable "${t.value}" is not valid`, t.cursor, this.tokenStream.expression, t.value, this.getNames());
                      }
                      r = t.value, this.objectMatches[r] !== void 0 && (r = this.getNames()[this.objectMatches[r]]);
                    }
                    e = new Ge(r);
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
      class Ze {
        constructor(t) {
          i(this, "getFunction", (e) => this.functions[e]), i(this, "getSource", () => this.source), i(this, "reset", () => (this.source = "", this)), i(this, "compile", (e) => (e.compile(this), this)), i(this, "subcompile", (e) => {
            let r = this.source;
            this.source = "", e.compile(this);
            let n = this.source;
            return this.source = r, n;
          }), i(this, "raw", (e) => (this.source += e, this)), i(this, "string", (e) => (this.source += '"' + We(e, '\0	"$\\') + '"', this)), i(this, "repr", (e, r = !1) => {
            if (r) this.raw(e);
            else if (Number.isInteger(e) || +e === e && (!isFinite(e) || e % 1)) this.raw(e);
            else if (e === null) this.raw("null");
            else if (typeof e == "boolean") this.raw(e ? "true" : "false");
            else if (typeof e == "object") {
              this.raw("{");
              let n = !0;
              for (let u of Object.keys(e)) n || this.raw(", "), n = !1, this.repr(u), this.raw(":"), this.repr(e[u]);
              this.raw("}");
            } else if (Array.isArray(e)) {
              this.raw("[");
              let n = !0;
              for (let u of e) n || this.raw(", "), n = !1, this.repr(u);
              this.raw("]");
            } else this.string(e);
            return this;
          }), this.source = "", this.functions = t;
        }
      }
      class Mt {
        constructor(t) {
          this.expression = t;
        }
        toString() {
          return this.expression;
        }
      }
      class oe extends Mt {
        constructor(t, e) {
          super(t), i(this, "getNodes", () => this.nodes), this.nodes = e;
        }
        static fromJSON(t) {
          const e = typeof t == "string" ? JSON.parse(t) : t, r = (c) => {
            if (c == null || c instanceof R || typeof c != "object" || !c.name) return c;
            switch (c.name) {
              case "ConstantNode":
                return new P(c.attributes?.value, !!c.isIdentifier, !!c.isNullSafe);
              case "NameNode":
                return new Ge(c.attributes?.name);
              case "NullCoalescedNameNode":
                return new Je(c.attributes?.name);
              case "UnaryNode":
                return new ae(c.attributes?.operator, r(c.nodes?.node));
              case "BinaryNode":
                return new $(c.attributes?.operator, r(c.nodes?.left), r(c.nodes?.right));
              case "ConditionalNode":
                return new Ce(r(c.nodes?.expr1), r(c.nodes?.expr2), r(c.nodes?.expr3));
              case "NullCoalesceNode":
                return new qe(r(c.nodes?.expr1), r(c.nodes?.expr2));
              case "ArgumentsNode": {
                const h = new Ie();
                typeof c.type == "string" && (h.type = c.type), typeof c.index == "number" && (h.index = c.index), typeof c.keyIndex == "number" && (h.keyIndex = c.keyIndex), h.nodes = {};
                for (const d of Object.keys(c.nodes || {})) h.nodes[d] = r(c.nodes[d]);
                return h;
              }
              case "ArrayNode": {
                const h = new we();
                typeof c.type == "string" && (h.type = c.type), typeof c.index == "number" && (h.index = c.index), typeof c.keyIndex == "number" && (h.keyIndex = c.keyIndex), h.nodes = {};
                for (const d of Object.keys(c.nodes || {})) h.nodes[d] = r(c.nodes[d]);
                return h;
              }
              case "FunctionNode": {
                const h = r(c.nodes?.arguments);
                return new Be(c.attributes?.name, h);
              }
              case "GetAttrNode": {
                const h = new A(r(c.nodes?.node), r(c.nodes?.attribute), r(c.nodes?.fnArguments), c.attributes?.type);
                return c.attributes && typeof c.attributes.is_null_coalesce == "boolean" && (h.attributes.is_null_coalesce = c.attributes.is_null_coalesce), c.attributes && typeof c.attributes.is_short_circuited == "boolean" && (h.attributes.is_short_circuited = c.attributes.is_short_circuited), h;
              }
              case "Node": {
                const h = new R();
                if (Array.isArray(c.nodes)) h.nodes = c.nodes.map(r);
                else {
                  h.nodes = {};
                  for (const d of Object.keys(c.nodes || {})) h.nodes[d] = r(c.nodes[d]);
                }
                return h.attributes = c.attributes || {}, h;
              }
              default: {
                const h = new R();
                if (h.name = c.name, Array.isArray(c.nodes)) h.nodes = c.nodes.map(r);
                else {
                  h.nodes = {};
                  for (const d of Object.keys(c.nodes || {})) h.nodes[d] = r(c.nodes[d]);
                }
                return h.attributes = c.attributes || {}, h;
              }
            }
          }, n = e.expression, u = ((c) => {
            if (c == null) return c;
            if (c.name) return r(c);
            if (Array.isArray(c)) return c.map(r);
            if (typeof c == "object") {
              const h = {};
              for (const d of Object.keys(c)) h[d] = r(c[d]);
              return h;
            }
            return c;
          })(e.nodes);
          return new oe(n, u);
        }
      }
      var Xe;
      class Qe {
        constructor(t = 0) {
          i(this, "createCacheItem", (e, r, n) => {
            let u = new Y();
            return u.key = e, u.value = r, u.isHit = n, u.defaultLifetime = this.defaultLifetime, u;
          }), i(this, "get", (e, r, n = null, u = null) => {
            let c = this.getItem(e);
            return c.isHit || this.save(c.set(r(c, !0))), c.get();
          }), i(this, "getItem", (e) => {
            let r = this.hasItem(e), n = null;
            return r ? n = this.values[e] : this.values[e] = null, (0, this.createCacheItem)(e, n, r);
          }), i(this, "getItems", (e) => {
            for (let r of e) typeof r == "string" || this.expiries[r] || Y.validateKey(r);
            return this.generateItems(e, (/* @__PURE__ */ new Date()).getTime() / 1e3, this.createCacheItem);
          }), i(this, "deleteItems", (e) => {
            for (let r of e) this.deleteItem(r);
            return !0;
          }), i(this, "save", (e) => !(!e instanceof Y) && (e.expiry !== null && e.expiry <= (/* @__PURE__ */ new Date()).getTime() / 1e3 ? (this.deleteItem(e.key), !0) : (e.expiry === null && 0 < e.defaultLifetime && (e.expiry = (/* @__PURE__ */ new Date()).getTime() / 1e3 + e.defaultLifetime), this.values[e.key] = e.value, this.expiries[e.key] = e.expiry || Number.MAX_SAFE_INTEGER, !0))), i(this, "saveDeferred", (e) => this.save(e)), i(this, "commit", () => !0), i(this, "delete", (e) => this.deleteItem(e)), i(this, "getValues", () => this.values), i(this, "hasItem", (e) => !!(typeof e == "string" && this.expiries[e] && this.expiries[e] > (/* @__PURE__ */ new Date()).getTime() / 1e3) || (Y.validateKey(e), !!this.expiries[e] && !this.deleteItem(e))), i(this, "clear", () => (this.values = {}, this.expiries = {}, !0)), i(this, "deleteItem", (e) => (typeof e == "string" && this.expiries[e] || Y.validateKey(e), delete this.values[e], delete this.expiries[e], !0)), i(this, "reset", () => {
            this.clear();
          }), i(this, "generateItems", (e, r, n) => {
            let u = [];
            for (let c of e) {
              let h = null, d = !!this.expiries[c];
              d || !(this.expiries[c] > r) && this.deleteItem(c) ? h = this.values[c] : this.values[c] = null, u[c] = n(c, h, d);
            }
            return u;
          }), this.defaultLifetime = t, this.values = {}, this.expiries = {};
        }
      }
      class Y {
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
      Xe = Y, i(Y, "METADATA_EXPIRY_OFFSET", 1527506807), i(Y, "RESERVED_CHARACTERS", ["{", "}", "(", ")", "/", "\\", "@", ":"]), i(Y, "validateKey", (o) => {
        if (typeof o != "string") throw new Error(`Cache key must be string, "${typeof o}" given.`);
        if (o === "") throw new Error("Cache key length must be greater than zero");
        for (let t of Xe.RESERVED_CHARACTERS) if (o.indexOf(t) >= 0) throw new Error(`Cache key "${o}" contains reserved character "${t}".`);
        return o;
      });
      class Yt extends Error {
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
          const r = t.replace(/^\/+/, ""), n = r.split(".");
          let u = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof W < "u" ? W : {};
          for (const c of n) {
            if (u == null) break;
            u = u[c];
          }
          if (typeof u != "function") throw new Error(`JavaScript function "${r}" does not exist.`);
          if (!e && n.length > 1) throw new Error(`An expression function name must be defined when JavaScript function "${r}" is namespaced.`);
          return new this(e || n[n.length - 1], (...c) => `${r}(${c.join(", ")})`, (c, ...h) => u(...h));
        }
      }
      class et {
        constructor(t = null, e = []) {
          i(this, "compile", (r, n = []) => this.getCompiler().compile(this.parse(r, n).getNodes()).getSource()), i(this, "evaluate", (r, n = {}) => this.parse(r, Object.keys(n)).getNodes().evaluate(this.functions, n)), i(this, "parse", (r, n, u = 0) => {
            if (r instanceof oe) return r;
            n.sort((g, y) => {
              let E = g, v = y;
              return typeof g == "object" && (E = Object.values(g)[0]), typeof y == "object" && (v = Object.values(y)[0]), E.localeCompare(v);
            });
            let c = [];
            for (let g of n) {
              let y = g;
              typeof g == "object" && (y = Object.keys(g)[0] + ":" + Object.values(g)[0]), c.push(y);
            }
            let h = this.cache.getItem(this.fixedEncodeURIComponent(r + "//" + c.join("|"))), d = h.get();
            if (d === null) {
              let g = this.getParser().parse(this.getLexer().tokenize(r), n, u);
              d = new oe(r, g), h.set(d), this.cache.save(h);
            }
            return d;
          }), i(this, "lint", (r, n = null, u = 0) => {
            n === null && (console.log('Deprecated: passing "null" as the second argument of lint is deprecated, pass IGNORE_UNKNOWN_VARIABLES instead as the third argument'), u |= 1, n = []), r instanceof oe || this.getParser().lint(this.getLexer().tokenize(r), n, u);
          }), i(this, "fixedEncodeURIComponent", (r) => encodeURIComponent(r).replace(/[!'()*]/g, function(n) {
            return "%" + n.charCodeAt(0).toString(16);
          })), i(this, "register", (r, n, u) => {
            if (this.parser !== null) throw new Yt("Registering functions after calling evaluate(), compile(), or parse() is not supported.");
            this.functions[r] = { compiler: n, evaluator: u };
          }), i(this, "addFunction", (r) => {
            this.register(r.getName(), r.getCompiler(), r.getEvaluator());
          }), i(this, "registerProvider", (r) => {
            for (let n of r.getFunctions()) this.addFunction(n);
          }), i(this, "getLexer", () => (this.lexer === null && (this.lexer = { tokenize: T }), this.lexer)), i(this, "getParser", () => (this.parser === null && (this.parser = new Ke(this.functions)), this.parser)), i(this, "getCompiler", () => (this.compiler === null && (this.compiler = new Ze(this.functions)), this.compiler.reset())), this.functions = [], this.lexer = null, this.parser = null, this.compiler = null, this.cache = t || new Qe(), this._registerBuiltinFunctions();
          for (let r of e) this.registerProvider(r);
        }
        _registerBuiltinFunctions() {
          const t = O.fromJavascript("Math.min", "min"), e = O.fromJavascript("Math.max", "max");
          this.addFunction(t), this.addFunction(e), this.addFunction(new O("constant", function(r) {
            return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));return __n.split('.').reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${r})`;
          }, function(r, n) {
            if (typeof n != "string" || !n) return;
            let u = (c = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof W < "u" ? W : {}, n.split(".").reduce((h, d) => h?.[d], c));
            var c;
            return u === void 0 && r && Object.prototype.hasOwnProperty.call(r, n) && (u = r[n]), u;
          })), this.addFunction(new O("enum", function(r) {
            return `(function(__n){var __g=(typeof globalThis!=='undefined'?globalThis:(typeof window!=='undefined'?window:(typeof global!=='undefined'?global:{})));if(typeof __n!=='string'||!__n)return undefined;var s=String(__n);var keys=[],buf='';for(var i=0;i<s.length;i++){var c=s.charCodeAt(i);if(c===46||c===92){if(buf){keys.push(buf);buf='';}continue;}if(c===58){if(i+1<s.length&&s.charCodeAt(i+1)===58){if(buf){keys.push(buf);buf='';}i++;continue;}}buf+=s[i];}if(buf)keys.push(buf);return keys.reduce(function(o,k){return o==null?undefined:o[k];}, __g)})(${r})`;
          }, function(r, n) {
            if (typeof n != "string" || !n) return;
            const u = String(n).replace(/\\/g, ".").replace(/::/g, ".");
            var c;
            return u ? (c = typeof globalThis < "u" ? globalThis : typeof window < "u" ? window : typeof W < "u" ? W : {}, u.split(".").reduce((h, d) => h?.[d], c)) : void 0;
          }));
        }
      }
      class ue {
        getFunctions() {
          throw new Error("getFunctions must be implemented by " + this.name);
        }
      }
      const Vt = new O("isset", function(o) {
        return `isset(${o})`;
      }, function(o, t) {
        if (typeof t != "string") return t != null;
        if (!(t.split(/[.\[]/)[0] in o)) return !0;
        let e = "", r = [], n = "", u = "";
        for (let c = 0; c < t.length; c++) {
          let h = t[c];
          if (h !== "]") if (h !== "[") {
            if (n === "object" && (!/[A-z0-9_]/.test(h) || c === t.length - 1)) {
              let d = !1;
              if (c === t.length - 1 && (u += h, d = !0), n = "", r.push({ type: "object", attribute: u }), u = "", d) continue;
            }
            h !== "." ? n ? u += h : e += h : (n = "object", u = "");
          } else n = "array", u = "";
          else n = "", r.push({ type: "array", index: u.replace(/"/g, "").replace(/'/g, "") }), u = "";
        }
        if (r.length > 0) {
          if (o[e] !== void 0) {
            let c = o[e];
            for (let h of r) {
              if (h.type === "array") {
                if (c[h.index] === void 0) return !1;
                c = c[h.index];
              }
              if (h.type === "object") {
                if (c[h.attribute] === void 0) return !1;
                c = c[h.attribute];
              }
            }
            return !0;
          }
          return !1;
        }
        return o[e] !== void 0;
      }), tt = (o) => Object.entries(o);
      function rt(o) {
        return typeof o == "object" && o !== null;
      }
      function Re(o) {
        return rt(o) && !(function(t) {
          return Array.isArray(t);
        })(o);
      }
      function st(o) {
        return (function(t) {
          return rt(t);
        })(o) ? o : {};
      }
      const nt = typeof window == "object" && window !== null ? window : typeof W == "object" && W !== null ? W : {};
      function zt() {
        const o = (() => {
          let y = nt.$locutus;
          typeof y == "object" && y !== null || (y = {}, nt.$locutus = y);
          let E = y.php;
          return typeof E == "object" && E !== null || (E = {}, y.php = E), E;
        })(), t = o.ini, e = o.locales, r = o.localeCategories, n = o.pointers, u = Re(t) ? t : {}, c = ((y) => Re(y))(e) ? e : {}, h = ((y) => Re(y))(r) ? r : {}, d = Array.isArray(n) ? n : [];
        t !== u && (o.ini = u), e !== c && (o.locales = c), r !== h && (o.localeCategories = h), n !== d && (o.pointers = d);
        const g = o.locale_default;
        return { ini: u, locales: c, localeCategories: h, pointers: d, locale_default: typeof g == "string" ? g : void 0 };
      }
      function it(o) {
        const t = zt().ini[o];
        return t && t.local_value !== void 0 ? t.local_value === null ? "" : String(t.local_value) : "";
      }
      function Ht(o, t, e) {
        const r = (function(h) {
          if (typeof h == "boolean") return h ? "1" : "";
          if (typeof h == "string") return h;
          if (typeof h == "number") return isNaN(h) ? "NAN" : isFinite(h) ? h + "" : (h < 0 ? "-" : "") + "INF";
          if (h === void 0) return "";
          if (typeof h == "object") return Array.isArray(h) ? "Array" : h !== null ? "Object" : "";
          throw new Error("Unsupported value type");
        })(o), n = it("unicode.semantics") === "on" ? r.match(/[\uD800-\uDBFF][\uDC00-\uDFFF]|[\s\S]/g) || [] : null, u = n ? n.length : r.length;
        let c = u;
        return t < 0 && (t += c), e !== void 0 && (c = e < 0 ? e + c : e + t), !(t > u || t < 0 || t > c) && (n ? n.slice(t, c).join("") : r.slice(t, c));
      }
      function Wt(o, ...t) {
        const e = {};
        if (t.length < 1) return e;
        const r = st(o);
        e: for (const [n, u] of tt(r)) {
          for (const c of t) {
            const h = st(c);
            let d = !1;
            for (const [, g] of tt(h)) if (g === u) {
              d = !0;
              break;
            }
            if (!d) continue e;
          }
          e[n] = u;
        }
        return e;
      }
      const at = (o) => {
        if (!o || typeof o != "object") return !1;
        const t = Object.getPrototypeOf(o);
        return t === Array.prototype || t === Object.prototype;
      };
      function je(o, t = 0) {
        let e = 0;
        if (o == null) return 0;
        if (typeof o != "object") return 1;
        const r = Object.getPrototypeOf(o);
        if (r !== Array.prototype && r !== Object.prototype) return 1;
        const n = t === "COUNT_RECURSIVE" || t === 1;
        if (Array.isArray(o)) {
          for (const u of Object.keys(o)) {
            e++;
            const c = o[Number(u)];
            n && at(c) && (e += je(c, 1));
          }
          return e;
        }
        for (const u in o) if (Object.prototype.hasOwnProperty.call(o, u)) {
          e++;
          const c = o[u];
          n && at(c) && (e += je(c, 1));
        }
        return e;
      }
      const Bt = new O("implode", function(o, t) {
        return `implode(${o}, ${t})`;
      }, function(o, t, e) {
        return (function(...r) {
          let n, u = "", c = "", h = "";
          if (r.length === 1) {
            const [d] = r;
            n = d;
          } else {
            const [d, g] = r;
            h = String(d ?? ""), n = g;
          }
          if (typeof n == "object" && n !== null) {
            if (Array.isArray(n)) return n.join(h);
            for (const d in n) u += c + n[d], c = h;
            return u;
          }
          return String(n);
        })(t, e);
      }), Gt = new O("count", function(o, t) {
        let e = "";
        return t && (e = `, ${t}`), `count(${o}${e})`;
      }, function(o, t, e) {
        return je(t, e);
      }), qt = new O("array_intersect", function(o, ...t) {
        let e = "";
        return t.length > 0 && (e = ", " + t.join(", ")), `array_intersect(${o}${e})`;
      }, function(o) {
        let t = [], e = !0;
        for (let n = 1; n < arguments.length; n++) t.push(arguments[n]), Array.isArray(arguments[n]) || (e = !1);
        let r = Wt.apply(null, t);
        return e ? Object.values(r) : r;
      });
      function Jt(o, t) {
        let e, r = /* @__PURE__ */ new Date();
        const n = ["Sun", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"], u = /\\?(.?)/gi, c = function(y, E) {
          return v = y, Object.prototype.hasOwnProperty.call(e, v) ? String(e[y]()) : E;
          var v;
        }, h = function(y, E) {
          let v = String(y);
          for (; v.length < E; ) v = "0" + v;
          return v;
        };
        return e = { d: function() {
          return h(e.j(), 2);
        }, D: function() {
          return String(e.l()).slice(0, 3);
        }, j: function() {
          return r.getDate();
        }, l: function() {
          return (n[Number(e.w())] ?? "") + "day";
        }, N: function() {
          return Number(e.w()) || 7;
        }, S: function() {
          const y = Number(e.j());
          let E = y % 10;
          return E <= 3 && Number.parseInt(String(y % 100 / 10), 10) === 1 && (E = 0), ["st", "nd", "rd"][E - 1] || "th";
        }, w: function() {
          return r.getDay();
        }, z: function() {
          const y = new Date(Number(e.Y()), Number(e.n()) - 1, Number(e.j())), E = new Date(Number(e.Y()), 0, 1);
          return Math.round((y.getTime() - E.getTime()) / 864e5);
        }, W: function() {
          const y = new Date(Number(e.Y()), Number(e.n()) - 1, Number(e.j()) - Number(e.N()) + 3), E = new Date(y.getFullYear(), 0, 4);
          return h(1 + Math.round((y.getTime() - E.getTime()) / 864e5 / 7), 2);
        }, F: function() {
          return n[6 + Number(e.n())] ?? "";
        }, m: function() {
          return h(e.n(), 2);
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
          const y = Number(e.n()), E = Number(e.W());
          return Number(e.Y()) + (y === 12 && E < 9 ? 1 : y === 1 && E > 9 ? -1 : 0);
        }, Y: function() {
          return r.getFullYear();
        }, y: function() {
          return String(e.Y()).slice(-2);
        }, a: function() {
          return r.getHours() > 11 ? "pm" : "am";
        }, A: function() {
          return String(e.a()).toUpperCase();
        }, B: function() {
          const y = 3600 * r.getUTCHours(), E = 60 * r.getUTCMinutes(), v = r.getUTCSeconds();
          return h(Math.floor((y + E + v + 3600) / 86.4) % 1e3, 3);
        }, g: function() {
          return Number(e.G()) % 12 || 12;
        }, G: function() {
          return r.getHours();
        }, h: function() {
          return h(e.g(), 2);
        }, H: function() {
          return h(e.G(), 2);
        }, i: function() {
          return h(r.getMinutes(), 2);
        }, s: function() {
          return h(r.getSeconds(), 2);
        }, u: function() {
          return h(1e3 * r.getMilliseconds(), 6);
        }, e: function() {
          throw new Error("Not supported (see source code of date() for timezone on how to add support)");
        }, I: function() {
          const y = new Date(Number(e.Y()), 0), E = Date.UTC(Number(e.Y()), 0), v = new Date(Number(e.Y()), 6), G = Date.UTC(Number(e.Y()), 6);
          return y.getTime() - E !== v.getTime() - G ? 1 : 0;
        }, O: function() {
          const y = r.getTimezoneOffset(), E = Math.abs(y);
          return (y > 0 ? "-" : "+") + h(100 * Math.floor(E / 60) + E % 60, 4);
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
      const _e = "[ \\t]+", Z = "[ \\t]*", X = "(?:([ap])\\.?m\\.?([\\t ]|$))", L = "(2[0-4]|[01]?[0-9])", ce = "([01][0-9]|2[0-4])", re = "(0?[1-9]|1[0-2])", V = "([0-5]?[0-9])", D = "([0-5][0-9])", xe = "(60|[0-5]?[0-9])", z = "(60|[0-5][0-9])", ot = "(?:\\.([0-9]+))", ut = "sunday|monday|tuesday|wednesday|thursday|friday|saturday|sun|mon|tue|wed|thu|fri|sat|weekdays?", ct = "next|last|previous|this", lt = "(?:second|sec|minute|min|hour|day|fortnight|forthnight|month|year)s?|weeks|" + ut, le = "([0-9]{1,4})", C = "([0-9]{4})", B = "(1[0-2]|0?[0-9])", Q = "(0[0-9]|1[0-2])", U = "(?:(3[01]|[0-2]?[0-9])(?:st|nd|rd|th)?)", H = "(0[0-9]|[1-2][0-9]|3[01])", ht = "january|february|march|april|may|june|july|august|september|october|november|december", he = "jan|feb|mar|apr|may|jun|jul|aug|sept?|oct|nov|dec", se = "(" + ht + "|" + he + "|i[vx]|vi{0,3}|xi{0,2}|i{1,3})", Fe = "((?:GMT)?([+-])" + L + ":?" + V + "?)", de = se + "[ .\\t-]*" + U + "[,.stndrh\\t ]*";
      function ee(o, t) {
        switch (t?.toLowerCase()) {
          case "a":
            o += o === 12 ? -12 : 0;
            break;
          case "p":
            o += o !== 12 ? 12 : 0;
        }
        return o;
      }
      function te(o) {
        let t = +o;
        return o.length < 4 && t < 100 && (t += t < 70 ? 2e3 : 1900), t;
      }
      function j(o) {
        return { jan: 0, january: 0, i: 0, feb: 1, february: 1, ii: 1, mar: 2, march: 2, iii: 2, apr: 3, april: 3, iv: 3, may: 4, v: 4, jun: 5, june: 5, vi: 5, jul: 6, july: 6, vii: 6, aug: 7, august: 7, viii: 7, sep: 8, sept: 8, september: 8, ix: 8, oct: 9, october: 9, x: 9, nov: 10, november: 10, xi: 10, dec: 11, december: 11, xii: 11 }[o.toLowerCase()] ?? Number.NaN;
      }
      function $e(o, t = 0) {
        return { mon: 1, monday: 1, tue: 2, tuesday: 2, wed: 3, wednesday: 3, thu: 4, thursday: 4, fri: 5, friday: 5, sat: 6, saturday: 6, sun: 0, sunday: 0 }[o.toLowerCase()] || t;
      }
      function Ue(o, t = Number.NaN) {
        const e = o?.match(/(?:GMT)?([+-])(\d+)(:?)(\d{0,2})/i);
        if (!e) return t;
        const r = e[1] === "-" ? -1 : 1;
        let n = +(e[2] ?? 0), u = +(e[4] ?? 0);
        return e[4] || e[3] || (u = Math.floor(n % 100), n = Math.floor(n / 100)), r * (60 * n + u) * 60;
      }
      const Kt = { acdt: 37800, acst: 34200, addt: -7200, adt: -10800, aedt: 39600, aest: 36e3, ahdt: -32400, ahst: -36e3, akdt: -28800, akst: -32400, amt: -13840, apt: -10800, ast: -14400, awdt: 32400, awst: 28800, awt: -10800, bdst: 7200, bdt: -36e3, bmt: -14309, bst: 3600, cast: 34200, cat: 7200, cddt: -14400, cdt: -18e3, cemt: 10800, cest: 7200, cet: 3600, cmt: -15408, cpt: -18e3, cst: -21600, cwt: -18e3, chst: 36e3, dmt: -1521, eat: 10800, eddt: -10800, edt: -14400, eest: 10800, eet: 7200, emt: -26248, ept: -14400, est: -18e3, ewt: -14400, ffmt: -14660, fmt: -4056, gdt: 39600, gmt: 0, gst: 36e3, hdt: -34200, hkst: 32400, hkt: 28800, hmt: -19776, hpt: -34200, hst: -36e3, hwt: -34200, iddt: 14400, idt: 10800, imt: 25025, ist: 7200, jdt: 36e3, jmt: 8440, jst: 32400, kdt: 36e3, kmt: 5736, kst: 30600, lst: 9394, mddt: -18e3, mdst: 16279, mdt: -21600, mest: 7200, met: 3600, mmt: 9017, mpt: -21600, msd: 14400, msk: 10800, mst: -25200, mwt: -21600, nddt: -5400, ndt: -9052, npt: -9e3, nst: -12600, nwt: -9e3, nzdt: 46800, nzmt: 41400, nzst: 43200, pddt: -21600, pdt: -25200, pkst: 21600, pkt: 18e3, plmt: 25590, pmt: -13236, ppmt: -17340, ppt: -25200, pst: -28800, pwt: -25200, qmt: -18840, rmt: 5794, sast: 7200, sdmt: -16800, sjmt: -20173, smt: -13884, sst: -39600, tbmt: 10751, tmt: 12344, uct: 0, utc: 0, wast: 7200, wat: 3600, wemt: 7200, west: 3600, wet: 0, wib: 25200, wita: 28800, wit: 32400, wmt: 5040, yddt: -25200, ydt: -28800, ypt: -28800, yst: -32400, ywt: -28800, a: 3600, b: 7200, c: 10800, d: 14400, e: 18e3, f: 21600, g: 25200, h: 28800, i: 32400, k: 36e3, l: 39600, m: 43200, n: -3600, o: -7200, p: -10800, q: -14400, r: -18e3, s: -21600, t: -25200, u: -28800, v: -32400, w: -36e3, x: -39600, y: -43200, z: 0 }, N = { yesterday: { regex: /^yesterday/i, name: "yesterday", callback() {
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
      } }, backOrFrontOf: { regex: new RegExp("^(back|front) of " + L + Z + X + "?", "i"), name: "backof | frontof", callback(o, t, e, r) {
        let n = +e, u = 15;
        return t.toLowerCase() === "back" || (n -= 1, u = 45), n = ee(n, r), this.resetTime() && this.time(n, u, 0, 0);
      } }, mssqltime: { regex: new RegExp("^" + re + ":" + D + ":" + z + "[:.]([0-9]+)" + X, "i"), name: "mssqltime", callback(o, t, e, r, n, u) {
        return this.time(ee(+t, u), +e, +r, +n.substr(0, 3));
      } }, oracledate: { regex: /^(\d{2})-([A-Z]{3})-(\d{2})$/i, name: "d-M-y", callback(o, t, e, r) {
        const n = { JAN: 0, FEB: 1, MAR: 2, APR: 3, MAY: 4, JUN: 5, JUL: 6, AUG: 7, SEP: 8, OCT: 9, NOV: 10, DEC: 11 }[e.toUpperCase()] ?? Number.NaN;
        return this.ymd(2e3 + parseInt(r, 10), n, parseInt(t, 10));
      } }, timeLong12: { regex: new RegExp("^" + re + "[:.]" + V + "[:.]" + z + Z + X, "i"), name: "timelong12", callback(o, t, e, r, n) {
        return this.time(ee(+t, n), +e, +r, 0);
      } }, timeShort12: { regex: new RegExp("^" + re + "[:.]" + D + Z + X, "i"), name: "timeshort12", callback(o, t, e, r) {
        return this.time(ee(+t, r), +e, 0, 0);
      } }, timeTiny12: { regex: new RegExp("^" + re + Z + X, "i"), name: "timetiny12", callback(o, t, e) {
        return this.time(ee(+t, e), 0, 0, 0);
      } }, soap: { regex: new RegExp("^" + C + "-" + Q + "-" + H + "T" + ce + ":" + D + ":" + z + ot + Fe + "?", "i"), name: "soap", callback(o, t, e, r, n, u, c, h, d) {
        return this.ymd(+t, +e - 1, +r) && this.time(+n, +u, +c, +h.substr(0, 3)) && this.zone(Ue(d));
      } }, wddx: { regex: new RegExp("^" + C + "-" + B + "-" + U + "T" + L + ":" + V + ":" + xe), name: "wddx", callback(o, t, e, r, n, u, c) {
        return this.ymd(+t, +e - 1, +r) && this.time(+n, +u, +c, 0);
      } }, exif: { regex: new RegExp("^" + C + ":" + Q + ":" + H + " " + ce + ":" + D + ":" + z, "i"), name: "exif", callback(o, t, e, r, n, u, c) {
        return this.ymd(+t, +e - 1, +r) && this.time(+n, +u, +c, 0);
      } }, xmlRpc: { regex: new RegExp("^" + C + Q + H + "T" + L + ":" + D + ":" + z), name: "xmlrpc", callback(o, t, e, r, n, u, c) {
        return this.ymd(+t, +e - 1, +r) && this.time(+n, +u, +c, 0);
      } }, xmlRpcNoColon: { regex: new RegExp("^" + C + Q + H + "[Tt]" + L + D + z), name: "xmlrpcnocolon", callback(o, t, e, r, n, u, c) {
        return this.ymd(+t, +e - 1, +r) && this.time(+n, +u, +c, 0);
      } }, clf: { regex: new RegExp("^" + U + "/(" + he + ")/" + C + ":" + ce + ":" + D + ":" + z + _e + Fe, "i"), name: "clf", callback(o, t, e, r, n, u, c, h) {
        return this.ymd(+r, j(e), +t) && this.time(+n, +u, +c, 0) && this.zone(Ue(h));
      } }, iso8601long: { regex: new RegExp("^t?" + L + "[:.]" + V + "[:.]" + xe + ot, "i"), name: "iso8601long", callback(o, t, e, r, n) {
        return this.time(+t, +e, +r, +n.substr(0, 3));
      } }, dateTextual: { regex: new RegExp("^" + se + "[ .\\t-]*" + U + "[,.stndrh\\t ]+" + le, "i"), name: "datetextual", callback(o, t, e, r) {
        return this.ymd(te(r), j(t), +e);
      } }, pointedDate4: { regex: new RegExp("^" + U + "[.\\t-]" + B + "[.-]" + C), name: "pointeddate4", callback(o, t, e, r) {
        return this.ymd(+r, +e - 1, +t);
      } }, pointedDate2: { regex: new RegExp("^" + U + "[.\\t]" + B + "\\.([0-9]{2})"), name: "pointeddate2", callback(o, t, e, r) {
        return this.ymd(te(r), +e - 1, +t);
      } }, timeLong24: { regex: new RegExp("^t?" + L + "[:.]" + V + "[:.]" + xe), name: "timelong24", callback(o, t, e, r) {
        return this.time(+t, +e, +r, 0);
      } }, dateNoColon: { regex: new RegExp("^" + C + Q + H), name: "datenocolon", callback(o, t, e, r) {
        return this.ymd(+t, +e - 1, +r);
      } }, pgydotd: { regex: new RegExp("^" + C + "\\.?(00[1-9]|0[1-9][0-9]|[12][0-9][0-9]|3[0-5][0-9]|36[0-6])"), name: "pgydotd", callback(o, t, e) {
        return this.ymd(+t, 0, +e);
      } }, timeShort24: { regex: new RegExp("^t?" + L + "[:.]" + V, "i"), name: "timeshort24", callback(o, t, e) {
        return this.time(+t, +e, 0, 0);
      } }, iso8601noColon: { regex: new RegExp("^t?" + ce + D + z, "i"), name: "iso8601nocolon", callback(o, t, e, r) {
        return this.time(+t, +e, +r, 0);
      } }, iso8601dateSlash: { regex: new RegExp("^" + C + "/" + Q + "/" + H + "/"), name: "iso8601dateslash", callback(o, t, e, r) {
        return this.ymd(+t, +e - 1, +r);
      } }, dateSlash: { regex: new RegExp("^" + C + "/" + B + "/" + U), name: "dateslash", callback(o, t, e, r) {
        return this.ymd(+t, +e - 1, +r);
      } }, american: { regex: new RegExp("^" + B + "/" + U + "/" + le), name: "american", callback(o, t, e, r) {
        return this.ymd(te(r), +t - 1, +e);
      } }, americanShort: { regex: new RegExp("^" + B + "/" + U), name: "americanshort", callback(o, t, e) {
        return this.ymd(this.y, +t - 1, +e);
      } }, gnuDateShortOrIso8601date2: { regex: new RegExp("^" + le + "-" + B + "-" + U), name: "gnudateshort | iso8601date2", callback(o, t, e, r) {
        return this.ymd(te(t), +e - 1, +r);
      } }, iso8601date4: { regex: new RegExp("^([+-]?[0-9]{4})-" + Q + "-" + H), name: "iso8601date4", callback(o, t, e, r) {
        return this.ymd(+t, +e - 1, +r);
      } }, gnuNoColon: { regex: new RegExp("^t?" + ce + D, "i"), name: "gnunocolon", callback(o, t, e) {
        switch (this.times) {
          case 0:
            return this.time(+t, +e, 0, this.f);
          case 1:
            return this.y = 100 * +t + +e, this.times++, !0;
          default:
            return !1;
        }
      } }, gnuDateShorter: { regex: new RegExp("^" + C + "-" + B), name: "gnudateshorter", callback(o, t, e) {
        return this.ymd(+t, +e - 1, 1);
      } }, pgTextReverse: { regex: new RegExp("^(\\d{3,4}|[4-9]\\d|3[2-9])-(" + he + ")-" + H, "i"), name: "pgtextreverse", callback(o, t, e, r) {
        return this.ymd(te(t), j(e), +r);
      } }, dateFull: { regex: new RegExp("^" + U + "[ \\t.-]*" + se + "[ \\t.-]*" + le, "i"), name: "datefull", callback(o, t, e, r) {
        return this.ymd(te(r), j(e), +t);
      } }, dateNoDay: { regex: new RegExp("^" + se + "[ .\\t-]*" + C, "i"), name: "datenoday", callback(o, t, e) {
        return this.ymd(+e, j(t), 1);
      } }, dateNoDayRev: { regex: new RegExp("^" + C + "[ .\\t-]*" + se, "i"), name: "datenodayrev", callback(o, t, e) {
        return this.ymd(+t, j(e), 1);
      } }, pgTextShort: { regex: new RegExp("^(" + he + ")-" + H + "-" + le, "i"), name: "pgtextshort", callback(o, t, e, r) {
        return this.ymd(te(r), j(t), +e);
      } }, dateNoYear: { regex: new RegExp("^" + de, "i"), name: "datenoyear", callback(o, t, e) {
        return this.ymd(this.y, j(t), +e);
      } }, dateNoYearRev: { regex: new RegExp("^" + U + "[ .\\t-]*" + se, "i"), name: "datenoyearrev", callback(o, t, e) {
        return this.ymd(this.y, j(e), +t);
      } }, isoWeekDay: { regex: new RegExp("^" + C + "-?W(0[1-9]|[1-4][0-9]|5[0-3])(?:-?([0-7]))?"), name: "isoweekday | isoweek", callback(o, t, e, r) {
        const n = r ? +r : 1;
        if (!this.ymd(+t, 0, 1)) return !1;
        let u = new Date(this.y, this.m, this.d).getDay();
        return u = 0 - (u > 4 ? u - 7 : u), this.rd += u + 7 * (+e - 1) + n, !0;
      } }, relativeText: { regex: new RegExp("^(first|second|third|fourth|fifth|sixth|seventh|eighth?|ninth|tenth|eleventh|twelfth|" + ct + ")" + _e + "(" + lt + ")", "i"), name: "relativetext", callback(o, t, e) {
        const { amount: r } = (function(n) {
          const u = n.toLowerCase();
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
            this.resetTime(), this.weekday = $e(e, 7), this.weekdayBehavior = 1, this.rd += 7 * (r > 0 ? r - 1 : r);
        }
      } }, relative: { regex: new RegExp("^([+-]*)[ \\t]*(\\d+)" + Z + "(" + lt + "|week)", "i"), name: "relative", callback(o, t, e, r) {
        const n = t.replace(/[^-]/g, "").length, u = +e * Math.pow(-1, n);
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
            this.resetTime(), this.weekday = $e(r, 7), this.weekdayBehavior = 1, this.rd += 7 * (u > 0 ? u - 1 : u);
        }
      } }, dayText: { regex: new RegExp("^(" + ut + ")", "i"), name: "daytext", callback(o, t) {
        this.resetTime(), this.weekday = $e(t, 0), this.weekdayBehavior !== 2 && (this.weekdayBehavior = 1);
      } }, relativeTextWeek: { regex: new RegExp("^(" + ct + ")" + _e + "week", "i"), name: "relativetextweek", callback(o, t) {
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
      } }, monthFullOrMonthAbbr: { regex: new RegExp("^(" + ht + "|" + he + ")", "i"), name: "monthfull | monthabbr", callback(o, t) {
        return this.ymd(this.y, j(t), this.d);
      } }, tzCorrection: { regex: new RegExp("^" + Fe, "i"), name: "tzcorrection", callback(o) {
        return this.zone(Ue(o));
      } }, tzAbbr: { regex: new RegExp("^\\(?([a-zA-Z]{1,6})\\)?"), name: "tzabbr", callback(o, t) {
        const e = Kt[t.toLowerCase()];
        return e != null && !Number.isNaN(e) && this.zone(e);
      } }, ago: { regex: /^ago/i, name: "ago", callback() {
        this.ry = -this.ry, this.rm = -this.rm, this.rd = -this.rd, this.rh = -this.rh, this.ri = -this.ri, this.rs = -this.rs, this.rf = -this.rf;
      } }, year4: { regex: new RegExp("^" + C), name: "year4", callback(o, t) {
        return this.y = +t, !0;
      } }, whitespace: { regex: /^[ .,\t]+/, name: "whitespace" }, dateShortWithTimeLong: { regex: new RegExp("^" + de + "t?" + L + "[:.]" + V + "[:.]" + xe, "i"), name: "dateshortwithtimelong", callback(o, t, e, r, n, u) {
        return this.ymd(this.y, j(t), +e) && this.time(+r, +n, +u, 0);
      } }, dateShortWithTimeLong12: { regex: new RegExp("^" + de + re + "[:.]" + V + "[:.]" + z + Z + X, "i"), name: "dateshortwithtimelong12", callback(o, t, e, r, n, u, c) {
        return this.ymd(this.y, j(t), +e) && this.time(ee(+r, c), +n, +u, 0);
      } }, dateShortWithTimeShort: { regex: new RegExp("^" + de + "t?" + L + "[:.]" + V, "i"), name: "dateshortwithtimeshort", callback(o, t, e, r, n) {
        return this.ymd(this.y, j(t), +e) && this.time(+r, +n, 0, 0);
      } }, dateShortWithTimeShort12: { regex: new RegExp("^" + de + re + "[:.]" + D + Z + X, "i"), name: "dateshortwithtimeshort12", callback(o, t, e, r, n, u) {
        return this.ymd(this.y, j(t), +e) && this.time(ee(+r, u), +n, 0, 0);
      } } }, Zt = { y: NaN, m: NaN, d: NaN, h: NaN, i: NaN, s: NaN, f: NaN, ry: 0, rm: 0, rd: 0, rh: 0, ri: 0, rs: 0, rf: 0, weekday: NaN, weekdayBehavior: 0, firstOrLastDayOfMonth: 0, z: NaN, dates: 0, times: 0, zones: 0, ymd(o, t, e) {
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
            let n = this.weekday - r;
            (this.rd < 0 && n < 0 || this.rd >= 0 && n <= -this.weekdayBehavior) && (n += 7), this.weekday >= 0 ? this.d += n : this.d -= 7 - (Math.abs(this.weekday) - r), this.weekday = NaN;
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
      l.AbstractProvider = ue, l.ArrayAdapter = Qe, l.ArrayProvider = class extends ue {
        getFunctions() {
          return [Bt, Gt, qt];
        }
      }, l.BasicProvider = class extends ue {
        getFunctions() {
          return [Vt];
        }
      }, l.Compiler = Ze, l.DateProvider = class extends ue {
        getFunctions() {
          return [new O("date", function(o, t) {
            let e = "";
            return t && (e = `, ${t}`), `date(${o}${e})`;
          }, function(o, t, e) {
            return Jt(t, e);
          }), new O("strtotime", function(o, t) {
            let e = "";
            return t && (e = `, ${t}`), `strtotime(${o}${e})`;
          }, function(o, t, e) {
            return (function(r, n) {
              const u = n ?? Math.floor(Date.now() / 1e3), c = [N.yesterday, N.now, N.noon, N.midnightOrToday, N.tomorrow, N.timestamp, N.firstOrLastDay, N.backOrFrontOf, N.timeTiny12, N.timeShort12, N.timeLong12, N.mssqltime, N.oracledate, N.timeShort24, N.timeLong24, N.iso8601long, N.gnuNoColon, N.iso8601noColon, N.americanShort, N.american, N.iso8601date4, N.iso8601dateSlash, N.dateSlash, N.gnuDateShortOrIso8601date2, N.gnuDateShorter, N.dateFull, N.pointedDate4, N.pointedDate2, N.dateNoDay, N.dateNoDayRev, N.dateTextual, N.dateNoYear, N.dateNoYearRev, N.dateNoColon, N.xmlRpc, N.xmlRpcNoColon, N.soap, N.wddx, N.exif, N.pgydotd, N.isoWeekDay, N.pgTextShort, N.pgTextReverse, N.clf, N.year4, N.ago, N.dayText, N.relativeTextWeek, N.relativeText, N.monthFullOrMonthAbbr, N.tzCorrection, N.tzAbbr, N.dateShortWithTimeShort12, N.dateShortWithTimeLong12, N.dateShortWithTimeShort, N.dateShortWithTimeLong, N.relative, N.whitespace], h = { ...Zt };
              for (; r.length; ) {
                let d = null, g = null;
                for (const y of c) {
                  const E = r.match(y.regex);
                  E && (!d || E[0].length > d[0].length) && (d = E, g = y);
                }
                if (!g || !d || g.callback && g.callback.apply(h, d) === !1) return !1;
                r = r.substr(d[0].length), g = null, d = null;
              }
              return Math.floor(h.toDate(new Date(1e3 * u)).getTime() / 1e3);
            })(t, e);
          })];
        }
      }, l.ExpressionFunction = O, l.ExpressionLanguage = et, l.IGNORE_UNKNOWN_FUNCTIONS = 2, l.IGNORE_UNKNOWN_VARIABLES = 1, l.Parser = Ke, l.StringProvider = class extends ue {
        getFunctions() {
          return [new O("strtolower", (o) => "strtolower(" + o + ")", (o, t) => (function(e) {
            return (e + "").toLowerCase();
          })(t)), new O("strtoupper", (o) => "strtoupper(" + o + ")", (o, t) => (function(e) {
            return (e + "").toUpperCase();
          })(t)), new O("explode", (o, t, e = "null") => `explode(${o}, ${t}, ${e})`, (o, t, e, r = null) => (function(...n) {
            let [u, c, h] = n, d = u;
            const g = c;
            if (n.length < 2 || d === void 0 || g === void 0) return null;
            if (d === "" || d === !1 || d === null) return !1;
            if (typeof d == "function" || typeof d == "object" || typeof g == "function" || typeof g == "object") return { 0: "" };
            d === !0 && (d = "1");
            const y = d + "", E = (g + "").split(y);
            return h === void 0 ? E : (h === 0 && (h = 1), h > 0 ? h >= E.length ? E : E.slice(0, h - 1).concat([E.slice(h - 1).join(y)]) : -h >= E.length ? [] : (E.splice(E.length + h), E));
          })(t, e, r)), new O("strlen", function(o) {
            return `strlen(${o});`;
          }, function(o, t) {
            return (function(e) {
              const r = e + "";
              if ((it("unicode.semantics") || "off") === "off") return r.length;
              let n = 0, u = 0;
              const c = function(h, d) {
                const g = h.charCodeAt(d);
                if (g >= 55296 && g <= 56319) {
                  if (h.length <= d + 1) throw new Error("High surrogate without following low surrogate");
                  const y = h.charCodeAt(d + 1);
                  if (y < 56320 || y > 57343) throw new Error("High surrogate without following low surrogate");
                  return h.charAt(d) + h.charAt(d + 1);
                }
                if (g >= 56320 && g <= 57343) {
                  if (d === 0) throw new Error("Low surrogate without preceding high surrogate");
                  const y = h.charCodeAt(d - 1);
                  if (y < 55296 || y > 56319) throw new Error("Low surrogate without preceding high surrogate");
                  return !1;
                }
                return h.charAt(d);
              };
              for (n = 0, u = 0; n < r.length; n++) c(r, n) !== !1 && u++;
              return u;
            })(t);
          }), new O("strstr", function(o, t, e) {
            let r = "";
            return e && (r = `, ${e}`), `strstr(${o}, ${t}${r});`;
          }, function(o, t, e, r) {
            return (function(n, u, c) {
              let h = 0;
              return h = (n += "").indexOf(u), h !== -1 && (c ? n.substr(0, h) : n.slice(h));
            })(t, e, r);
          }), new O("stristr", function(o, t, e) {
            let r = "";
            return e && (r = `, ${e}`), `stristr(${o}, ${t}${r});`;
          }, function(o, t, e, r) {
            return (function(n, u, c) {
              let h = 0;
              return h = (n += "").toLowerCase().indexOf((u + "").toLowerCase()), h !== -1 && (c ? n.substr(0, h) : n.slice(h));
            })(t, e, r);
          }), new O("substr", function(o, t, e) {
            let r = "";
            return e && (r = `, ${e}`), `substr(${o}, ${t}${r});`;
          }, function(o, t, e, r) {
            return Ht(t, e, r);
          })];
        }
      }, l.default = et, l.tokenize = T, Object.defineProperty(l, "__esModule", { value: !0 });
    }), (function(l) {
      var i = l.ExpressionLanguage;
      if (i && typeof i.ExpressionLanguage == "function") {
        var m = i.ExpressionLanguage;
        Object.keys(i).forEach(function(p) {
          p in m || (m[p] = i[p]);
        }), l.ExpressionLanguage = m;
      }
    })(typeof globalThis < "u" ? globalThis : typeof self < "u" ? self : kt);
  })(fe, fe.exports)), fe.exports;
}
var Ut = vr();
const Sr = /* @__PURE__ */ kr(Ut), St = /* @__PURE__ */ Xt({
  __proto__: null,
  default: Sr
}, [Ut]);
let At = null;
function Ar() {
  const s = St, a = s.ExpressionLanguage || s.default || St;
  if (typeof a != "function")
    throw new TypeError("Unable to resolve expression-language constructor.");
  return a;
}
function Or() {
  return At ??= new (Ar())(), At;
}
function Yr(s) {
  return (s.formula?.expression || s.formula?.formula || "").trim();
}
function Vr(s) {
  return Object.entries(s.formula?.variables || {}).filter((a) => !!a[1]?.sourceKey);
}
function zr(s, a) {
  return Object.entries(s).forEach(([l, i]) => {
    if (Array.isArray(i)) {
      const m = i.map((b) => typeof b == "string" && b.trim() !== "" && !Number.isNaN(Number(b)) ? Number(b) : b), p = m.filter((b) => typeof b == "number");
      s[l] = p.length === m.length && m.length > 0 ? p.reduce((b, f) => b + Number(f || 0), 0) : m;
      return;
    }
    typeof i == "string" && i.trim() !== "" && !Number.isNaN(Number(i)) && (s[l] = Number(i));
  }), s;
}
function Pr(s, a) {
  if (a.formatting !== "number")
    return typeof s == "number" || typeof s == "string" ? s : "";
  let l = s;
  Array.isArray(l) && (l = l.reduce((p, b) => p + Number(b || 0), 0));
  const i = typeof a.decimals == "number" ? a.decimals : 0, m = Number(l || 0).toFixed(i);
  return `${a.prefix || ""}${m}${a.suffix || ""}`;
}
function Hr(s, a) {
  const l = s.type?.endsWith("\\Number");
  return s.type?.endsWith("\\Checkboxes") ? Array.isArray(a) ? a.length ? a : "" : a ? [a] : "" : Array.isArray(a) ? a.length ? l ? a.map((m) => Number(m || 0)) : a : "" : l ? Number(a || 0) : a;
}
function Wr(s, a, l) {
  return Pr(Or().evaluate(s, a), l);
}
function Te(s, a) {
  if (a.startsWith("http://") || a.startsWith("https://"))
    return a;
  if (s.startsWith("http://") || s.startsWith("https://"))
    return new URL(a, s).toString();
  const l = s.trim();
  return !l || l === "/" ? a : `${l.replace(/\/+$/, "")}${a}`;
}
async function ke(s, a) {
  const l = await fetch(s, a);
  if (!l.ok)
    throw new Error(`Request failed with status ${l.status}.`);
  return l.json();
}
function Me(s, a) {
  const l = a?.tokens?.csrf;
  !l?.name || !l.value || (s[l.name] = l.value);
}
async function Br(s) {
  const a = Te(s.endpoint, "/actions/formie/client/forms/load"), l = JSON.stringify({
    handle: s.formHandle,
    siteId: s.siteId
  });
  return ke(a, {
    method: "POST",
    credentials: s.credentials ?? "same-origin",
    headers: {
      "Content-Type": "application/json"
    },
    body: l
  });
}
function Gr(s) {
  return {
    async submit({ definition: a, session: l, values: i, action: m }) {
      const p = Te(s.endpoint, "/actions/formie/client/submissions/submit"), b = await Se(a, i), f = {
        handle: s.formHandle,
        siteId: s.siteId,
        action: m,
        session: l,
        values: b
      };
      return Me(f, l), ke(p, {
        method: "POST",
        credentials: s.credentials ?? "same-origin",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(f)
      });
    },
    async refreshSession({ session: a }) {
      const l = Te(s.endpoint, "/actions/formie/client/sessions/refresh"), i = {
        handle: s.formHandle,
        siteId: s.siteId,
        session: a
      };
      return Me(i, a), ke(l, {
        method: "POST",
        credentials: s.credentials ?? "same-origin",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(i)
      });
    },
    async setPage({ definition: a, session: l, values: i, currentPageId: m, targetPageId: p }) {
      const b = Te(s.endpoint, "/actions/formie/client/forms/page"), f = await Se(a, i), T = {
        handle: s.formHandle,
        siteId: s.siteId,
        currentPageId: m,
        targetPageId: p,
        session: l,
        values: f
      };
      return Me(T, l), ke(b, {
        method: "POST",
        credentials: s.credentials ?? "same-origin",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(T)
      });
    }
  };
}
const Ae = `
    id
    currentPageId
    tokens
    continuation
`, Cr = `
    success
    submissionUid
    currentPageId
    nextPageId
    previousPageId
    isFinalPage
    errors
    messages
    clientEvents
    paymentStatus
    paymentMessage
    paymentRedirectUrl
    paymentAction
    paymentDecision
    keepSubmitLoading
    session {
        ${Ae}
    }
    quizResult
`;
function Ir(s) {
  if (s.startsWith("http://") || s.startsWith("https://"))
    return s;
  const a = s.trim();
  return !a || a === "/" ? "/api" : a;
}
async function ve(s, a, l) {
  const i = await fetch(Ir(s.endpoint), {
    method: "POST",
    // Default `same-origin`: credentialed cross-origin + `Allow-Origin: *` is invalid in browsers.
    credentials: s.credentials ?? "same-origin",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json"
    },
    body: JSON.stringify({
      query: a,
      variables: l
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
async function qr(s) {
  const a = await ve(
    s,
    `
            query ClientForm($handle: String!, $siteId: Int) {
                formieClientForm(handle: $handle, siteId: $siteId) {
                    schemaVersion
                    definition
                    session {
                        ${Ae}
                    }
                }
            }
        `,
    {
      handle: s.formHandle,
      siteId: s.siteId
    }
  );
  if (!a.formieClientForm)
    throw new Error("No client form definition was returned.");
  return a.formieClientForm;
}
function Jr(s) {
  return {
    async submit({ definition: a, session: l, values: i, action: m }) {
      const p = await Se(a, i), b = await ve(
        s,
        `
                    mutation SubmitFormieClientForm(
                        $input: FormieClientSubmitInput!
                    ) {
                        submitFormieClientForm(input: $input) {
                            ${Cr}
                        }
                    }
                `,
        {
          input: {
            handle: s.formHandle,
            siteId: s.siteId,
            action: m,
            session: l,
            values: p
          }
        }
      );
      if (!b.submitFormieClientForm)
        throw new Error("No client submit result was returned.");
      return b.submitFormieClientForm;
    },
    async refreshSession({ session: a }) {
      const l = await ve(
        s,
        `
                    mutation RefreshFormieClientSession(
                        $input: FormieClientSessionRefreshInput!
                    ) {
                        refreshFormieClientSession(input: $input) {
                            ${Ae}
                        }
                    }
                `,
        {
          input: {
            handle: s.formHandle,
            siteId: s.siteId,
            session: a
          }
        }
      );
      if (!l.refreshFormieClientSession)
        throw new Error("No client session was returned.");
      return l.refreshFormieClientSession;
    },
    async setPage({ definition: a, session: l, values: i, currentPageId: m, targetPageId: p }) {
      const b = await Se(a, i), f = await ve(
        s,
        `
                    mutation SetFormieClientPage(
                        $input: FormieClientSetPageInput!
                    ) {
                        setFormieClientPage(input: $input) {
                            ${Ae}
                        }
                    }
                `,
        {
          input: {
            handle: s.formHandle,
            siteId: s.siteId,
            currentPageId: m,
            targetPageId: p,
            session: l,
            values: b
          }
        }
      );
      if (!f.setFormieClientPage)
        throw new Error("No client page session was returned.");
      return f.setFormieClientPage;
    }
  };
}
const Ot = (() => {
  const s = Intl.Segmenter;
  return s ? new s(void 0, { granularity: "grapheme" }) : null;
})(), Rr = /[\p{L}\p{N}\p{M}]+(?:['’._-][\p{L}\p{N}\p{M}]+)*/gu;
function jr(s) {
  return typeof DOMParser < "u" ? new DOMParser().parseFromString(s, "text/html").body.textContent || "" : s.replace(/<[^>]*>/g, " ");
}
function Lt(s) {
  return jr(s);
}
function _r(s) {
  return Lt(s).replace(/[\s\t\n\r]+/g, " ").trim();
}
function Fr(s) {
  return Ot ? Array.from(Ot.segment(s)).length : Array.from(s).length;
}
function $r(s) {
  return s.match(Rr)?.length || 0;
}
function Kr(s) {
  const a = Lt(s), l = _r(s);
  return {
    graphemeCount: Fr(a),
    wordCount: $r(l)
  };
}
export {
  Mr as FRONTEND_CLIENT_EVENT_NAMES,
  ie as allFields,
  zr as coerceCalculationVariables,
  Oe as compositePartDefinitions,
  Fr as countGraphemes,
  Dr as createFrontendFormInstance,
  Jr as createGraphqlFrontendTransport,
  ar as createRepeaterRowValue,
  Gr as createRestFrontendTransport,
  He as defaultValueForField,
  Wr as evaluateCalculationExpression,
  Pt as evaluateConditionDefinition,
  ne as fieldValueAsStrings,
  tr as fieldValueContract,
  Rt as fieldValueStructure,
  Ct as finalizeConditionEvaluation,
  It as findFieldByHandle,
  me as findFieldById,
  Pr as formatCalculationValue,
  Yr as getCalculationFormula,
  Vr as getCalculationVariableEntries,
  Kr as getTextLimitMetrics,
  $r as getWordCount,
  rr as isBooleanField,
  pe as isCompositeField,
  nr as isEmailField,
  ye as isFileField,
  Lr as isKnownFrontendFieldType,
  ze as isMultiValueField,
  sr as isNumericField,
  ge as isRepeatableField,
  Br as loadFrontendEnvelope,
  qr as loadGraphqlFrontendEnvelope,
  _r as normalizeText,
  Hr as readCalculationVariableValue,
  Pe as repeaterFieldDefinitions,
  ir as repeaterRowDefinitions,
  Ur as serializeFieldValues,
  Se as serializeTransportFieldValues
};
