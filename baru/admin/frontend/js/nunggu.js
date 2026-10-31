setTimeout(() => {

    document.getElementById("titleStatus")
        .innerHTML = "Siap Diambil";

    document.getElementById("descStatus")
        .innerHTML = "Selamat Menikmati !";

    document.querySelector(".icon-left")
        .className = "fa-solid fa-utensils icon-left";

    document.querySelector(".icon-right")
        .className = "fa-solid fa-utensils icon-right";

}, 10000);

function openConfirmModal(id){

    document
        .getElementById('confirmModal')
        .classList
        .add('show');

    document
        .getElementById('confirmLink')
        .href =
        'update.php?id_order=' + id;
}

function closeConfirmModal(){

    document
        .getElementById('confirmModal')
        .classList
        .remove('show');
}

window.onclick = function(e){

    const modal =
        document.getElementById('confirmModal');

    if(e.target === modal){
        closeConfirmModal();
    }
}