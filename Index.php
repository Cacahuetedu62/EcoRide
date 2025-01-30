<?php
require_once('templates/header.php');
?>

    <div class="row p-3 justify-content-center align-items-md-stretch containHistoire">
        <div class="col-md-8 d-flex justify-content-center" style="flex: 7;">
            <div class="h-100 p-1 rounded-3 image-backgroundHistoire">
                <p class="descriptionHistoire">EcoRide est une entreprise de covoiturage innovante qui met l'accent sur l'utilisation de véhicules électriques, alliant ainsi mobilité durable et respect de l'environnement. En choisissant EcoRide, vous contribuez à réduire les émissions de CO2 tout en profitant d'un transport économique et pratique. Nous offrons une alternative écologique aux trajets quotidiens, en permettant à nos utilisateurs de partager des trajets en toute sécurité et à moindre coût. Grâce à notre plateforme facile d'accès, chacun peut trouver un trajet éco-responsable adapté à ses besoins. EcoRide, c'est la solution idéale pour ceux qui veulent voyager intelligemment, économiquement et en respectant la planète. Rejoignez notre communauté et participez à la révolution verte du transport !</p>
            </div>
        </div>

        <div class="col-md-4 p-1 d-flex justify-content-center" style="flex: 3;">
            <div class="col-md-7 col-lg-8 contenair">
                <h4 class="mb-3 text-center">Chercher un trajet</h4>
                <form method="POST" action="covoiturages.php">
                    <div class="row g-3 cardTrajet">
                        <!-- Ville de départ -->
<!-- Ville de départ -->
<div class="col-12">
    <label for="ville_depart" class="form-label">Ville de départ</label>
    <input type="text" class="form-control" id="ville_depart" name="ville_depart" 
           placeholder="Entrez une ville de départ" required 
           value="<?php echo isset($_POST['ville_depart']) ? htmlspecialchars($_POST['ville_depart'], ENT_QUOTES, 'UTF-8') : ''; ?>">
</div>

<!-- Ville d'arrivée -->
<div class="col-12">
    <label for="ville_arrive" class="form-label">Ville d'arrivée</label>
    <input type="text" class="form-control" id="ville_arrive" name="ville_arrive" 
           placeholder="Entrez une ville d'arrivée" required 
           value="<?php echo isset($_POST['ville_arrive']) ? htmlspecialchars($_POST['ville_arrive'], ENT_QUOTES, 'UTF-8') : ''; ?>">
</div>

                        <!-- Date de départ -->
                        <div class="col-12">
                            <label for="date_depart" class="form-label">Date de départ</label>
                            <input type="date" class="form-control" id="date_depart" name="date_depart" required
                                   value="<?php echo isset($_POST['date_depart']) ? htmlspecialchars($_POST['date_depart'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                   min="<?php echo date('Y-m-d'); ?>" >
                            <div class="invalid-feedback">
                                Veuillez saisir une date de départ valide (pas avant aujourd'hui).
                            </div>
                        </div>

                        <!-- Nombre de passagers -->
                        <div class="col-12">
                            <label for="nb_passagers" class="form-label">Nombre de passagers</label>
                            <input type="number" class="form-control" id="nb_passagers" name="nb_passagers" placeholder="Nombre de passagers" required value="<?php echo isset($_POST['nb_passagers']) ? htmlspecialchars($_POST['nb_passagers'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <div class="invalid-feedback">
                                Veuillez saisir le nombre de passagers
                            </div>
                        </div>

                        <!-- Bouton de recherche -->
                        <button class="buttonVert" type="submit" name="chercher">Chercher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <div class="row carousselImageDescription">
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/poignee_de_main.jpg" class="bd-placeholder-img rounded-circle" width="200" height="150" alt="poignée de main">
                <p class="p-3">Le covoiturage favorise la solidarité et l'entraide entre les conducteurs et passagers, permettant de partager des trajets tout en économisant de l'argent. C'est aussi une manière de renforcer les liens sociaux et d'encourager une mobilité plus responsable.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/electric-car.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="voiture éléctrique en charge">
                <p class="p-3">Le covoiturage électrique contribue à réduire les émissions de CO2 et à diminuer la pollution de l'air. En choisissant des véhicules électriques, nous participons activement à la préservation de l'environnement.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/pouce-levé.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="pouce levé">
                <p class="p-3">L'application et le site web "EcoRide" sont conçus pour offrir une expérience utilisateur simple et rapide. Trouver un trajet éco-responsable n'a jamais été aussi facile grâce à une interface claire et intuitive.</p>
            </div>
        </div>

        <script>
function setupAutocomplete(inputId) {
    const input = document.getElementById(inputId);
    let timeout = null;

    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value;

        if (query.length < 3) return; // Attendre au moins 3 caractères

        timeout = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}, France&format=json&limit=5`)
                .then(response => response.json())
                .then(data => {
                    const datalist = document.getElementById(inputId + '-list');
                    if (!datalist) {
                        const newDatalist = document.createElement('datalist');
                        newDatalist.id = inputId + '-list';
                        input.parentNode.appendChild(newDatalist);
                        input.setAttribute('list', inputId + '-list');
                    }

                    document.getElementById(inputId + '-list').innerHTML = data
                        .map(item => `<option value="${item.display_name}">`)
                        .join('');
                });
        }, 300);
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter les datalists pour l'autocomplétion
    setupAutocomplete('ville_depart');
    setupAutocomplete('ville_arrive');
});
</script>

<?php
require_once('templates/footer.php');
?>
