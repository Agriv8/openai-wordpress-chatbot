jQuery(document).ready(function($) {
    // Test API connection on page load
    testAPIConnection();
    
    // Test API button click
    $('#test-api').on('click', function() {
        testAPIConnection();
    });
    
    // Expand with AI button
    $('#expand-with-ai').on('click', function() {
        expandContentWithAI();
    });
    
    // Apply AI version
    $('#apply-ai-version').on('click', function() {
        const enhancedData = $('#ai-enhanced-data').val();
        $('#service-data').val(enhancedData);
        $('#ai-preview').slideUp();
    });
    
    // Cancel AI version
    $('#cancel-ai-version').on('click', function() {
        $('#ai-preview').slideUp();
    });
    
    // Save settings
    $('#save-settings').on('click', function() {
        saveSettings();
    });
    
    // Auto-expand checkbox change
    $('#auto-expand').on('change', function() {
        if ($(this).is(':checked')) {
            expandContentWithAI();
        }
    });
    
    // Function to test API connection
    function testAPIConnection() {
        const $status = $('#api-status');
        const $button = $('#test-api');
        
        $status.removeClass('success error').addClass('checking').text('Checking...');
        $button.prop('disabled', true);
        
        $.post(openai_admin.ajax_url, {
            action: 'test_openai_api',
            nonce: openai_admin.nonce
        })
        .done(function(response) {
            if (response.success) {
                $status.removeClass('checking error').addClass('success').text('Connected');
            } else {
                // Check if it's specifically an API key not configured error
                if (response.data.includes('not configured')) {
                    $status.removeClass('checking success').addClass('error warning').text('API key not configured');
                } else {
                    $status.removeClass('checking success').addClass('error').text('Error: ' + response.data);
                }
            }
        })
        .fail(function() {
            $status.removeClass('checking success').addClass('error').text('Connection failed');
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    }
    
    // Function to expand content with AI
    function expandContentWithAI() {
        const $button = $('#expand-with-ai');
        const $serviceData = $('#service-data');
        const currentData = $serviceData.val();
        
        // Validate JSON
        try {
            JSON.parse(currentData);
        } catch (e) {
            alert('Invalid JSON format. Please fix the JSON before expanding.');
            return;
        }
        
        $button.prop('disabled', true).text('Expanding...');
        
        $.post(openai_admin.ajax_url, {
            action: 'expand_content_ai',
            nonce: openai_admin.nonce,
            content: currentData
        })
        .done(function(response) {
            if (response.success) {
                $('#ai-enhanced-data').val(response.data);
                $('#ai-preview').slideDown();
            } else {
                alert('Error: ' + response.data);
            }
        })
        .fail(function() {
            alert('Failed to expand content with AI');
        })
        .always(function() {
            $button.prop('disabled', false).text('Expand with AI');
        });
    }
    
    // Function to save all settings
    function saveSettings() {
        const $button = $('#save-settings');
        const $spinner = $button.siblings('.spinner');
        const $message = $('#save-message');
        
        // Collect all settings
        const settings = {
            company: $('#company-name').val(),
            language: $('#language').val(),
            email_recipient: $('#email-recipient').val(),
            service_data: $('#service-data').val()
        };
        
        // Validate JSON
        try {
            JSON.parse(settings.service_data);
        } catch (e) {
            $message.removeClass('success').addClass('error').text('Invalid JSON format');
            return;
        }
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $message.text('');
        
        $.post(openai_admin.ajax_url, {
            action: 'save_chatbot_settings',
            nonce: openai_admin.nonce,
            settings: JSON.stringify(settings)
        })
        .done(function(response) {
            if (response.success) {
                $message.removeClass('error').addClass('success').text(response.data);
            } else {
                $message.removeClass('success').addClass('error').text('Error: ' + response.data);
            }
        })
        .fail(function() {
            $message.removeClass('success').addClass('error').text('Failed to save settings');
        })
        .always(function() {
            $button.prop('disabled', false);
            $spinner.removeClass('is-active');
            
            // Clear message after 3 seconds
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).text('').show();
                });
            }, 3000);
        });
    }
    
    // JSON syntax highlighting (optional enhancement)
    function highlightJSON() {
        const $textarea = $('#service-data');
        const content = $textarea.val();
        
        // This is a placeholder for JSON syntax highlighting
        // You could implement a more sophisticated solution using CodeMirror or similar
    }
    
    // Auto-save functionality (optional)
    let autoSaveTimer;
    $('#service-data, #company-name, #email-recipient').on('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function() {
            // Could implement auto-save here
        }, 2000);
    });
    
    // Validate JSON on blur
    $('#service-data').on('blur', function() {
        const $this = $(this);
        const value = $this.val();
        
        try {
            JSON.parse(value);
            $this.css('border-color', '');
        } catch (e) {
            $this.css('border-color', '#dc3232');
        }
    });
    
    // Pretty print JSON
    $('#service-data').on('blur', function() {
        const $this = $(this);
        const value = $this.val();
        
        try {
            const parsed = JSON.parse(value);
            const pretty = JSON.stringify(parsed, null, 2);
            $this.val(pretty);
            $this.css('border-color', '');
        } catch (e) {
            $this.css('border-color', '#dc3232');
        }
    });
});