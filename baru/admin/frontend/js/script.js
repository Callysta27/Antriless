function checkout(){

    let total = 0;
    let receiptHTML = "";

    keranjang.forEach(item => {

        const subtotal =
        item.harga * item.jumlah;

        total += subtotal;

        receiptHTML += `
        <div class="receipt-item">
            <span>
                ${item.nama} x${item.jumlah}
            </span>

            <span>
                Rp ${subtotal.toLocaleString('id-ID')}
            </span>
        </div>
        `;
    });

    document.getElementById("receiptItems").innerHTML =
    receiptHTML;

    document.getElementById("receiptTotal").innerText =
    total.toLocaleString('id-ID');

    document.getElementById("receiptModal")
    .classList.add("active");

    fetch('proses_checkout.php',{

    method:'POST',

    headers:{
        'Content-Type':'application/json'
    },

    body:JSON.stringify({
        cart: keranjang,
        total: total
    })

})
.then(response => response.text())
.then(data => {

    console.log("HASIL PHP:");
    console.log(data);

    try{

        let hasil = JSON.parse(data);

        document.getElementById("qrcode").innerHTML = "";

        new QRCode(
            document.getElementById("qrcode"),
            {
                text:"ORDER-" + hasil.id_order,
                width:220,
                height:220
            }
        );

    }catch(err){

        console.error("ERROR JSON:", err);

    }

});

}

function closeReceipt(){

    document
    .getElementById("receiptModal")
    .classList.remove("active");

    keranjang = [];

    window.location.href = "status.php";
}
/* FILTER HARGA */

function sortProducts(){

const cards =
Array.from(
document.querySelectorAll(".products .card")
);

const container =
document.querySelector(".products");

const value =
document.getElementById("sortPrice").value;

cards.sort((a,b)=>{

const priceA =
parseInt(
a.querySelector("p")
.innerText
.replace(/[^\d]/g,"")
);

const priceB =
parseInt(
b.querySelector("p")
.innerText
.replace(/[^\d]/g,"")
);

if(value === "low"){
return priceA - priceB;
}

if(value === "high"){
return priceB - priceA;
}

return 0;

});

cards.forEach(card=>{
container.appendChild(card);
});

}