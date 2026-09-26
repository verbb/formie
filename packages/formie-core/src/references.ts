/** Shared grammar and explicit browser projection evaluation. */
export interface ReferenceExpression {
    raw: string;
    target: string;
    identifier: string;
    selector: string;
    default: string;
    transformerId: string;
    transformerParams: Record<string, string>;
    version: number;
    isValid: boolean;
    diagnostic?: string;
}

const aliases: Record<string, string> = { username: 'user:name' };
Object.entries({ form: ['name', 'handle'], submission: ['id', 'uid', 'title', 'url', 'date', 'site', 'status'], site: ['id', 'name', 'handle', 'url', 'language'] }).forEach(([target, names]) => {
    names.forEach((name) => { aliases[`${target}.${name}`] = `${target}:${name}`; });
});
Object.entries({ form: ['Name', 'Handle'], submission: ['Title', 'Url', 'Id', 'Uid', 'Date', 'Site', 'Status'], system: ['Name', 'Email', 'ReplyTo'], site: ['Name', 'Handle', 'Url', 'Id', 'Language'], user: ['Ip', 'Id', 'Email', 'FullName', 'FirstName', 'LastName'] }).forEach(([target, names]) => {
    names.forEach((name) => { aliases[target + name] = `${target}:${name[0].toLowerCase()}${name.slice(1)}`; });
});
Object.entries({ dateUs: 'm/d/Y', dateInt: 'd/m/Y', time12: 'h:i a', time24: 'H:i' }).forEach(([name, pattern]) => {
    aliases[name] = `timestamp;transform=format;preset=custom;pattern=${encodeURIComponent(pattern)}`;
});

export function parseReference(raw: string): ReferenceExpression {
    const result: ReferenceExpression = { raw, target: '', identifier: '', selector: '', default: '', transformerId: '', transformerParams: {}, version: 1, isValid: false };
    const invalid = (diagnostic: string) => ({ ...result, diagnostic });
    const match = raw.trim().match(/^\{([^{}]+)\}$/);
    if (!match) return invalid('invalidSyntax');
    let body = match[1].replace(/^field\./, 'field:');
    const separator = body.indexOf('|');
    const defaultValue = separator < 0 ? '' : body.slice(separator + 1);
    body = separator < 0 ? body : body.slice(0, separator);
    const splitAlias = body.indexOf(';');
    const sourceAlias = splitAlias < 0 ? body : body.slice(0, splitAlias);
    body = (Object.prototype.hasOwnProperty.call(aliases, sourceAlias) ? aliases[sourceAlias] : sourceAlias) + (splitAlias < 0 ? '' : body.slice(splitAlias));
    const [source, ...parts] = body.split(';');
    const metadata: Record<string, string> = Object.create(null);
    try {
        for (const part of parts) {
            const entry = part.match(/^([a-zA-Z][a-zA-Z0-9_]*)=(.*)$/);
            if (!entry || Object.prototype.hasOwnProperty.call(metadata, entry[1])) return invalid('invalidMetadata');
            metadata[entry[1]] = decodeURIComponent(entry[2]);
        }
        if ((metadata.v ?? '1') !== '1') return invalid('unsupportedVersion');
        delete metadata.v;
        const colon = source.indexOf(':');
        const target = colon < 0 ? source : source.slice(0, colon);
        let identifier = colon < 0 ? '' : source.slice(colon + 1);
        if (!/^[a-zA-Z][a-zA-Z0-9_-]*$/.test(target)) return invalid('invalidSource');
        let selector = '';
        if (target === 'field' && identifier.includes(':')) {
            const split = identifier.indexOf(':');
            selector = identifier.slice(split + 1);
            identifier = identifier.slice(0, split);
        }
        if ((!['timestamp', 'allFields', 'allContentFields', 'allVisibleFields'].includes(target) && !identifier) || /[\s{}]/.test(identifier + selector)) return invalid('invalidIdentifier');
        const transformerId = metadata.transform ?? '';
        delete metadata.transform;
        return { ...result, target, identifier: decodeURIComponent(identifier), selector: decodeURIComponent(selector), default: decodeURIComponent(defaultValue), transformerId, transformerParams: metadata, isValid: true };
    } catch {
        return invalid('invalidEncoding');
    }
}

export function serializeReference(expression: ReferenceExpression): string {
    if (!expression.isValid || expression.version !== 1) throw new Error('Cannot serialize an invalid reference expression.');
    const component = (value: string) => encodeURIComponent(value).replace(/[!'()*]/g, (char) => `%${char.charCodeAt(0).toString(16).toUpperCase()}`);
    const encode = (value: string) => component(value).replace(/%2F/g, '/');
    let body = expression.target;
    if (expression.identifier) body += `:${encode(expression.identifier)}`;
    if (expression.selector) body += `:${encode(expression.selector).replace(/%3A/g, ':')}`;
    if (expression.transformerId) body += `;transform=${component(expression.transformerId)}`;
    for (const [key, value] of Object.entries(expression.transformerParams)) body += `;${key}=${component(value)}`;
    if (expression.default) body += `|${component(expression.default)}`;
    return `{${body}}`;
}

export interface ReferenceDefinition {
    id: string;
    availability: { server: boolean; browser: boolean };
    selectors?: string[];
    transforms?: string[];
}
export interface ReferenceContext {
    definitions: Record<string, ReferenceDefinition>;
    values: Record<string, unknown>;
    transforms?: Record<string, { browser: boolean; parameters: string[]; accepts: (value: unknown) => boolean; acceptsOutput: (value: unknown) => boolean; resolve: (value: unknown, parameters: Record<string, string>) => unknown }>;
}
export interface ResolvedReference {
    expression: ReferenceExpression;
    value?: unknown;
    diagnostic?: 'invalidExpression' | 'missingField' | 'unknownSource' | 'forbiddenSource' | 'invalidSelector' | 'unknownTransform' | 'invalidType' | 'invalidRowScope';
}

/** Browser evaluation accepts only explicitly supplied browser projections and definitions. */
export function resolveReference(raw: string, context: ReferenceContext): ResolvedReference {
    const expression = parseReference(raw);
    if (!expression.isValid) return { expression, diagnostic: 'invalidExpression' };
    const id = `${expression.target}:${expression.identifier}`;
    const own = (object: object, key: string) => Object.prototype.hasOwnProperty.call(object, key);
    if (!own(context.definitions, id)) return { expression, diagnostic: expression.target === 'field' ? 'missingField' : 'unknownSource' };
    const definition = context.definitions[id];
    if (!definition.availability.browser) return { expression, diagnostic: 'forbiddenSource' };
    if (Object.keys(expression.transformerParams).some((key) => ['scope', 'index', 'rows'].includes(key))) return { expression, diagnostic: 'invalidRowScope' };
    if (expression.selector && !definition.selectors?.includes(expression.selector)) return { expression, diagnostic: 'invalidSelector' };
    const key = expression.selector ? `${id}:${expression.selector}` : id;
    if (!own(context.values, key)) return { expression, diagnostic: 'missingField' };
    let value = context.values[key];
    if (expression.transformerId) {
        const transform = context.transforms?.[expression.transformerId];
        if (!transform) return { expression, diagnostic: 'unknownTransform' };
        if (!transform.browser) return { expression, diagnostic: 'forbiddenSource' };
        if ((definition.transforms && !definition.transforms.includes(expression.transformerId)) || !transform.accepts(value) || Object.keys(expression.transformerParams).some((key) => !transform.parameters.includes(key))) return { expression, diagnostic: 'invalidType' };
        value = transform.resolve(value, expression.transformerParams);
        if (!transform.acceptsOutput(value)) return { expression, diagnostic: 'invalidType' };
    } else if (Object.keys(expression.transformerParams).length) return { expression, diagnostic: 'invalidExpression' };
    if ((value === '' || value == null || (Array.isArray(value) && value.length === 0)) && expression.default) value = expression.default;
    return { expression, value };
}
