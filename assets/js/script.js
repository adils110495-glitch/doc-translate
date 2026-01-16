// Toast notification configuration
toastr.options = {
    "closeButton": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "timeOut": "5000",
    "extendedTimeOut": "1000"
};

// Load translated files on page load
$(document).ready(function() {
    loadTranslatedFiles();
    loadSourceFiles();

    // Handle form submission
    $('#translation-form').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const fileSource = $('input[name="file_source"]:checked').val();
        const fileName = fileSource === 'upload' 
            ? $('#docx-file')[0].files[0]?.name 
            : $('#existing-file option:selected').text();

        // Add file source indicator
        formData.append('use_existing', fileSource === 'existing');

        // Validate file
        if (fileSource === 'upload') {
            const file = $('#docx-file')[0].files[0];
            if (!file) {
                toastr.error('Please select a file to upload');
                return;
            }

            // Check file extension
            const fileExt = file.name.split('.').pop().toLowerCase();
            if (fileExt !== 'docx') {
                toastr.error('Only DOCX files are allowed');
                return;
            }

            // Check file size (max 50MB)
            if (file.size > 50 * 1024 * 1024) {
                toastr.error('File size must be less than 50MB');
                return;
            }
        } else {
            const existingFile = $('#existing-file').val();
            if (!existingFile) {
                toastr.error('Please select an existing file');
                return;
            }
        }

        // Show upload started notification
        toastr.info('Translation started for: ' + fileName);

        // Disable submit button
        const submitBtn = $(this).find('button[type="submit"]');
        const originalHtml = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        // Submit form via AJAX
        $.ajax({
            url: 'api/translate.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;

                    if (data.success) {
                        toastr.success('Translation completed successfully: ' + fileName);

                        // Reset form
                        $('#translation-form')[0].reset();
                        $('input[name="file_source"][value="upload"]').prop('checked', true);
                        toggleFileSource();

                        // Reload files list
                        setTimeout(function() {
                            loadTranslatedFiles();
                            loadSourceFiles();
                        }, 1000);
                    } else {
                        toastr.error(data.message || 'Translation failed');
                    }
                } catch (e) {
                    toastr.error('Error processing response');
                }
            },
            error: function(xhr, status, error) {
                toastr.error('Translation failed: ' + error);
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});

// Toggle between upload and existing file
function toggleFileSource() {
    const fileSource = $('input[name="file_source"]:checked').val();
    
    if (fileSource === 'upload') {
        $('#upload-section').show();
        $('#existing-section').hide();
        $('#docx-file').prop('required', true);
        $('#existing-file').prop('required', false);
    } else {
        $('#upload-section').hide();
        $('#existing-section').show();
        $('#docx-file').prop('required', false);
        $('#existing-file').prop('required', true);
    }
}

// Load source files for dropdown
function loadSourceFiles() {
    $.ajax({
        url: 'api/get_source_files.php',
        type: 'GET',
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.success) {
                    populateSourceFilesDropdown(data.files);
                }
            } catch (e) {
                console.error('Error loading source files:', e);
            }
        }
    });
}

// Populate source files dropdown
function populateSourceFilesDropdown(files) {
    const select = $('#existing-file');
    select.empty();
    select.append('<option value="">Select a file...</option>');

    if (!files || Object.keys(files).length === 0) {
        select.append('<option value="" disabled>No source files available</option>');
        return;
    }

    for (const project in files) {
        const projectGroup = $('<optgroup label="' + escapeHtml(project) + '">');
        
        for (const topic in files[project]) {
            files[project][topic].forEach(function(file) {
                projectGroup.append(
                    '<option value="' + escapeHtml(file.path) + '">' +
                    escapeHtml(topic) + ' / ' + escapeHtml(file.name) +
                    '</option>'
                );
            });
        }
        
        select.append(projectGroup);
    }
}

// Load translated files
function loadTranslatedFiles() {
    $.ajax({
        url: 'api/get_files.php',
        type: 'GET',
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.success) {
                    displayFiles(data.files);
                } else {
                    $('#files-container').html('<p class="loading">' + (data.message || 'No files found') + '</p>');
                }
            } catch (e) {
                $('#files-container').html('<p class="loading">Error loading files</p>');
            }
        },
        error: function() {
            $('#files-container').html('<p class="loading">Error loading files</p>');
        }
    });
}

// Display files grouped by project and topic
function displayFiles(files) {
    if (!files || Object.keys(files).length === 0) {
        $('#files-container').html('<p class="loading">No translated files yet</p>');
        return;
    }

    let html = '';

    // Group files by project
    for (const project in files) {
        html += '<div class="project-group">';
        html += '<div class="project-title">' + escapeHtml(project) + '</div>';

        // Group by topic within project
        for (const topic in files[project]) {
            html += '<div class="topic-group">';
            html += '<div class="topic-title">' + escapeHtml(topic) + '</div>';

            // Display files
            files[project][topic].forEach(function(file) {
                html += '<div class="file-item">';
                html += '<input type="checkbox" class="file-checkbox" data-path="' + escapeHtml(file.path) + '">';
                html += '<div class="file-info">';
                html += '<div class="file-name">' + escapeHtml(file.name) + '</div>';
                html += '<div class="file-meta">Language: ' + escapeHtml(file.language) + ' | ' + escapeHtml(file.date) + '</div>';
                html += '</div>';
                html += '<div class="file-actions">';
                html += '<button class="icon-btn btn-download-file" onclick="downloadFile(\'' + escapeHtml(file.path) + '\')" title="Download">';
                html += '<i class="fas fa-download"></i>';
                html += '</button>';
                html += '<button class="icon-btn btn-delete-file" onclick="deleteSingleFile(\'' + escapeHtml(file.path) + '\')" title="Delete">';
                html += '<i class="fas fa-trash"></i>';
                html += '</button>';
                html += '</div>';
                html += '</div>';
            });

            html += '</div>';
        }

        html += '</div>';
    }

    $('#files-container').html(html);
}

// Download single file
function downloadFile(path) {
    window.open('api/download.php?file=' + encodeURIComponent(path), '_blank');
}

// Delete single file
function deleteSingleFile(path) {
    if (!confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
        return;
    }

    $.ajax({
        url: 'api/bulk_delete.php',
        type: 'POST',
        data: JSON.stringify({ files: [path] }),
        contentType: 'application/json',
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.success) {
                    toastr.success('File deleted successfully');
                    loadTranslatedFiles();
                } else {
                    toastr.error(data.message || 'Failed to delete file');
                }
            } catch (e) {
                toastr.error('Error processing response');
            }
        },
        error: function() {
            toastr.error('Failed to delete file');
        }
    });
}

// Bulk download selected files
function bulkDownload() {
    const selected = getSelectedFiles();

    if (selected.length === 0) {
        toastr.warning('Please select files to download');
        return;
    }

    toastr.info('Preparing download for ' + selected.length + ' file(s)...');

    if (selected.length === 1) {
        // Single file download
        window.open('api/download.php?file=' + encodeURIComponent(selected[0]), '_blank');
    } else {
        // Bulk download as ZIP
        $.ajax({
            url: 'api/bulk_download.php',
            type: 'POST',
            data: JSON.stringify({ files: selected }),
            contentType: 'application/json',
            xhrFields: {
                responseType: 'blob'
            },
            success: function(blob, status, xhr) {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'translated_files_' + Date.now() + '.zip';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                toastr.success('Download started successfully');
            },
            error: function() {
                toastr.error('Failed to download files');
            }
        });
    }
}

// Bulk delete selected files
function bulkDelete() {
    const selected = getSelectedFiles();

    if (selected.length === 0) {
        toastr.warning('Please select files to delete');
        return;
    }

    if (!confirm('Are you sure you want to delete ' + selected.length + ' file(s)? This action cannot be undone.')) {
        return;
    }

    $.ajax({
        url: 'api/bulk_delete.php',
        type: 'POST',
        data: JSON.stringify({ files: selected }),
        contentType: 'application/json',
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.success) {
                    toastr.success(data.message || 'Files deleted successfully');
                    loadTranslatedFiles();
                } else {
                    toastr.error(data.message || 'Failed to delete files');
                }
            } catch (e) {
                toastr.error('Error processing response');
            }
        },
        error: function() {
            toastr.error('Failed to delete files');
        }
    });
}

// Toggle log viewer
function toggleLogViewer() {
    const logViewer = $('#log-viewer');
    if (logViewer.is(':visible')) {
        logViewer.fadeOut(300);
    } else {
        logViewer.fadeIn(300);
        refreshLogs();
    }
}

// Refresh logs
function refreshLogs() {
    const lines = $('#log-lines').val() || 100;
    
    $('#log-content').html('<p class="loading">Loading logs...</p>');
    
    $.ajax({
        url: 'api/get_logs.php?lines=' + lines,
        type: 'GET',
        success: function(response) {
            try {
                const data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.success) {
                    displayLogs(data.logs, data.total_lines);
                } else {
                    $('#log-content').html('<p class="error">Error: ' + escapeHtml(data.message) + '</p>');
                }
            } catch (e) {
                $('#log-content').html('<p class="error">Error parsing logs</p>');
            }
        },
        error: function() {
            $('#log-content').html('<p class="error">Error loading logs</p>');
        }
    });
}

// Display logs
function displayLogs(logs, totalLines) {
    if (!logs || logs.length === 0) {
        $('#log-content').html('<p class="empty">No logs available</p>');
        return;
    }

    let html = '<div class="log-info">Showing ' + logs.length + ' of ' + totalLines + ' total lines</div>';
    html += '<div class="log-entries">';

    logs.forEach(function(log) {
        const logClass = log.includes('SUCCESS') ? 'log-success' : 
                        log.includes('FAILED') ? 'log-error' : 
                        log.includes('DELETED') ? 'log-warning' : '';
        
        html += '<div class="log-entry ' + logClass + '">' + escapeHtml(log) + '</div>';
    });

    html += '</div>';

    $('#log-content').html(html);
}

// Get selected file paths
function getSelectedFiles() {
    const selected = [];
    $('.file-checkbox:checked').each(function() {
        selected.push($(this).data('path'));
    });
    return selected;
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
}
