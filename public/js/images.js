window.onload = () => {
    //Gestion des boutons "Supprimer"
    let links = document.querySelectorAll("[data-delete]")

    //On boucle sur links
    for(link of links) {
        //On �coute le clic
        link.addEventListener("click",  function(e) {
            //On emp�che la navigation
            e.preventDefault()

            //On demande confirmation
            if(confirm("Voulez-vous supprimer cette image ?")){
                //On envoie une requ�te Ajax vers le href du lien avec la m�thode DELETE
                fetch(this.getAttribute("href"), {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({"_token": this.dataset.token})
                }).then(
                    // On recup�re la r�ponse en JSON
                    response => response.json()
                ).then(data => {
                    if (data.success)
                        this.parentElement.remove()
                    else 
                        alert(data.error)
                }).catch(e => alert(e))
            }
        })
    }
}