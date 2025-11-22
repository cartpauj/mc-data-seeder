/**
 * MemberCore Data Seeder Admin JavaScript
 */
(function($) {
    'use strict';

    const MCDS = {
        // Track which seeders should stop processing
        stoppedSeeders: {},

        // Store global check interval
        globalCheckInterval: null,

        init: function() {
            this.bindEvents();
            this.checkRunningProcesses();
            this.startGlobalStatusCheck();
        },

        bindEvents: function() {
            // Start seeder
            $(document).on('submit', '.mcds-seeder-form', this.handleStartSeeder.bind(this));

            // Stop seeder
            $(document).on('click', '.mcds-stop', this.handleStopSeeder.bind(this));

            // Reset seeder
            $(document).on('click', '.mcds-reset', this.handleResetSeeder.bind(this));

            // Reset all
            $(document).on('click', '.mcds-reset-all', this.handleResetAll.bind(this));
        },

        checkRunningProcesses: function() {
            $('.mcds-seeder').each(function() {
                const $seeder = $(this);
                const status = $seeder.data('status');

                if (status === 'running') {
                    const seederKey = $seeder.data('seeder-key');
                    MCDS.resumeProcessing(seederKey);
                }
            });
        },

        handleStartSeeder: function(e) {
            e.preventDefault();

            const $form = $(e.currentTarget);
            const $seeder = $form.closest('.mcds-seeder');
            const seederKey = $seeder.data('seeder-key');

            // Disable form
            $form.find('input, button').prop('disabled', true);

            // Collect settings
            const settings = {};
            $form.find('input, select').each(function() {
                const $field = $(this);
                settings[$field.attr('name')] = $field.val();
            });

            // Show progress container
            const $progress = $seeder.find('.mcds-progress-container');
            $progress.show();
            this.updateProgress($seeder, {
                status: 'starting',
                message: mcdsAdmin.strings.starting,
                percentage: 0,
                processed: 0,
                total: parseInt(settings.count) || 1
            });

            // Start seeder
            $.ajax({
                url: mcdsAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'mcds_start_seeder',
                    nonce: mcdsAdmin.nonce,
                    seeder_key: seederKey,
                    settings: JSON.stringify(settings)
                },
                success: (response) => {
                    if (response.success) {
                        this.startProcessing(seederKey);
                    } else {
                        this.handleError($seeder, response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.handleError($seeder, mcdsAdmin.strings.error + ': ' + error);
                }
            });
        },

        startProcessing: function(seederKey) {
            const $seeder = $('.mcds-seeder[data-seeder-key="' + seederKey + '"]');
            // Clear stopped flag when starting
            delete this.stoppedSeeders[seederKey];
            this.processBatch(seederKey);
        },

        resumeProcessing: function(seederKey) {
            const $seeder = $('.mcds-seeder[data-seeder-key="' + seederKey + '"]');
            // Clear stopped flag when resuming
            delete this.stoppedSeeders[seederKey];
            this.updateProgress($seeder, {
                status: 'running',
                message: mcdsAdmin.strings.processing
            });
            this.processBatch(seederKey);
        },

        processBatch: function(seederKey) {
            // Check if this seeder has been stopped
            if (this.stoppedSeeders[seederKey]) {
                console.log('Seeder ' + seederKey + ' stopped, not processing batch');
                return;
            }

            $.ajax({
                url: mcdsAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'mcds_process_batch',
                    nonce: mcdsAdmin.nonce,
                    seeder_key: seederKey
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;
                        const $seeder = $('.mcds-seeder[data-seeder-key="' + seederKey + '"]');

                        // Check if stopped BEFORE scheduling next batch
                        if (this.stoppedSeeders[seederKey]) {
                            console.log('Seeder ' + seederKey + ' stopped after batch completed');
                            // Update progress with final count
                            this.updateProgress($seeder, {
                                status: 'cancelled',
                                message: 'Cancelled',
                                percentage: data.percentage,
                                processed: data.processed,
                                total: data.total
                            });
                            // Re-enable form
                            const $form = $seeder.find('.mcds-seeder-form');
                            $form.find('input, button').prop('disabled', false);
                            // Clear the flag
                            delete this.stoppedSeeders[seederKey];
                            return;
                        }

                        // Update progress normally
                        this.updateProgress($seeder, {
                            status: data.completed ? (data.cancelled ? 'cancelled' : 'completed') : 'running',
                            message: data.message,
                            percentage: data.percentage,
                            processed: data.processed,
                            total: data.total
                        });

                        if (!data.completed) {
                            // Continue processing (will check stoppedSeeders flag on next call)
                            setTimeout(() => {
                                this.processBatch(seederKey);
                            }, 100);
                        } else {
                            // Completed or cancelled - clear stopped flag
                            delete this.stoppedSeeders[seederKey];
                            this.handleCompletion($seeder, data.cancelled);
                        }
                    } else {
                        const $seeder = $('.mcds-seeder[data-seeder-key="' + seederKey + '"]');
                        this.handleError($seeder, response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    const $seeder = $('.mcds-seeder[data-seeder-key="' + seederKey + '"]');
                    this.handleError($seeder, mcdsAdmin.strings.error + ': ' + error);
                }
            });
        },

        updateProgress: function($seeder, data) {
            const $progress = $seeder.find('.mcds-progress-container');
            const $text = $progress.find('.mcds-progress-text');
            const $percentage = $progress.find('.mcds-progress-percentage');
            const $fill = $progress.find('.mcds-progress-fill');
            const $status = $progress.find('.mcds-progress-status');
            const $stopBtn = $progress.find('.mcds-stop');

            if (typeof data.processed !== 'undefined' && typeof data.total !== 'undefined') {
                $text.text(data.processed + ' of ' + data.total);
            }

            if (typeof data.percentage !== 'undefined') {
                $percentage.text(data.percentage + '%');
                $fill.css('width', data.percentage + '%');
            }

            if (data.status) {
                $status.removeClass('running stopping completed error cancelled').addClass(data.status);
                $seeder.attr('data-status', data.status);

                // Show/hide stop button based on status
                if (data.status === 'running') {
                    $stopBtn.show();
                } else {
                    $stopBtn.hide();
                }
            }

            if (data.message) {
                $status.text(data.message);
            }
        },

        handleCompletion: function($seeder, cancelled) {
            const $form = $seeder.find('.mcds-seeder-form');
            $form.find('input, button').prop('disabled', false);

            // Hide stop button
            $seeder.find('.mcds-stop').hide();
        },

        handleError: function($seeder, message) {
            const $form = $seeder.find('.mcds-seeder-form');
            $form.find('input, button').prop('disabled', false);

            this.updateProgress($seeder, {
                status: 'error',
                message: message
            });
        },

        handleStopSeeder: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to stop this seeder?')) {
                return;
            }

            const $button = $(e.currentTarget);
            const $seeder = $button.closest('.mcds-seeder');
            const seederKey = $seeder.data('seeder-key');

            // IMMEDIATELY stop the processing loop
            this.stoppedSeeders[seederKey] = true;

            // Update UI to show "Stopping..." status
            this.updateProgress($seeder, {
                status: 'stopping',
                message: 'Stopping...'
            });

            $button.prop('disabled', true).hide();

            $.ajax({
                url: mcdsAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'mcds_cancel_seeder',
                    nonce: mcdsAdmin.nonce,
                    seeder_key: seederKey
                },
                success: (response) => {
                    if (!response.success) {
                        // If cancel failed, clear the stopped flag
                        delete this.stoppedSeeders[seederKey];
                        alert(response.data.message);
                        this.updateProgress($seeder, {
                            status: 'running',
                            message: 'Processing...'
                        });
                        $button.prop('disabled', false).show();
                    }
                    // Don't update UI here on success - let the batch completion handle it
                },
                error: (xhr, status, error) => {
                    // If cancel failed, clear the stopped flag
                    delete this.stoppedSeeders[seederKey];
                    alert(mcdsAdmin.strings.error + ': ' + error);
                    this.updateProgress($seeder, {
                        status: 'running',
                        message: 'Processing...'
                    });
                    $button.prop('disabled', false).show();
                }
            });
        },

        handleResetSeeder: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $seeder = $button.closest('.mcds-seeder');
            const seederKey = $seeder.data('seeder-key');

            // First check if there are dependent seeders that would block this reset
            $.ajax({
                url: mcdsAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'mcds_check_reset_dependencies',
                    nonce: mcdsAdmin.nonce,
                    seeder_key: seederKey
                },
                success: function(response) {
                    if (response.success) {
                        // No dependencies blocking, proceed with confirmation
                        if (!confirm(mcdsAdmin.strings.confirm_reset)) {
                            return;
                        }

                        // Start the reset
                        $button.prop('disabled', true).text(mcdsAdmin.strings.resetting);

                        const $progress = $seeder.find('.mcds-progress-container');
                        $progress.show();
                        MCDS.updateProgress($seeder, {
                            status: 'running',
                            message: 'Resetting...',
                            percentage: 0,
                            processed: 0,
                            total: 0
                        });

                        // Start batched reset
                        $.ajax({
                            url: mcdsAdmin.ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'mcds_start_reset',
                                nonce: mcdsAdmin.nonce,
                                seeder_key: seederKey
                            },
                            success: function(response) {
                                if (response.success) {
                                    if (response.data.completed) {
                                        MCDS.handleResetCompletion($seeder, $button);
                                    } else {
                                        MCDS.processResetBatch(seederKey, $button);
                                    }
                                } else {
                                    alert(response.data.message);
                                    $button.prop('disabled', false).text('Reset');
                                    $progress.hide();
                                }
                            },
                            error: function(xhr, status, error) {
                                alert(mcdsAdmin.strings.error + ': ' + error);
                                $button.prop('disabled', false).text('Reset');
                                $progress.hide();
                            }
                        });
                    } else {
                        // Dependencies blocking - show error message
                        alert(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    // If check fails, show basic confirmation and proceed
                    if (!confirm(mcdsAdmin.strings.confirm_reset)) {
                        return;
                    }

                    $button.prop('disabled', true).text(mcdsAdmin.strings.resetting);

                    const $progress = $seeder.find('.mcds-progress-container');
                    $progress.show();
                    MCDS.updateProgress($seeder, {
                        status: 'running',
                        message: 'Resetting...',
                        percentage: 0,
                        processed: 0,
                        total: 0
                    });

                    $.ajax({
                        url: mcdsAdmin.ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'mcds_start_reset',
                            nonce: mcdsAdmin.nonce,
                            seeder_key: seederKey
                        },
                        success: function(response) {
                            if (response.success) {
                                if (response.data.completed) {
                                    MCDS.handleResetCompletion($seeder, $button);
                                } else {
                                    MCDS.processResetBatch(seederKey, $button);
                                }
                            } else {
                                alert(response.data.message);
                                $button.prop('disabled', false).text('Reset');
                                $progress.hide();
                            }
                        },
                        error: function(xhr, status, error) {
                            alert(mcdsAdmin.strings.error + ': ' + error);
                            $button.prop('disabled', false).text('Reset');
                            $progress.hide();
                        }
                    });
                }
            });
        },

        processResetBatch: function(seederKey, $button) {
            const $seeder = $('.mcds-seeder[data-seeder-key="' + seederKey + '"]');

            $.ajax({
                url: mcdsAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'mcds_process_reset_batch',
                    nonce: mcdsAdmin.nonce,
                    seeder_key: seederKey
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;

                        this.updateProgress($seeder, {
                            status: data.completed ? 'completed' : 'running',
                            message: data.message,
                            percentage: data.percentage,
                            processed: data.processed,
                            total: data.total
                        });

                        if (!data.completed) {
                            // Continue processing
                            setTimeout(() => {
                                this.processResetBatch(seederKey, $button);
                            }, 100);
                        } else {
                            this.handleResetCompletion($seeder, $button);
                        }
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Reset');
                    }
                },
                error: (xhr, status, error) => {
                    alert(mcdsAdmin.strings.error + ': ' + error);
                    $button.prop('disabled', false).text('Reset');
                }
            });
        },

        handleResetCompletion: function($seeder, $button) {
            // Reset UI
            $seeder.attr('data-status', 'idle');
            $seeder.find('.mcds-progress-container').hide();
            $seeder.find('input, button').prop('disabled', false);
            $button.text('Reset');

            // Reset progress display
            this.updateProgress($seeder, {
                percentage: 0,
                processed: 0,
                total: 0,
                status: 'idle'
            });
        },

        handleResetAll: function(e) {
            e.preventDefault();

            if (!confirm(mcdsAdmin.strings.confirm_reset_all)) {
                return;
            }

            const $button = $(e.currentTarget);
            $button.prop('disabled', true).text(mcdsAdmin.strings.resetting);

            // Create a status message div if it doesn't exist
            let $statusDiv = $('#mcds-reset-all-status');
            if ($statusDiv.length === 0) {
                $statusDiv = $('<div id="mcds-reset-all-status" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;"></div>');
                $button.after($statusDiv);
            }
            $statusDiv.show().text('Preparing to reset all seeders...');

            // Start batched reset all
            $.ajax({
                url: mcdsAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'mcds_start_reset_all',
                    nonce: mcdsAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.completed) {
                            // Nothing to reset
                            $statusDiv.text(response.data.message);
                            $button.prop('disabled', false).text('Reset All Seeders');
                            this.handleResetAllCompletion();
                        } else {
                            // Start processing
                            this.processResetAllBatch($button, $statusDiv);
                        }
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Reset All Seeders');
                        $statusDiv.hide();
                    }
                },
                error: (xhr, status, error) => {
                    alert(mcdsAdmin.strings.error + ': ' + error);
                    $button.prop('disabled', false).text('Reset All Seeders');
                    $statusDiv.hide();
                }
            });
        },

        processResetAllBatch: function($button, $statusDiv) {
            $.ajax({
                url: mcdsAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'mcds_process_reset_all_batch',
                    nonce: mcdsAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const data = response.data;

                        if (data.completed) {
                            // All done
                            $statusDiv.text(data.message).css('border-left-color', '#00a32a');
                            $button.prop('disabled', false).text('Reset All Seeders');
                            this.handleResetAllCompletion();
                            setTimeout(() => {
                                $statusDiv.fadeOut();
                            }, 3000);
                        } else {
                            // Update status and continue
                            $statusDiv.text(data.message);
                            setTimeout(() => {
                                this.processResetAllBatch($button, $statusDiv);
                            }, 50);
                        }
                    } else {
                        alert(response.data.message);
                        $button.prop('disabled', false).text('Reset All Seeders');
                        $statusDiv.hide();
                    }
                },
                error: (xhr, status, error) => {
                    alert(mcdsAdmin.strings.error + ': ' + error);
                    $button.prop('disabled', false).text('Reset All Seeders');
                    $statusDiv.hide();
                }
            });
        },

        handleResetAllCompletion: function() {
            // Reset all seeder UIs
            $('.mcds-seeder').each(function() {
                const $seeder = $(this);
                $seeder.attr('data-status', 'idle');
                $seeder.find('.mcds-progress-container').hide();
                $seeder.find('input, button').prop('disabled', false);

                MCDS.updateProgress($seeder, {
                    percentage: 0,
                    processed: 0,
                    total: 0,
                    status: 'idle'
                });
            });
        },

        startGlobalStatusCheck: function() {
            // Check global status every 2 seconds
            this.globalCheckInterval = setInterval(() => {
                this.checkGlobalStatus();
            }, 2000);

            // Also check immediately
            this.checkGlobalStatus();
        },

        checkGlobalStatus: function() {
            $.ajax({
                url: mcdsAdmin.ajaxurl,
                method: 'POST',
                data: {
                    action: 'mcds_get_global_status',
                    nonce: mcdsAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateGlobalUI(response.data);
                    }
                }
            });
        },

        updateGlobalUI: function(data) {
            const isRunning = data.is_running;
            const runningSeederKey = data.seeder_key;

            $('.mcds-seeder').each(function() {
                const $seeder = $(this);
                const seederKey = $seeder.data('seeder-key');
                const currentStatus = $seeder.attr('data-status');
                const $form = $seeder.find('.mcds-seeder-form');
                const $startButton = $form.find('button[type="submit"]');
                const $lockMessage = $seeder.find('.mcds-lock-message');

                // If this seeder is the one running, don't disable it
                if (seederKey === runningSeederKey) {
                    return;
                }

                // If another seeder is running and this one is idle/completed/error
                if (isRunning && ['idle', 'completed', 'error', 'cancelled'].includes(currentStatus)) {
                    // Disable the form
                    $startButton.prop('disabled', true);

                    // Show lock message
                    if ($lockMessage.length === 0) {
                        $form.append(
                            '<div class="mcds-lock-message" style="margin-top: 10px; padding: 8px; background: #fef7e7; border-left: 4px solid #f0b849; color: #333;">' +
                            '<strong>Locked:</strong> Another seeder (' + data.seeder_name + ') is currently running. ' +
                            'Please wait for it to complete.' +
                            '</div>'
                        );
                    }
                } else if (!isRunning && ['idle', 'completed', 'error', 'cancelled'].includes(currentStatus)) {
                    // No seeder running, enable this form
                    $startButton.prop('disabled', false);

                    // Remove lock message
                    $seeder.find('.mcds-lock-message').remove();
                }
            });
        }
    };

    // Initialize on document ready
    $(function() {
        MCDS.init();
    });

})(jQuery);
