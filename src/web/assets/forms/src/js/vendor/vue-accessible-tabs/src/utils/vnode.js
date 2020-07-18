export function cleanChildren(vnodes) {
    if (!vnodes) return [];
    return vnodes.filter((vnode) => vnode.tag);
}
