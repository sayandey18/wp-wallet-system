/**
 * Front facing scripts for WKWC_Wallet core module.
 */

"use strict";

let wkwcJQ = jQuery.noConflict();

wkwcJQ(function () {
    wkwcJQ(document).ready(function () {
        //Selecting/Deselecting all transaction on clicking checkbox.
        wkwcJQ('#wkwc_wallet_checkall').on('click', function () {
            if (wkwcJQ(this).is(':checked')) {
                wkwcJQ('.delete_checkbox').each(function () {
                    this.checked = true;
                });
            } else {
                wkwcJQ('.delete_checkbox').each(function () {
                    this.checked = false;
                });
            }
        });
        // Ends Selecting/Deselecting all transaction on clicking checkbox.

        //Applying bulk action to remove the wallet transactions.
        wkwcJQ('#wkwc_wallet_bulk_delete').on('click', function () {
            if ('delete' !== wkwcJQ('#wkwc_wallet_action').val()) {
                alert('Please select an action');
                return false;
            }

            let checkbox = wkwcJQ('.delete_checkbox:checked');

            if (checkbox.length > 0) {
                let checkbox_value = [];
                wkwcJQ(checkbox).each(function () {
                    checkbox_value.push(wkwcJQ(this).val());
                });
                wkwcJQ('.wkwc_wallet-spin-loader').show();

                wkwcJQ.ajax({
                    url: wkwc_wallet_obj.ajax.ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'wkwc_wallet_frontend_bulk_delete',
                        post_action: 'delete',
                        nonce: wkwc_wallet_obj.ajax.ajaxNonce,
                        checkbox_value: checkbox_value
                    },
                    success: function (response) {
                        if (true === response.success) {
                            wkwcJQ('#wkwc_wallet_action_message').css('display', 'block').html(response.message);  // Change the div's contents to the result.
                            wkwcJQ('.wkwc_wallet-spin-loader').hide();
                        }
                        setTimeout(function () {
                            wkwcJQ('#wkwc_wallet_action_message').hide();
                            location.reload();
                        }, 1500);
                    },
                    error: function (response) {
                        alert('Error retrieving the information: ' + response.status + ' ' + response.statusText);
                    }
                });
            }
            else {
                alert("Select atleast one records");
            }
        });
        //Ends applying bulk action to remove the wallet transactions.

        //Sent OTP for wallet transfer.
        wkwcJQ('#wkwc_wallet_transfer_money').on('click', function () {
            sendOTP();
        });
        //Ends Send otp for wallet transfer

        // Send OTP.
        function sendOTP(resend) {
            let receiver_mail = wkwcJQ('#wkwc_wallet_receiver').val();
            let amount = wkwcJQ('#wkwc_wallet_pay_amount').val();
            let note = wkwcJQ('#wkwc_wallet_pay_note').val();
            wkwcJQ('.wkwc_wallet_otp_success_notice').remove();

            if ('' === receiver_mail) {
                wkwcJQ('#wkwc_wallet_err_msg').text('Receiver mail is empty');
                return false;
            }

            if ('' === amount || isNaN(amount)) {
                wkwcJQ('#wkwc_wallet_err_msg').text('Amount must be greater than 0');
                return false;
            }
            if ('' === note || note.length < 10) {
                wkwcJQ('#wkwc_wallet_err_msg').text('Add a note having greater than 10 characters.');
                return false;
            }

            wkwcJQ('#wkwc_wallet_err_msg').html('<span class="wkwc_wallet_spinner is-active"></span>');

            wkwcJQ.ajax({
                url: wkwc_wallet_obj.ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wkwc_wallet_send_transfer_otp',
                    nonce: wkwc_wallet_obj.ajax.ajaxNonce,
                    wkwc_wallet_receiver: receiver_mail,
                    wkwc_wallet_pay_amount: amount,
                    wkwc_wallet_pay_note: note,
                },
                success: function (response) {
                    wkwcJQ('.wkwc_wallet_otp_success_notice').remove();
                    wkwcJQ('#wkwc_wallet_transfer_from').append('<tr class="wkwc_wallet_otp_success_notice"><td colspan=2><p>' + response.message + '</p></td><tr>');
                    if (true === response.success) {
                        wkwcJQ('#wkwc_wallet_transfer_money').hide();
                        wkwcJQ('#wkwc_wallet_verify_otp').show();
                        wkwcJQ('#wkwc_wallet_transfer_otp').show();
                        wkwcJQ('#wkwc_wallet_transfer_from .wkwc_wallet_otp_success_notice').addClass('success');

                        if (resend) {
                            wkwcJQ('#wkwc_wallet_verify_otp').prop('disabled', false);
                        } else {
                            wkwcJQ('.wkwc_wallet_transaction_note').after('<tr class="wkwc_wallet_otp_input"><td><label for="wkwc_wallet_pay_note">Enter your OTP to verify:</label></td><td><input type="password" id="wkwc_wallet_transfer_otp" placeholder="Enter your received OTP" /></td><tr>');
                        }
                        showTimer(response.otp_seconds);
                    } else {
                        wkwcJQ('#wkwc_wallet_transfer_from .wkwc_wallet_otp_success_notice').addClass('error');
                        wkwcJQ('#wkwc_wallet_err_msg').empty();
                    }
                    setTimeout(function () {
                        wkwcJQ('#wkwc_wallet_action_message').hide();
                        // location.reload();
                    }, 2500);
                },
                error: function (response) {
                    alert('Error retrieving the information: ' + response.status + ' ' + response.statusText);
                }
            });
        }; //Send OTP end

        // Show timer after sending OTP.
        function showTimer(seconds) {
            let now = new Date();
            now = (Date.parse(now) / 1000);
            let endtime = now + seconds;

            let intervalID = setInterval(function () {
                let now1 = new Date();
                now1 = (Date.parse(now1) / 1000);

                let remaining = endtime - now1;

                if (remaining < 0) {
                    wkwcJQ('#wkwc_wallet_resend_otp').show().prop('disabled', false);
                    wkwcJQ('#wkwc_wallet_verify_otp').attr('disabled', true);
                    clearInterval(intervalID);

                } else {
                    wkwcJQ('#wkwc_wallet_err_msg').html(endtime - now1 + ' Seconds');
                }
            }, 1000);

        }; //showTimer end

        // Resend OTP after timer expired and on clicking 'Resend OTP' button.
        wkwcJQ('#wkwc_wallet_resend_otp').on('click', function () {
            sendOTP(true);
            wkwcJQ(this).attr('disabled', true);
        });
        // End Resend OTP after timer expired and on clicking 'Resend OTP' button.

        // Verify entered OTP on checkout and My-Account/wallet/transfer
        wkwcJQ(document).on('click', '#wkwc_wallet_verify_otp', function () {
            let transfer_otp = wkwcJQ('#wkwc_wallet_transfer_otp').val();
            let checkout_otp = wkwcJQ('#wkwc_wallet_checkout_otp').val();
            let otp = (undefined === transfer_otp) ? checkout_otp : transfer_otp;

            let regOtp = new RegExp("^[0-9]{6}$");

            if (!regOtp.test(otp)) {
                wkwcJQ('.wkwc_wallet-otp-msg, .wkwc_wallet_otp_success_notice td p').text('Invalid OTP.');
                wkwcJQ('.wkwc_wallet-otp-msg').removeClass('wkwc-wallet-hide wkwc_wallet-success').addClass('wkwc_wallet-error');
                wkwcJQ('.wkwc_wallet_otp_success_notice').removeClass('success').addClass('error');
                setTimeout(function () {
                    wkwcJQ('.wkwc_wallet-otp-msg').addClass('wkwc-wallet-hide');
                }, 3500);

                return;
            }

            let otp_type = (undefined === transfer_otp) ? 'checkout' : 'transfer';

            wkwcJQ('.wkwc_wallet-spin-loader').show();

            wkwcJQ.ajax({
                url: wkwc_wallet_obj.ajax.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wkwc_wallet_verify_otp',
                    nonce: wkwc_wallet_obj.ajax.ajaxNonce,
                    otp: otp,
                    otp_type: otp_type
                },
                success: function (response) {
                    wkwcJQ('.wkwc_wallet-spin-loader').hide();

                    if (true === response.success) {
                        wkwcJQ('.wkwc_wallet_otp_success_notice td p').html(response.message);  // Change the div's contents to the result.

                        if ('checkout' === otp_type) {
                            wkwcJQ('.wkwc_wallet-otp-msg.wkwc_wallet-success').html(response.message).removeClass('wkwc-wallet-hide');

                            wkwcJQ(document.body).trigger('update_checkout');

                            if (true === response.full_payment) {
                                wkwcJQ('.wkwc_wallet-otp-wrap').hide();
                            }
                        } else {
                            wkwcJQ('#wkwc_wallet_verify_otp').attr('disabled', true);
                            wkwcJQ('.wkwc_wallet_otp_success_notice').removeClass('error').addClass('success');

                            setTimeout(function () {
                                wkwcJQ('#wkwc_wallet_err_msg').hide();
                                if ('transfer' === otp_type) {
                                    location.reload();
                                }
                            }, 5000);
                        }
                    } else {
                        if ('transfer' === otp_type) {
                            wkwcJQ('.wkwc_wallet_otp_success_notice td p').html(response.message);  // Change the div's contents to the result.
                            wkwcJQ('.wkwc_wallet_otp_success_notice').removeClass('success').addClass('error');
                        } else {
                            wkwcJQ('.wkwc_wallet-otp-msg').text(response.message).removeClass('wkwc-wallet-hide wkwc_wallet-success').addClass('wkwc_wallet-error');
                        }
                    }

                },
                error: function (response) {
                    alert('Error retrieving the information: ' + response.status + ' ' + response.statusText);
                }
            });
        });
        // End Verify OTP.

        // Creating checkout OTP.
        wkwcJQ(document).on('click', '#wkwc_wallet-checkout-payment', function () {
            if (this.checked) {
                wkwcJQ('.wkwc_wallet-spin-loader').show();
                wkwcJQ.ajax({
                    url: wkwc_wallet_obj.ajax.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wkwc_wallet_checkout_validate',
                        'nonce': wkwc_wallet_obj.ajax.ajaxNonce
                    },
                    success: function (response) {
                        wkwcJQ('.wkwc_wallet-spin-loader').hide();

                        if (true === response.success) {
                            if (response.update_checkout) {
                                wkwcJQ(document.body).trigger('update_checkout');
                            } else {
                                wkwcJQ('input[name=wkwc_wallet-email-otp]').val('');
                                wkwcJQ('.wkwc_wallet-otp-wrap').show();

                                wkwcJQ('.wkwc_wallet-otp-msg-wrap').removeClass('wkwc-wallet-hide');
                                wkwcJQ('.wkwc_wallet-otp-msg').text(response.message).removeClass('wkwc_wallet-error').addClass('wkwc_wallet-success');
                            }
                        } else {
                            wkwcJQ('.wkwc_wallet-otp-msg-wrap').removeClass('wkwc-wallet-hide');
                            wkwcJQ('.wkwc_wallet-otp-msg').text(response.message).addClass('wkwc_wallet-error').removeClass('wkwc_wallet-success');
                        }
                    }
                });
            } else {
                wkwcJQ('.wkwc_wallet-spin-loader').show();
                wkwcJQ.ajax({
                    url: wkwc_wallet_obj.ajax.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wkwc_wallet_remove_wallet',
                        'nonce': wkwc_wallet_obj.ajax.ajaxNonce
                    },
                    success: function (response) {
                        wkwcJQ('.wkwc_wallet-spin-loader').hide();

                        if (true === response.success) {
                            if (response.update_checkout) {
                                wkwcJQ(document.body).trigger('update_checkout');
                            }
                        } else {
                            console.error('Error: wallet session is not empty.');
                        }
                    }
                });
                wkwcJQ('.wkwc_wallet-otp-wrap').hide();
                wkwcJQ('.wkwc_wallet-otp-msg-wrap').addClass('wkwc-wallet-hide');
            }
        });
        // Creating Checkout OTP end.

        //Updating twilio mobile number from wallet endpoitn on my-account
        wkwcJQ('.sms-number-wrap').on('click', '#wkwc_wallet_twilio_update', function () {
            let phone = wkwcJQ('.sms-number-wrap #wkwc_wallet_twilio_sms_number').val();

            if ('' === phone || phone.length < 4) {
                wkwcJQ('.woocommerce-notices-wrapper').html('<p class="woocommerce-error">Please enter a valid phone.</p>');
                return;
            }
            wkwcJQ('.woocommerce-notices-wrapper').empty();
            wkwcJQ('.wkwc-wallet-twilio-action').addClass('updating');

            wkwcJQ.ajax({
                url: wkwc_wallet_obj.ajax.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wkwc_wallet_update_phone',
                    phone: phone,
                    'nonce': wkwc_wallet_obj.ajax.ajaxNonce
                },
                success: function (response) {
                    wkwcJQ('.wkwc-wallet-twilio-action').removeClass('updating');
                    wkwcJQ('.woocommerce-notices-wrapper').html('<p class="woocommerce-message">' + response.message + '.</p>');
                    if (true === response.success) {
                        wkwcJQ('#wkwc_wallet_twilio_sms_number').empty();
                        wkwcJQ('.sms-number-wrap').html('<span id="wkwc_wallet_twilio_phone">' + phone + '</span><span id="wkwc_wallet_twilio_edit" title="Edit" class="wkwc-wallet-twilio-action dashicons dashicons-edit"></span>');
                        location.reload();
                    } else {
                        wkwcJQ('.woocommerce-notices-wrapper p').removeClass('woocommerce-message').addClass('woocommerce-error');
                    }
                }
            });
        });
        //Updating twilio mobile number from wallet endpoitn on my-account ends.

        //Editiong Twilio mobile number.
        wkwcJQ('.sms-number-wrap').on('click', '#wkwc_wallet_twilio_edit', function () {
            let phone = wkwcJQ('.sms-number-wrap #wkwc_wallet_twilio_phone').text();
            wkwcJQ('.sms-number-wrap').html('<input value="' + phone + '" id="wkwc_wallet_twilio_sms_number" type="text" placeholder="Enter your mobile number..."><span id="wkwc_wallet_twilio_update" title="Update" class="wkwc-wallet-twilio-action dashicons dashicons-update"></span>');
        });
        //Editiong Twilio mobile number ends.

    }); //document ready closed.
});


