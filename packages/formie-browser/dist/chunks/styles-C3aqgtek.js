const t = /* @__PURE__ */ new Set();
function l(n, r) {
  if (typeof document > "u")
    return;
  const e = `formie-module-style:${n}`;
  if (t.has(e) || document.querySelector(`style[data-formie-module-style="${n}"]`)) {
    t.add(e);
    return;
  }
  const d = r.filter((s) => typeof s == "string" && s.trim().length > 0).join(`
`);
  if (!d) {
    t.add(e);
    return;
  }
  const o = document.createElement("style");
  o.setAttribute("data-formie-module-style", n), o.textContent = d, document.head.appendChild(o), t.add(e);
}
export {
  l as e
};
