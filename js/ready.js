$(document).ready(function () {
    cart = JSON.parse(localStorage.getItem('cart')) || [];
    loadMenu(); // Загружаем меню
    loadData(0);
});

function loadMenu() {
    getTypes().then(categories => {
        const menuContainer = $('#main_menu');
        menuContainer.find('.dynamic-category').remove();
        const addButton = menuContainer.find('.add-new-type-card');

        // --- Tree building and flattening ---
        const categoryMap = new Map(categories.map(c => [c.id, {...c, children: []}]));
        const tree = [];
        categories.forEach(c => {
            if (c.parent_id == 0) {
                tree.push(categoryMap.get(c.id));
            } else if (categoryMap.has(c.parent_id)) {
                categoryMap.get(c.parent_id).children.push(categoryMap.get(c.id));
            }
        });

        const flatOrderedList = [];
        function flatten(node, level) {
            flatOrderedList.push({...node, level: level});
            node.children.forEach(child => flatten(child, level + 1));
        }
        tree.forEach(rootNode => flatten(rootNode, 0));
        // --- End of tree logic ---

        let allCatsHTML = '';
        flatOrderedList.forEach(category => {
            const imgId = ((category.id - 1) % 2) + 1;
            const isSubcategory = category.parent_id != 0;
            const hidden = isSubcategory ? 'style="display: none;"' : '';
            const subcategoryClass = isSubcategory ? 'subcategory-item' : '';
            const marginLeft = 15 * category.level;

            allCatsHTML += `
                <div class="menu_item dynamic-category ${subcategoryClass}" cat-id="${category.id}" data-type-parent-id="${category.parent_id}" ${hidden}>
                    <div class="menu_item_img"><img src="media/images/menu/${imgId}.png" alt="${category.name}"></div>
                    <div class="menu_item_name" style="margin-left: ${marginLeft}px;">${category.name}</div>
                    <div class="menu_item_edit_icon" data-type-id="${category.id}" data-type-name="${category.name}" data-type-parent-id="${category.parent_id}">✏️</div>
                </div>
            `;
        });
        
        addButton.before(allCatsHTML);

        // Устанавливаем начальное состояние для кнопки "Сириус"
        $('.menu_item[cat-id=0]').addClass('current-cat');

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