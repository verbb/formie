import {
    get as getValue,
    set as setValue,
    cloneDeep,
    find,
    findIndex,
    pullAt,
    isEqual,
} from 'lodash-es';

// Basic array operations (for direct array manipulation)
export const add = (array, item) => {
    return [...array, item];
};

export const insert = (array, index, item) => {
    const newArray = [...array];

    newArray.splice(index, 0, item);

    return newArray;
};

export const remove = (array, index) => {
    const newArray = [...array];

    newArray.splice(index, 1);

    return newArray;
};

export const update = (array, index, updates) => {
    const newArray = [...array];

    newArray[index] = { ...newArray[index], ...updates };

    return newArray;
};

export const move = (array, fromIndex, toIndex) => {
    const newArray = [...array];
    const [movedItem] = newArray.splice(fromIndex, 1);

    newArray.splice(toIndex, 0, movedItem);

    return newArray;
};

export const duplicate = (array, index, transformCallback = null) => {
    const newArray = [...array];
    const itemToDuplicate = newArray[index];
    const newItem = { ...itemToDuplicate };

    if (transformCallback) {
        transformCallback(newItem);
    }

    newArray.splice(index + 1, 0, newItem);

    return newArray;
};

// Path-based operations (accept dot notation by default)
export const get = (obj, path) => {
    return getValue(obj, path);
};

export const set = (obj, path, value) => {
    return setValue(cloneDeep(obj), path, value);
};

export const updateAt = (obj, path, updates) => {
    const currentValue = getValue(obj, path);

    if (currentValue && typeof currentValue === 'object' && !Array.isArray(currentValue)) {
        return setValue(cloneDeep(obj), path, { ...currentValue, ...updates });
    }

    return setValue(cloneDeep(obj), path, updates);
};

export const addAt = (obj, path, item) => {
    const currentArray = getValue(obj, path);

    if (!Array.isArray(currentArray)) {
        throw new Error(`Path ${path} does not point to an array`);
    }

    const newArray = [...currentArray, item];

    return setValue(cloneDeep(obj), path, newArray);
};

export const insertAt = (obj, path, item, index = null) => {
    const currentArray = getValue(obj, path);

    if (!Array.isArray(currentArray)) {
        throw new Error(`Path ${path} does not point to an array`);
    }

    const insertIndex = index !== null ? index : currentArray.length;
    const newArray = insert(currentArray, insertIndex, item);

    return setValue(cloneDeep(obj), path, newArray);
};

export const removeAt = (obj, path, index) => {
    const currentArray = getValue(obj, path);

    if (!Array.isArray(currentArray)) {
        throw new Error(`Path ${path} does not point to an array`);
    }

    const newArray = remove(currentArray, index);

    return setValue(cloneDeep(obj), path, newArray);
};

export const moveAt = (obj, path, fromIndex, toIndex) => {
    const currentArray = getValue(obj, path);

    if (!Array.isArray(currentArray)) {
        throw new Error(`Path ${path} does not point to an array`);
    }

    const newArray = move(currentArray, fromIndex, toIndex);

    return setValue(cloneDeep(obj), path, newArray);
};

export const findAt = (obj, path, predicate) => {
    const array = getValue(obj, path);

    if (!Array.isArray(array)) {
        throw new Error(`Path ${path} does not point to an array`);
    }

    return find(array, predicate);
};

export const findIndexAt = (obj, path, predicate) => {
    const array = getValue(obj, path);

    if (!Array.isArray(array)) {
        throw new Error(`Path ${path} does not point to an array`);
    }

    return findIndex(array, predicate);
};

export const removeMultipleAt = (obj, path, indices) => {
    const array = getValue(obj, path);

    if (!Array.isArray(array)) {
        throw new Error(`Path ${path} does not point to an array`);
    }

    const newArray = [...array];

    pullAt(newArray, indices);

    return setValue(cloneDeep(obj), path, newArray);
};

export const isEqualAt = (obj1, path1, obj2, path2) => {
    const value1 = getValue(obj1, path1);
    const value2 = getValue(obj2, path2);

    return isEqual(value1, value2);
};

export const lengthAt = (obj, path) => {
    const array = getValue(obj, path);

    return Array.isArray(array) ? array.length : 0;
};

export const has = (obj, path) => {
    const value = getValue(obj, path);

    return value !== undefined && value !== null;
};
