<?php

header('Content-Type: application/json');

// Vérifie la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifie le champ piège (honeypot)
// if (!empty($_POST['website'])) {
//     echo json_encode(['success' => false, 'message' => 'Validation échouée']);
//     exit;
// }

// Récupération des données du formulaire
$nom = isset($_POST['nom']) ? trim(($_POST['nom'])) : '';
$email = isset($_POST['email']) ? trim(($_POST['email'])) : '';
$telephone = isset($_POST['telephone']) ? trim(($_POST['telephone'])) : '';
$prestation = isset($_POST['prestation']) ? trim(htmlspecialchars($_POST['prestation'])) : '';
$message = isset($_POST['message']) ? trim(htmlspecialchars($_POST['message'])) : '';

// Adresse email de destination
$to = "malikaenergeticienne@hotmail.com";
$subject = "Nouvelle demande de devis pour séance bien être";

// Construction du message
$body = "
    <html>
        <head>
            <title>Demande de devis</title>
        </head>
        <body>
            <h2>Nouvelle demande de devis</h2>
            <p><strong>Nom :</strong> $nom</p>
            <p><strong>Email :</strong> $email</p>
            <p><strong>Téléphone :</strong> $telephone</p>
            <p><strong>Prestation :</strong> $prestation</p>
            <p><strong>Message :</strong></p>
            <p>$message</p>
        </body>
    </html>
";

// En-têtes pour l'email HTML
$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: $email" . "\r\n";

// Envoi de l'email
if (mail($to, $subject, $body, $headers)) {
    echo json_encode(['success' => true, 'message' => 'Email envoyé avec succès']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'envoi']);
}
