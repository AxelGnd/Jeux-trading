const updatePeriod = 120 * 1000; // 2 min la

async function tryUpdate() {
    const now = Date.now();
    const lastUpdate = parseInt(localStorage.getItem("lastUpdate") || "0", 10); // Récupère le timestamp de la dernière mise à jour (ou sionon prend 0)

    if (now - lastUpdate >= updatePeriod) {
        console.log("Assez de temps écoulé, appel de update.php...");
        try {
            const response = await fetch("update.php");
            if (!response.ok) throw new Error(`Erreur HTTP : ${response.status}`);
            const data = await response.text();
            console.log("Mise à jour réussie :", data);
            localStorage.setItem("lastUpdate", Date.now());

            // Recharger la page après la mise à jour
            location.reload();
        } catch (error) {
            console.error("Erreur lors de l'appel à update.php :", error);
        }
    } else {
        console.log("Mise à jour pas nécessaire pour l'instant.");
    }
}

// Appelle immédiatement
tryUpdate();

// Et relance toutes les X secondes
setInterval(tryUpdate, 125 * 1000); // toutes les 15 secondes, on vérifie si on doit relancer