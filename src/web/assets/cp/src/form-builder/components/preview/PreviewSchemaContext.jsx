import React, { createContext, useContext } from 'react';

const PreviewSchemaContext = createContext(null);

export const PreviewSchemaProvider = ({ value, children }) => {
    return (
        <PreviewSchemaContext.Provider value={value}>
            {children}
        </PreviewSchemaContext.Provider>
    );
};

export const usePreviewSchemaContext = () => {
    const context = useContext(PreviewSchemaContext);

    if (!context) {
        throw new Error('usePreviewSchemaContext must be used within a PreviewSchemaProvider');
    }

    return context;
};
