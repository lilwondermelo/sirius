function loadMenu() {
    smartAjaxCall({
        classFile: 'app.class',
        class: 'App',
        method: 'getTypes'
    }).then(categories => {
        const menuContainer = $('#main_menu');
        // Удаляем только динамически добавленные категории
        menuContainer.find('.dynamic-category').remove();

        categories.forEach(category => {
            const imgId = ((category.id - 1) % 2) + 1;
            const menuItemHTML = `
                <div class="menu_item dynamic-category" cat-id="${category.id}">
                    <div class="menu_item_img"><img src="media/images/menu/${imgId}.png" alt="${category.name}"></div>
                    <div class="menu_item_name">${category.name}</div>
                    <div class="menu_item_edit_icon" data-type-id="${category.id}" data-type-name="${category.name}">✏️</div>
                </div>
            `;
            // Добавляем категории перед кнопкой "Добавить"
            $('.add-new-type-card').before(menuItemHTML);
        });
    }).catch(err => {
        console.error("Ошибка при загрузке категорий меню:", err);
    });
}

$(document).ready(function () {
    cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Добавляем кнопку добавления категории в конец меню
    $('#main_menu').append(`
        <div class="menu_item add-new-type-card">
            <div class="add-new-item-icon">+</div>
            <div class="add-new-item-text">Добавить категорию</div>
        </div>
    `);

    loadMenu(); // Загружаем меню
    loadData(0);
});