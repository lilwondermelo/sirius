document.addEventListener("DOMContentLoaded", function() {
    let lastScrollTop = 0;
    const header = document.querySelector('header');

    if (header) {
        window.addEventListener("scroll", function() {
            let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollTop > lastScrollTop && scrollTop > header.offsetHeight) {
                // Scroll down
                header.classList.add('header--hidden');
            } else {
                // Scroll up
                header.classList.remove('header--hidden');
            }
            lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
        }, false);
    }
});

function getItem(id) {
    return items.find(item => item.id == id);
}

function loadMenu() {
    getTypes().then(types => {
        const menu = $('#main_menu');
        menu.html(''); // Очищаем меню перед отрисовкой

        // Добавляем "Все товары"
        menu.append('<div class="menu_item" cat-id="0">Все товары</div>');

        // Создаем карту для быстрого доступа к элементам
        const typeMap = new Map(types.map(t => [t.id, t]));
        
        // Добавляем дочерние элементы к родительским
        types.forEach(t => {
            if (t.parent_id && t.parent_id != 0) {
                const parent = typeMap.get(t.parent_id);
                if (parent) {
                    if (!parent.children) {
                        parent.children = [];
                    }
                    parent.children.push(t);
                }
            }
        });

        // Отрисовываем только верхнеуровневые элементы
        types.forEach(t => {
            if (!t.parent_id || t.parent_id == 0) {
                renderMenuItem(t, menu);
            }
        });

        // Добавляем карточку "Добавить категорию"
        menu.append('<div class="menu_item add-new-type-card">+</div>');

    }).catch(error => {
        console.error("Ошибка при загрузке меню:", error);
    });
}

function renderMenuItem(type, container) {
    const imageUrl = `media/images/menu/${type.id}.png?v=${new Date().getTime()}`;
    const hasChildren = type.children && type.children.length > 0;

    const menuItem = $(`
        <div class="menu_item dynamic-category ${type.parent_id != 0 ? 'subcategory-item' : ''}" 
             cat-id="${type.id}" 
             data-type-parent-id="${type.parent_id || 0}">
            <div class="menu_item_img">
                <img src="${imageUrl}" alt="${type.name}">
            </div>
            <div class="menu_item_name">
                <span>${type.name}</span>
            </div>
            <div class="menu_item_edit_icon" 
                 data-type-id="${type.id}" 
                 data-type-name="${type.name}"
                 data-type-parent-id="${type.parent_id || 0}">
                 &#9998;
            </div>
        </div>
    `);

    // Скрываем подкатегории по умолчанию
    if (type.parent_id != 0) {
        menuItem.hide();
    }

    container.append(menuItem);
}