let currentZoom = 1;
const zoomFactor = 0.2;
let currentImageIndex = 0;
let imagePaths = [];

function initReceipt(images) {
    imagePaths = images;
}

function showImage() {
    const modal = document.getElementById("imageModal");
    updateModalImage();
    modal.style.display = "flex";
    
    // Update navigation buttons visibility
    updateNavigationButtons();
    
    // Reset zoom when opening
    resetZoom();
}

function updateModalImage() {
    const modalImage = document.getElementById("modalImage");
    const counter = document.getElementById("imageCounter");
    
    if (imagePaths.length > 0) {
        modalImage.src = "../../" + imagePaths[currentImageIndex];
        counter.textContent = `${currentImageIndex + 1}/${imagePaths.length}`;
    }
}

function updateNavigationButtons() {
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    
    // Hide navigation buttons if there's only one image
    if (imagePaths.length <= 1) {
        prevBtn.style.display = "none";
        nextBtn.style.display = "none";
        return;
    }
    
    prevBtn.style.display = "flex";
    nextBtn.style.display = "flex";
}

function prevImage() {
    if (currentImageIndex > 0) {
        currentImageIndex--;
        updateModalImage();
        resetZoom();
    }
}

function nextImage() {
    if (currentImageIndex < imagePaths.length - 1) {
        currentImageIndex++;
        updateModalImage();
        resetZoom();
    }
}

function closeModal() {
    document.getElementById("imageModal").style.display = "none";
    currentImageIndex = 0; // Reset to first image when closing
}

function zoomIn() {
    currentZoom += zoomFactor;
    applyZoom();
}

function zoomOut() {
    currentZoom = Math.max(0.5, currentZoom - zoomFactor);
    applyZoom();
}

function resetZoom() {
    currentZoom = 1;
    applyZoom();
}

function applyZoom() {
    const modalImage = document.getElementById("modalImage");
    modalImage.style.transform = `scale(${currentZoom})`;
    modalImage.style.transition = "transform 0.3s ease-in-out";
}

// Close modal when clicking outside the image container
window.onclick = function(event) {
    const modal = document.getElementById("imageModal");
    if (event.target === modal) {
        closeModal();
    }
}

// Add keyboard navigation
document.addEventListener("keydown", function(event) {
    if (document.getElementById("imageModal").style.display === "flex") {
        if (event.key === "ArrowLeft") {
            prevImage();
        } else if (event.key === "ArrowRight") {
            nextImage();
        } else if (event.key === "Escape") {
            closeModal();
        } else if (event.key === "+" || event.key === "=") {
            zoomIn();
        } else if (event.key === "-") {
            zoomOut();
        } else if (event.key === "0") {
            resetZoom();
        }
    }
}); 