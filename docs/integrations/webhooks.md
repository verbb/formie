# Webhooks
Webhooks are one of the provided integrations with Formie, and are used to forward on a payload of data to a specified URL. This can be useful to send to submission data to third-parties when a form is submitted. The third-party can then perform any number of actions.

The URL can be set at the plugin-settings level, or customised per-form. When editing a form, you can trigger a test payload to be sent, with random-generated data to test your connections to your Webhook URL.

There are 2 types of Webhooks Formie provides.

## Webhook
A general-purpose webhook can be used to send any URL you provide with the payload of content. This can be used to POST-forward content to a URL you choose.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Webhooks**.
1. Click the **New Integration** button.
1. Select Webhook as the **Integration Provider**.

### Step 2. Add your Webhook URL
1. Add your URL to the **Webhook URL** field in Formie.

### Step 3. Form Setting & Test Payload
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.
1. Click on the **Send Test Payload** button to send dummy content to the URL.


## Zapier
Follow the below steps to connect to the Zapier API.

### Step 1. Create the Integration
1. Navigate to **Formie** → **Settings** → **Webhooks**.
1. Click the **New Integration** button.
1. Select Zapier as the **Integration Provider**.

### Step 2. Connect to the Zapier API
1. Go to <a href="https://zapier.com/app/zaps" target="_blank">Zapier</a> and create a new Zap.
1. For the **Choose App & Event** value, enter **Webhooks by Zapier**.
1. For the **Choose Trigger Event** value, enter **Catch Hook**.
1. Click **Continue**.
1. Copy the **Custom Webhook URL** into the **Webhook** field in Formie.

### Step 3. Form Setting & Test Payload
1. Go to the form you want to enable this integration on.
1. Click the **Integrations** tab.
1. In the left-hand sidebar, select the name you gave the integration.
1. Enable the integration and fill out all required fields.
1. Click **Save** to save the form.
1. Click on the **Send Test Payload** button to send dummy content to the URL.


## Payloads
Data sent to these nominated URL‘s is in the form of a JSON payload, and contains information about your form and submission.

An example payload would look something like:

```json
{
    "json":{
        "submission":{
            "id":123,
            "formId":1025,
            "statusId":null,
            "userId":null,
            "ipAddress":null,
            "isIncomplete":false,
            "isSpam":false,
            "spamReason":null,
            "uid":null,
            "title":null,
            "dateCreated":{
                "date":"2020-08-14 22:33:48.000000",
                "timezone_type":3,
                "timezone":"Australia\/Melbourne"
            },
            "dateUpdated":{
                "date":"2020-08-30 11:40:38.000000",
                "timezone_type":3,
                "timezone":"Australia\/Melbourne"
            },
            "text":"Natus ex sint aut et. Laudantium aut voluptas necessitatibus mollitia. Dolorum aut officiis ea.",
            "email":"hackett.pauline@gmail.com",
            "multiName":"{\"prefix\":\"Prof.\",\"firstName\":\"Catherine\",\"middleName\":\"Erling\",\"lastName\":\"Padberg\",\"name\":null,\"isMultiple\":true}",
            "checkboxes":[
                "Option 2"
            ],
            "datetime":"2008-01-10 22:01:29",
            "address":"{\"autocomplete\":null,\"address1\":\"9812 Adolfo Street Apt. 382\\nKassulkeburgh, AZ 14824\",\"address2\":\"1206\",\"address3\":\"Well\",\"city\":\"Adahberg\",\"state\":\"Michigan\",\"zip\":\"73546-4092\",\"country\":\"French Guiana\"}",
        },
        "form":{
            "settings":{
                "displayFormTitle":"",
                "displayPageTabs":"",
                "displayCurrentPageTitle":"",
                "displayPageProgress":"",
                "submitMethod":"page-reload",
                "submitAction":"message",
                "submitActionTab":null,
                "submitActionUrl":"",
                "submitActionFormHide":"",
                "submitActionMessage":"[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Submission saved.\"}]}]",
                "submitActionMessageTimeout":"",
                "errorMessage":"[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Couldn\u2019t save submission due to errors.\"}]}]",
                "loadingIndicator":"",
                "loadingIndicatorText":"",
                "validationOnSubmit":"1",
                "validationOnFocus":"",
                "submissionTitleFormat":"{timestamp}",
                "collectIp":"",
                "collectUser":"",
                "storeData":null,
                "availabilityMessage":null,
                "availabilityMessageDate":null,
                "availabilityMessageSubmissions":null,
                "defaultLabelPosition":"verbb\\formie\\positions\\AboveInput",
                "defaultInstructionsPosition":"verbb\\formie\\positions\\BelowInput",
                "progressPosition":"end"
            },
            "handle":"contactForm",
            "submitActionEntryId":null,
            "requireUser":"0",
            "availability":"always",
            "availabilityFrom":null,
            "availabilityTo":null,
            "availabilitySubmissions":null,
            "defaultStatusId":"1",
            "dataRetention":"forever",
            "dataRetentionValue":null,
            "userDeletedAction":"retain",
            "fieldLayoutId":241,
            "id":1025,
            "uid":"67c09dce-c4ec-4be8-8dbe-4154708c6443",
            "title":"Contact Us",
            "dateCreated":{
                "date":"2020-08-14 22:33:48.000000",
                "timezone_type":3,
                "timezone":"Australia\/Melbourne"
            },
            "dateUpdated":{
                "date":"2020-08-30 11:40:38.000000",
                "timezone_type":3,
                "timezone":"Australia\/Melbourne"
            }
        }
    }
}
```