<?php

require_once("../connexion/connexion.php");

if(isset($_POST["email"])){

    $email = $_POST["email"];

    $sql = "
        SELECT email
        FROM utilisateur
        WHERE email = ?

        UNION

        SELECT email
        FROM utilisateur_en_attente
        WHERE email = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){
        echo "existe";
    }else{
        echo "libre";
    }

    $stmt->close();
    $conn->close();
}
?>