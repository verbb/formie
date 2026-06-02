import React from 'react';

const DEFAULT_HEADING_STYLES = {
    h1: { fontWeight: 'bold', fontSize: '1.5rem' },
    h2: { fontWeight: 'bold', fontSize: '1.25rem' },
    h3: { fontWeight: 'bold', fontSize: '1.125rem' },
    h4: { fontWeight: 'bold', fontSize: '1rem' },
    h5: { fontWeight: 'bold', fontSize: '0.875rem' },
    h6: { fontWeight: 'bold', fontSize: '0.75rem' },
};

export const PreviewHeading = ({ level = 'h2', text = '' }) => {
    const normalizedLevel = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(level) ? level : 'h2';
    const Tag = normalizedLevel;

    return <Tag style={DEFAULT_HEADING_STYLES[normalizedLevel]}>{text}</Tag>;
};
