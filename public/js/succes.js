function fadeOutAlert(AlertId){
    setTimeout(function(){
        $("#" + AlertId).fadeOut("slow");
    }, 3000)
}

fadeOutAlert("error-alert")
fadeOutAlert("success-alert")
fadeOutAlert("error2-alert")
fadeOutAlert("error3-alert")

document.addEventListener("DOMContentLoaded", function () {
    let button = document.getElementById("standardValuesButton");
    let overlay = document.getElementById("overlayDefaultValue");
    let popup = document.getElementById("popup-defaultValue");

    button.addEventListener("click", function () {
        togglePopup();
    });

    // Close the popup if the user clicks outside of it
    window.onclick = function (event) {
        if (event.target === overlayDefaultValue) {
            closePopup();
        }
    }

    function togglePopup() {
        overlay.style.display = "block";
        popup.style.display = "block";
    }

    function closePopup() {
        overlay.style.display = "none";
        popup.style.display = "none";
    }
});