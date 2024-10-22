// Function to open the popup
function openPopup() {
    document.getElementById('overlay').style.display = 'flex';
}

// Function to close the popup
function closePopup() {
    document.getElementById('overlay').style.display = 'none';
}

// Close the popup if the overlay is clicked
document.getElementById('overlay').addEventListener('click', function(event) {
    if (event.target === this) {
        closePopup();
    }
});