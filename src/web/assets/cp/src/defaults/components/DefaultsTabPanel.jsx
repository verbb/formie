import { TabPanel } from '@verbb/plugin-kit-react/components';

/**
 * Pane tab body for Defaults / Form Groups.
 * Padding must live on light-DOM content — host className on pk-tab-panel cannot
 * inset the shadow `.content` slot (same pattern as ReportTabPanel / FormBuilderTabContent).
 */
export const DefaultsTabPanel = ({ value, children }) => (
    <TabPanel value={value}>
        <div className="formie-defaults-panel">{children}</div>
    </TabPanel>
);
