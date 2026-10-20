// =====================================================
// CROPPER.JS - PROFILE IMAGE CROPPER
// For Job Seeker & Employer Profile Pictures
// =====================================================

// Initialize cropper when modal opens
let cropper = null;

function initCropper(inputElement, previewElement, resultInput) {
    if (inputElement.files && inputElement.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Create modal if not exists
            let modal = document.getElementById('cropperModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'cropperModal';
                modal.className = 'cropper-modal';
                modal.innerHTML = `
                    <div class="cropper-modal-content">
                        <div class="cropper-modal-header">
                            <h3>Crop Profile Picture</h3>
                            <button class="cropper-close">&times;</button>
                        </div>
                        <div class="cropper-container">
                            <img id="cropperImage" src="">
                        </div>
                        <div class="cropper-modal-footer">
                            <button class="btn-outline-light" id="cancelCrop">Cancel</button>
                            <button class="btn-primary-gradient" id="applyCrop">Apply Crop</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
                
                // Add styles
                const style = document.createElement('style');
                style.textContent = `
                    .cropper-modal {
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.9);
                        z-index: 10000;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    .cropper-modal-content {
                        background: #0f172a;
                        border-radius: 24px;
                        width: 90%;
                        max-width: 600px;
                        overflow: hidden;
                    }
                    .cropper-modal-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding: 16px 24px;
                        border-bottom: 1px solid rgba(255,255,255,0.1);
                    }
                    .cropper-container {
                        padding: 20px;
                        background: #1e293b;
                    }
                    .cropper-modal-footer {
                        display: flex;
                        justify-content: flex-end;
                        gap: 12px;
                        padding: 16px 24px;
                        border-top: 1px solid rgba(255,255,255,0.1);
                    }
                    .cropper-close {
                        background: none;
                        border: none;
                        color: white;
                        font-size: 24px;
                        cursor: pointer;
                    }
                `;
                document.head.appendChild(style);
            }
            
            const img = document.getElementById('cropperImage');
            img.src = e.target.result;
            modal.style.display = 'flex';
            
            // Initialize cropper after image loads
            img.onload = function() {
                if (cropper) cropper.destroy();
                cropper = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    background: false,
                    autoCropArea: 1,
                    strict: true
                });
            };
        };
        
        reader.readAsDataURL(inputElement.files[0]);
        
        // Setup modal buttons
        setTimeout(() => {
            const closeBtn = document.querySelector('.cropper-close');
            const cancelBtn = document.getElementById('cancelCrop');
            const applyBtn = document.getElementById('applyCrop');
            
            if (closeBtn) {
                closeBtn.onclick = () => {
                    document.getElementById('cropperModal').style.display = 'none';
                    if (cropper) cropper.destroy();
                };
            }
            if (cancelBtn) {
                cancelBtn.onclick = () => {
                    document.getElementById('cropperModal').style.display = 'none';
                    if (cropper) cropper.destroy();
                };
            }
            if (applyBtn) {
                applyBtn.onclick = () => {
                    if (cropper) {
                        const canvas = cropper.getCroppedCanvas({
                            width: 300,
                            height: 300,
                            imageSmoothingQuality: 'high'
                        });
                        canvas.toBlob(function(blob) {
                            const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            inputElement.files = dataTransfer.files;
                            if (previewElement) {
                                previewElement.src = URL.createObjectURL(blob);
                            }
                            document.getElementById('cropperModal').style.display = 'none';
                            if (cropper) cropper.destroy();
                        }, 'image/jpeg', 0.9);
                    }
                };
            }
        }, 100);
    }
}

// =====================================================
// LOGO CROPPER FOR EMPLOYERS
// =====================================================

function initLogoCropper(inputElement, previewElement, resultInput) {
    if (inputElement.files && inputElement.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            let modal = document.getElementById('cropperModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'cropperModal';
                modal.className = 'cropper-modal';
                modal.innerHTML = `
                    <div class="cropper-modal-content">
                        <div class="cropper-modal-header">
                            <h3>Crop Company Logo</h3>
                            <button class="cropper-close">&times;</button>
                        </div>
                        <div class="cropper-container">
                            <img id="cropperImage" src="">
                        </div>
                        <div class="cropper-modal-footer">
                            <button class="btn-outline-light" id="cancelCrop">Cancel</button>
                            <button class="btn-primary-gradient" id="applyCrop">Apply Crop</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }
            
            const img = document.getElementById('cropperImage');
            img.src = e.target.result;
            modal.style.display = 'flex';
            
            img.onload = function() {
                if (cropper) cropper.destroy();
                cropper = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    background: false,
                    autoCropArea: 1
                });
            };
        };
        
        reader.readAsDataURL(inputElement.files[0]);
        
        setTimeout(() => {
            const closeBtn = document.querySelector('.cropper-close');
            const cancelBtn = document.getElementById('cancelCrop');
            const applyBtn = document.getElementById('applyCrop');
            
            if (closeBtn) closeBtn.onclick = () => { document.getElementById('cropperModal').style.display = 'none'; if (cropper) cropper.destroy(); };
            if (cancelBtn) cancelBtn.onclick = () => { document.getElementById('cropperModal').style.display = 'none'; if (cropper) cropper.destroy(); };
            if (applyBtn) {
                applyBtn.onclick = () => {
                    if (cropper) {
                        const canvas = cropper.getCroppedCanvas({ width: 200, height: 200, imageSmoothingQuality: 'high' });
                        canvas.toBlob(function(blob) {
                            const file = new File([blob], 'logo.jpg', { type: 'image/jpeg' });
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            inputElement.files = dataTransfer.files;
                            if (previewElement) previewElement.src = URL.createObjectURL(blob);
                            document.getElementById('cropperModal').style.display = 'none';
                            if (cropper) cropper.destroy();
                        }, 'image/jpeg', 0.9);
                    }
                };
            }
        }, 100);
    }
}