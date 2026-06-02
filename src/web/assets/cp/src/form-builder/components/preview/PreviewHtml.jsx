import React from 'react';

export const PreviewHtml = ({ html = '' }) => {
    return <div dangerouslySetInnerHTML={{ __html: html || '' }} />;
};
