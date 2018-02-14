(function authorizeFormsScript($, Drupal) {

    'use strict';

    Drupal.behaviors.authorizeForms = {};
    Drupal.behaviors.authorizeForms.form = $('.payment-form form');

    Drupal.behaviors.authorizeForms.attach = function (context, settings) {

        $('body', context).once('authorizeForms').each(function () {

            // Payment form submit
            Drupal.behaviors.authorizeForms.form.find('input[name="runAccept"]').on('click', function(event){
                event.preventDefault();
                Drupal.behaviors.authorizeForms.sendPaymentDataToAnet()
            });

            // Payment Confirmation Back button
            $('.payment-confirmation input[name="back"]').on('click', function(){
                window.history.back();
            });

            // Payment fail back button
            $('.payment-process input[name="back"]').on('click', function(){
                window.history.go(-2);
            });

        });

    };


    Drupal.behaviors.authorizeForms.sendPaymentDataToAnet = function(){

        var authData = {};
        authData.clientKey = $('input[name="publicClientKey"]').val();
        authData.apiLoginID = $('input[name="APILoginID"]').val();

        var cardData = {};
        cardData.cardNumber = $('input[name="cardNumber"]').val();
        cardData.month = $('select[name="expiryMonth"]').val();
        cardData.year = $('select[name="expiryYear"]').val();
        cardData.cardCode = $('input[name="cardCode"]').val();

        var secureData = {};
        secureData.authData = authData;
        secureData.cardData = cardData;

        Accept.dispatchData(secureData, responseHandler);

        function responseHandler(response) {

            if (response.messages.resultCode === "Error") {

                // Remove error message if exists
                $('.payment-error').remove();

                // Set drupal message HTML
                Drupal.behaviors.authorizeForms.form.after('<div role="contentinfo" aria-label="Status message" class="messages messages--error payment-error"><h2 class="visually-hidden">Status message</h2></div>');

                // Loop errors and add to message
                var i = 0;
                while (i < response.messages.message.length) {
                    $('.payment-error h2').after(
                        response.messages.message[i].code + ": " +
                        response.messages.message[i].text + "<br />"
                    );
                    i = i + 1;
                }

            } else {
                Drupal.behaviors.authorizeForms.paymentFormUpdate(response.opaqueData);
            }

        }

    };


    Drupal.behaviors.authorizeForms.paymentFormUpdate = function(opaqueData) {

        var cardField = $('input[name="cardNumber"]');

        // Set payment nonce values
        $('input[name="dataDescriptor"]').val(opaqueData.dataDescriptor);
        $('input[name="dataValue"]').val(opaqueData.dataValue);

        // If form is valid, set card field hashed
        if (typeof Drupal.behaviors.authorizeForms.form[0].checkValidity() !== "undefined") {

            if(Drupal.behaviors.authorizeForms.form[0].checkValidity()){
                cardField.val('************'+cardField.val().substring(11,15));
            }

        }else{
            cardField.val('');
        }


        // Submit form
        Drupal.behaviors.authorizeForms.form.find('input[name="submit"]').click();

    }



}(jQuery, Drupal));
