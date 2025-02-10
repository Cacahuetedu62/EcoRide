<?php require_once('templates/header.php'); ?>

<style>
/* Mobile first styles */
.support-container {
    padding: 1rem;
    max-width: 100%;
}

.support-header {
    background: linear-gradient(135deg, #295e3c, #519168);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 1.5rem;
}

.support-title {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.support-subtitle {
    font-size: 1rem;
    opacity: 0.9;
}

.support-cards {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.support-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
    background: #295e3c;
    color: white;
    padding: 1rem;
    border-radius: 8px 8px 0 0;
}

.card-title {
    font-size: 1.2rem;
    margin: 0;
}

.card-body {
    padding: 1rem;
}

.contact-info {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0.5rem 0;
}

.contact-icon {
    font-size: 1.2rem;
}

/* Tablet breakpoint */
@media (min-width: 768px) {
    .support-container {
        max-width: 90%;
        margin: 0 auto;
    }

    .support-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }

    .support-title {
        font-size: 1.8rem;
    }
}

/* Desktop breakpoint */
@media (min-width: 1024px) {
    .support-container {
        max-width: 1200px;
        padding: 2rem;
    }

    .support-cards {
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }

    .support-title {
        font-size: 2rem;
    }

    .support-header {
        padding: 2.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }
}
</style>

<div class="support-container">
    <header class="support-header">
        <h1 class="support-title">Nous sommes à votre écoute</h1>
        <p class="support-subtitle">Du lundi au vendredi de 8h00 à 19h00 sans interruption</p>
    </header>

    <div class="support-cards">
        <!-- Carte 1 -->
        <div class="support-card">
            <div class="card-header">
                <h2 class="card-title">Nouveaux utilisateurs</h2>
            </div>
            <div class="card-body">
                <p>Découvrez nos offres exclusives et créez votre compte.</p>
                <div class="contact-info">
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <span>06 51 62 15 94</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">📧</span>
                        <span>contact@ecoride.fr</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte 2 -->
        <div class="support-card">
            <div class="card-header">
                <h2 class="card-title">Service Client</h2>
            </div>
            <div class="card-body">
                <p>Besoin d'aide avec votre compte ? Nous sommes là pour vous.</p>
                <div class="contact-info">
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <span>06 51 84 95 61</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">📧</span>
                        <span>contact@ecoride.fr</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Carte 3 -->
        <div class="support-card">
            <div class="card-header">
                <h2 class="card-title">Support Technique</h2>
            </div>
            <div class="card-body">
                <p>Un problème technique ? Notre équipe est là pour vous aider.</p>
                <div class="contact-info">
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <span>03 62 51 84 95</span>
                    </div>
                    <div class="contact-item">
                        <span class="contact-icon">📧</span>
                        <span>contact@ecoride.fr</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once('templates/footer.php'); ?>