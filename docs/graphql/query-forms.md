# Query Forms

Formie exposes forms, pages, rows and fields through GraphQL. This is useful when you need to build your own front-end, inspect a form’s field layout, or fetch the settings your app needs before rendering a form.

Use `formieForm` when you expect one form, or `formieForms` when you need a list.

```graphql
{
    formieForm(handle: "contactForm") {
        title
        handle
        isAvailable

        settings {
            errorMessageHtml
            submitMethod
            submitAction
        }

        pages {
            label

            rows {
                rowFields {
                    label
                    handle
                    typeName
                    inputTypeName

                    ... on Field_Name {
                        fields {
                            label
                            handle
                            enabled
                            required
                        }
                    }

                    ... on Field_Email {
                        placeholder
                        validateDomain
                    }
                }
            }
        }
    }
}
```

## Select the Fields Your Page Needs

For a contact form with handle `contactForm`, run this query in Craft's GraphQL explorer using a schema that can read that form:

```graphql
query ContactFields {
    formieForm(handle: "contactForm") {
        handle
        formFields {
            handle
            inputTypeName
        }
    }
}
```

The response pairs each field handle with the input type accepted by its submission mutation. Use those names when constructing a typed mutation; a Name field takes a structured value while a Single-Line Text field takes a string. See [Create Submissions](/graphql/create-submissions) for both examples.

Querying the form does not grant access to saved submissions. A client that submits answers also needs submission-creation access for this form. Keep private submission-query scopes out of public browser schemas.

<span id="form-queries"></span>
<span id="form-fields"></span>
<span id="form-settings"></span>
<span id="pages-and-rows"></span>
<span id="field-interface"></span>
<span id="field-specific-settings"></span>
<span id="address"></span>
<span id="agree"></span>
<span id="content"></span>
<span id="calculations"></span>
<span id="categories"></span>
<span id="checkboxes"></span>
<span id="date-time"></span>
<span id="dropdown"></span>
<span id="email-address"></span>
<span id="entries"></span>
<span id="file-upload"></span>
<span id="group"></span>
<span id="heading"></span>
<span id="hidden"></span>
<span id="html"></span>
<span id="multi-line-text"></span>
<span id="name"></span>
<span id="number"></span>
<span id="payment"></span>
<span id="phone"></span>
<span id="products"></span>
<span id="radio"></span>
<span id="recipients"></span>
<span id="repeater"></span>
<span id="section"></span>
<span id="signature"></span>
<span id="single-line-text"></span>
<span id="summary"></span>
<span id="table"></span>
<span id="tags"></span>
<span id="users"></span>
<span id="variants"></span>
<span id="supporting-types"></span>
<span id="fieldattribute"></span>
<span id="fieldoption"></span>
<span id="countryoption"></span>
<span id="table-columns"></span>

## Detailed Reference

See [GraphQL Form Reference](/reference/graphql-form-reference) for the complete lookup, including types and field-specific options.
