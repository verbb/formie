import { Modifier } from '@dnd-kit/abstract';
import { getEventCoordinates } from '@dnd-kit/utilities';

export class SnapTopLeftCornerToCursor extends Modifier {
    apply({
        activatorEvent, shape, source, transform,
    }) {
        if (!activatorEvent || !shape?.initial) {
            return transform;
        }

        const activatorCoordinates = getEventCoordinates(activatorEvent);
        if (!activatorCoordinates) {
            return transform;
        }

        const { left, top } = shape.initial.boundingRectangle;
        const offsetX = activatorCoordinates.x - left;
        const offsetY = activatorCoordinates.y - top;
        const sourceData = source?.data?.current ?? source?.data;
        const cursorNudge = sourceData?.cursorNudge ?? {};
        const nudgeX = Number.isFinite(cursorNudge?.x) ? cursorNudge.x : 0;
        const nudgeY = Number.isFinite(cursorNudge?.y) ? cursorNudge.y : 0;

        return {
            ...transform,
            x: transform.x + offsetX + nudgeX,
            y: transform.y + offsetY + nudgeY,
        };
    }
}
