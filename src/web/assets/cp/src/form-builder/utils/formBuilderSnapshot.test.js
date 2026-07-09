import { describe, expect, it } from 'vitest';

import { stableSerialize } from '@form-builder/hooks/useUnloadWarning';
import { dirtyFormSnapshot } from './formBuilderSnapshot';

describe('dirtyFormSnapshot', () => {
    it('treats stale layout variant references as unchanged when active rows match', () => {
        const activeDateReference = '11111111-1111-4111-8111-111111111111';
        const staleDateReference = 'd294ef98-6bed-4de1-b072-31faedb5a304';
        const activeTimeReference = '22222222-2222-4222-8222-222222222222';

        const beforeHydration = {
            pages: [{
                rows: [{
                    fields: [{
                        type: 'verbb\\formie\\fields\\Date',
                        reference: 'parent-reference',
                        handle: 'dateSigned',
                        displayType: 'calendar',
                        rows: [{
                            fields: [
                                { handle: 'date', reference: activeDateReference },
                                { handle: 'time', reference: activeTimeReference },
                            ],
                        }],
                        layouts: {
                            calendar: [{
                                fields: [
                                    { handle: 'date', reference: staleDateReference },
                                    { handle: 'time', reference: activeTimeReference },
                                ],
                            }],
                        },
                    }],
                }],
            }],
        };

        const afterHydration = {
            pages: [{
                rows: [{
                    fields: [{
                        type: 'verbb\\formie\\fields\\Date',
                        reference: 'parent-reference',
                        handle: 'dateSigned',
                        displayType: 'calendar',
                        rows: [{
                            fields: [
                                { handle: 'date', reference: activeDateReference },
                                { handle: 'time', reference: activeTimeReference },
                            ],
                        }],
                        settings: {
                            displayType: 'calendar',
                            rows: [{
                                fields: [
                                    { handle: 'date', reference: activeDateReference },
                                    { handle: 'time', reference: activeTimeReference },
                                ],
                            }],
                            layouts: {
                                calendar: [{
                                    fields: [
                                        { handle: 'date', reference: staleDateReference },
                                        { handle: 'time', reference: activeTimeReference },
                                    ],
                                }],
                            },
                        },
                    }],
                }],
            }],
        };

        const beforeSnapshot = stableSerialize(dirtyFormSnapshot(beforeHydration));
        const afterSnapshot = stableSerialize(dirtyFormSnapshot(afterHydration));

        expect(afterSnapshot).toBe(beforeSnapshot);
        expect(beforeSnapshot).toContain(activeDateReference);
        expect(beforeSnapshot).not.toContain(staleDateReference);
    });
});

describe('formBuilderSnapshot', () => {
    it('keeps shared date sub-field references stable for dirty checks', () => {
        const sharedDateReference = 'a16c2d4b-33ad-436c-90a8-4a4b1dd95401';
        const sharedTimeReference = '277a9abd-6b91-4add-92d6-92f401b31d15';

        const formValues = {
            pages: [{
                rows: [{
                    fields: [{
                        type: 'verbb\\formie\\fields\\Date',
                        reference: 'parent-reference',
                        handle: 'dateSigned',
                        settings: {
                            displayType: 'calendar',
                            rows: [{
                                fields: [{
                                    handle: 'date',
                                    reference: sharedDateReference,
                                }, {
                                    handle: 'time',
                                    reference: sharedTimeReference,
                                }],
                            }],
                            layouts: {
                                calendar: [{
                                    fields: [{
                                        handle: 'date',
                                        reference: sharedDateReference,
                                    }, {
                                        handle: 'time',
                                        reference: sharedTimeReference,
                                    }],
                                }],
                                dropdowns: [{
                                    fields: [{
                                        handle: 'year',
                                        reference: 'year-reference',
                                    }],
                                }],
                            },
                        },
                    }],
                }],
            }],
        };

        const dirtySnapshot = stableSerialize(dirtyFormSnapshot(formValues));
        const secondDirtySnapshot = stableSerialize(dirtyFormSnapshot(formValues));

        expect(secondDirtySnapshot).toBe(dirtySnapshot);
        expect(dirtySnapshot).toContain(sharedDateReference);
        expect(dirtySnapshot).toContain(sharedTimeReference);
    });
});
