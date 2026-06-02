const e={minHeight:280,markup:String.raw`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="messages-demo">
            <div class="formie-form-header formie-form-messages" data-formie-form-messages-top>
                <div class="formie-errors" data-formie-errors>
                    <div class="formie-message formie-message-error" data-formie-message data-formie-message-error role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="formie-error" data-formie-error>There was a problem submitting the form. Review the highlighted fields and try again.</div>
                    </div>
                </div>

                <div class="formie-successes" data-formie-success-container>
                    <div class="formie-message formie-message-success" data-formie-message data-formie-message-success role="status" aria-live="polite" aria-atomic="true">
                        <div data-formie-success>Your message has been queued successfully. We will get back to you shortly.</div>
                    </div>
                </div>
            </div>

            <div class="formie-form-footer formie-form-messages" data-formie-form-messages-bottom>
                <div class="formie-message formie-message-error" data-formie-message data-formie-message-error>
                    <div class="formie-error" data-formie-error>Longer guidance should wrap naturally and still feel comfortably separated from the rest of the form content when displayed at the bottom of the form.</div>
                </div>
            </div>
        </form>
    `};export{e as default};
