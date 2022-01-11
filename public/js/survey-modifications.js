
((Survey) => {
    [
        "mode",
        "navigateToUrl",
        "navigateToUrlOnCondition",
        "maxTimeToFinish",
        "maxTimeToFinishPage",
        "showTimerPanel",
        "showTimerPanelMode",
        "completedBeforeHtml",
        "completedHtml",
        "completedHtmlOnCondition",
        "showCompletedPage",
        "loadingHtml",
        "storeOthersAsComment",
        "sendResultOnPageNext",
        "requiredText",
        "questionStartIndex",
        // "logo",
        // "logoFit",
        // "logoHeight",
        // "logoPosition",
        // "logoWidth",
        // "locale",
        // "title",
        // "showTitle",
        // "description",
        "cookieName",
        "showPreviewBeforeComplete",
        // "pageNextText",
        // "pagePrevText",
        // "startSurveyText",
        // "requiredText",
        // "completeText",
        // "previewText",
        "emptySurveyText",
        // "editText",
        // "firstPageIsStarted",
        // "progressText"


    ].forEach((property) => Survey.Serializer.removeProperty("survey", property));

    Survey.Serializer.removeProperty("selectBase", "choicesByUrl");
    [
        "signaturepad",
        "file",
        "multipletext",
        "paneldynamic",
        "matrixdynamic",
        "comment",
        "imagepicker",
        "rating",
        "matrix",
        "image",
        "expression"

    ].forEach(Survey.QuestionFactory.Instance.unregisterElement, Survey.QuestionFactory.Instance);
    console.log("Removed question types", Object.keys(Survey.QuestionFactory.Instance.creatorHash));
    /**
     * Todo:
     * sendResultOnPageNext --> should be forced to true
     */
    Survey.Serializer.addClass("coloritemvalue", [
        {
            name: "text",
            visible: false,
        },
        {
            name: "color",
            type: "color"
        },
    ], null, "itemvalue");
    Survey.Serializer.addProperty("survey", {
        category: "Reporting & Dashboarding",
        default: [
            {
                value: "A1",
                color: "#ff0000"
            },
            {
                value: "A2",
                color: "#00ff00"
            }

        ],
        isLocalizable: false,
        // onSetValue: (obj, value, jsonConverter) => {
        //     debugger;
        //     // obj.setPropertyValue("colors", value);
        // },


        type: "coloritemvalue[]",
        name: "colors",
        displayName: "Color Dictionary",

    });
// New question type for facility type:
    const facilityQuestionType = {
        name: "facilityType",
        title: "Facility Type",
        iconName: "icon-radiogroup",
        category: "HeRAMS",
        isFit(question) {
            return question.getType() === 'facilityType';
        },
        isDefaultRender: true,

        widgetIsLoaded() {
            return true
        },

        init() {
            Survey.Serializer.addClass("facilityitemvalue", [
                {
                    name: "tier",
                    displayName: "Tier",
                    choices: ["primary", "secondary", "tertiary"]
                }, {
                    name: "text"
                }
            ], null, "itemvalue");

            Survey.Serializer.addClass("facilityType", [
                {
                    name: "choices",
                    type: "facilityitemvalue[]"
                },
            ], null, "radiogroup");

        }
    };

    Survey.CustomWidgetCollection.Instance.add(facilityQuestionType, "customtype");
})(Survey);
