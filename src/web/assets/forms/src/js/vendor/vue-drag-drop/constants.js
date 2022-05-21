const keyMirror = (keys) => { return keys.reduce((acc, k) => { return (acc[k] = k) && acc; }, {}); };

// Vue 3 has `v-on` Listener Inheritance, so events for `@drag` will be fired twice.
// One for the event listener on the component, and again for the custom one we fire.
// So ensure we change the name of the event to something custom.
export const events = {
    'drag': 'on-drag',
    'dragend': 'on-dragend',
    'dragenter': 'on-dragenter',
    'dragleave': 'on-dragleave',
    'dragstart': 'on-dragstart',
    'dragover': 'on-dragover',
    'drop': 'on-drop',
};

export const dropEffects = keyMirror(['copy', 'move', 'link', 'none']);
export const effectsAllowed = keyMirror([
    'none', 'copy', 'copyLink', 'copyMove', 'link', 'linkMove', 'move', 'all',
    'uninitialized',
]);
