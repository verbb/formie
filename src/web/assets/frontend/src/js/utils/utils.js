export const isEmpty = function(obj) {
    return obj && Object.keys(obj).length === 0 && obj.constructor === Object;
};

export const toBoolean = function(val) {
    return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;
};

export const eventKey = function(eventName) {
    return eventName + '.' + Math.random();
};

