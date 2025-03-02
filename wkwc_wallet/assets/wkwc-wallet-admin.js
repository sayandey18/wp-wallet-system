/**
 * Admin facing scripts for WKWC_Wallet core module.
 */

"use strict";

let wkwcJQ = jQuery.noConflict();

wkwcJQ(function () {
    wkwcJQ(document).ready(function () {

        //Hiding Twillio SMS field on selecting mail.
        ('mail' === wkwcJQ('#wkwc_wallet_otp_method').val()) ? wkwcJQ('.smshide').hide() : wkwcJQ('.smshide').show();
        wkwcJQ('#wkwc_wallet_otp_method').on('change', function () {
            ('mail' === wkwcJQ(this).val()) ? wkwcJQ('.smshide').hide() : wkwcJQ('.smshide').show();
        });
        //Hiding Twillio SMS field on selecting mail End.

        //Adding enhanced select2 js for searching users.
        function getEnhancedSelectFormatString() {
            return {
                'language': {
                    errorLoading: function () {
                        return wkwc_wallet_obj.ajax.i18n_searching;
                    },
                    inputTooLong: function (args) {
                        var overChars = args.input.length - args.maximum;

                        if (1 === overChars) {
                            return wkwc_wallet_obj.ajax.i18n_input_too_long_1;
                        }

                        return wkwc_wallet_obj.ajax.i18n_input_too_long_n.replace('%qty%', overChars);
                    },
                    inputTooShort: function (args) {
                        var remainingChars = args.minimum - args.input.length;

                        if (1 === remainingChars) {
                            return wkwc_wallet_obj.ajax.i18n_input_too_short_1;
                        }

                        return wkwc_wallet_obj.ajax.i18n_input_too_short_n.replace('%qty%', remainingChars);
                    },
                    loadingMore: function () {
                        return wkwc_wallet_obj.ajax.i18n_load_more;
                    },
                    maximumSelected: function (args) {
                        if (args.maximum === 1) {
                            return wkwc_wallet_obj.ajax.i18n_selection_too_long_1;
                        }

                        return wkwc_wallet_obj.ajax.i18n_selection_too_long_n.replace('%qty%', args.maximum);
                    },
                    noResults: function () {
                        return wkwc_wallet_obj.ajax.i18n_no_matches;
                    },
                    searching: function () {
                        return wkwc_wallet_obj.ajax.i18n_searching;
                    }
                }
            };
        } // Get enhanced select format string


        // Ajax customer search boxes
        wkwcJQ(':input.wkwc-wallet-customer').filter(':not(.enhanced)').each(function () {
            var select2_args = {
                allowClear: wkwcJQ(this).data('allow_clear') ? true : false,
                placeholder: wkwcJQ(this).data('placeholder'),
                minimumInputLength: wkwcJQ(this).data('minimum_input_length') ? wkwcJQ(this).data('minimum_input_length') : '1',
                escapeMarkup: function (m) {
                    return m;
                },
                ajax: {
                    url: wkwc_wallet_obj.ajax.ajaxUrl,
                    dataType: 'json',
                    delay: 1000,
                    data: function (params) {
                        return {
                            term: params.term,
                            action: 'wkwc_wallet_json_search_customers',
                            security: wkwc_wallet_obj.ajax.ajaxNonce,
                        };
                    },
                    processResults: function (data) {
                        var terms = [];
                        if (data) {
                            if (data.error == false && Object.keys(data.data).length > 0) {
                                wkwcJQ.each(data.data, function (id, text) {
                                    terms.push({
                                        id: id,
                                        text: text
                                    });
                                });
                            }
                        }
                        return {
                            results: terms
                        };
                    },
                    cache: true
                }
            };

            select2_args = wkwcJQ.extend(select2_args, getEnhancedSelectFormatString());
            wkwcJQ(this).selectWoo(select2_args).addClass('enhanced');
        });

    }); //document ready closed.
});

