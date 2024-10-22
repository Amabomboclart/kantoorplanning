document.addEventListener("DOMContentLoaded", function(event) {

    const showNavbar = (toggleId, navId, bodyId, headerId, locationTextId, colleaguesTextId, logoutTextId) => {
        const toggle = document.getElementById(toggleId),
            nav = document.getElementById(navId),
            bodypd = document.getElementById(bodyId),
            headerpd = document.getElementById(headerId),
            locationText = document.getElementById(locationTextId),
            colleaguesText = document.getElementById(colleaguesTextId),
            logoutText = document.getElementById(logoutTextId);

        if (toggle && nav && bodypd && headerpd && locationText && colleaguesText && logoutText) {
            toggle.addEventListener('click', () => {
                nav.classList.toggle('show');
                toggle.classList.toggle('bx-x');
                bodypd.classList.toggle('body-pd');
                headerpd.classList.toggle('body-pd');
                locationText.classList.toggle('showText');
                colleaguesText.classList.toggle('showText');
                logoutText.classList.toggle('showText');

                // Delay setting opacity to 1 to ensure the transition works
                setTimeout(() => {
                    if (nav.classList.contains('show')) {
                        locationText.style.opacity = 1;
                        colleaguesText.style.opacity = 1;
                        logoutText.style.opacity = 1;
                    } else {
                        locationText.style.opacity = 0;
                        colleaguesText.style.opacity = 0;
                        logoutText.style.opacity = 0;
                    }
                }, 10);
            });
        }
    }

    showNavbar('header-toggle', 'nav-bar', 'body-pd', 'header', 'locationText', 'colleaguesText', 'logoutText');

    const linkColor = document.querySelectorAll('.nav_link');

    function colorLink() {
        if (linkColor) {
            linkColor.forEach(l => l.classList.remove('active'))
            this.classList.add('active');
        }
    }
    linkColor.forEach(l => l.addEventListener('click', colorLink));
});
