import { createFormieClient as A, FORMIE_HTML_EVENT_NAMES as B } from "@verbb/formie-browser";
import { defineComponent as g, shallowRef as x, ref as k, computed as m, provide as W, watch as I, h as u, inject as J, onMounted as G, onBeforeUnmount as X } from "vue";
import { createFrontendFormInstance as Q, FRONTEND_CLIENT_EVENT_NAMES as Y, loadGraphqlFrontendEnvelope as Z, loadFrontendEnvelope as ee, createGraphqlFrontendTransport as te, createRestFrontendTransport as ne, isKnownFrontendFieldType as U, isCompositeField as oe, isRepeatableField as ie, isFileField as re, compositePartDefinitions as ue, repeaterRowDefinitions as se, createRepeaterRowValue as le } from "@verbb/formie-core";
function M(e, t) {
  if (e == null)
    return String(e);
  if (typeof e == "string")
    return JSON.stringify(e);
  if (typeof e == "number" || typeof e == "boolean")
    return String(e);
  if (typeof e == "function")
    return "[function]";
  if (typeof File < "u" && e instanceof File)
    return `[file:${e.name}:${e.size}:${e.type}]`;
  if (typeof Blob < "u" && e instanceof Blob)
    return `[blob:${e.size}:${e.type}]`;
  if (Array.isArray(e))
    return `[${e.map((n) => M(n, t)).join(",")}]`;
  if (typeof e == "object") {
    if (t.has(e))
      return "[circular]";
    t.add(e);
    const n = Object.entries(e).sort(([o], [i]) => o.localeCompare(i)).map(([o, i]) => `${JSON.stringify(o)}:${M(i, t)}`);
    return t.delete(e), `{${n.join(",")}}`;
  }
  return JSON.stringify(String(e));
}
function q(e) {
  return M(e, /* @__PURE__ */ new WeakSet());
}
const K = /* @__PURE__ */ Symbol("formie-definition-context"), w = {
  field: {
    type: Object,
    required: !0
  },
  value: {
    type: null,
    default: void 0
  },
  errors: {
    type: Array,
    default: () => []
  },
  errorKey: {
    type: String,
    required: !0
  },
  disabled: {
    type: Boolean,
    default: !1
  },
  hidden: {
    type: Boolean,
    default: !1
  },
  setValue: {
    type: Function,
    required: !0
  }
};
function h() {
  const e = J(K);
  if (!e)
    throw new Error("Formie definition composables must be used within a client-rendered <FormieForm>.");
  return e;
}
function L(e) {
  return "definition" in e;
}
async function ae(e) {
  return L(e) ? e.definition : e.transport === "graphql" ? Z({
    endpoint: e.endpoint,
    formHandle: e.formHandle,
    siteId: e.siteId
  }) : ee({
    endpoint: e.endpoint,
    formHandle: e.formHandle,
    siteId: e.siteId
  });
}
function de(e) {
  const t = L(e) ? e.transport : {
    type: e.transport,
    endpoint: e.endpoint,
    formHandle: e.formHandle,
    siteId: e.siteId
  };
  return t.type === "graphql" ? te(t) : ne(t);
}
function ce(e) {
  return e.pages.flatMap((t) => t.rows).flatMap((t) => t.fields);
}
function _(e) {
  if (U(e.type))
    return e.type;
  const t = typeof e.input.fieldKind == "string" ? e.input.fieldKind : null;
  return t === "text" ? "single-line-text" : t === "textarea" ? "multi-line-text" : t === "boolean" ? "agree" : t === "file" ? "file" : e.type;
}
function fe(e, t, n) {
  const o = new Set(e.moduleRefs || []);
  return t.modules.find((i) => o.has(i.id) && i.capability === n) || null;
}
function $(e, t, n, o) {
  if (!n)
    return null;
  const i = e.slots.value[t];
  return i ? u(i, {
    slotKey: t,
    attributes: o
  }, {
    default: () => [n]
  }) : n;
}
const me = g({
  name: "FormieVueDefaultErrorSummary",
  props: {
    errors: {
      type: Array,
      required: !0
    }
  },
  setup(e) {
    return () => e.errors.length === 0 ? null : u("div", {
      class: "formie-vue-errors"
    }, [
      u("ul", null, e.errors.map((t, n) => u("li", {
        key: `${t}:${n}`
      }, t)))
    ]);
  }
}), ve = g({
  name: "FormieVueDefaultField",
  props: {
    field: {
      type: Object,
      required: !0
    },
    errors: {
      type: Array,
      required: !0
    }
  },
  setup(e, t) {
    const n = h();
    return () => {
      const o = t.slots.default?.() || [];
      return u("div", {
        class: "formie-vue-field",
        "data-field-type": e.field.type
      }, [
        e.field.label ? $(n, "label", u("label", {
          class: "formie-vue-label"
        }, e.field.label), {
          class: "formie-vue-label"
        }) : null,
        e.field.instructions ? $(n, "instructions", u("div", {
          class: "formie-vue-description"
        }, e.field.instructions), {
          class: "formie-vue-description"
        }) : null,
        $(n, "input", u("div", {
          class: "formie-vue-input"
        }, o), {
          class: "formie-vue-input"
        }),
        e.errors.length > 0 ? $(n, "errors", u("ul", {
          class: "formie-vue-field-errors"
        }, e.errors.map((i, r) => u("li", {
          key: `${i}:${r}`
        }, i))), {
          class: "formie-vue-field-errors"
        }) : null
      ]);
    };
  }
}), ye = g({
  name: "FormieVueDefaultForm",
  props: {
    definition: {
      type: Object,
      required: !0
    },
    session: {
      type: Object,
      required: !0
    },
    state: {
      type: Object,
      required: !0
    },
    className: {
      type: String,
      default: void 0
    },
    onSubmit: {
      type: Function,
      required: !0
    }
  },
  setup(e, t) {
    return () => u("form", {
      class: e.className,
      onSubmit: (n) => {
        n.preventDefault(), e.onSubmit();
      },
      "data-formie-definition": e.definition.handle,
      "data-formie-render-id": e.session.tokens.render
    }, t.slots.default?.() || []);
  }
}), be = g({
  name: "FormieVueDefaultPage",
  props: {
    page: {
      type: Object,
      required: !0
    },
    state: {
      type: Object,
      required: !0
    }
  },
  setup(e, t) {
    return () => u("section", {
      "data-page-id": e.page.id,
      class: "formie-vue-page"
    }, t.slots.default?.() || []);
  }
}), ge = g({
  name: "FormieVueSignatureFieldInput",
  props: w,
  setup(e) {
    const t = h(), n = k(null), o = x(null), i = k(null), r = m(() => fe(e.field, t.state.value?.definition || {
      modules: []
    }, "draw-signature")?.config), s = m(() => {
      const l = r.value?.options;
      return typeof l?.backgroundColor == "string" ? l.backgroundColor : "#ffffff";
    }), a = m(() => {
      const l = r.value?.options;
      return typeof l?.penColor == "string" ? l.penColor : "#000000";
    }), f = m(() => {
      const l = r.value?.options;
      return Number(l?.penWeight ?? 2) || 2;
    }), d = m(() => typeof e.value == "string" ? e.value : "");
    let c = !1, S = () => {
    }, C = () => {
    };
    return G(() => {
      (async () => {
        try {
          const v = n.value;
          if (!v)
            return;
          const { default: y } = await import("./signature_pad-CKGlHEaq.js");
          if (c)
            return;
          const p = new y(v, {
            backgroundColor: s.value,
            penColor: a.value,
            minWidth: f.value,
            maxWidth: f.value
          }), R = () => {
            const b = typeof window > "u" ? 1 : Math.max(window.devicePixelRatio || 1, 1), N = Math.max(1, Math.floor(v.clientWidth || 480)), E = 192, F = v.getContext("2d");
            v.width = N * b, v.height = E * b, v.style.height = `${E}px`, F && (F.setTransform(1, 0, 0, 1, 0, 0), F.scale(b, b)), p.clear();
          }, V = () => {
            e.setValue(p.isEmpty() ? "" : p.toDataURL());
          };
          R(), p.addEventListener?.("endStroke", V), S = () => {
            p.removeEventListener?.("endStroke", V);
          }, typeof window < "u" && (window.addEventListener("resize", R), C = () => {
            window.removeEventListener("resize", R);
          }), o.value = p, i.value = null;
        } catch (v) {
          c || (i.value = v.message || "Unable to load signature support.");
        }
      })();
    }), X(() => {
      c = !0, S(), C(), o.value = null;
    }), I(d, (l) => {
      const v = o.value;
      if (v) {
        if (!l) {
          v.isEmpty() || v.clear();
          return;
        }
        try {
          v.fromDataURL(l);
        } catch {
        }
      }
    }, { immediate: !0 }), () => u("div", {
      class: "formie-vue-signature"
    }, [
      u("canvas", {
        key: "canvas",
        ref: n,
        "data-formie-signature-canvas": !0,
        style: e.disabled ? { pointerEvents: "none" } : void 0
      }),
      u("button", {
        key: "clear",
        type: "button",
        disabled: e.disabled,
        "data-formie-signature-clear": !0,
        onClick: () => {
          o.value?.clear(), e.setValue("");
        }
      }, "Clear"),
      i.value ? u("div", {
        key: "error",
        class: "formie-vue-unsupported"
      }, i.value) : null
    ]);
  }
}), Se = g({
  name: "FormieVueCompositeFieldInput",
  props: w,
  setup(e) {
    const t = h();
    return () => {
      const n = t.state.value;
      if (!n)
        return null;
      const o = ue(e.field), i = e.value && typeof e.value == "object" ? e.value : {};
      return o.length === 0 ? u("div", {
        class: "formie-vue-unsupported"
      }, `Unsupported field type: ${e.field.type}`) : u("div", {
        class: "formie-vue-name-grid"
      }, o.filter((r) => r.meta?.hidden !== !0).map((r) => {
        const s = `${e.errorKey}.${r.handle}`;
        return u(D, {
          key: `${e.field.id}:${r.handle}`,
          field: r,
          value: i[r.handle],
          errors: n.errors.fields[s] || [],
          errorKey: s,
          disabled: e.disabled || r.meta?.disabled === !0,
          hidden: !1,
          setValue(a) {
            e.setValue({
              ...i,
              [r.handle]: a
            });
          }
        });
      }));
    };
  }
}), Fe = g({
  name: "FormieVueFileFieldInput",
  props: w,
  setup(e) {
    return () => {
      const t = e.field.input, n = Array.isArray(e.value) ? e.value : [], o = t.multiple === !0, i = n.map((r, s) => r && typeof r == "object" && "name" in r && typeof r.name == "string" ? r.name : r && typeof r == "object" && "filename" in r && typeof r.filename == "string" ? r.filename : r && typeof r == "object" && "assetId" in r && typeof r.assetId == "number" ? `Asset #${r.assetId}` : `File ${s + 1}`);
      return u("div", {
        class: "formie-vue-file"
      }, [
        u("input", {
          key: "input",
          type: "file",
          disabled: e.disabled,
          multiple: o,
          onChange: (r) => {
            const s = r.target;
            e.setValue(Array.from(s.files || []));
          }
        }),
        i.length > 0 ? u("ul", {
          key: "summary",
          class: "formie-vue-field-errors"
        }, i.map((r, s) => u("li", {
          key: `${r}:${s}`
        }, r))) : null
      ]);
    };
  }
}), D = g({
  name: "FormieVueConfigFieldNode",
  props: w,
  setup(e) {
    const t = h();
    return () => {
      const n = t.state.value;
      if (!n)
        return null;
      const i = n.fieldStates[e.field.id]?.hidden === !0;
      if (i)
        return null;
      const r = _(e.field), s = t.fieldComponents.value[e.field.type] || t.fieldComponents.value[r] || Ce, a = t.components.value.Field || ve;
      return u(a, {
        field: e.field,
        errors: e.errors
      }, {
        default: () => [
          u(s, {
            field: e.field,
            value: e.value,
            errors: e.errors,
            errorKey: e.errorKey,
            disabled: e.disabled,
            hidden: i,
            setValue: e.setValue
          })
        ]
      });
    };
  }
}), he = g({
  name: "FormieVueConfigField",
  props: {
    field: {
      type: Object,
      required: !0
    }
  },
  setup(e) {
    const t = h();
    return () => {
      const n = t.state.value, o = t.instance.value;
      if (!n || !o)
        return null;
      const i = n.fieldStates[e.field.id];
      return u(D, {
        field: e.field,
        value: n.values[e.field.id],
        errors: n.errors.fields[e.field.id] || [],
        errorKey: e.field.id,
        disabled: i?.disabled === !0,
        hidden: i?.hidden === !0,
        setValue(r) {
          o.setValue(e.field.id, r);
        }
      });
    };
  }
}), z = g({
  name: "FormieVueConfigRow",
  props: {
    row: {
      type: Object,
      required: !0
    },
    rowIndex: {
      type: Number,
      required: !0
    },
    values: {
      type: Object,
      default: void 0
    },
    errorPrefix: {
      type: String,
      default: void 0
    },
    disabled: {
      type: Boolean,
      default: !1
    },
    setFieldValue: {
      type: Function,
      default: void 0
    }
  },
  setup(e) {
    const t = h();
    return () => {
      const n = t.state.value;
      return n ? u("div", {
        class: "formie-vue-row"
      }, e.row.fields.map((o, i) => {
        if (!e.values || !e.setFieldValue)
          return u(he, {
            key: o.id || `${e.rowIndex}:${i}`,
            field: o
          });
        const r = `${e.errorPrefix}.${o.handle}`;
        return u(D, {
          key: o.id || `${e.rowIndex}:${i}`,
          field: o,
          value: e.values[o.handle],
          errors: n.errors.fields[r] || [],
          errorKey: r,
          disabled: e.disabled === !0 || n.fieldStates[o.id]?.disabled === !0,
          hidden: n.fieldStates[o.id]?.hidden === !0,
          setValue(s) {
            e.setFieldValue?.(o, s);
          }
        });
      })) : null;
    };
  }
}), pe = g({
  name: "FormieVueRepeaterFieldInput",
  props: w,
  setup(e) {
    const t = h();
    return () => {
      const n = t.state.value;
      if (!n)
        return null;
      const o = se(e.field), i = Array.isArray(e.value) ? e.value : [], r = e.field.input, s = Number(r.minRows ?? 0) || 0, a = Number(r.maxRows ?? 0) || 0, f = !e.disabled && (a <= 0 || i.length < a);
      return o.length === 0 ? u("div", {
        class: "formie-vue-unsupported"
      }, "Unsupported repeater field.") : u("div", {
        class: "formie-vue-repeater",
        "data-formie-repeater-container": !0
      }, [
        ...i.map((d, c) => {
          const S = `${e.field.id}:${c}`;
          return u("div", {
            key: S,
            class: "formie-vue-repeater-item",
            "data-formie-repeater-item": !0
          }, [
            ...o.map((C, l) => u(z, {
              key: `${S}:${l}`,
              row: C,
              rowIndex: l,
              values: d,
              errorPrefix: `${e.errorKey}.${c}`,
              disabled: e.disabled,
              setFieldValue(v, y) {
                const p = i.map((R, V) => V !== c ? R : {
                  ...R,
                  [v.handle]: y
                });
                e.setValue(p);
              }
            })),
            u("button", {
              key: "remove",
              type: "button",
              disabled: e.disabled || s > 0 && i.length <= s,
              "data-formie-repeater-remove": !0,
              onClick: () => {
                e.setValue(i.filter((C, l) => l !== c));
              }
            }, "Remove")
          ]);
        }),
        u("button", {
          key: "add",
          type: "button",
          disabled: !f,
          "data-formie-repeater-add": e.field.handle,
          onClick: () => {
            e.setValue([...i, le(e.field)]);
          }
        }, String(r.addLabel ?? "Add another row")),
        n.errors.fields[e.errorKey] && n.errors.fields[e.errorKey].length > 0 ? u("ul", {
          key: "errors",
          class: "formie-vue-field-errors"
        }, n.errors.fields[e.errorKey].map((d, c) => u("li", {
          key: `${d}:${c}`
        }, d))) : null
      ]);
    };
  }
});
function T(e, t, n, o) {
  const i = e.input;
  if (e.type === "multi-line-text")
    return u("textarea", {
      value: typeof t == "string" ? t : "",
      disabled: n,
      placeholder: typeof i.placeholder == "string" ? i.placeholder : void 0,
      onInput: (s) => {
        const a = s.target;
        o(a.value);
      }
    });
  if (e.type === "dropdown") {
    const s = Array.isArray(i.options) ? i.options : [], a = i.multiple === !0;
    return u("select", {
      value: a ? void 0 : typeof t == "string" ? t : "",
      disabled: n,
      multiple: a,
      onChange: (f) => {
        const d = f.target;
        if (a) {
          o(Array.from(d.selectedOptions).map((c) => c.value));
          return;
        }
        o(d.value);
      }
    }, s.map((f) => {
      const d = String(f.value ?? "");
      return u("option", {
        key: `${e.id}:${d}`,
        value: d,
        disabled: f.disabled === !0
      }, String(f.label ?? d));
    }));
  }
  const r = typeof i.inputType == "string" ? i.inputType : e.type === "email" ? "email" : e.type === "phone" ? "tel" : e.type === "number" ? "number" : "text";
  return u("input", {
    type: r,
    value: typeof t == "string" || typeof t == "number" ? String(t) : "",
    disabled: n,
    placeholder: typeof i.placeholder == "string" ? i.placeholder : void 0,
    onInput: (s) => {
      const a = s.target;
      if (r === "number") {
        const f = a.valueAsNumber;
        o(Number.isFinite(f) ? f : "");
        return;
      }
      o(a.value);
    }
  });
}
const Ce = g({
  name: "FormieVueDefaultFieldInput",
  props: w,
  setup(e) {
    return () => {
      const t = e.field.input, n = _(e.field);
      if (oe(e.field))
        return u(Se, e);
      if (ie(e.field))
        return u(pe, e);
      if (re(e.field))
        return u(Fe, e);
      if (n === "signature")
        return u(ge, e);
      if (n === "multi-line-text" || n === "dropdown")
        return T(e.field, e.value, e.disabled, e.setValue);
      if (n === "radio") {
        const o = Array.isArray(t.options) ? t.options : [];
        return u("div", {
          class: "formie-vue-choices"
        }, o.map((i) => {
          const r = String(i.value ?? "");
          return u("label", {
            key: `${e.field.id}:${r}`
          }, [
            u("input", {
              key: "input",
              type: "radio",
              checked: e.value === r,
              disabled: e.disabled,
              onChange: () => {
                e.setValue(r);
              }
            }),
            u("span", {
              key: "label"
            }, String(i.label ?? r))
          ]);
        }));
      }
      if (n === "checkboxes") {
        const o = Array.isArray(t.options) ? t.options : [], i = Array.isArray(e.value) ? e.value.map((r) => String(r)) : [];
        return u("div", {
          class: "formie-vue-choices"
        }, o.map((r) => {
          const s = String(r.value ?? ""), a = i.includes(s);
          return u("label", {
            key: `${e.field.id}:${s}`
          }, [
            u("input", {
              key: "input",
              type: "checkbox",
              checked: a,
              disabled: e.disabled,
              onChange: () => {
                const f = a ? i.filter((d) => d !== s) : [...i, s];
                e.setValue(f);
              }
            }),
            u("span", {
              key: "label"
            }, String(r.label ?? s))
          ]);
        }));
      }
      if (n === "agree") {
        const o = typeof t.descriptionHtml == "string" ? t.descriptionHtml : null;
        return u("label", {
          class: "formie-vue-boolean"
        }, [
          u("input", {
            key: "input",
            type: "checkbox",
            checked: e.value === !0,
            disabled: e.disabled,
            onChange: (i) => {
              const r = i.target;
              e.setValue(r.checked);
            }
          }),
          o ? u("span", {
            key: "description",
            innerHTML: o
          }) : u("span", {
            key: "description"
          }, e.field.label)
        ]);
      }
      return U(n) ? T(e.field, e.value, e.disabled, e.setValue) : u("div", {
        class: "formie-vue-unsupported"
      }, `Unsupported field type: ${String(e.field.meta?.fieldType ?? e.field.type)}`);
    };
  }
}), Ee = g({
  name: "FormieVueConfigPageActions",
  setup() {
    const e = h();
    return () => {
      const t = e.state.value, n = e.instance.value;
      if (!t || !n)
        return null;
      const o = t.definition.pages.find((r) => r.id === t.currentPageId);
      if (!o)
        return null;
      const i = [];
      return o.actions.secondary.forEach((r) => {
        i.push(u("button", {
          key: r.type,
          type: "button",
          onClick: () => {
            n.submit(r.type);
          }
        }, r.label));
      }), i.push(u("button", {
        key: o.actions.primary.type,
        type: "submit"
      }, o.actions.primary.label)), u("div", {
        class: "formie-page-actions"
      }, i);
    };
  }
}), Re = g({
  name: "FormieVueConfigRenderer",
  props: {
    className: {
      type: String,
      default: void 0
    }
  },
  setup(e) {
    const t = h();
    return () => {
      const n = t.instance.value, o = t.state.value;
      if (!n || !o)
        return null;
      const i = t.components.value.Form || ye, r = t.components.value.Page || be, s = t.components.value.ErrorSummary || me, a = o.definition.pages.find((c) => c.id === o.currentPageId && o.pageStates[c.id]?.hidden !== !0) || o.definition.pages.find((c) => o.pageStates[c.id]?.hidden !== !0) || o.definition.pages[0], f = o.lastSubmitResult?.messages.error, d = !!f && !o.errors.form.includes(f);
      return a ? u(i, {
        definition: o.definition,
        session: o.session,
        state: o,
        className: e.className,
        onSubmit: () => {
          n.submit();
        }
      }, {
        default: () => [
          u(s, {
            key: "errors",
            errors: o.errors.form
          }),
          o.lastSubmitResult?.messages.notice ? u("div", {
            key: "notice",
            class: "formie-vue-notice"
          }, o.lastSubmitResult.messages.notice) : null,
          d ? u("div", {
            key: "error",
            class: "formie-vue-error"
          }, f) : null,
          u(r, {
            key: a.id,
            page: a,
            state: o
          }, {
            default: () => [
              ...a.rows.map((c, S) => u(z, {
                key: `${a.id}:${S}`,
                row: c,
                rowIndex: S
              })),
              u(Ee, {
                key: "actions"
              })
            ]
          })
        ]
      }) : null;
    };
  }
}), Ve = {
  source: {
    type: Object,
    required: !0
  },
  components: {
    type: Object,
    default: () => ({})
  },
  fieldComponents: {
    type: Object,
    default: () => ({})
  },
  slots: {
    type: Object,
    default: () => ({})
  },
  className: {
    type: String,
    default: void 0
  },
  onMount: {
    type: Function,
    default: void 0
  },
  onReady: {
    type: Function,
    default: void 0
  },
  onUnmount: {
    type: Function,
    default: void 0
  },
  onResult: {
    type: Function,
    default: void 0
  },
  onSuccess: {
    type: Function,
    default: void 0
  },
  onError: {
    type: Function,
    default: void 0
  },
  onSubmitResult: {
    type: Function,
    default: void 0
  },
  onSubmitSuccess: {
    type: Function,
    default: void 0
  },
  onSubmitError: {
    type: Function,
    default: void 0
  },
  onEvent: {
    type: Function,
    default: void 0
  }
};
function O(e, t, ...n) {
  e?.(...n), t && t !== e && t(...n);
}
const ke = g({
  name: "FormieVueDefinitionFormView",
  props: Ve,
  emits: ["mount", "ready", "unmount", "result", "success", "error", "submit-result", "submit-success", "submit-error", "event"],
  setup(e, { emit: t }) {
    const n = x(null), o = x(null), i = k(null), r = m(() => e.components || {}), s = m(() => e.fieldComponents || {}), a = m(() => e.slots || {}), f = {
      instance: n,
      state: o,
      components: r,
      fieldComponents: s,
      slots: a
    }, d = m(() => q(e.source));
    return W(K, f), I(d, (c, S, C) => {
      let l = !1, v = null, y = () => {
      };
      (async () => {
        try {
          const R = await ae(e.source), V = de(e.source), b = Q({
            envelope: R,
            transport: V
          });
          if (l) {
            await b.destroy();
            return;
          }
          v = b, i.value = null, n.value = b, o.value = b.getState(), e.onMount?.(b), e.onReady?.(b), t("mount", b), t("ready", b);
          const N = [
            b.subscribe((E) => {
              o.value = E;
            }),
            b.on("formie:submit:result", (E) => {
              const F = E;
              O(e.onSubmitResult, e.onResult, F), t("result", F), t("submit-result", F), F.success ? (O(e.onSubmitSuccess, e.onSuccess, F), t("success", F), t("submit-success", F)) : (O(e.onSubmitError, e.onError, F), t("error", F), t("submit-error", F));
            }),
            ...Y.map((E) => b.on(E, (F) => {
              const H = {
                name: E,
                payload: F
              };
              e.onEvent?.(H), t("event", H);
            }))
          ];
          y = () => {
            N.forEach((E) => E()), b.destroy(), n.value === b && (n.value = null, o.value = null), e.onUnmount?.(), t("unmount");
          };
        } catch (R) {
          l || (i.value = R);
        }
      })(), C(() => {
        l = !0, y();
      });
    }, { immediate: !0 }), () => i.value ? u("div", {
      class: "formie-vue-error"
    }, i.value.message) : !n.value || !o.value ? u("div", {
      class: "formie-vue-loading"
    }, "Loading form...") : u(Re, {
      className: e.className
    });
  }
});
function He() {
  const e = h();
  return {
    definition: m(() => e.state.value?.definition || null),
    session: m(() => e.state.value?.session || null),
    state: e.state,
    instance: e.instance
  };
}
function Te(e) {
  const t = h(), n = m(() => {
    const o = t.state.value?.definition;
    return o && ce(o).find((i) => i.id === e) || null;
  });
  return {
    field: n,
    value: m(() => t.state.value?.values[e]),
    errors: m(() => t.state.value?.errors.fields[e] || []),
    hidden: m(() => t.state.value?.fieldStates[e]?.hidden === !0),
    disabled: m(() => t.state.value?.fieldStates[e]?.disabled === !0),
    setValue(o) {
      !n.value || !t.instance.value || t.instance.value.setValue(n.value.id, o);
    }
  };
}
function Pe(e) {
  const t = h();
  return {
    page: m(() => t.state.value?.definition.pages.find((n) => n.id === e) || null),
    isCurrent: m(() => t.state.value?.currentPageId === e),
    hidden: m(() => t.state.value?.pageStates[e]?.hidden === !0)
  };
}
function Ue() {
  return h().instance;
}
function Ke(e) {
  const t = h();
  return m(() => t.slots.value[e] || null);
}
function P(e) {
  return !!e && "payload" in e;
}
function we(e) {
  return "success" in e ? e.success : e.ok;
}
function j(e, t, ...n) {
  e?.(...n), t && t !== e && t(...n);
}
function $e(e) {
  const t = e.transport;
  if (!t && !P(e.source))
    throw new Error("`transport` is required for <FormieForm>.");
  return {
    mode: "server-rendered",
    transport: t,
    endpoint: e.endpoint,
    formHandle: e.formHandle,
    payload: P(e.source) ? e.source.payload : void 0,
    staticCache: e.staticCache,
    refreshTokens: e.refreshTokens,
    locale: e.locale,
    siteId: e.siteId,
    autoVisible: e.autoVisible,
    theme: e.theme,
    themeConfig: e.themeConfig
  };
}
function xe(e) {
  if (e.source)
    return e.source;
  const t = e.transport, n = e.endpoint, o = e.formHandle;
  if (t !== "rest" && t !== "graphql")
    throw new Error('Vue client-rendered forms require `transport="rest"` or `transport="graphql"`.');
  if (!n || !o)
    throw new Error("Vue client-rendered forms require either `source` or both `endpoint` and `formHandle`.");
  return {
    transport: t,
    endpoint: n,
    formHandle: o,
    siteId: e.siteId
  };
}
function Le() {
  return A();
}
function Ie() {
  return A();
}
function _e(e) {
  const t = Ie(), n = k(null), o = x(null), i = k(null), r = m(() => q(e));
  return I([n, r], ([s], a, f) => {
    if (!s)
      return;
    let d = !1, c = !1;
    const S = async () => {
      c || (c = !0, await t.unmount(s));
    }, C = Promise.resolve().then(async () => {
      if (!d)
        try {
          const l = await t.mount(s, {
            ...e,
            mode: "server-rendered"
          });
          if (d) {
            await S();
            return;
          }
          o.value = l, i.value = null;
        } catch (l) {
          d || (i.value = l);
        }
    });
    f(() => {
      d = !0, o.value = null, C.finally(S);
    });
  }, { immediate: !0 }), {
    rootRef: n,
    state: {
      instance: o,
      isMounted: m(() => !!o.value),
      error: i
    },
    submit: async (s = "submit") => o.value ? o.value.submit(s) : null
  };
}
const Ne = {
  options: {
    type: Object,
    required: !0
  }
}, Oe = g({
  name: "FormieVueHtmlFormView",
  props: Ne,
  emits: ["mount", "ready", "unmount", "result", "success", "error", "submit-result", "submit-success", "submit-error", "event"],
  setup(e, { emit: t }) {
    const n = k(null), o = A(), i = m(() => $e(e.options)), r = m(() => q(i.value));
    return I([n, r], ([s], a, f) => {
      if (!s)
        return;
      let d = !1, c = null;
      const S = [], C = Promise.resolve().then(async () => {
        const l = await o.mount(s, i.value);
        if (d) {
          await o.unmount(s);
          return;
        }
        c = l, e.options.onMount?.(l), e.options.onReady?.(l), t("mount", l), t("ready", l), S.push(l.on("formie:submit:result", (v) => {
          const y = v;
          j(e.options.onSubmitResult, e.options.onResult, y), t("result", y), t("submit-result", y), we(y) ? (j(e.options.onSubmitSuccess, e.options.onSuccess, y), t("success", y), t("submit-success", y)) : (j(e.options.onSubmitError, e.options.onError, y), t("error", y), t("submit-error", y));
        })), B.forEach((v) => {
          S.push(l.on(v, (y) => {
            const p = {
              name: v,
              payload: y
            };
            e.options.onEvent?.(p), t("event", p);
          }));
        });
      });
      f(() => {
        d = !0, S.forEach((l) => l()), C.finally(async () => {
          await o.unmount(s), c && (e.options.onUnmount?.(), t("unmount"), c = null);
        });
      });
    }, { immediate: !0 }), () => u("div", {
      ref: n,
      class: e.options.className
    });
  }
}), je = {
  source: {
    type: Object,
    default: void 0
  },
  transport: {
    type: String,
    default: void 0
  },
  endpoint: {
    type: String,
    default: void 0
  },
  formHandle: {
    type: String,
    default: void 0
  },
  staticCache: {
    type: Boolean,
    default: void 0
  },
  refreshTokens: {
    type: Boolean,
    default: void 0
  },
  locale: {
    type: String,
    default: void 0
  },
  siteId: {
    type: Number,
    default: void 0
  },
  autoVisible: {
    type: Boolean,
    default: void 0
  },
  theme: {
    type: String,
    default: void 0
  },
  themeConfig: {
    type: Object,
    default: void 0
  },
  className: {
    type: String,
    default: void 0
  },
  onMount: {
    type: Function,
    default: void 0
  },
  onReady: {
    type: Function,
    default: void 0
  },
  onUnmount: {
    type: Function,
    default: void 0
  },
  onResult: {
    type: Function,
    default: void 0
  },
  onSuccess: {
    type: Function,
    default: void 0
  },
  onError: {
    type: Function,
    default: void 0
  },
  onSubmitResult: {
    type: Function,
    default: void 0
  },
  onSubmitSuccess: {
    type: Function,
    default: void 0
  },
  onSubmitError: {
    type: Function,
    default: void 0
  },
  onEvent: {
    type: Function,
    default: void 0
  }
}, Me = {
  source: {
    type: Object,
    default: void 0
  },
  transport: {
    type: String,
    default: void 0
  },
  endpoint: {
    type: String,
    default: void 0
  },
  formHandle: {
    type: String,
    default: void 0
  },
  siteId: {
    type: Number,
    default: void 0
  },
  components: {
    type: Object,
    default: void 0
  },
  fieldComponents: {
    type: Object,
    default: void 0
  },
  slots: {
    type: Object,
    default: void 0
  },
  className: {
    type: String,
    default: void 0
  },
  onMount: {
    type: Function,
    default: void 0
  },
  onReady: {
    type: Function,
    default: void 0
  },
  onUnmount: {
    type: Function,
    default: void 0
  },
  onResult: {
    type: Function,
    default: void 0
  },
  onSuccess: {
    type: Function,
    default: void 0
  },
  onError: {
    type: Function,
    default: void 0
  },
  onSubmitResult: {
    type: Function,
    default: void 0
  },
  onSubmitSuccess: {
    type: Function,
    default: void 0
  },
  onSubmitError: {
    type: Function,
    default: void 0
  },
  onEvent: {
    type: Function,
    default: void 0
  }
}, ze = g({
  name: "FormieVueForm",
  props: je,
  emits: ["mount", "ready", "unmount", "result", "success", "error", "submit-result", "submit-success", "submit-error", "event"],
  setup(e, { emit: t }) {
    return () => {
      const n = {
        source: e.source,
        transport: e.transport,
        endpoint: e.endpoint,
        formHandle: e.formHandle,
        staticCache: e.staticCache,
        refreshTokens: e.refreshTokens,
        locale: e.locale,
        siteId: e.siteId,
        autoVisible: e.autoVisible,
        theme: e.theme,
        themeConfig: e.themeConfig,
        className: e.className,
        onMount: e.onMount,
        onReady: e.onReady,
        onUnmount: e.onUnmount,
        onResult: e.onResult,
        onSuccess: e.onSuccess,
        onError: e.onError,
        onSubmitResult: e.onSubmitResult,
        onSubmitSuccess: e.onSubmitSuccess,
        onSubmitError: e.onSubmitError,
        onEvent: e.onEvent
      };
      return u(Oe, {
        options: n,
        onMount: (o) => t("mount", o),
        onReady: (o) => t("ready", o),
        onUnmount: () => t("unmount"),
        onResult: (o) => t("result", o),
        onSuccess: (o) => t("success", o),
        onError: (o) => t("error", o),
        onSubmitResult: (o) => t("submit-result", o),
        onSubmitSuccess: (o) => t("submit-success", o),
        onSubmitError: (o) => t("submit-error", o),
        onEvent: (o) => t("event", o)
      });
    };
  }
}), Be = g({
  name: "FormieVueClientForm",
  props: Me,
  emits: ["mount", "ready", "unmount", "result", "success", "error", "submit-result", "submit-success", "submit-error", "event"],
  setup(e, { emit: t }) {
    return () => u(ke, {
      source: xe({
        source: e.source,
        transport: e.transport,
        endpoint: e.endpoint,
        formHandle: e.formHandle,
        siteId: e.siteId,
        components: e.components,
        fieldComponents: e.fieldComponents,
        slots: e.slots,
        className: e.className,
        onMount: e.onMount,
        onReady: e.onReady,
        onUnmount: e.onUnmount,
        onResult: e.onResult,
        onSuccess: e.onSuccess,
        onError: e.onError,
        onSubmitResult: e.onSubmitResult,
        onSubmitSuccess: e.onSubmitSuccess,
        onSubmitError: e.onSubmitError,
        onEvent: e.onEvent
      }),
      components: e.components,
      fieldComponents: e.fieldComponents,
      slots: e.slots,
      className: e.className,
      onMount: (n) => {
        e.onMount?.(n), t("mount", n);
      },
      onReady: (n) => {
        e.onReady?.(n), t("ready", n);
      },
      onUnmount: () => {
        e.onUnmount?.(), t("unmount");
      },
      onSubmitResult: (n) => {
        e.onSubmitResult?.(n), e.onResult?.(n), t("result", n), t("submit-result", n);
      },
      onSubmitSuccess: (n) => {
        e.onSubmitSuccess?.(n), e.onSuccess?.(n), t("success", n), t("submit-success", n);
      },
      onSubmitError: (n) => {
        e.onSubmitError?.(n), e.onError?.(n), t("error", n), t("submit-error", n);
      },
      onEvent: (n) => {
        e.onEvent?.(n), t("event", n);
      }
    });
  }
});
export {
  Be as FormieClientForm,
  ze as FormieForm,
  Le as createVueFormieClient,
  He as useFormie,
  Ie as useFormieClient,
  Te as useFormieField,
  _e as useFormieHtml,
  Ue as useFormieInstance,
  Pe as useFormiePage,
  Ke as useFormieSlot
};
