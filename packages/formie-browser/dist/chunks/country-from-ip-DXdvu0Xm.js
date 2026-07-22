const e = "formie/address/country-from-ip";
let r = null;
function c(t) {
  return new URL(t.startsWith("/") ? t : `/actions/${t}`, window.location.origin).toString();
}
async function u(t = e) {
  return r || (r = (async () => {
    try {
      const n = await fetch(c(t), {
        headers: {
          Accept: "application/json"
        }
      });
      if (!n.ok)
        return null;
      const o = await n.json();
      return o?.countryCode ? o : null;
    } catch {
      return null;
    }
  })()), r;
}
function i(t = e) {
  return (n) => {
    u(t).then((o) => {
      n(o?.countryCode?.toLowerCase() || "");
    });
  };
}
export {
  i as c,
  u as f
};
