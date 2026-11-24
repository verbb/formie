import { afterAll, describe, expect, it } from 'vitest';

import { getSubmissionIndexDateCriteria, toPickedDay } from './submissionIndexDates.js';

// Pin a daylight-saving zone west of UTC, so the cases below cover both the day lengths and the
// mobile picker's UTC midnights. In 2026, America/Santiago turns its clocks back at midnight on
// 4 April (a 25-hour day) and skips from midnight to 01:00 on 6 September (a 23-hour day).
const originalTimeZone = process.env.TZ;
process.env.TZ = 'America/Santiago';

afterAll(() => {
    if (originalTimeZone === undefined) {
        delete process.env.TZ;
    } else {
        process.env.TZ = originalTimeZone;
    }
});

const HOUR = 60 * 60 * 1000;

// What Craft's picker gives on mobile, where a `type="date"` value goes through `Date.parse()`.
const mobilePickerDate = (ymd) => {
    return new Date(Date.parse(ymd));
};

describe('pinned time zone', () => {
    it('has the daylight-saving days the cases rely on', () => {
        expect(new Date(2026, 3, 5) - new Date(2026, 3, 4)).toBe(25 * HOUR);
        expect(new Date(2026, 8, 7) - new Date(2026, 8, 6)).toBe(23 * HOUR);
        expect(new Date(2026, 8, 6).getHours()).toBe(1);
        expect(mobilePickerDate('2026-04-04').getDate()).toBe(3);
    });
});

describe('toPickedDay', () => {
    it('keeps a desktop picker date', () => {
        expect(toPickedDay(new Date(2026, 3, 4))).toEqual(new Date(2026, 3, 4));
        // A local time whose UTC date is the next day must still read as the local day.
        expect(toPickedDay(new Date(2026, 5, 15, 22))).toEqual(new Date(2026, 5, 15));
    });

    it.each([
        ['an ordinary day', '2026-06-15', new Date(2026, 5, 15)],
        ['a 25-hour day', '2026-04-04', new Date(2026, 3, 4)],
        ['a day whose clocks skip midnight', '2026-09-06', new Date(2026, 8, 6)],
    ])('reads a mobile picker date on %s as the day picked', (_name, ymd, expected) => {
        expect(toPickedDay(mobilePickerDate(ymd))).toEqual(expected);
    });

    it('keeps a picked day unchanged when read again', () => {
        const day = toPickedDay(mobilePickerDate('2026-09-06'));

        expect(toPickedDay(day)).toEqual(day);
    });

    it('drops missing and invalid dates', () => {
        expect(toPickedDay(null)).toBeNull();
        expect(toPickedDay(new Date(Number.NaN))).toBeNull();
    });
});

describe('getSubmissionIndexDateCriteria', () => {
    it('sends nothing without dates', () => {
        expect(getSubmissionIndexDateCriteria(null, null)).toBeNull();
    });

    it('covers whole calendar days from the start of From to the start of the day after To', () => {
        expect(getSubmissionIndexDateCriteria(new Date(2026, 5, 15), new Date(2026, 5, 16))).toEqual([
            'and',
            '>=2026-06-15 00:00:00',
            '<2026-06-17 00:00:00',
        ]);
    });

    // Fixed 24-hour arithmetic ended the 25-hour day an hour early, still on the same day.
    it.each([
        ['a 25-hour day', new Date(2026, 3, 4), '>=2026-04-04 00:00:00', '<2026-04-05 00:00:00'],
        ['a 23-hour day that starts at 01:00', new Date(2026, 8, 6), '>=2026-09-06 00:00:00', '<2026-09-07 00:00:00'],
        ['the last day of the year', new Date(2026, 11, 31), '>=2026-12-31 00:00:00', '<2027-01-01 00:00:00'],
    ])('ends %s at the next midnight', (_name, day, start, end) => {
        expect(getSubmissionIndexDateCriteria(day, day)).toEqual(['and', start, end]);
    });

    it('covers the day picked on mobile', () => {
        const day = toPickedDay(mobilePickerDate('2026-04-04'));

        expect(getSubmissionIndexDateCriteria(day, day)).toEqual(['and', '>=2026-04-04 00:00:00', '<2026-04-05 00:00:00']);
    });

    it('supports an open-ended range', () => {
        expect(getSubmissionIndexDateCriteria(new Date(2026, 3, 4), null)).toEqual(['and', '>=2026-04-04 00:00:00']);
        expect(getSubmissionIndexDateCriteria(null, new Date(2026, 3, 4))).toEqual(['and', '<2026-04-05 00:00:00']);
    });
});
