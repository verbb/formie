import { useEffect, useMemo, useState } from 'react';

import {
    DatePicker,
    Input,
    SelectInput,
    TimePicker,
} from '@verbb/plugin-kit-react/components';

import {
    DATE_BOUND_OFFSET_OPTIONS,
    DATE_BOUND_OFFSET_TYPE_OPTIONS,
    DATE_BOUND_OPTIONS,
    DEFAULT_DATE_BOUND,
    normalizeDateBound,
} from '@reports/utils/reportDateBound';

const parseBoundDateTime = (value, defaultTime) => {
    if (!value) {
        return {
            date: undefined,
            time: defaultTime,
        };
    }

    const dateOnlyMatch = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?:\s|$)/);

    if (dateOnlyMatch) {
        const [, year, month, day] = dateOnlyMatch;

        return {
            date: new Date(Number(year), Number(month) - 1, Number(day)),
            time: defaultTime,
        };
    }

    const normalizedValue = String(value).includes('T')
        ? String(value)
        : String(value).replace(' ', 'T');

    const parsed = new Date(normalizedValue);

    if (Number.isNaN(parsed.getTime())) {
        return {
            date: undefined,
            time: defaultTime,
        };
    }

    const hours = String(parsed.getHours()).padStart(2, '0');
    const minutes = String(parsed.getMinutes()).padStart(2, '0');

    return {
        date: parsed,
        time: `${hours}:${minutes}`,
    };
};

const formatBoundDateTime = (date, time) => {
    if (!date) {
        return null;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const timeValue = time || '00:00';

    return `${year}-${month}-${day} ${timeValue}:00`;
};

export function ReportDateBoundEditor({
    value = DEFAULT_DATE_BOUND,
    onChange,
    disabled = false,
    boundary = 'start',
}) {
    const bound = normalizeDateBound(value, boundary);
    const defaultTime = boundary === 'end' ? '23:59' : '00:00';
    const parsedDate = useMemo(() => {
        return parseBoundDateTime(bound.date, defaultTime);
    }, [bound.date, defaultTime]);
    const [dateValue, setDateValue] = useState(parsedDate.date);
    const [timeValue, setTimeValue] = useState(parsedDate.time);

    useEffect(() => {
        setDateValue(parsedDate.date);
        setTimeValue(parsedDate.time);
    }, [parsedDate.date, parsedDate.time]);

    const emitChange = (nextBound) => {
        onChange?.(normalizeDateBound(nextBound, boundary));
    };

    const handleOptionChange = (nextOption) => {
        emitChange({
            ...bound,
            option: nextOption,
            date: nextOption === 'date' ? bound.date : null,
        });
    };

    const handleDateChange = (nextDate) => {
        setDateValue(nextDate ?? undefined);

        if (!nextDate) {
            emitChange({
                ...bound,
                option: 'date',
                date: null,
            });
            return;
        }

        emitChange({
            ...bound,
            option: 'date',
            date: formatBoundDateTime(nextDate, timeValue || defaultTime),
        });
    };

    const handleTimeChange = (nextTime) => {
        setTimeValue(nextTime);

        if (!dateValue) {
            return;
        }

        emitChange({
            ...bound,
            option: 'date',
            date: formatBoundDateTime(dateValue, nextTime || defaultTime),
        });
    };

    return (
        <div className="flex flex-col gap-3">
            <SelectInput
                value={bound.option}
                options={DATE_BOUND_OPTIONS}
                disabled={disabled}
                onChange={handleOptionChange}
                triggerClassName="w-full max-w-md"
            />

            {bound.option === 'date' && (
                <div className="flex flex-wrap gap-2">
                    <DatePicker
                        value={dateValue}
                        placeholder={Craft.t('formie', 'Select Date')}
                        disabled={disabled}
                        onValueChange={handleDateChange}
                    />
                    <TimePicker
                        value={timeValue}
                        disabled={disabled || !dateValue}
                        onValueChange={handleTimeChange}
                    />
                </div>
            )}

            {bound.option === 'today' && (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-gray-500">{Craft.t('formie', 'Offset')}</span>
                    <SelectInput
                        value={bound.offset}
                        options={DATE_BOUND_OFFSET_OPTIONS}
                        disabled={disabled}
                        onChange={(nextOffset) => {
                            emitChange({
                                ...bound,
                                offset: nextOffset,
                            });
                        }}
                        triggerClassName="min-w-[120px]"
                    />
                    <Input
                        type="number"
                        min={0}
                        value={bound.offsetNumber}
                        disabled={disabled}
                        className="w-[88px]"
                        onChange={(event) => {
                            emitChange({
                                ...bound,
                                offsetNumber: Math.max(0, Number.parseInt(event.target.value, 10) || 0),
                            });
                        }}
                    />
                    <SelectInput
                        value={bound.offsetType}
                        options={DATE_BOUND_OFFSET_TYPE_OPTIONS}
                        disabled={disabled}
                        onChange={(nextOffsetType) => {
                            emitChange({
                                ...bound,
                                offsetType: nextOffsetType,
                            });
                        }}
                        triggerClassName="min-w-[120px]"
                    />
                </div>
            )}
        </div>
    );
}
