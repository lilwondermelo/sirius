$(document).ready(function () {
    cart = JSON.parse(localStorage.getItem('cart')) || [];
    loadMenu(); // Загружаем меню
    loadData(0);
});

function loadMenu() {
    smartAjaxCall({
        classFile: 'app.class',
        class: 'App',
        method: 'getTypes'
    }).then(categories => {
        const menuContainer = $('#main_menu');
        categories.forEach(category => {
            // Зацикливаем использование иконок 1.png и 2.png для всех категорий
            const imgId = ((category.id - 1) % 2) + 1;
            const menuItemHTML = `
                <div class="menu_item" cat-id="${category.id}">
                    <div class="menu_item_img"><img src="media/images/menu/${imgId}.png" alt="${category.name}"></div>
                    <div class="menu_item_name">${category.name}</div>
                </div>
            `;
            menuContainer.append(menuItemHTML);
        });
    }).catch(err => {
        console.error("Ошибка при загрузке категорий меню:", err);
    });
}
