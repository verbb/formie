import { w as c } from "./index-BneHZL41.js";
const o = /* @__PURE__ */ new Map();
async function l(e, r = 5e3) {
  return c(() => {
    const n = window[e];
    return typeof n > "u" || n === null ? null : n;
  }, {
    timeoutMs: r,
    intervalMs: 30
  });
}
async function d({
  id: e,
  src: r,
  async: n = !0,
  defer: s = !0
}) {
  const a = document.getElementById(e);
  return a || (o.has(e) || o.set(e, new Promise((i, u) => {
    const t = document.createElement("script");
    t.id = e, t.src = r, t.async = n, t.defer = s, t.onload = () => {
      i(t);
    }, t.onerror = () => {
      o.delete(e), u(new Error(`Failed to load external script: ${r}`));
    }, document.body.appendChild(t);
  })), o.get(e));
}
async function p(e, r) {
  const n = window[e];
  return typeof n < "u" && n !== null ? n : (await d(r), l(e, r.timeoutMs));
}
export {
  d as a,
  l as e,
  p as l
};
