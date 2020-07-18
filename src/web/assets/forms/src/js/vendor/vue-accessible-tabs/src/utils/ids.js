window.__tabs = {
    ids: {
        current: 0,
        blacklist: [],
    },
};

const { ids } = window.__tabs;

export function useId() {
    const id = ++ids.current;

    // If the generated ID has already been used
    //  we can recursively attempt to generate a new ID.
    if (ids.blacklist.includes(id)) {
        return useId();
    } else {
        return id;
    }
}

export function useCustomId(id) {
    // If they requested an ID that has already
    //  been generated, throw an error to bail out
    if (ids.blacklist.includes(id)) {
        throw new Error(`The id "${id}" has already been used`);
    }

    // Add this id to the blacklist
    ids.blacklist.push(id);

    // Return their requested ID
    return id;
}
