<?php
require_once('templates/header.php');
?>

<section class="d-flex m-3 justify-content-center">
    <div class="colonne-formulaire">
        <form method="POST" action="">
            <div><h4>Chercher un trajet</h4>
            <label for="ville_depart" class="form-label">Ville de départ</label>
            <input type="text" class="form-control" id="ville_depart" name="ville_depart" placeholder="Ville de départ" required value="<?php echo isset($_POST['ville_depart']) ? htmlspecialchars($_POST['ville_depart'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            <div class="invalid-feedback">
                Veuillez saisir une ville de départ
            </div>
        </div>

        <div>
            <label for="ville_arrive" class="form-label">Ville d'arrivé</label>
            <input type="text" class="form-control" id="ville_arrive" name="ville_arrive" placeholder="Ville d'arrivé" required value="<?php echo isset($_POST['ville_arrive']) ? htmlspecialchars($_POST['ville_arrive'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            <div class="invalid-feedback">
                Veuillez saisir une ville d'arrivé
            </div>
        </div>

        <div>
            <label for="date_depart" class="form-label">Date de départ</label>
            <input type="date" class="form-control" id="date_depart" name="date_depart" required
                value="<?php echo isset($_POST['date_depart']) ? htmlspecialchars($_POST['date_depart'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                min="<?php echo date('Y-m-d'); ?>" >
            <div class="invalid-feedback">
                Veuillez saisir une date de départ valide (pas avant aujourd'hui).
            </div>
        </div>

        <div>
            <label for="nb_passagers" class="form-label">Nombre de passagers</label>
            <input type="number" class="form-control" id="nb_passagers" name="nb_passagers" placeholder="Nombre de passagers" required value="<?php echo isset($_POST['nb_passagers']) ? htmlspecialchars($_POST['nb_passagers'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            <div class="invalid-feedback">
                Veuillez saisir le nombre de passagers
            </div>
        </div>

        <div>
            <button class="buttonVert m-3" type="submit" name="chercher">Chercher</button>
        </div>
        </form>
    </div>

    <div class="colonne-affiner">
        <?php
        // Deuxième partie du formulaire qui affine la recherche
        if (isset($_POST['chercher']) || isset($_POST['filtrer'])) {
            echo '
                <div><h4>Vous pouvez affiner vos recherches</h4>
                    <div>Souhaitez-vous un trajet effectué avec une voiture électrique/hybride ? Il sera alors considéré comme "écologique".</div>
                    <div><input type="checkbox" id="ecologique" name="ecologique" value="1" ' . (isset($_POST['ecologique']) && $_POST['ecologique'] == '1' ? 'checked' : '') . '> Oui</div>

                    <div>
                        <label for="prix_min" class="form-label">Prix minimum</label>
                        <input type="number" class="form-control" id="prix_min" name="prix_min" placeholder="Prix minimum en €"
                               value="' . (isset($_POST['prix_min']) ? htmlspecialchars($_POST['prix_min'], ENT_QUOTES, 'UTF-8') : '') . '">
                    </div>

                    <div>
                        <label for="duree" class="form-label">Durée du trajet</label>
                        <input type="text" class="form-control" id="duree" name="duree" placeholder="Durée du trajet (en heures)"
                               value="' . (isset($_POST['duree']) ? htmlspecialchars($_POST['duree'], ENT_QUOTES, 'UTF-8') : '') . '">
                    </div>

                    <div>
                        <label for="note_min" class="form-label">Note minimale pour le chauffeur</label>
                        <input type="number" class="form-control" id="note_min" name="note_min" placeholder="Note minimale (1-5)"
                               min="1" max="5"
                               value="' . (isset($_POST['note_min']) ? htmlspecialchars($_POST['note_min'], ENT_QUOTES, 'UTF-8') : '') . '">
                    </div>

                    <div>
                        <button class="buttonVert m-3" type="submit" name="filtrer">Appliquer les filtres</button>
                    </div>
                </div>
            ';
        }
        ?>
    </div>
</section>

<?php
if (isset($_POST['chercher']) || isset($_POST['filtrer'])) {
    // Récupérer les données du formulaire
    $ville_depart = $_POST['ville_depart'];
    $ville_arrive = $_POST['ville_arrive'];
    $date_depart = $_POST['date_depart'];
    $nb_passagers = $_POST['nb_passagers'];
    $ecologique = isset($_POST['ecologique']) ? 1 : 0;
    $prix_min = isset($_POST['prix_min']) ? $_POST['prix_min'] : null;
    $duree = isset($_POST['duree']) ? $_POST['duree'] : null;
    $note_min = isset($_POST['note_min']) ? $_POST['note_min'] : null;

    // Validation des villes (uniquement lettres et espaces autorisés)
    if (!preg_match("/^[a-zA-Z\s]+$/", $ville_depart)) {
        die("Ville de départ invalide. Seules les lettres et les espaces sont autorisés.");
    }
    if (!preg_match("/^[a-zA-Z\s]+$/", $ville_arrive)) {
        die("Ville d'arrivé invalide. Seules les lettres et les espaces sont autorisés.");
    }

    if ($duree && !is_numeric($duree)) {
        die("La durée du trajet doit être un nombre valide.");
    }

    $duree = (int) $duree;  // Assurez-vous que la durée est un entier

    // Filtrage pour assainir les villes en supprimant les caractères spéciaux HTML
    $ville_depart = htmlspecialchars($ville_depart, ENT_QUOTES, 'UTF-8');
    $ville_arrive = htmlspecialchars($ville_arrive, ENT_QUOTES, 'UTF-8');
    $date_depart = htmlspecialchars($date_depart, ENT_QUOTES, 'UTF-8');
    $nb_passagers = intval($nb_passagers); // Assurer que le nombre de passagers est un entier

    // Préparer la requête SQL
    $sql = "SELECT DISTINCT t.*, u.pseudo, u.photo, u.note_moyenne, v.energie, v.nb_places AS voiture_places, t.prix_personnes,
    TIMESTAMPDIFF(HOUR, CONCAT(t.date_depart, ' ', t.heure_depart), CONCAT(t.date_arrive, ' ', t.heure_arrive)) AS duree
    FROM trajets t
    JOIN trajet_utilisateur tu ON t.id = tu.trajet_id
    JOIN utilisateurs u ON tu.utilisateur_id = u.id
    LEFT JOIN avis a ON a.trajet_id = t.id
    LEFT JOIN voitures v ON v.utilisateur_id = u.id
    WHERE t.lieu_depart LIKE :ville_depart
    AND t.lieu_arrive LIKE :ville_arrive
    AND t.date_depart = :date_depart
    AND t.nb_places >= :nb_passagers";

    if ($ecologique) {
        $sql .= " AND v.energie IN ('électrique', 'hybride')";
    }
    if ($prix_min) {
        $sql .= " AND t.prix_personnes >= :prix_min";
    }
    if ($duree) {
        $sql .= " AND TIMESTAMPDIFF(HOUR, CONCAT(t.date_depart, ' ', t.heure_depart), CONCAT(t.date_arrive, ' ', t.heure_arrive)) >= :duree";
    }
    if ($note_min) {
        $sql .= " AND ROUND(u.note_moyenne, 1) >= :note_min"; // Arrondir la moyenne à 1 décimale
    }

    $stmt = $pdo->prepare($sql);

    // Lier les paramètres
    $stmt->bindValue(':ville_depart', '%' . $ville_depart . '%');
    $stmt->bindValue(':ville_arrive', '%' . $ville_arrive . '%');
    $stmt->bindValue(':date_depart', $date_depart);
    $stmt->bindValue(':nb_passagers', $nb_passagers, PDO::PARAM_INT);
    if ($prix_min) {
        $stmt->bindValue(':prix_min', $prix_min, PDO::PARAM_INT);
    }
    if ($duree) {
        $stmt->bindValue(':duree', $duree, PDO::PARAM_STR);
    }
    if ($note_min) {
        $stmt->bindValue(':note_min', $note_min, PDO::PARAM_INT);
    }

    // Exécuter la requête
    $stmt->execute();

    // Vérifier si des trajets correspondent
    $trajets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $trajets_disponibles = array_filter($trajets, function($trajet) {
        return $trajet['nb_places'] >= 1; // Vérifier si au moins une place est disponible
    });

    if (count($trajets_disponibles) > 0) {
        // Afficher les résultats
        foreach ($trajets_disponibles as $trajet) {
            // Vérifier si le trajet est écologique (si l'énergie est électrique || hybride)
            $ecologique = ($trajet['energie'] === 'électrique' || $trajet['energie'] === 'hybride') ? 'Oui' : 'Non';
            ?>

    <div class="m-3"><h4>Trajet n° <?= htmlspecialchars($trajet['id']) ?></h4></div>
    <section class="recapTrajet d-flex">
    <div class="trajetComplet p-3">
        <h3>Départ</h3>
        <label for="#" class="form-label">Ville de départ</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_depart']) ?>" disabled>

        <label for="#" class="form-label">Date de départ</label>
        <input type="date" class="form-control" value="<?= $trajet['date_depart'] ?>" disabled>

        <label for="#" class="form-label">Heure de départ</label>
        <input type="text" class="form-control" value="<?= $trajet['heure_depart'] ?>" disabled>
    </div>

    <div class="trajetComplet p-3">
        <h3>Arrivée</h3>
        <label for="#" class="form-label">Ville d'arrivé</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($trajet['lieu_arrive']) ?>" disabled>

        <label for="#" class="form-label">Date d'arrivé</label>
        <input type="date" class="form-control" value="<?= $trajet['date_arrive'] ?>" disabled>

        <label for="#" class="form-label">Heure d'arrivé</label>
        <input type="text" class="form-control" value="<?= $trajet['heure_arrive'] ?>" disabled>
    </div>

    <div class="trajetComplet p-3">
        <h4>Informations sur le chauffeur</h4>
        <p class="profilPhotoPseudo">
            <img src="<?= htmlspecialchars($trajet['photo']) ?>" alt="Photo du chauffeur" class="bd-placeholder-img rounded-circle" width="75" height="75">
            <?= htmlspecialchars($trajet['pseudo']) ?>
        </p>

        <p>Note :
        <?php
            $note_moyenne = $trajet['note_moyenne'];
            for ($i = 0; $i < $note_moyenne; $i++) {
                echo "🚗"; // Affichage de l'icône pour chaque point de la note
            }
        ?>
        </p>

        <p>Nombre de places restantes :<?= $trajet['nb_places'] ?></p>
        <p>Prix :<?= $trajet['prix_personnes'] ?> € par personne</p>
        <p>
            <?php
            if ($ecologique) {
                echo "🌱 C'est un trajet écologique*";
            } else {
                echo "⛽ Ce trajet n'est pas écologique*";
            }
            ?>
        </p>
        <p style="font-size: 15px;">*Un trajet est dit "écologique" s'il est effectué avec une voiture éléctrique/hybride</p>
        <div>
            <button class="buttonVert mt-2" name="détails"><a href="reservations.php?id=<?php echo htmlspecialchars($trajet['id']); ?>">+ détails</a></button>
        </div>
    </div>
</section>

    <?php
}
} else {
     // Affichage du toast ici seulement quand aucun trajet n'est trouvé
     ?>
     <!-- Toast Bootstrap pour "Aucun trajet trouvé" -->
     <div class="toast-container p-3 position-fixed top-50 start-50 translate-middle z-index-5">
         <div class="toast" role="alert" aria-live="assertive" aria-atomic="true">
             <div class="toast-header">
                 <strong class="me-auto">Résultat de la recherche</strong>
                 <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
             </div>
             <div class="toast-body">
                 Aucun trajet trouvé pour les dates sélectionnées. Peut-être que des trajets sont disponibles à d'autres dates. Essayez de modifier vos dates de départ.
             </div>
     <?php
 }
}
?>
</div>

<script>
 // Vérifie si le toast existe sur la page et l'affiche
 var toastElement = document.querySelector('.toast');
 if (toastElement) {
     var toast = new bootstrap.Toast(toastElement);
     toast.show(); // Afficher le toast
 }
</script>
</main>
<?php
require_once('templates/footer.php');
?>
