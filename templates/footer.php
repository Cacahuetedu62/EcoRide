</main>
<div class="cookie-consent" id="cookieConsent">
    <div class="container">
        <h3>Nous utilisons des cookies</h3>
        <p>Nous utilisons des cookies pour améliorer votre expérience sur notre site. En continuant à naviguer, vous acceptez notre utilisation des cookies.</p>
        <div class="text-center">
            <button class="btn btn-primary" id="acceptCookies">J'accepte</button>
            <button class="btn btn-secondary" id="declineCookies">Refuser</button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cookieConsent = document.getElementById('cookieConsent');
    const acceptCookies = document.getElementById('acceptCookies');
    const declineCookies = document.getElementById('declineCookies');

    // Vérifie si le consentement a déjà été donné
    const consent = getCookie('cookieConsent');
    if (consent !== 'accepted') {
        cookieConsent.style.display = 'block';
    }

    acceptCookies.addEventListener('click', function() {
        setCookie('cookieConsent', 'accepted', 365); // Cookie persistant pendant 1 an
        enableNonEssentialCookies(); // Activer les cookies non essentiels
        cookieConsent.style.display = 'none';
    });

    declineCookies.addEventListener('click', function() {
        setCookie('cookieConsent', 'declined', null); // Cookie de session
        disableNonEssentialCookies(); // Désactiver les cookies non essentiels
        cookieConsent.style.display = 'none';
    });

    // Fonction pour activer ou désactiver les cookies non essentiels
    function manageCookies() {
        const consent = getCookie('cookieConsent');
        if (consent === 'accepted') {
            enableNonEssentialCookies(); // Activer les cookies non essentiels
        } else if (consent === 'declined') {
            disableNonEssentialCookies(); // Désactiver les cookies non essentiels
        }
    }

    // Appeler la fonction pour gérer les cookies au chargement de la page
    manageCookies();

    // Fonction pour définir un cookie
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    // Fonction pour obtenir un cookie
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // Fonction pour activer les cookies non essentiels
    function enableNonEssentialCookies() {
        // Ajoutez ici le code pour activer les cookies non essentiels
        console.log('Cookies non essentiels activés');
        // Exemple : activer Google Analytics
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'YOUR_GOOGLE_ANALYTICS_ID');
    }

    // Fonction pour désactiver les cookies non essentiels
    function disableNonEssentialCookies() {
        // Ajoutez ici le code pour désactiver les cookies non essentiels
        console.log('Cookies non essentiels désactivés');
        // Exemple : désactiver Google Analytics
        window['ga-disable-YOUR_GOOGLE_ANALYTICS_ID'] = true;
    }
});
</script>




<footer>
    <div class="footer-container">
        <div class="footer-content">
            <nav class="footer-nav">
                <ul class="footer-list">
                    <li class="footer-item">
                        <a href="contact.php" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="footer-icon" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 2.383-4.708 2.825L15 11.105zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741M1 11.105l4.708-2.897L1 5.383z"/>
                            </svg>
                            <span>E-mail</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="#" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="footer-icon" viewBox="0 0 16 16">
                                <path d="M12.6.75h2.454l-5.36 6.142L16 15.25h-4.937l-3.867-5.07-4.425 5.07H.316l5.733-6.57L0 .75h5.063l3.495 4.633L12.601.75Zm-.86 13.028h1.36L4.323 2.145H2.865z"/>
                            </svg>
                            <span>Twitter X</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="#" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="footer-icon" viewBox="0 0 16 16">
                                <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/>
                            </svg>
                            <span>Facebook</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="mentionsLegales.php" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="footer-icon" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                            </svg>
                            <span>Mentions légales</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="CGDV.php" class="footer-link">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="footer-icon">
                                <path d="m40-240 20-80h220l-20 80H40Zm80-160 20-80h260l-20 80H120Zm623 240 20-160 29-240 10-79-59 479ZM240-80q-33 0-56.5-23.5T160-160h583l59-479H692l-11 85q-2 17-15 26.5t-30 7.5q-17-2-26.5-14.5T602-564l9-75H452l-11 84q-2 17-15 27t-30 8q-17-2-27-15t-8-30l9-74H220q4-34 26-57.5t54-23.5h80q8-75 51.5-117.5T550-880q64 0 106.5 47.5T698-720h102q36 1 60 28t19 63l-60 480q-4 30-26.5 49.5T740-80H240Zm220-640h159q1-33-22.5-56.5T540-800q-35 0-55.5 21.5T460-720Z"/>
                            </svg>
                            <span>CGV</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="politiqueCookies.php" class="footer-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="footer-icon" ><path d="M480-320q75 0 127.5-52.5T660-500q0-75-52.5-127.5T480-680q-75 0-127.5 52.5T300-500q0 75 52.5 127.5T480-320Zm0-72q-45 0-76.5-31.5T372-500q0-45 31.5-76.5T480-608q45 0 76.5 31.5T588-500q0 45-31.5 76.5T480-392Zm0 192q-146 0-266-81.5T40-500q54-137 174-218.5T480-800q146 0 266 81.5T920-500q-54 137-174 218.5T480-200Zm0-300Zm0 220q113 0 207.5-59.5T832-500q-50-101-144.5-160.5T480-720q-113 0-207.5 59.5T128-500q50 101 144.5 160.5T480-280Z"/></svg>
                            <span>Cookies</span>
                        </a>
                    </li>
                    <li class="footer-item">
                        <a href="contact.php" class="footer-link">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" class="footer-icon"><path d="m480-80-10-120h-10q-142 0-241-99t-99-241q0-142 99-241t241-99q71 0 132.5 26.5t108 73q46.5 46.5 73 108T800-540q0 75-24.5 144t-67 128q-42.5 59-101 107T480-80Zm80-146q71-60 115.5-140.5T720-540q0-109-75.5-184.5T460-800q-109 0-184.5 75.5T200-540q0 109 75.5 184.5T460-280h100v54Zm-101-95q17 0 29-12t12-29q0-17-12-29t-29-12q-17 0-29 12t-12 29q0 17 12 29t29 12Zm-29-127h60q0-30 6-42t38-44q18-18 30-39t12-45q0-51-34.5-76.5T460-720q-44 0-74 24.5T344-636l56 22q5-17 19-33.5t41-16.5q27 0 40.5 15t13.5 33q0 17-10 30.5T480-558q-35 30-42.5 47.5T430-448Zm30-65Z"/></svg>                            
                        <span>Contact</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <p class="footer-copyright">© 2024/2025 Rogez Aurore - ECF EcoRide</p>
        </div>
    </div>
</footer>
</body>
</html>