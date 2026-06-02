function n(e){return`
        <div class="formie-field" data-formie-field data-formie-field-handle="${e.handle}" data-formie-field-type="date" data-formie-input-id="${e.inputId}">
            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                <label class="formie-label formie-field-label" for="${e.inputId}" data-formie-label data-formie-field-label>${e.label}</label>
                <div class="formie-field-content" data-formie-field-content>
                    <div class="formie-field-control" data-formie-field-control>
                        <input
                            id="${e.inputId}"
                            type="text"
                            class="formie-input"
                            name="fields[${e.handle}][datetime]"
                            value="${e.value}"
                            placeholder="${e.placeholder}"
                            data-formie-input
                            data-formie-date-datepicker-input
                            data-formie-input-id="${e.inputId}"
                            data-formie-input-type="date"
                        >
                    </div>
                </div>
            </div>
        </div>
    `}function a(e){return`
        <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="${e.inputHandle}">
            <input
                id="${e.inputId}"
                type="${e.inputType}"
                class="formie-input"
                name="fields[${e.handle}][${e.inputHandle}]"
                value="${e.value}"
                placeholder="${e.placeholder}"
                data-formie-input
                data-formie-input-id="${e.inputId}"
                data-formie-input-type="${e.inputType}"
            >
        </div>
    `}function i(e){return`
        <div class="formie-field formie-field-nested" data-formie-field data-formie-field-handle="${e.inputHandle}">
            <select
                id="${e.inputId}"
                class="formie-select formie-input"
                name="fields[${e.handle}][${e.inputHandle}]"
                data-formie-input
                data-formie-input-id="${e.inputId}"
                data-formie-input-type="select"
            >
                ${e.options.map(t=>`<option value="${t.value}" ${t.selected?"selected":""}>${t.label}</option>`).join("")}
            </select>
        </div>
    `}function l(e){return`
        <div class="formie-field" data-formie-field data-formie-field-handle="${e.handle}" data-formie-field-type="date">
            <fieldset class="formie-field-layout formie-date-field-layout formie-subfield-fieldset" data-formie-field-layout data-formie-date-field-layout data-formie-subfield-fieldset>
                <legend class="formie-label formie-field-label formie-date-field-label" data-formie-label data-formie-field-label data-formie-date-field-label>${e.label}</legend>
                <div class="formie-field-content" data-formie-field-content>
                    <div class="formie-field-control" data-formie-field-control>
                        <div class="formie-subfield-rows" data-formie-subfield-rows>
                            ${e.rows.join(`
`)}
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
    `}function d(e){return`
        <div class="formie-subfield-row" data-formie-subfield-row>
            ${e.join(`
`)}
        </div>
    `}function o(e){return`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="date-demo">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    ${e.map(t=>`
                                            <div class="formie-row" data-formie-row data-formie-field-count="1">
                                                ${t}
                                            </div>
                                        `).join("")}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </form>
    `}function r(e,t){return{id:"date-picker",type:"field",targets:[{targetType:"field",targetId:e}],config:{options:{locale:"en",...t}}}}const m={minHeight:1500,modules:[r("advancedDate",{dateFormat:"Y-m-d",getIsDate:!0,timeFormat:"H:i"}),r("advancedTime",{dateFormat:"Y-m-d",getIsTime:!0,timeFormat:"H:i"}),r("advancedDateTime",{dateFormat:"Y-m-d",getIsDateTime:!0,timeFormat:"H:i"})],markup:o([l({handle:"simpleDate",label:"Calendar (native date input)",rows:[d([a({handle:"simpleDate",inputHandle:"date",inputId:"simple-date-date",inputType:"date",placeholder:"YYYY-MM-DD",value:"2026-04-15"})])]}),l({handle:"simpleDateTime",label:"Calendar (native date + time inputs)",rows:[d([a({handle:"simpleDateTime",inputHandle:"date",inputId:"simple-date-time-date",inputType:"date",placeholder:"YYYY-MM-DD",value:"2026-04-15"}),a({handle:"simpleDateTime",inputHandle:"time",inputId:"simple-date-time-time",inputType:"time",placeholder:"HH:MM",value:"14:30"})])]}),n({handle:"advancedDate",inputId:"advanced-date",label:"Flatpickr (`datePicker`, date only)",placeholder:"Select a date",value:"2026-04-15 00:00:00"}),n({handle:"advancedTime",inputId:"advanced-time",label:"Flatpickr (`datePicker`, time only)",placeholder:"Select a time",value:"2026-04-15 14:30:00"}),n({handle:"advancedDateTime",inputId:"advanced-date-time",label:"Flatpickr (`datePicker`, date + time)",placeholder:"Select a date and time",value:"2026-04-15 14:30:00"}),l({handle:"dropdownDateTime",label:"Dropdowns",rows:[d([i({handle:"dropdownDateTime",inputHandle:"year",inputId:"dropdown-date-time-year",options:[{label:"2025",value:"2025"},{label:"2026",value:"2026",selected:!0},{label:"2027",value:"2027"}]}),i({handle:"dropdownDateTime",inputHandle:"month",inputId:"dropdown-date-time-month",options:[{label:"April",value:"4",selected:!0},{label:"May",value:"5"},{label:"June",value:"6"}]}),i({handle:"dropdownDateTime",inputHandle:"day",inputId:"dropdown-date-time-day",options:[{label:"14",value:"14"},{label:"15",value:"15",selected:!0},{label:"16",value:"16"}]})]),d([i({handle:"dropdownDateTime",inputHandle:"hour",inputId:"dropdown-date-time-hour",options:[{label:"13",value:"13"},{label:"14",value:"14",selected:!0},{label:"15",value:"15"}]}),i({handle:"dropdownDateTime",inputHandle:"minute",inputId:"dropdown-date-time-minute",options:[{label:"00",value:"00"},{label:"30",value:"30",selected:!0},{label:"45",value:"45"}]})])]}),l({handle:"inputDateTime",label:"Inputs",rows:[d([a({handle:"inputDateTime",inputHandle:"year",inputId:"input-date-time-year",inputType:"text",placeholder:"Year",value:"2026"}),a({handle:"inputDateTime",inputHandle:"month",inputId:"input-date-time-month",inputType:"text",placeholder:"Month",value:"04"}),a({handle:"inputDateTime",inputHandle:"day",inputId:"input-date-time-day",inputType:"text",placeholder:"Day",value:"15"})]),d([a({handle:"inputDateTime",inputHandle:"hour",inputId:"input-date-time-hour",inputType:"text",placeholder:"Hour",value:"14"}),a({handle:"inputDateTime",inputHandle:"minute",inputId:"input-date-time-minute",inputType:"text",placeholder:"Minute",value:"30"})])]})])};export{m as default};
