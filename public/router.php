<?php
if (preg_match('/\.(?:png|jpg|jpeg|gif|webp)$/', $_SERVER["REQUEST_URI"])) {
    return false; // Sert le fichier statique directement
}
require 'index.php';
