<?php
require_once('templates/header.php');
require_once('lib/config.php');
require_once('lib/pdo.php');


//Récupération de tous les utilisateurs
// $query = $pdo->prepare("SELECT * FROM trajets");
// $query->execute();
// $allUsers = $query->fetchAll(PDO::FETCH_ASSOC);
// var_dump($allUsers);
// ?>


<main>
    
 <div class="row p-3 justify-content-center align-items-md-stretch containHistoire">
        <div class="col-md-6 d-flex justify-content-center">
            <div class="h-100 p-1 rounded-3 image-backgroundHistoire">
                <p class="descriptionHistoire">EcoRide est une entreprise de covoiturage innovante qui met l'accent sur l'utilisation de véhicules électriques, alliant ainsi mobilité durable et respect de l'environnement. En choisissant EcoRide, vous contribuez à réduire les émissions de CO2 tout en profitant d'un transport économique et pratique. Nous offrons une alternative écologique aux trajets quotidiens, en permettant à nos utilisateurs de partager des trajets en toute sécurité et à moindre coût. Grâce à notre plateforme facile d'accès, chacun peut trouver un trajet éco-responsable adapté à ses besoins. EcoRide, c'est la solution idéale pour ceux qui veulent voyager intelligemment, économiquement et en respectant la planète. Rejoignez notre communauté et participez à la révolution verte du transport !</p>
            </div>
        </div>


      
        <div class="col-md-6 p-3 d-flex justify-content-center">  
            <div class="col-md-7 col-lg-8 contenair">
                <h4 class="mb-3 text-center">Chercher un trajet</h4>
                <form class="needs-validation" novalidate="" data-np-intersection-state="visible" data-np-autofill-form-type="credit_card" data-np-checked="1" data-np-watching="1">
                    <div class="row g-3 cardTrajet">


                        <div class="col-12">
                            <label for="#" class="form-label">Ville de départ</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368">
                                        <path d="M480-480q33 0 56.5-23.5T560-560q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560q0 33 23.5 56.5T480-480Zm0 294q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-80Zm0-480Z" />
                                    </svg></span>
                                <input type="text" class="form-control" id="#" placeholder="Ville de départ" required="" data-np-intersection-state="observed">
                                <div class="invalid-feedback">
                                    Veuillez saisir une ville de départ
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="#" class="form-label">Ville d'arrivé</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368">
                                        <path d="M480-480q33 0 56.5-23.5T560-560q0-33-23.5-56.5T480-640q-33 0-56.5 23.5T400-560q0 33 23.5 56.5T480-480Zm0 294q122-112 181-203.5T720-552q0-109-69.5-178.5T480-800q-101 0-170.5 69.5T240-552q0 71 59 162.5T480-186Zm0 106Q319-217 239.5-334.5T160-552q0-150 96.5-239T480-880q127 0 223.5 89T800-552q0 100-79.5 217.5T480-80Zm0-480Z" />
                                    </svg></span>
                                <input type="text" class="form-control" id="#" placeholder="Ville d'arrivé" required="" data-np-intersection-state="observed">
                                <div class="invalid-feedback">
                                    Veuillez saisir une ville d'arrivé
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="#" class="form-label">Date de départ</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368">
                                        <path d="M200-80q-33 0-56.5-23.5T120-160v-560q0-33 23.5-56.5T200-800h40v-80h80v80h320v-80h80v80h40q33 0 56.5 23.5T840-720v560q0 33-23.5 56.5T760-80H200Zm0-80h560v-400H200v400Zm0-480h560v-80H200v80Zm0 0v-80 80Zm280 240q-17 0-28.5-11.5T440-440q0-17 11.5-28.5T480-480q17 0 28.5 11.5T520-440q0 17-11.5 28.5T480-400Zm-160 0q-17 0-28.5-11.5T280-440q0-17 11.5-28.5T320-480q17 0 28.5 11.5T360-440q0 17-11.5 28.5T320-400Zm320 0q-17 0-28.5-11.5T600-440q0-17 11.5-28.5T640-480q17 0 28.5 11.5T680-440q0 17-11.5 28.5T640-400ZM480-240q-17 0-28.5-11.5T440-280q0-17 11.5-28.5T480-320q17 0 28.5 11.5T520-280q0 17-11.5 28.5T480-240Zm-160 0q-17 0-28.5-11.5T280-280q0-17 11.5-28.5T320-320q17 0 28.5 11.5T360-280q0 17-11.5 28.5T320-240Zm320 0q-17 0-28.5-11.5T600-280q0-17 11.5-28.5T640-320q17 0 28.5 11.5T680-280q0 17-11.5 28.5T640-240Z" />
                                    </svg> </span>
                                <input type="date" class="form-control" id="#" placeholder="Date de départ" required>
                                <div class="invalid-feedback">
                                    Veuillez saisir une date de départ
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="#" class="form-label">Nombre de passagers</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368">
                                        <path d="M720-400v-120H600v-80h120v-120h80v120h120v80H800v120h-80Zm-360-80q-66 0-113-47t-47-113q0-66 47-113t113-47q66 0 113 47t47 113q0 66-47 113t-113 47ZM40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm80-80h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm240-320q33 0 56.5-23.5T440-640q0-33-23.5-56.5T360-720q-33 0-56.5 23.5T280-640q0 33 23.5 56.5T360-560Zm0-80Zm0 400Z" />
                                    </svg></span>
                                <input type="text" class="form-control" id="#" placeholder="Nombre de passagers" required="" data-np-intersection-state="observed">
                                <div class="invalid-feedback">
                                    Veuillez saisir le nombre de passagers
                                </div>
                            </div>
                        </div>

                    

                        <button class="buttonVert" type="submit">Chercher <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="24px" fill="#ffffff">
                                <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
                            </svg></button>
                        </div>


                </form>
            </div>
        </div>
    </div>


    <div class="container marketing">
        <div class="row carousselImageDescription">
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/poignee_de_main.jpg" class="bd-placeholder-img rounded-circle" width="200" height="150" alt="Femme qui part en voyage">
                <p class="p-3">Le covoiturage favorise la solidarité et l'entraide entre les conducteurs et passagers, permettant de partager des trajets tout en économisant de l'argent. C'est aussi une manière de renforcer les liens sociaux et d'encourager une mobilité plus responsable.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/electric-car.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="Femme qui part en voyage">
                <p class="p-3">Le covoiturage électrique contribue à réduire les émissions de CO2 et à diminuer la pollution de l'air. En choisissant des véhicules électriques, nous participons activement à la préservation de l'environnement.</p>
            </div>
            <div class="col-lg-4 d-flex flex-column align-items-center text-center">
                <img src="images/pouce-levé.jpg" class="bd-placeholder-img rounded-circle" width="200" height="140" alt="Femme qui part en voyage">
                <p class="p-3">L'application et le site web "EcoRide" sont conçus pour offrir une expérience utilisateur simple et rapide. Trouver un trajet éco-responsable n'a jamais été aussi facile grâce à une interface claire et intuitive.</p>
            </div>
        </div>
</main>


<?php
require_once('templates/footer.php');
?>