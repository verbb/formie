import { create } from 'zustand';

const useElementStore = create((set, get) => ({
    elementData: {},

    getElementData: (fieldName) => {
        return get().elementData[fieldName];
    },

    setElementData: (fieldName, data) => {
        set((state) => ({
            ...state,
            elementData: {
                ...state.elementData,
                [fieldName]: data,
            },
        }));
    },

    hasElementData: (fieldName) => {
        return fieldName in get().elementData;
    },
}));

export default useElementStore;
