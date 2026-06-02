function i(e){return e.replaceAll("&","&amp;").replaceAll('"',"&quot;").replaceAll("<","&lt;").replaceAll(">","&gt;")}function r(e){return e.replaceAll("&","&amp;").replaceAll("<","&lt;").replaceAll(">","&gt;")}function w(e){const t=new Set(e.selectedValues||[]),o=!!e.multiple,a=o?`fields[${e.handle}][]`:`fields[${e.handle}]`,l=`${e.inputIdPrefix}-select`;return`
        <div class="formie-field" data-formie-field data-formie-field-handle="${i(e.handle)}" data-formie-field-type="${i(e.fieldType)}" data-formie-input-id="${i(l)}">
            <div class="formie-field-layout formie-field-layout-label-above formie-field-layout-instructions-above" data-formie-field-layout data-formie-label-position="above" data-formie-instructions-position="above">
                <label class="formie-label formie-field-label" for="${i(l)}" data-formie-label data-formie-field-label>${r(e.label)}</label>
                <div class="formie-field-content" data-formie-field-content>
                    <div class="formie-field-control" data-formie-field-control>
                        <select
                            id="${i(l)}"
                            class="formie-select formie-input"
                            name="${i(a)}"
                            ${o?'multiple size="4"':""}
                            data-formie-input
                            data-formie-input-id="${i(l)}"
                            data-formie-input-type="select"
                        >
                            ${e.options.map(d=>`
                                    <option value="${i(d.value)}" ${t.has(d.value)?"selected":""}>${r(d.label)}</option>
                                `).join("")}
                        </select>
                    </div>
                </div>
            </div>
        </div>
    `}function L(e){const t=new Set(e.selectedValues||[]),o=e.layout||"vertical",a=e.displayType==="checkboxes",l=`data-formie-layout="${i(o)}"`,d=a?"data-formie-checkboxes-field-layout":"data-formie-radio-field-layout",m=a?"formie-checkboxes-field-layout":"formie-radio-field-layout",c=a?"data-formie-checkboxes-field-label":"data-formie-radio-field-label",p=a?"formie-checkboxes-field-label":"formie-radio-field-label",u=a?"data-formie-checkboxes-options":"data-formie-radio-options",b=a?"formie-checkboxes-options":"formie-radio-options",$=a?"data-formie-checkbox-option":"data-formie-radio-option",v=a?"formie-checkbox-option":"formie-radio-option",y=a?"data-formie-checkbox-option-label":"data-formie-radio-option-label",h=a?"formie-checkbox-option-label":"formie-radio-option-label",x=a?"data-formie-checkbox-input":"data-formie-radio-input",k=a?"formie-checkbox-input":"formie-radio-input",n=a?"checkbox":"radio",A=a?`fields[${e.handle}][]`:`fields[${e.handle}]`;return`
        <div class="formie-field" data-formie-field data-formie-field-handle="${i(e.handle)}" data-formie-field-type="${i(e.fieldType)}">
            <fieldset class="formie-field-layout ${m} formie-layout-${o} formie-field-layout-label-above" data-formie-field-layout ${d} ${l} data-formie-label-position="above">
                <legend class="formie-label formie-field-label ${p}" data-formie-label data-formie-field-label ${c}>${r(e.label)}</legend>
                <div class="formie-field-content" data-formie-field-content>
                    <div class="formie-field-control" data-formie-field-control>
                        ${a?`<input type="hidden" name="fields[${i(e.handle)}]" value="">`:""}
                        <div class="formie-field-options ${b} formie-layout-${o}" data-formie-field-options ${u} ${l}>
                            ${e.options.map((f,C)=>{const s=`${e.inputIdPrefix}-${C+1}`;return`
                                    <div class="formie-field-option ${v}" data-formie-field-option ${$}>
                                        <input
                                            id="${i(s)}"
                                            class="formie-input ${k}"
                                            type="${n}"
                                            name="${i(A)}"
                                            value="${i(f.value)}"
                                            ${t.has(f.value)?"checked":""}
                                            data-formie-input
                                            ${x}
                                            data-formie-input-id="${i(s)}"
                                            data-formie-input-type="${n}"
                                        >
                                        <label class="formie-field-option-label ${h}" for="${i(s)}" data-formie-field-option-label ${y}>${r(f.label)}</label>
                                    </div>
                                `}).join("")}
                        </div>
                    </div>
                </div>
            </fieldset>
        </div>
    `}function T(e){return e.displayType==="dropdown"?w(e):L(e)}function F(e,t){return`
        <form class="formie-form" data-formie data-formie-form data-formie-handle="${i(e)}">
            <div class="formie-form-body" data-formie-form-body>
                <div class="formie-pages" data-formie-pages>
                    <section class="formie-page" data-formie-page data-formie-page-id="demo-page-1">
                        <div class="formie-page-container" data-formie-page-container>
                            <div class="formie-page-body" data-formie-page-body>
                                <div class="formie-rows" data-formie-rows>
                                    ${t.map(o=>`
                                            <div class="formie-row" data-formie-row data-formie-field-count="1">
                                                ${o}
                                            </div>
                                        `).join("")}
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </form>
    `}export{T as a,F as r};
