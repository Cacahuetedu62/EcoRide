<?php
require_once('templates/header.php');
require_once('lib/pdo.php');
require_once('lib/config.php');
?>
<main>
<div class="d-flex">
  <!-- Menu à gauche -->
  <div class="d-flex flex-column flex-shrink-0 p-3 bg-body-tertiary" style="width: 300px; height: 100vh;">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
      <svg class="bi pe-none me-2" width="40" height="32"><use xlink:href="#bootstrap"></use></svg>
      <span class="fs-4">Menu</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
      <li class="nav-item">
        <a href="#" class="nav-link" onclick="loadContent('mesInformations.php')" style="color: inherit; text-decoration: none;"> <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M440-280h80v-240h-80v240Zm40-320q17 0 28.5-11.5T520-640q0-17-11.5-28.5T480-680q-17 0-28.5 11.5T440-640q0 17 11.5 28.5T480-600Zm0 520q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg> Mes informations</a>
      </li>
      <li class="nav-item">
        <a href="#" class="nav-link" onclick="loadContent('mesVehicules.php')" style="color: inherit; text-decoration: none;"> <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M240-200v40q0 17-11.5 28.5T200-120h-40q-17 0-28.5-11.5T120-160v-320l84-240q6-18 21.5-29t34.5-11h440q19 0 34.5 11t21.5 29l84 240v320q0 17-11.5 28.5T800-120h-40q-17 0-28.5-11.5T720-160v-40H240Zm-8-360h496l-42-120H274l-42 120Zm-32 80v200-200Zm100 160q25 0 42.5-17.5T360-380q0-25-17.5-42.5T300-440q-25 0-42.5 17.5T240-380q0 25 17.5 42.5T300-320Zm360 0q25 0 42.5-17.5T720-380q0-25-17.5-42.5T660-440q-25 0-42.5 17.5T600-380q0 25 17.5 42.5T660-320Zm-460 40h560v-200H200v200Z"/></svg> Mes véhicules</a>
      </li>
      <li class="nav-item">
        <a href="#" class="nav-link" onclick="loadContent('mesTrajets.php')" style="color: inherit; text-decoration: none;"> <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M280-120q-33 0-56.5-23.5T200-200v-440q0-33 23.5-56.5T280-720h80v-80q0-33 23.5-56.5T440-880h80q33 0 56.5 23.5T600-800v80h80q33 0 56.5 23.5T760-640v440q0 33-23.5 56.5T680-120q0 17-11.5 28.5T640-80q-17 0-28.5-11.5T600-120H360q0 17-11.5 28.5T320-80q-17 0-28.5-11.5T280-120Zm0-80h400v-440H280v440Zm80-40h80v-360h-80v360Zm160 0h80v-360h-80v360Zm-80-480h80v-80h-80v80Zm40 300Z"/></svg> Mes trajets à venir</a>
      </li>
      <li class="nav-item">
        <a href="#" class="nav-link" onclick="loadContent('historiqueTrajets.php')" style="color: inherit; text-decoration: none;"> <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M222-200 80-342l56-56 85 85 170-170 56 57-225 226Zm0-320L80-662l56-56 85 85 170-170 56 57-225 226Zm298 240v-80h360v80H520Zm0-320v-80h360v80H520Z"/></svg> Historique de mes trajets</a>
      </li>
      <li class="nav-item">
        <a href="#" class="nav-link" onclick="loadContent('addTrajets.php')" style="color: inherit; text-decoration: none;"> <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5f6368"><path d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q65 0 123 19t107 53l-58 59q-38-24-81-37.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160q32 0 62-6t58-17l60 61q-41 20-86 31t-94 11Zm280-80v-120H640v-80h120v-120h80v120h120v80H840v120h-80ZM424-296 254-466l56-56 114 114 400-401 56 56-456 457Z"/></svg> Proposer un trajet</a>
      </li>
    </ul>
  </div>

  <!-- Contenu à droite -->
  <div class="flex-grow-1">
    <!-- Le contenu se charge ici -->
    <iframe id="content" src="" style="width: 100%; height: 100vh; border: none;"></iframe>
  </div>
</div>

<script>
  function loadContent(page) {
    // Met à jour l'iframe avec la page correspondante
    document.getElementById('content').src = page;
  }
</script>










</main>
<?php
require_once('templates/footer.php');
?>
