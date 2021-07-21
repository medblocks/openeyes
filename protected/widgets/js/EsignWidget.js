OpenEyes = OpenEyes || {};
OpenEyes.UI = OpenEyes.UI || {};

/* global baseUrl */
/* global moduleName */
/* global OE_print_url */

(function(exports) {
    /**
     * @param {jQuery} $element
     * @param {Object} options
     * @constructor
     */
    function EsignWidget($element, options) {
        this.$element = $element;
        this.options = $.extend(true, {}, EsignWidget._defaultOptions, options);
        this.create();
    }

    EsignWidget._defaultOptions = {
        "submitAction" : ""
    };

    /**
     * @private
     */
    EsignWidget.prototype.create = function()
    {
        this.$pinInput = this.$element.find(".js-pin-input");
        this.$signButton = this.$element.find(".js-sign-button");
        this.$popupSignButton = this.$element.find(".js-popup-sign-btn");
        this.$controlWrapper = this.$element.find(".js-signature-control");
        this.$date = this.$element.find(".js-signature-date");
        this.$time = this.$element.find(".js-signature-time");
        this.$signatureWrapper = this.$element.find(".js-signature-wrapper");
        this.$signature = this.$element.find(".js-signature");
        this.bindEvents();
    };

    /**
     * @private
     */
    EsignWidget.prototype.bindEvents = function()
    {
        let widget = this;
        this.$signButton.click(function () {
            const pin = widget.$pinInput.val();
            console.log(pin);
            widget.$pinInput.val("");
            if(pin === "") {
                let dlg = new OpenEyes.UI.Dialog.Alert({
                    content: "Please enter PIN"
                });
                dlg.open();
                return false;
            }
            $.post(
                baseUrl + "/" + moduleName + "/default/" + widget.options.submitAction,
                {
                    "pin": pin,
                    "YII_CSRF_TOKEN": YII_CSRF_TOKEN
                },
                function (response) {
                    if (response.code === 0) {
                        widget.displaySignature(
                            response.signature_file_id,
                            response.date,
                            response.time
                        );
                    } else {
                        let dlg = new OpenEyes.UI.Dialog.Alert({
                            content: "There has been an error while signing: " + response.error
                        });
                        dlg.open();
                    }
                }
            );
        });
        this.$popupSignButton.click(function () {
            let printUrl = OE_print_url + "?html=1&sign=1&element_id=";
            let popup = new OpenEyes.UI.Dialog({
                title: "e-Sign",
                iframe: printUrl,
                popupContentClass: "oe-popup-content max max-height",
                width: "100%"
            });
            popup.open();
        });
    };

    /**
     * @param {int} signature_file_id
     * @param {string} date
     * @param {string} time
     * @private
     */
    EsignWidget.prototype.displaySignature = function(signature_file_id, date, time)
    {
        console.log((this.$signatureWrapper));
        this.$controlWrapper.hide();
        //const $image = $('<div class="esign-check js-has-tooltip" data-tip="{}" style="background-image: url(\'/idg-php/imgDemo/esign/esign2.png\')"></div>');

        const $image = $("<span>Kecso</span>");
            //$('<div class="esign-check js-has-tooltip" data-tooltip-content="<img src=\''+(signature_file_id)+'\'>" style="background-image: url('+signature_file_id+');">');

        $image.appendTo(this.$signatureWrapper);
        this.$date.text(date).show();
        this.$time.text(time);
        this.$signatureWrapper.show();
    };

    exports.EsignWidget = EsignWidget;
})(OpenEyes.UI);