$(document).ready(function () {
    cart = JSON.parse(localStorage.getItem('cart')) || [];
    //console.log(window.Telegram.WebApp.initDataUnsafe);
    //clearCart();
    //renderCart(); // Отображаем корзину при старте
    loadData(0);
});