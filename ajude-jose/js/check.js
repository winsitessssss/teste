let interval;

function checkPayment() {
    const transaction = localStorage.getItem("transactionId");
    const client = localStorage.getItem("identifier");

    if (!transaction || !client) {
        console.error("Dados não encontrados");
        clearInterval(interval);
        return;
    }

    fetch(`./api/checkpayment.php?transaction=${transaction}&client=${client}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'COMPLETED') {
                clearInterval(interval);
                localStorage.removeItem("identifier");
                window.location.href = '../../brinde/'; //redirect
            }
        })
        .catch(error => console.error("Erro:", error));
}

if (localStorage.getItem("transactionId")) {
    interval = setInterval(checkPayment, 6000);
}