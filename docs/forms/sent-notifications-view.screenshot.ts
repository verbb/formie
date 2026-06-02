import { defineScreenshotScenario } from '@verbb/docs-screenshots/api';
import { createCpDetailViewPreset } from '../.screenshots/formie/presets';
import { seedContactFormFixture, type ContactFormFixture } from '../.screenshots/formie/fixtures';

let fixture: ContactFormFixture | null = null;

// This screen has been the most layout-sensitive of the lot. The screenshot is
// deliberately anchored to a cloned preview block so we can present the email
// content cleanly even when the live page layout is still a little awkward.
const preset = createCpDetailViewPreset({
    viewport: {
        width: 1000,
        height: 800,
        deviceScaleFactor: 2,
    },
    selector: '#docs-screenshot-sent-notification-preview',
    padding: {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
    },
    hidePlaceholder: false,
});

export default defineScreenshotScenario({
    id: 'forms-sent-notifications-view',
    output: '_screenshots/forms/sent-notifications-view.png',
    route: () => {
        const sentNotificationId = fixture?.sentNotificationIds[0];
        return sentNotificationId ? `/admin/formie/sent-notifications/edit/${sentNotificationId}` : '/admin/formie/sent-notifications';
    },
    viewport: preset.viewport,
    async setup(context) {
        fixture = await seedContactFormFixture(context, {
            includeNotifications: true,
            includeSentNotifications: true,
        });
    },
    waitFor: [
        { type: 'text', text: 'Status', selector: '#details-container' },
        { type: 'text', text: 'Form Name', selector: '#details-container' },
        { type: 'selector', selector: '.fui-email-preview .email-iframe' },
    ],
    steps: [
        ...preset.steps,
        {
            type: 'evaluate',
            expression: `
                (() => {
                    // The live sent-notification page mixes preview and details
                    // layout in a way that has been hard to crop reliably. We
                    // collapse everything down to a dedicated screenshot-only
                    // preview node so the final capture is at least consistent.
                    window.scrollTo(0, 0);
                    document.documentElement.scrollLeft = 0;
                    document.documentElement.scrollTop = 0;
                    document.body.scrollLeft = 0;
                    document.body.scrollTop = 0;

                    const detailsContainer = document.querySelector('#details-container');
                    if (detailsContainer instanceof HTMLElement) {
                        detailsContainer.style.display = 'none';
                    }

                    document.querySelectorAll('#settings, #meta-details').forEach((node) => {
                        if (node instanceof HTMLElement) {
                            node.style.display = 'none';
                        }
                    });

                    const content = document.querySelector('#content');
                    if (content instanceof HTMLElement) {
                        content.style.maxWidth = 'none';
                        content.style.width = '100%';
                        content.style.margin = '0';
                    }

                    const contentPane = document.querySelector('.content-pane');
                    if (contentPane instanceof HTMLElement) {
                        contentPane.style.maxWidth = 'none';
                        contentPane.style.width = '100%';
                        contentPane.style.margin = '0';
                    }

                    const preview = document.querySelector('.fui-email-preview');
                    if (preview instanceof HTMLElement) {
                        document.querySelector('#docs-screenshot-sent-notification-preview')?.remove();

                        const clone = preview.cloneNode(true);

                        if (clone instanceof HTMLElement) {
                            // A detached, fixed-position clone avoids most of
                            // the reflow weirdness from the original page while
                            // still giving us the real rendered email content.
                            clone.id = 'docs-screenshot-sent-notification-preview';
                            clone.style.position = 'fixed';
                            clone.style.left = '50%';
                            clone.style.top = '80px';
                            clone.style.transform = 'translateX(-50%)';
                            clone.style.width = '900px';
                            clone.style.maxWidth = '900px';
                            clone.style.margin = '0';
                            clone.style.zIndex = '1000';
                            clone.style.background = '#fff';
                            clone.style.pointerEvents = 'none';

                            document.body.appendChild(clone);
                            preview.style.visibility = 'hidden';
                        }
                    }

                    document.querySelectorAll('*').forEach((node) => {
                        if (!(node instanceof HTMLElement)) {
                            return;
                        }

                        if (node.scrollLeft !== 0) {
                            node.scrollLeft = 0;
                        }

                        if (node.scrollTop !== 0) {
                            node.scrollTop = 0;
                        }
                    });
                })();
            `,
        },
        {
            type: 'wait',
            waitFor: {
                type: 'timeout',
                ms: 250,
            },
        },
    ],
    target: preset.target,
    caption: 'Sent notification detail view showing the rendered email preview and metadata.',
    intent: 'Capture the sent notification detail screen rather than the index listing.',
});
