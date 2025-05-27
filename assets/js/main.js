/**
 * Main JavaScript file for SMA Forms System
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Form validation
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });

    // Auto-dismiss alerts
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert.alert-success, .alert.alert-info');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Toggle password visibility
    var togglePassword = document.querySelector('#togglePassword');
    var passwordInput = document.querySelector('#password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('bi-eye');
            this.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }

    // Initialize datepicker if available
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            allowInput: true
        });
    }

    // Add active class to current nav item
    var currentLocation = window.location.pathname;
    var navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(function(link) {
        if (link.getAttribute('href') === currentLocation) {
            link.classList.add('active');
        }
    });

    // File upload preview handling
    var fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            var filePreviewContainer = document.querySelector('#' + this.getAttribute('data-preview'));
            
            if (filePreviewContainer) {
                filePreviewContainer.innerHTML = '';
                
                if (this.files.length > 0) {
                    for (var i = 0; i < this.files.length; i++) {
                        var file = this.files[i];
                        var preview = createFilePreview(file, i);
                        filePreviewContainer.appendChild(preview);
                    }
                } else {
                    filePreviewContainer.innerHTML = '<p class="text-muted">No files selected</p>';
                }
            }
        });
    });

    // Create file preview element
    function createFilePreview(file, index) {
        var card = document.createElement('div');
        card.className = 'card mb-2';
        
        var cardBody = document.createElement('div');
        cardBody.className = 'card-body p-2';
        
        var row = document.createElement('div');
        row.className = 'row align-items-center';
        
        // File icon and type
        var iconCol = document.createElement('div');
        iconCol.className = 'col-auto';
        
        var icon = document.createElement('i');
        var extension = file.name.split('.').pop().toLowerCase();
        
        switch (extension) {
            case 'pdf':
                icon.className = 'bi bi-file-earmark-pdf display-6 text-danger';
                break;
            case 'doc':
            case 'docx':
                icon.className = 'bi bi-file-earmark-word display-6 text-primary';
                break;
            case 'jpg':
            case 'jpeg':
            case 'png':
                icon.className = 'bi bi-file-earmark-image display-6 text-success';
                break;
            default:
                icon.className = 'bi bi-file-earmark display-6';
        }
        
        iconCol.appendChild(icon);
        
        // File details
        var detailsCol = document.createElement('div');
        detailsCol.className = 'col';
        
        var fileName = document.createElement('h6');
        fileName.className = 'mb-0';
        fileName.textContent = file.name;
        
        var fileSize = document.createElement('small');
        fileSize.className = 'text-muted';
        fileSize.textContent = formatFileSize(file.size);
        
        detailsCol.appendChild(fileName);
        detailsCol.appendChild(fileSize);
        
        // Remove button
        var removeCol = document.createElement('div');
        removeCol.className = 'col-auto';
        
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-outline-danger';
        removeBtn.innerHTML = '<i class="bi bi-trash"></i>';
        removeBtn.addEventListener('click', function() {
            card.remove();
        });
        
        removeCol.appendChild(removeBtn);
        
        // Assemble the card
        row.appendChild(iconCol);
        row.appendChild(detailsCol);
        row.appendChild(removeCol);
        
        cardBody.appendChild(row);
        card.appendChild(cardBody);
        
        return card;
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // File drop zone functionality
    var fileDropZones = document.querySelectorAll('.file-drop-zone');
    
    fileDropZones.forEach(function(dropZone) {
        var fileInput = dropZone.querySelector('input[type="file"]');
        
        if (fileInput) {
            // Click to browse
            dropZone.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                dropZone.classList.add('highlight');
            }
            
            function unhighlight() {
                dropZone.classList.remove('highlight');
            }
            
            dropZone.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                fileInput.files = files;
                
                // Trigger change event
                var event = new Event('change');
                fileInput.dispatchEvent(event);
            }
        }
    });
});