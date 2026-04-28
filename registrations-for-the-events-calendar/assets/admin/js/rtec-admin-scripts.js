jQuery(document).ready(function($){

    // FORM tab
    var $body = $('body');
    $body.on('click', '.rtec_require_checkbox', function (event) {
        if ($(event.target).is(':checked')) {
            $(event.target).closest('.rtec-checkbox-row').find('.rtec_include_checkbox').prop( "checked", true );
        }
    });

    $body.on('click', '.rtec_include_checkbox', function (event) {
        if (!$(event.target).is(':checked')) {
            $(event.target).closest('.rtec-checkbox-row').find('.rtec_require_checkbox').prop( "checked", false );
        }
    });

    $body.on('click', '.rtec-reveal-field-atts', function (event) {
        var $self = $(event.target);
        $self.next().slideToggle();
    });

    $('.rtec-field-wrapper-email .rtec_include_checkbox').on('click',function() {
        var $self = $(this);

        if (!$self.is(':checked')) {
            if (!confirm('This field is used for sending all emails to your attendees. Removing it from this form will prevent confirmation emails from being sent. Continue?')) {
                $self.attr('checked',true);
            }
        }

    });

    function rtecGoogleType() {
        setTimeout(function(){
            $('.rtec-recaptcha-type').hide();
            $('.rtec-recaptcha-type.rtec-recaptcha-type-'+jQuery('.rtec-recaptcha-type-radio:checked').val()).show();
        }, 1);
    }
    rtecGoogleType();
    $('.rtec-recaptcha-type-radio').on('change',rtecGoogleType);

    var $rtecAttendanceMessageType = $('.rtec_attendance_message_type');
    function rtecToggleMessageTypeOptions(val) {
        if ( val === 'down' ) {
            $('#rtec-message-text-wrapper-up').css('opacity', '.7').find('input').prop('disabled', true);
            $('#rtec-message-text-wrapper-down').css('opacity', '1').find('input').prop('disabled', false);
        } else {
            $('#rtec-message-text-wrapper-up').css('opacity', '1').find('input').prop('disabled', false);
            $('#rtec-message-text-wrapper-down').css('opacity', '.7').find('input').prop('disabled', true);
        }
    }
    $rtecAttendanceMessageType.on('change',function(){
        rtecToggleMessageTypeOptions($(this).val());
    });
    $rtecAttendanceMessageType.each(function(){
        if ($(this).is(':checked')) {
            rtecToggleMessageTypeOptions($(this).val());
        }
    });

    function rtecUpdateCustomNames() {
        var names = [];
        $('.rtec-custom-field').each(function() {
            names.push($(this).attr('data-name'));
        });

        $('#rtec_custom_field_names').val(names.join(','));
    }


    $('.rtec-add-field').on('click',function(event) {
        event.preventDefault();

        var rtecFieldIndex = 1;
        while($('#rtec-custom-field-'+rtecFieldIndex).length) {
            rtecFieldIndex++;
        }
        var customFieldID = rtecFieldIndex;

        $(this).closest('div').before(
            '<div id="rtec-custom-field-'+customFieldID+'" class="rtec-field-options-wrapper rtec-custom-field" data-name="custom'+customFieldID+'">' +
                '<a href="JavaScript:void(0);" class="rtec-custom-field-remove"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor" aria-hidden="true"><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg></a>' +
                '<h4>Custom Field '+customFieldID+'</h4> ' +
                '<p>' +
                    '<label>Label:</label><input type="text" name="rtec_options[custom'+customFieldID+'_label]" value="Custom '+customFieldID+'" class="large-text">' +
                '</p>' +
                '<p class="rtec-checkbox-row">' +
                    '<input type="checkbox" class="rtec_include_checkbox" name="rtec_options[custom'+customFieldID+'_show]" checked="checked">' +
                    '<label>include</label>' +

                    '<input type="checkbox" class="rtec_require_checkbox" name="rtec_options[custom'+customFieldID+'_require]">' +
                    '<label>require</label>' +
                '</p>' +
                '<p>' +
                    '<label>Error Message:</label>' +
                    '<input type="text" name="rtec_options[custom'+customFieldID+'_error]" value="Error" class="large-text rtec-other-input">' +
                '</p>' +
            '</div>'
        );
        rtecUpdateCustomNames();
    });

    $body.on('click', '.rtec-custom-field-remove', function (event) {
        $(event.target).closest('.rtec-field-options-wrapper').remove();
        rtecUpdateCustomNames();
    });

    // color picker
    var $rtecColorpicker = $('.rtec-colorpicker');

    if ($rtecColorpicker.length > 0){
        $rtecColorpicker.wpColorPicker();
    }

    // EMAIL Tab
    var $rtecNotMessageTr = $('.rtec-notification-message-tr');

    function toggleCustomNotificationTextArea() {
        if ($(this).is(':checked')) {
            $rtecNotMessageTr.fadeIn();
        } else {
            $rtecNotMessageTr.fadeOut();
        }
    }
    toggleCustomNotificationTextArea.apply($('#rtec_use_custom_notification'));

    $('#rtec_use_custom_notification').on('click',function() {
        toggleCustomNotificationTextArea.apply($(this));
    });

    String.prototype.replaceAll = function(search, replacement) {
        var target = this;
        return target.replace(new RegExp(search, 'g'), replacement);
    };

    var $rtecConfirmationTextarea = $('.confirmation_message_textarea'),
        typingTimer,
        doneTypingInterval = 1500;
    function updateText() {
        $('.confirmation_message_textarea').each( function() {
            var confirmationMessage = $(this).val();
            confirmationMessage = confirmationMessage.replaceAll('{venue}', 'Secret Headquarters');
            confirmationMessage = confirmationMessage.replaceAll('{event-title}', 'Secret Meeting');
            confirmationMessage = confirmationMessage.replaceAll('{venue-address}', '123 1st Street');
            confirmationMessage = confirmationMessage.replaceAll('{venue-city}', 'Miami');
            confirmationMessage = confirmationMessage.replaceAll('{venue-state}', 'Florida');
            confirmationMessage = confirmationMessage.replaceAll('{venue-zip}', '55555');
            confirmationMessage = confirmationMessage.replaceAll('{event-date}', 'July 3');
            confirmationMessage = confirmationMessage.replaceAll('{first}', 'James');
            confirmationMessage = confirmationMessage.replaceAll('{last}', 'Bond');
            confirmationMessage = confirmationMessage.replaceAll('{email}', 'Bond007@ohmss.com');
            confirmationMessage = confirmationMessage.replaceAll('{phone}', '(007) 555-5555');
            confirmationMessage = confirmationMessage.replaceAll('{other}', 'Shaken not Stirred');
            confirmationMessage = confirmationMessage.replaceAll('{ical-url}', 'http://example.com/event/secret-meeting/?ical=1');
            confirmationMessage = confirmationMessage.replaceAll('{nl}', "\n");
            $(this).closest('tr').find('.rtec_js_preview').find('pre').text(confirmationMessage);
        });

    }
    if ( $rtecConfirmationTextarea.length){
        updateText();
    }
    $rtecConfirmationTextarea.on('keyup',function(){
        clearTimeout(typingTimer);
        typingTimer = setTimeout(updateText, doneTypingInterval);
    });

    // notices

    if (jQuery('#rtec-notice-bar').length) {
        jQuery('#wpadminbar').after(jQuery('#rtec-notice-bar'));
        jQuery('#wpcontent').css('padding-left', 0);
        jQuery('#wpbody, .rtec-settings-header-inner').css('padding-left', '20px');
        jQuery('#rtec-notice-bar').show();
    }

    jQuery('#rtec-notice-bar .dismiss').on('click',function(e) {
        e.preventDefault();
        jQuery('#rtec-notice-bar').remove();
        var submitData = {
            action : 'rtec_lite_dismiss',
            rtec_nonce : rtecAdminScript.rtec_nonce
        };
        var successFunc = function(data){};
        rtecRegistrationAjax(submitData,successFunc);
    });

    function rtecHelpNoticeDismiss() {
        jQuery('#rtec-help-notice').slideUp();

        var submitData = {
            action : 'rtec_help_notice_dismiss',
            rtec_nonce : rtecAdminScript.rtec_nonce
        };
        var successFunc = function(data){};
        rtecRegistrationAjax(submitData,successFunc);
    }
    $('body').on('click', '#rtec-help-notice .notice-dismiss', function () {
        rtecHelpNoticeDismiss()
    });

    jQuery('#rtec-help-notice .rtec-dismiss').on('click',function(e) {
        e.preventDefault();
        rtecHelpNoticeDismiss()
    });

    // Tooltip
    $('.rtec-tooltip').hide();
    $('.rtec-tooltip-link').on('click', function() {
        if ($(this).next('.rtec-tooltip').is(':visible')) {
            $(this).next('.rtec-tooltip').slideUp();
        } else {
            $(this).next('.rtec-tooltip').slideDown();
        }
    });

    function rtecLocationToggle() {
        $('.rtec-form-location-example').hide();
        $('.rtec-form-location-'+$('#rtec_template_location').val()).show();
    }rtecLocationToggle();
    $('#rtec_template_location').on('change',rtecLocationToggle);

    // REGISTRATIONS overview tab
    // View selector tool
    $('#rtec-filter-go').on('click',function() {
        $('#rtec-toolbar-form').submit();
    });

    // add nav to the top of the page as well
    /*if ($('.rtec-next').length) {
     $('.rtec-toolbar').after($('.rtec-overview-nav').clone());
     }*/

    $('#rtec-registrations-date').on('change', function() {
        var selected = $(this).find(':selected').val();
        if (selected === 'start') {
            $('#rtec-date-picker').show();
        } else {
            $('#rtec-date-picker').hide();
        }
    });

    // date picker
    function rtecDiffInDays( a, b ) {
        return Math.ceil( (a - b) / (1000 * 60 * 60 * 24) );
    }

    var deadlineDate = parseInt( $('.rtec-date-picker').attr('data-rtec-deadline') ) * 1000,
        nowTime = Date.now();

    $('.rtec-date-picker').each(function() {
        $(this).datepicker({
            defaultDate: rtecDiffInDays(deadlineDate, nowTime),
            dateFormat: 'yy-mm-dd',
            beforeShow: function( element, object ){
                // Capture the datepicker div here; it's dynamically generated so best to grab here instead of elsewhere.
                $dpDiv = $( object.dpDiv );

                // "Namespace" our CSS a bit so that our custom jquery-ui-datepicker styles don't interfere with other plugins'/themes'.
                $dpDiv.addClass( 'tribe-ui-datepicker rtec-ui-datepicker' );
            }
        });
    });

    // search registrants
    var $rtecSearchInput = $('#rtec-search-input');

    function rtecGetSearchResults() {
        if($rtecSearchInput.val() !== ''){
            $rtecSearchInput.prop('disabled', true);
            var submitData = {
                    action: 'rtec_get_search_results',
                    term: $rtecSearchInput.val(),
                    rtec_nonce : rtecAdminScript.rtec_nonce
                },
                successFunc = function (data) {
                    $('.rtec-overview').html(data);
                    $rtecSearchInput.prop('disabled',false);

                    $('.rtec-manage-match').on('click',function(event) {
                        event.preventDefault();
                        if ($(this).next('.rtec-manage-match-actions').is(':visible')) {
                            $(this).next('.rtec-manage-match-actions').slideUp();
                        } else {
                            $(this).next('.rtec-manage-match-actions').slideDown();
                        }
                    });
                    $('.rtec-manage-match-actions button').on('click',function(event){
                        var $self = $(this),
                            $context = $self.closest('.rtec-manage-match-actions'),
                            entry_id = $context.attr('data-entry-id'),
                            email = $context.attr('data-email'),
                            action = typeof $self.attr('data-rtec-action') !== 'undefined' ? $self.attr('data-rtec-action') : 'none';
                        if ( action !== 'none' ) {
                            event.preventDefault();
                            var message = action === 'delete-all' ? 'Delete all records with the email address '+email+'? This cannot be undone.' : 'Delete this record? This cannot be undone.';
                            // start spinner to show user that request is processing
                            if (confirm(message)) {
                                $self
                                    .after('<div class="rtec-table-changing spinner is-active"></div>')
                                    .fadeTo("slow", .5).prop('disabled',true);

                                var edit_action = 'delete';
                                if ( action === 'delete-all' ) {
                                    edit_action = 'delete-all';
                                }
                                var submitData = {
                                        action : 'rtec_records_edit',
                                        edit_action : edit_action,
                                        registrations_to_be_deleted : [entry_id],
                                        event_id : 0,
                                        venue: '',
                                        email: email,
                                        rtec_nonce : rtecAdminScript.rtec_nonce
                                    },
                                    successFunc = function (data) {
                                        // remove deleted entries
                                        $('.rtec-being-removed').each(function () {
                                            $(this).remove();
                                        });
                                        // remove spinner
                                        $('.rtec-table-changing').remove();
                                        if ( action === 'delete-single' ) {
                                            $context.closest('tr').fadeOut();
                                        } else {
                                            $('.rtec-registrations-data').find('tbody tr').each(function() {
                                                if ($(this).attr('data-email') === email) {
                                                    $(this).fadeOut();
                                                }
                                            });
                                        }
                                        //location.reload();

                                    };
                                rtecRegistrationAjax(submitData,successFunc);
                            }


                        }
                    });
                    // remove spinner
                    //$targetForm.find('.rtec-table-changing').remove();
                    //$targetForm.find('.rtec-update-event-options').prop('disabled',false);
                };
            rtecRegistrationAjax(submitData,successFunc);
        }

    }

    $rtecSearchInput.on('keyup',function(){
        clearTimeout(typingTimer);
        typingTimer = setTimeout(rtecGetSearchResults, doneTypingInterval);
    });

    $rtecSearchInput.on('keydown',function() {
        clearTimeout(typingTimer);
    });

    // dismiss new
    $('#rtec-new-dismiss').on('click',function(event) {
        event.preventDefault();
        $('#rtec-new-dismiss,.rtec-notice-admin-reg-count').remove();

        var submitData = {
                action: 'rtec_dismiss_new',
                rtec_nonce : rtecAdminScript.rtec_nonce
            },
            successFunc = function (data) {
                $('#rtec-new-dismiss,.rtec-notice-admin-reg-count').remove();
                $('.rtec-identifier .rtec-icon-tag').closest('.rtec-new-registration-tag').hide();
            };
        rtecRegistrationAjax(submitData,successFunc);
    });
    $('.rtec-hidden-options').hide();

    var $rtecOptionsHandle = $('.rtec-event-options .handlediv');

    $rtecOptionsHandle.on('click',function() {
        var $rtecEventOptions = $(this).closest('.rtec-event-options')
        $rtecEventOptions.next().slideToggle();
        if ($rtecEventOptions.hasClass('open')) {
            $rtecEventOptions.addClass('closed').removeClass('open');
        } else {
            $rtecEventOptions.addClass('open').removeClass('closed');
        }
    });

    function rtecDisabledToggle($wrapEl) {
        var $disableReg = $wrapEl.find('input[name="_RTECregistrationsDisabled"]'),
            $limitReg = $wrapEl.find('input[name="_RTEClimitRegistrations"]'),
            $maxReg = $wrapEl.find('input[name="_RTECmaxRegistrations"]'),
            $deadlineType = $wrapEl.find('input[name="_RTECdeadlineType"]');

        if ($disableReg.is(':checked')) {
            $limitReg.prop('disabled','true');
            $maxReg.prop('disabled','true');
            $deadlineType.prop('disabled','true');
        } else {
            $limitReg.prop('disabled',false).closest('.rtec-fade').removeClass('rtec-fade');
            $deadlineType.prop('disabled',false).closest('.rtec-fade').removeClass('rtec-fade');
            if ($limitReg.is(':checked')) {
                $maxReg.prop('disabled',false).closest('.rtec-fade').removeClass('rtec-fade');
            } else {
                $maxReg.prop('disabled','true');
            }
        }

        if ($wrapEl.find('input[name=_RTECdeadlineType]:checked').val() === 'other') {
            $wrapEl.find('.rtec-time-picker, .rtec-date-picker').removeClass('rtec-fade');
        } else {
            $wrapEl.find('.rtec-time-picker, .rtec-date-picker').addClass('rtec-fade');
        }
    }

    $('.rtec-eventtable .rtec-hidden-option-wrap input').on('change', function() {
        rtecDisabledToggle($(this).closest('.rtec-eventtable'));
    });
    $('.rtec-hidden-options .rtec-hidden-option-wrap input').on('change', function() {
        rtecDisabledToggle($(this).closest('.rtec-hidden-options'));
    });


    // REGISTRATION single tab
    // set table width to a minimum in case of a lot of fields
    var $rtecSingle = $('.rtec-single'),
        onSingle = $rtecSingle.length;

    if (onSingle) {
        // Pro: min-width on wrapper is disabled; table width comes from CSS.
        // $rtecSingle.css('min-width', $('.rtec-single table th').length*125);

        // Column section navigation: show one group of form field columns at a time
        $rtecSingle.on('click', '.rtec-data-nav-wrap', function() {
            var nextIndex = $(this).find('.rtec-data-nav').attr('data-next-index');
            if (nextIndex !== undefined) {
                $rtecSingle.find('[class*="rtec-data-group-"]').hide();
                $rtecSingle.find('.rtec-data-group-' + nextIndex).show();
            }
        });

        var RtecRecordsEditor = {
            $table : $rtecSingle.find('.rtec-single-event').find('table'),
            $nav : $rtecSingle.find('.rtec-single-event').find('table').next(),
            eventID : $rtecSingle.find('.rtec-single-event').attr('data-rtec-event-id'),
            fieldAtts : JSON.parse($rtecSingle.find('.rtec-single-event').attr('data-rtec-field-atts')),
            checked : [],

            getChecked : function() {
                var idsChecked = [];
                $('.rtec-registration-select').each(function() {
                    if ($(this).is(':checked')) {
                        idsChecked.push($(this).val());
                    }
                });
                return idsChecked;
            },
            removeEntries : function(idsToRemove) {
                $.each(idsToRemove,function() {
                    $('#rtec-select-'+this).closest('tr').addClass('rtec-being-removed');
                });
                // start spinner to show user that request is processing
                $('.rtec-single table tbody')
                    .after('<div class="rtec-table-changing spinner is-active"></div>')
                    .fadeTo("slow", .2);

                var submitData = {
                        action : 'rtec_records_edit',
                        edit_action : 'delete',
                        registrations_to_be_deleted : idsToRemove,
                        event_id : this.eventID,
                        venue: this.mvtID,
                        rtec_nonce : rtecAdminScript.rtec_nonce
                    },
                    successFunc = function (data) {
                        // remove deleted entries
                        $('.rtec-being-removed').each(function () {
                            $(this).remove();
                        });
                        // remove spinner
                        $('.rtec-table-changing').remove();
                        $('.rtec-single table tbody').fadeTo("fast", 1);
                        idsToRemove = [];
                        $('.rtec-num-registered-text').text(parseInt(data));
                        location.reload();

                    };
                rtecRegistrationAjax(submitData,successFunc);
            }
        };
    }

    function rtecRegistrationAjax(submitData,successFunc) {
        $.ajax({
            url: rtecAdminScript.ajax_url,
            type: 'post',
            data: submitData,
            success: successFunc
        });
    }

    $('.rtec-action').on('click',function() {

        if ($(this).attr('data-rtec-action') === 'delete') {
            var idsToRemove = RtecRecordsEditor.getChecked();

            // if registrations_to_be_deleted is not empty
            if (idsToRemove.length) {
                // give a warning to the user that this cannot be undone
                if (confirm(idsToRemove.length + ' registrations to be deleted. This cannot be undone.')) {
                    RtecRecordsEditor.removeEntries(idsToRemove);
                }

            } // if registrations to be deleted is not empty
        }
    }); // action click

    // Content modal: save registration form
    $body.on('submit', '#rtec-content-modal form.rtec-add-edit-form', function (e) {
        e.preventDefault();
        var $form = $(this),
            standard = {},
            custom = {};
        $form.find('.rtec-standard-input').each(function () {
            var name = $(this).attr('name');
            if (name) { standard[name] = $(this).val(); }
        });
        $form.find('.rtec-custom-input').each(function () {
            var name = $(this).attr('name');
            if (name) { custom[name] = $(this).val(); }
        });
        var submitData = {
            action: 'rtec_records_edit',
            edit_action: $form.find('input[name="rtecAction"]').val(),
            entry_id: $form.find('input[name="rtecEntryID"]').val(),
            event_id: $form.find('input[name="rtecEventID"]').val(),
            standard: JSON.stringify(standard),
            custom: JSON.stringify(custom),
            rtec_nonce: rtecAdminScript.rtec_nonce
        };
        $('#rtec-content-modal-content').html('<div class="rtec-content-modal-placeholder"><span class="spinner is-active" style="float:none;display:block;margin:20px auto;"></span></div>');
        rtecRegistrationAjax(submitData, function () {
            $('#rtec-content-modal-backdrop').trigger('click');
            location.reload();
        });
    });

    // Content modal: delete registration button
    $body.on('click', '#rtec-content-modal .rtec-manage-registration .rtec-action-delete', function (e) {
        e.preventDefault();
        var $btn = $(this),
            entryId = $btn.attr('data-rtec-entryid') || $btn.closest('.rtec-manage-registration').attr('data-rtec-entry-id'),
            eventId = $btn.attr('data-rtec-eventid') || $btn.closest('.rtec-manage-registration').attr('data-rtec-event-id');
        if (!entryId || !eventId) return;
        if (!confirm('Delete this registration? This cannot be undone.')) return;
        var removeData = {
            action: 'rtec_records_edit',
            edit_action: 'delete',
            registrations_to_be_deleted: [entryId],
            event_id: eventId,
            venue: '',
            rtec_nonce: rtecAdminScript.rtec_nonce
        };
        $('#rtec-content-modal-content').html('<div class="rtec-content-modal-placeholder"><span class="spinner is-active" style="float:none;display:block;margin:20px auto;"></span></div>');
        rtecRegistrationAjax(removeData, function () {
            $('#rtec-content-modal-backdrop').trigger('click');
            location.reload();
        });
    });

  $('#rtec-banner-dismiss').on('click', function (event) {
    event.preventDefault();
    if (typeof $('#rtec-banner-dismiss').attr('data-disabled') === 'undefined') {
      rtecDismissBanner('always');
    }
  });

  $('body').on('click', '.rtec-admin-notice-banner .notice-dismiss', function () {
    if (typeof $('#rtec-banner-dismiss').attr('data-disabled') === 'undefined') {
      rtecDismissBanner($('#rtec-banner-dismiss').attr('data-time'));
    }
  });

  function rtecDismissBanner(time) {
    $('#rtec-banner-dismiss').css('opacity', '.5').attr('data-disabled', '1').after('<div class="spinner" style="visibility: visible; position: relative;float: left;"></div>');

    var submitData = {
        action: 'rtec_dismiss_banner',
        time: time,
        rtec_nonce : rtecAdminScript.rtec_nonce
      },
      successFunc = function (data) {
        if (data.success === true) {
          $('.rtec-admin-notice-banner').fadeOut();
        } else {
          $('#rtec-banner-dismiss').after('<div>Error: Please refresh the page and try again.</div>');
        }
      }
    rtecRegistrationAjax(submitData, successFunc);
  }

});
