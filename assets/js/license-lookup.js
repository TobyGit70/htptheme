/**
 * Happy Turtle Processing - License Lookup & Auto-Populate
 * Automatically fills partner information based on Arkansas Cannabis License Number
 */

(function($) {
    'use strict';

    console.log('HTB License Lookup: Script loaded v1.0.9');

    // Store partner data for saving updates
    let currentPartner = null;
    let fieldsModified = false;

    // Field mappings: WPForms field labels to partner data keys
    const fieldMappings = {
        // Contact form fields
        'Name': { key: 'contact_name', type: 'name' },
        'Contact Name': { key: 'contact_name', type: 'name' },
        'Your Name': { key: 'contact_name', type: 'name' },
        'Email': { key: 'email', type: 'email' },
        'Business Email': { key: 'email', type: 'email' },
        'Phone': { key: 'phone', type: 'phone' },
        'Company Name': { key: 'business_name', type: 'text' },
        'Company/License Name': { key: 'business_name', type: 'text' },
        'Arkansas Cannabis License Number': { key: 'license_number', type: 'text' },
        'Arkansas License Number': { key: 'license_number', type: 'text' },
        'License Number': { key: 'license_number', type: 'text' },
        'BioTrack THC ID': { key: 'biotrack_license', type: 'text' },
        'License Type': { key: 'license_type', type: 'select' }
    };

    // License type value mappings
    const licenseTypeMap = {
        'Cultivator': '1',
        'Processor': '2',
        'Dispensary': '3',
        'Transporter': '4',
        'cultivator': '1',
        'processor': '2',
        'dispensary': '3',
        'transporter': '4'
    };

    /**
     * Initialize license lookup functionality
     */
    function init() {
        // Wait for WPForms to be ready
        $(document).ready(function() {
            // Find license field and add lookup functionality
            setupLicenseField();

            // Track field modifications
            trackFieldChanges();

            // Add save button for modified data
            addSaveButton();
        });
    }

    /**
     * Find and setup the license field for lookup
     */
    function setupLicenseField() {
        // Find license field by label text
        const licenseLabels = [
            'Arkansas Cannabis License Number',
            'Arkansas License Number',
            'License Number'
        ];

        licenseLabels.forEach(function(labelText) {
            const $label = $('label:contains("' + labelText + '")').filter(function() {
                return $(this).text().trim() === labelText ||
                       $(this).text().trim().startsWith(labelText);
            });

            if ($label.length) {
                const $container = $label.closest('.wpforms-field');
                const $input = $container.find('input[type="text"]');

                if ($input.length && !$input.hasClass('htb-license-lookup')) {
                    $input.addClass('htb-license-lookup');

                    // Wrap input and button in flex container for inline layout
                    const $wrapper = $('<div class="htb-license-wrapper" style="display: flex; gap: 10px; align-items: flex-start;"></div>');
                    $input.wrap($wrapper);

                    // Add lookup button after input
                    const $lookupBtn = $('<button type="button" class="htb-lookup-btn" style="' +
                        'background: linear-gradient(135deg, #1B4332, #2D6A4F); color: #fff; border: none; ' +
                        'padding: 10px 20px; border-radius: 6px; cursor: pointer; white-space: nowrap; ' +
                        'font-weight: 600; font-size: 14px; transition: all 0.3s ease; height: 43px;">' +
                        'Lookup</button>');

                    $input.after($lookupBtn);

                    // Add status message container
                    const $status = $('<div class="htb-lookup-status" style="' +
                        'margin-top: 8px; padding: 10px; border-radius: 6px; display: none;"></div>');
                    $container.append($status);

                    // Bind lookup on button click
                    $lookupBtn.on('click', function(e) {
                        e.preventDefault();
                        performLookup($input.val());
                    });

                    // Bind lookup on Enter key
                    $input.on('keypress', function(e) {
                        if (e.which === 13) {
                            e.preventDefault();
                            performLookup($input.val());
                        }
                    });

                    // Auto-lookup on blur if field has value
                    $input.on('blur', function() {
                        const val = $(this).val().trim();
                        if (val.length >= 5 && !currentPartner) {
                            performLookup(val);
                        }
                    });
                }
            }
        });

        // Move license field to top of form
        moveLicenseFieldToTop();
    }

    /**
     * Move license field to the top of the form
     */
    function moveLicenseFieldToTop() {
        const $form = $('.wpforms-form');
        const $licenseField = $('.htb-license-lookup').closest('.wpforms-field');

        if ($licenseField.length && $form.length) {
            const $firstField = $form.find('.wpforms-field').first();

            // Only move if not already first
            if (!$licenseField.is($firstField)) {
                $licenseField.insertBefore($firstField);

                // Add instruction text
                if (!$licenseField.find('.htb-lookup-instruction').length) {
                    const $instruction = $('<p class="htb-lookup-instruction" style="' +
                        'color: #2D6A4F; font-size: 14px; margin-bottom: 15px; ' +
                        'padding: 12px; background: #f0f9f4; border-radius: 6px; ' +
                        'border-left: 4px solid #2D6A4F;">' +
                        'Enter your Arkansas Cannabis License Number to auto-fill your information. ' +
                        'You can edit any auto-filled fields before submitting.</p>');
                    $licenseField.prepend($instruction);
                }
            }
        }
    }

    /**
     * Perform AJAX license lookup
     */
    function performLookup(licenseNumber) {
        if (!licenseNumber || licenseNumber.length < 3) {
            showStatus('Please enter a valid license number', 'error');
            return;
        }

        const $btn = $('.htb-lookup-btn');
        const originalText = $btn.text();

        $btn.prop('disabled', true).text('Looking up...');
        showStatus('Searching for partner...', 'info');

        $.ajax({
            url: htbLicenseLookup.ajaxUrl,
            type: 'POST',
            data: {
                action: 'htb_lookup_partner_by_license',
                nonce: htbLicenseLookup.nonce,
                license_number: licenseNumber
            },
            success: function(response) {
                $btn.prop('disabled', false).text(originalText);

                if (response.success) {
                    currentPartner = response.data.partner;
                    populateFormFields(response.data.partner);
                    showStatus(response.data.message, 'success');
                    showSaveButton();
                } else {
                    showStatus(response.data.message, 'warning');
                    currentPartner = null;
                }
            },
            error: function() {
                $btn.prop('disabled', false).text(originalText);
                showStatus('Lookup failed. Please try again or enter information manually.', 'error');
            }
        });
    }

    /**
     * Populate form fields with partner data
     */
    function populateFormFields(partner) {
        if (!partner) return;

        // Find all form fields and try to match them
        $('.wpforms-field').each(function() {
            const $field = $(this);
            const $label = $field.find('label').first();
            const labelText = $label.text().replace('*', '').trim();

            const mapping = fieldMappings[labelText];
            if (mapping && partner[mapping.key]) {
                const value = partner[mapping.key];

                switch (mapping.type) {
                    case 'name':
                        // Handle name fields (first/last) - WPForms uses specific class names
                        const $firstInput = $field.find('input.wpforms-field-name-first, input[name*="[first]"]');
                        const $lastInput = $field.find('input.wpforms-field-name-last, input[name*="[last]"]');
                        const $singleInput = $field.find('input[type="text"]').first();

                        if ($firstInput.length && $lastInput.length && value) {
                            const nameParts = value.trim().split(' ');
                            const firstName = nameParts[0] || '';
                            const lastName = nameParts.slice(1).join(' ') || '';
                            $firstInput.val(firstName).addClass('htb-autofilled');
                            $lastInput.val(lastName).addClass('htb-autofilled');
                        } else if ($singleInput.length && value) {
                            $singleInput.val(value).addClass('htb-autofilled');
                        }
                        break;

                    case 'email':
                        $field.find('input[type="email"], input[type="text"]').first()
                            .val(value).addClass('htb-autofilled');
                        break;

                    case 'phone':
                        $field.find('input[type="tel"], input[type="text"]').first()
                            .val(value).addClass('htb-autofilled');
                        break;

                    case 'text':
                        $field.find('input[type="text"]').first()
                            .val(value).addClass('htb-autofilled');
                        break;

                    case 'select':
                        const $select = $field.find('select');
                        if ($select.length) {
                            // Try to match by value or by license type mapping
                            const mappedValue = licenseTypeMap[value] || value;
                            $select.val(mappedValue).addClass('htb-autofilled');
                        }
                        break;
                }
            }
        });

        // Add visual indicator for auto-filled fields
        addAutofilledStyles();
    }

    /**
     * Add CSS styles for auto-filled fields
     */
    function addAutofilledStyles() {
        if ($('#htb-autofill-styles').length === 0) {
            $('head').append(`
                <style id="htb-autofill-styles">
                    .htb-autofilled {
                        background-color: #f0f9f4 !important;
                        border-color: #2D6A4F !important;
                        transition: background-color 0.3s ease;
                    }
                    .htb-autofilled:focus {
                        background-color: #fff !important;
                    }
                    .htb-modified {
                        background-color: #fff9e6 !important;
                        border-color: #D4A574 !important;
                    }
                    .htb-save-updates-btn {
                        background: linear-gradient(135deg, #D4A574, #B8854A) !important;
                        color: #1B4332 !important;
                        border: none !important;
                        padding: 10px 20px !important;
                        border-radius: 8px !important;
                        font-weight: 700 !important;
                        cursor: pointer !important;
                        margin-top: 15px !important;
                        display: none;
                    }
                    .htb-save-updates-btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(212, 165, 116, 0.4);
                    }
                    .htb-lookup-btn:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(45, 106, 79, 0.4);
                    }
                </style>
            `);
        }
    }

    /**
     * Track changes to auto-filled fields
     */
    function trackFieldChanges() {
        $(document).on('input change', '.htb-autofilled', function() {
            $(this).removeClass('htb-autofilled').addClass('htb-modified');
            fieldsModified = true;
            showSaveButton();
        });
    }

    /**
     * Add save button for updating partner info
     */
    function addSaveButton() {
        const $form = $('.wpforms-form');
        if ($form.length && !$('.htb-save-updates-btn').length) {
            const $saveBtn = $('<button type="button" class="htb-save-updates-btn">' +
                'Save Updated Information to Partner Profile</button>');

            $form.find('.wpforms-submit-container').before($saveBtn);

            $saveBtn.on('click', function(e) {
                e.preventDefault();
                savePartnerUpdates();
            });
        }
    }

    /**
     * Show save button when fields are modified
     */
    function showSaveButton() {
        if (currentPartner && fieldsModified) {
            $('.htb-save-updates-btn').show();
        }
    }

    /**
     * Save updated partner information
     */
    function savePartnerUpdates() {
        if (!currentPartner) return;

        const $btn = $('.htb-save-updates-btn');
        $btn.prop('disabled', true).text('Saving...');

        // Collect modified field values
        const updateData = {
            action: 'htb_update_partner_from_form',
            nonce: htbLicenseLookup.nonce,
            partner_id: currentPartner.id,
            license_number: currentPartner.license_number
        };

        // Map form fields back to partner fields
        $('.htb-modified, .htb-autofilled').each(function() {
            const $input = $(this);
            const $field = $input.closest('.wpforms-field');
            const $label = $field.find('label').first();
            const labelText = $label.text().replace('*', '').trim();

            const mapping = fieldMappings[labelText];
            if (mapping) {
                updateData[mapping.key] = $input.val();
            }
        });

        $.ajax({
            url: htbLicenseLookup.ajaxUrl,
            type: 'POST',
            data: updateData,
            success: function(response) {
                $btn.prop('disabled', false).text('Save Updated Information to Partner Profile');

                if (response.success) {
                    showStatus('Partner information saved successfully!', 'success');
                    $('.htb-modified').removeClass('htb-modified').addClass('htb-autofilled');
                    fieldsModified = false;
                    $btn.hide();
                } else {
                    showStatus(response.data.message || 'Failed to save updates', 'error');
                }
            },
            error: function() {
                $btn.prop('disabled', false).text('Save Updated Information to Partner Profile');
                showStatus('Failed to save updates. Please try again.', 'error');
            }
        });
    }

    /**
     * Show status message
     */
    function showStatus(message, type) {
        const $status = $('.htb-lookup-status');

        const colors = {
            success: { bg: '#d1f2eb', border: '#10b981', text: '#065f46' },
            error: { bg: '#fee2e2', border: '#ef4444', text: '#991b1b' },
            warning: { bg: '#fef3cd', border: '#fbbf24', text: '#92400e' },
            info: { bg: '#dbeafe', border: '#3b82f6', text: '#1e40af' }
        };

        const style = colors[type] || colors.info;

        $status.css({
            'background-color': style.bg,
            'border-left': '4px solid ' + style.border,
            'color': style.text
        }).html(message).slideDown(200);

        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(function() {
                $status.slideUp(200);
            }, 5000);
        }
    }

    /**
     * Show custom modal dialog
     */
    function showModal(title, message, onConfirm, onCancel) {
        // Remove any existing modal
        $('.htb-modal-overlay').remove();

        const modalHtml = `
            <div class="htb-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.6);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                backdrop-filter: blur(4px);
            ">
                <div class="htb-modal" style="
                    background: #fff;
                    border-radius: 16px;
                    padding: 32px;
                    max-width: 480px;
                    width: 90%;
                    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
                    animation: htbModalSlideIn 0.3s ease;
                    text-align: center;
                ">
                    <div style="
                        width: 64px;
                        height: 64px;
                        background: linear-gradient(135deg, #1B4332, #2D6A4F);
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin: 0 auto 20px;
                    ">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <h3 style="
                        color: #1B4332;
                        font-size: 24px;
                        font-weight: 700;
                        margin: 0 0 12px;
                    ">${title}</h3>
                    <p style="
                        color: #4a5568;
                        font-size: 16px;
                        line-height: 1.6;
                        margin: 0 0 28px;
                    ">${message}</p>
                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button class="htb-modal-cancel" style="
                            padding: 12px 24px;
                            border: 2px solid #e2e8f0;
                            background: #fff;
                            color: #4a5568;
                            border-radius: 8px;
                            font-size: 16px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.2s ease;
                        ">Stay Here</button>
                        <button class="htb-modal-confirm" style="
                            padding: 12px 24px;
                            border: none;
                            background: linear-gradient(135deg, #1B4332, #2D6A4F);
                            color: #fff;
                            border-radius: 8px;
                            font-size: 16px;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.2s ease;
                            box-shadow: 0 4px 12px rgba(27, 67, 50, 0.3);
                        ">Go to ${title}</button>
                    </div>
                </div>
            </div>
            <style>
                @keyframes htbModalSlideIn {
                    from {
                        opacity: 0;
                        transform: translateY(-20px) scale(0.95);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                .htb-modal-cancel:hover {
                    background: #f7fafc !important;
                    border-color: #cbd5e0 !important;
                }
                .htb-modal-confirm:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 16px rgba(27, 67, 50, 0.4) !important;
                }
            </style>
        `;

        $('body').append(modalHtml);

        // Bind events
        $('.htb-modal-confirm').on('click', function() {
            $('.htb-modal-overlay').remove();
            if (onConfirm) onConfirm();
        });

        $('.htb-modal-cancel, .htb-modal-overlay').on('click', function(e) {
            if (e.target === this) {
                $('.htb-modal-overlay').remove();
                if (onCancel) onCancel();
            }
        });

        // Close on Escape key
        $(document).on('keydown.htbModal', function(e) {
            if (e.key === 'Escape') {
                $('.htb-modal-overlay').remove();
                $(document).off('keydown.htbModal');
                if (onCancel) onCancel();
            }
        });
    }

    /**
     * Setup redirect logic for "Become a Partner" and "Pricing & Quotes"
     */
    function setupPartnerRedirect() {
        $(document).on('change', 'select', function() {
            const $select = $(this);
            const $field = $select.closest('.wpforms-field');
            const $label = $field.find('label').first();
            const labelText = $label.text().replace('*', '').trim();

            // Check if this is the "Reason for Contact" field
            if (labelText === 'Reason for Contact') {
                const selectedText = $select.find('option:selected').text().trim();

                // Handle "Become a Partner" selection
                if (selectedText === 'Become a Partner') {
                    handleRedirect('Partner Application', '/partner-application/',
                        'You have a valid Arkansas license on file. Would you like to continue to our Partner Application with your information pre-filled?',
                        'Would you like to continue to our Partner Application? You can complete your full application there.',
                        'Please enter your Arkansas License Number first, then select "Become a Partner" to be redirected to our partner application.'
                    );
                }
            }
        });
    }

    /**
     * Handle redirect to a specific page with partner data
     */
    function handleRedirect(title, url, messageWithLicense, messageWithoutLicense, messageNoLicense) {
        if (currentPartner && currentPartner.license_number) {
            // Show modal and redirect with partner data
            showModal(
                title,
                messageWithLicense,
                function() {
                    const params = new URLSearchParams();
                    params.set('license', currentPartner.license_number);
                    params.set('name', currentPartner.contact_name || '');
                    params.set('company', currentPartner.business_name || '');
                    params.set('email', currentPartner.email || '');
                    params.set('phone', currentPartner.phone || '');

                    window.location.href = url + '#' + params.toString();
                }
            );
        } else {
            const licenseValue = $('.htb-license-lookup').val();
            if (!licenseValue || licenseValue.length < 5) {
                showStatus(messageNoLicense, 'info');
            } else {
                showModal(
                    title,
                    messageWithoutLicense,
                    function() {
                        window.location.href = url + '#license=' + encodeURIComponent(licenseValue);
                    }
                );
            }
        }
    }

    /**
     * Pre-fill form from URL parameters (for partner application page)
     * Supports both query params (?...) and hash params (#...)
     */
    function prefillFromUrlParams() {
        // Try query params first, then hash params
        let params = new URLSearchParams(window.location.search);

        // If no query params, check hash
        if (!params.has('license') && !params.has('name') && !params.has('email')) {
            const hash = window.location.hash.substring(1); // Remove the #
            if (hash) {
                params = new URLSearchParams(hash);
                console.log('HTB License Lookup: Using hash params');
            }
        }

        if (params.has('license') || params.has('name') || params.has('email')) {
            console.log('HTB License Lookup: Found params to prefill:', params.toString());
            attemptPrefill(params, 0);
        }
    }

    /**
     * Attempt to prefill form fields with retry logic
     */
    function attemptPrefill(params, attempt) {
        const maxAttempts = 10;
        const delay = attempt === 0 ? 500 : 1000;

        setTimeout(function() {
            console.log('HTB License Lookup: Prefill attempt', attempt + 1, 'of', maxAttempts);

            // Check if WPForms container exists and has labels
            const $wpformsContainer = $('.wpforms-container');
            const $wpformsLabels = $('.wpforms-field-label');
            console.log('HTB License Lookup: Container:', $wpformsContainer.length, 'Labels:', $wpformsLabels.length);

            // Also check if age gate is still blocking
            const $ageGate = $('.age-gate');
            if ($ageGate.length && $ageGate.is(':visible')) {
                console.log('HTB License Lookup: Age gate still visible, waiting...');
                if (attempt < maxAttempts - 1) {
                    attemptPrefill(params, attempt + 1);
                    return;
                }
            }

            if ($wpformsLabels.length === 0) {
                if (attempt < maxAttempts - 1) {
                    console.log('HTB License Lookup: No WPForms labels yet, retrying...');
                    attemptPrefill(params, attempt + 1);
                    return;
                } else {
                    console.log('HTB License Lookup: Max attempts reached, no form found');
                    return;
                }
            }

            // Labels found, proceed with prefill
            console.log('HTB License Lookup: Starting prefill');

            // Map URL params to field labels
            const urlParamMappings = {
                'license': ['Arkansas Cannabis License Number', 'Arkansas License Number', 'License Number'],
                'name': ['Name', 'Contact Name', 'Your Name'],
                'company': ['Company Name', 'Company/License Name', 'Business Name'],
                'email': ['Email', 'Business Email'],
                'phone': ['Phone', 'Phone Number']
            };

            // Log all WPForms labels on the page
            console.log('HTB License Lookup: WPForms labels on page:');
            $wpformsLabels.each(function() {
                console.log('  - "' + $(this).text().replace('*', '').trim() + '"');
            });

            // Fill each field from URL params
            params.forEach(function(value, key) {
                console.log('HTB License Lookup: Processing param', key, '=', value);
                if (!value) return;

                // Special handling for name field - look for wpforms-field-name class directly
                if (key === 'name') {
                    const $nameField = $('.wpforms-field-name');
                    if ($nameField.length) {
                        console.log('HTB License Lookup: Found name field by class');
                        const $firstInput = $nameField.find('input.wpforms-field-name-first, input[name*="[first]"]');
                        const $lastInput = $nameField.find('input.wpforms-field-name-last, input[name*="[last]"]');

                        if ($firstInput.length && $lastInput.length) {
                            const nameParts = value.trim().split(' ');
                            const firstName = nameParts[0] || '';
                            const lastName = nameParts.slice(1).join(' ') || '';
                            $firstInput.val(firstName).addClass('htb-autofilled');
                            $lastInput.val(lastName).addClass('htb-autofilled');
                            console.log('HTB License Lookup: Filled name:', firstName, lastName);
                        }
                    }
                    return;
                }

                // Standard label-based lookup for other fields
                if (urlParamMappings[key]) {
                    const labels = urlParamMappings[key];

                    labels.forEach(function(labelText) {
                        // Look specifically for WPForms labels
                        const $label = $('.wpforms-field-label').filter(function() {
                            const text = $(this).text().replace('*', '').trim();
                            return text === labelText || text.startsWith(labelText);
                        });

                        if ($label.length) {
                            console.log('HTB License Lookup: Found label "' + labelText + '"');
                            const $field = $label.closest('.wpforms-field');

                            if (key === 'email') {
                                $field.find('input[type="email"], input[type="text"]').first()
                                    .val(value).addClass('htb-autofilled');
                                console.log('HTB License Lookup: Filled email');
                            } else if (key === 'phone') {
                                $field.find('input[type="tel"], input[type="text"]').first()
                                    .val(value).addClass('htb-autofilled');
                                console.log('HTB License Lookup: Filled phone');
                            } else {
                                // License, company, etc.
                                const $input = $field.find('input[type="text"]').first();
                                if ($input.length) {
                                    $input.val(value).addClass('htb-autofilled');
                                    console.log('HTB License Lookup: Filled', key);

                                    // If this is the license field, also set up the lookup
                                    if (key === 'license') {
                                        $input.addClass('htb-license-lookup');
                                    }
                                }
                            }
                        }
                    });
                }
            });

            // Add autofill styles
            addAutofilledStyles();

            // Show success message if fields were populated
            if (params.has('license')) {
                showStatus('Your information has been pre-filled from your license lookup. Please review and complete the remaining fields.', 'success');

                // Also trigger a lookup to get any additional partner data
                const licenseValue = params.get('license');
                if (licenseValue) {
                    performLookup(licenseValue);
                }
            }

        }, delay);
    }

    // Initialize
    init();
    setupPartnerRedirect();
    prefillFromUrlParams();

})(jQuery);
