import { w as l } from "./index-CZtn5KAB.js";
const o = /* @__PURE__ */ new Map();
function d() {
  const e = document.querySelector('meta[property="csp-nonce"], meta[name="csp-nonce"]');
  return e && (e.nonce || e.getAttribute("nonce") || e.getAttribute("content")) || null;
}
async function p(e, r = 5e3) {
  return l(() => {
    const n = window[e];
    return typeof n > "u" || n === null ? null : n;
  }, {
    timeoutMs: r,
    intervalMs: 30
  });
}
async function f({
  id: e,
  src: r,
  async: n = !0,
  defer: a = !0
}) {
  const c = document.getElementById(e);
  return c || (o.has(e) || o.set(e, new Promise((s, i) => {
    const t = document.createElement("script");
    t.id = e, t.src = r, t.async = n, t.defer = a;
    const u = d();
    u && t.setAttribute("nonce", u), t.onload = () => {
      s(t);
    }, t.onerror = () => {
      o.delete(e), i(new Error(`Failed to load external script: ${r}`));
    }, document.body.appendChild(t);
  })), o.get(e));
}
async function w(e, r) {
  const n = window[e];
  return typeof n < "u" && n !== null ? n : (await f(r), p(e, r.timeoutMs));
}
export {
  f as a,
  p as e,
  w as l
};
