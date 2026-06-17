import { Spinner } from '@verbb/plugin-kit-react/components';

export function ReportDataLoadingOverlay() {
    return (
        <div
            className="absolute inset-0 z-10 flex items-center justify-center bg-white/60"
            aria-busy="true"
            aria-hidden="true"
        >
            <Spinner size="lg" />
        </div>
    );
}
