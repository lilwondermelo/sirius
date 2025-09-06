// Initialize cart from localStorage or as an empty array
let cart = [];

function loadCart() {
    const storedCart = localStorage.getItem('cart');
    if (storedCart) {
        cart = JSON.parse(storedCart);
    }
}

// Load the cart as soon as the script is loaded
loadCart();

function clearCart() {
    cart = [];
    saveCart();
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function removeFromCart(item_id) {
    let index = cart.findIndex(item_in_cart => item_in_cart.id == item_id);
    if (index !== -1) {
        cart.splice(index, 1);
    }
    saveCart();
}

function inCart(item_id) {
    return cart.find(item => item.id == item_id) || 0;
}

function addToCart(item_id, delta) {
    let in_cart = inCart(item_id);
    if (in_cart) {
        in_cart.amount += delta;
    } else {
        in_cart = getItem(item_id);
        in_cart.amount = delta;
        cart.push(in_cart);
    }
    saveCart();
    return in_cart;
}

function getSum() {
    sum = 0;
    if (cart.length > 0) {
        cart.forEach((i, index) => {
            sum += (i.amount * parseInt(i.price));
        });
    }
}