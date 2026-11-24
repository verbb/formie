import { formatViewerStartDate } from '@reports/utils/reportViewerDates';

const isMidnight = (hours, minutes, seconds, milliseconds) => {
    return hours === 0 && minutes === 0 && seconds === 0 && milliseconds === 0;
};

/**
 * Returns the calendar day a date from Craft's date range picker stands for, as a local date.
 *
 * On desktop the picker gives local midnights. On mobile it uses `type="date"` inputs and parses
 * their `Y-m-d` value with `Date.parse()`, which gives UTC midnight, so west of UTC its local date
 * is the day before the one picked. A date exactly on UTC midnight but not on local midnight can
 * only have come from there, so its UTC date is the picked day. In every other case, including
 * zones where the two midnights agree, the local date is.
 */
export const toPickedDay = (date) => {
    if (!date || Number.isNaN(date.getTime())) {
        return null;
    }

    const isUtcMidnight = isMidnight(date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds(), date.getUTCMilliseconds());
    const isLocalMidnight = isMidnight(date.getHours(), date.getMinutes(), date.getSeconds(), date.getMilliseconds());

    if (isUtcMidnight && !isLocalMidnight) {
        return new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
    }

    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
};

/**
 * Turns the index date picker's From and To days (from `toPickedDay()`) into element query criteria.
 *
 * Only each date's calendar day matters. Each day is sent as a wall-clock time, which Craft reads
 * in the site's time zone, so a day is the site's calendar day whatever zone the browser is in, and
 * keeps its real length across daylight-saving changes. This matches the export dialog and Craft's
 * own date condition rules, which end a range before midnight at the start of the day after the To day.
 */
export const getSubmissionIndexDateCriteria = (startDate, endDate) => {
    if (!startDate && !endDate) {
        return null;
    }

    const criteria = ['and'];

    if (startDate) {
        criteria.push(`>=${formatViewerStartDate(startDate)}`);
    }

    if (endDate) {
        const dayAfter = new Date(endDate.getFullYear(), endDate.getMonth(), endDate.getDate() + 1);

        criteria.push(`<${formatViewerStartDate(dayAfter)}`);
    }

    return criteria;
};
