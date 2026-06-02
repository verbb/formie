import type { FormModuleManifest } from '../../../../formie-browser/src/index';

export type FormiePreviewSourceDefinition = {
    markup: string;
    minHeight?: number;
    modules?: FormModuleManifest[];
};

const previewModules = import.meta.glob('../../../**/*.preview.ts');

type PreviewSourceModule = {
    default?: FormiePreviewSourceDefinition;
    preview?: FormiePreviewSourceDefinition;
};

function getRouteDirectory(routePath: string): string {
    const sanitizedPath = routePath.split(/[?#]/, 1)[0] || '/';

    if (sanitizedPath.endsWith('/')) {
        return sanitizedPath;
    }

    return `${sanitizedPath.slice(0, sanitizedPath.lastIndexOf('/') + 1)}`;
}

function stripSiteBase(docPath: string, siteBase = '/'): string {
    if (siteBase === '/' || !docPath.startsWith(siteBase)) {
        return docPath;
    }

    return `/${docPath.slice(siteBase.length)}`;
}

function resolveDocPath(src: string, routePath: string, siteBase = '/'): string {
    if (src.startsWith('@/')) {
        return `/${src.slice(2)}`;
    }

    return stripSiteBase(new URL(src, `https://docs.local${getRouteDirectory(routePath)}`).pathname, siteBase);
}

function toModuleKey(docPath: string): string {
    return `../../../${docPath.replace(/^\//, '')}`;
}

export async function resolvePreviewSource(src: string, routePath: string, siteBase = '/'): Promise<FormiePreviewSourceDefinition | null> {
    const docPath = resolveDocPath(src, routePath, siteBase);
    const moduleKey = toModuleKey(docPath);
    const loader = previewModules[moduleKey];

    if (!loader) {
        console.warn(`[FormiePreview] No preview source found for "${src}" resolved from "${routePath}".`);
        return null;
    }

    const module = await loader() as PreviewSourceModule;

    return module.default ?? module.preview ?? null;
}
