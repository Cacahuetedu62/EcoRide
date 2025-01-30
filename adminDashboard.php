<?php
require_once('templates/header.php');
?>


<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-12 text-center mb-5">
            <h1 class="display-4">Tableau de Bord Admin</h1>
            <p class="lead text-muted">Choisissez votre espace de travail</p>
        </div>
    </div>

    <div class="row justify-content-center g-4">
        <!-- Graphiques Section -->
        <div class="col-md-5">
            <div class="card dashboard-option shadow-lg border-0 h-100">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#DCEEB4" class="bi bi-graph-up text-primary" viewBox="0 0 16 16" aria-label="Graphiques">
                            <path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .706-.07z"/>
                        </svg>
                    </div>
                    <h2 class="card-title mb-3">Graphiques</h2>
                    <p class="card-text mb-4">
                        Visualisez les données et statistiques de la plateforme EcoRide en un coup d'œil.
                    </p>
                    <a href="graphiques.php" class="btn-retour" aria-label="Gérer les utilisateurs">
                        Gérer les utilisateurs
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-arrow-right ms-2" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Gestion des Utilisateurs Section -->
        <div class="col-md-5">
            <div class="card dashboard-option shadow-lg border-0 h-100">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#DCEEB4" class="bi bi-people-fill text-success" viewBox="0 0 16 16" aria-label="Gestion des Utilisateurs">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275M0 10c0-1 .444-1.958 1.009-2.786.601-.854 1.474-1.6 2.49-2.197C4.471 4.426 6.14 3.933 8 3.933s3.529.493 4.5 1.084c1.016.597 1.89 1.343 2.49 2.197C15.556 8.042 16 9 16 10zM13.5 5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                        </svg>
                    </div>
                    <h2 class="card-title mb-3">Gestion des Utilisateurs</h2>
                    <p class="card-text mb-4">
                        Administrez et gérez les comptes utilisateurs de la plateforme.
                    </p>
                    <a href="manage.php" class="btn-retour" aria-label="Gerer les utilisateurs">
                        Gérer les utilisateurs
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-arrow-right ms-2" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                        </svg>

                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once('templates/footer.php');
?>
