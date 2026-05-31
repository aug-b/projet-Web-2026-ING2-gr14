console.log("SmartCampus chargé");
const params = new URLSearchParams(window.location.search);

if(params.get("error") === "1"){

    document.getElementById("message-erreur").innerText =
    "Email ou mot de passe incorrect";

}