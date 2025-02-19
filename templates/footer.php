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
        console.log('Cookies non essentiels désactivés');
        window['ga-disable-YOUR_GOOGLE_ANALYTICS_ID'] = true;
    }
});
</script>

<footer>
    <div>
        <div class="row m-4">
            <div class="col-md-4 mb-4">
                <p class="mb-3"><strong>EcoRide</strong></p>
                <p>Votre solution de covoiturage électrique, alliant économie et écologie.</p>
                <div class="social-links">
                <a href="#" class="text-white me-3" aria-label="Visitez notre page Facebook">
                    <i class="fab fa-facebook-f" aria-hidden="true"></i>
                </a>
                <a href="#" class="text-white me-3" aria-label="Suivez-nous sur Twitter">
                    <i class="fab fa-twitter" aria-hidden="true"></i>
                </a>
                <a href="#" class="text-white me-3" aria-label="Retrouvez-nous sur Instagram">
                    <i class="fab fa-instagram" aria-hidden="true"></i>
                </a>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <p class="mb-3"><strong>Liens Rapides</strong></p>
                <ul class="list-unstyled">
                    <li><a href="contact.php" class="text-white-50">Nous contacter</a></li>
                </ul>
            </div>
            
            <div class="col-md-4 mb-4">
                <p class="mb-3"><strong>Informations Légales</strong></p>
                <ul class="list-unstyled">
                    <li><a href="mentionsLegales.php" class="text-white-50">Mentions légales</a></li>
                    <li><a href="CGDV.php" class="text-white-50">Conditions Générales</a></li>
                    <li><a href="politiqueCookies.php" class="text-white-50">Politique de Cookies</a></li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4 bg-light">
        
        <div class="row">
            <div class="col-12 text-center">
                <p>&copy; 2024-2025 EcoRide. Tous droits réservés.</p>
                <p>Développé par Rogez Aurore - ECF EcoRide</p>
            </div>
        </div>
    </div>
</footer>
</html>
</body>

