function renderCart() {
    $("#main_table").html("");

    if (cart.length > 0) {
        // Заголовок
        $("#main_table").append(`
            <div class="cart_row cart_header">
                <div>Код</div>
                <div>Фото</div>
                <div>Наименование</div>
                <div>Цена</div>
                <div>Кратно</div>
                <div>Наличие</div>
                <div class="cart_header_qty">Кол-во</div>
                <div>Сумма</div>
                <div></div>
            </div>
        `);
        console.log(cart);
        cart.forEach((item) => {
            const price = parseFloat(item.price);
            const total = (price * item.amount).toFixed(2);
            $("#main_table").append(`
                <div class="cart_row">
                    <div>Код: ${item.id}</div>
                    <div class="cart_img"><img src="media/images/items/${item.id}` + ((item.timestamp)?('_' + item.timestamp):'') +`.jpg" /></div>
                    <div class="mobile cart_text">
                        <div class="clickable_name" item-id="${item.id}">${item.name}</div>
                        <div class="cart_price">${price}</div>
                        <div class="cart_unit">1 ${item.unit}</div>
                        <div class="cart_stock">${item.amount}</div>
                    </div>
                    <div class="clickable_name pc" item-id="${item.id}">${item.name}</div>
                    <div class="cart_price pc">${price}</div>
                    <div class="cart_unit pc">1 ${item.unit}</div>
                    <div class="cart_stock pc">${item.amount}</div>
                    <div class="cart_qty_control">
                        <div class="flex_row_item_button flex_row_item_button_minus active_button">-</div>
                        <div class="flex_row_item_button_value active_button" item-id="${item.id}">${item.amount}</div>
                        <div class="flex_row_item_button flex_row_item_button_plus active_button">+</div>
                    </div>
                    <div class="cart_total">${total}</div>
                    <div><span class="cart_remove" data-id="${item.id}">🗑</span></div>
                </div>
            `);
        });

        $("#main_table").append(`
        <div class="cart_footer">
            <div class="cart_row">
                <div style="grid-column: span 6"></div>
                <div style="grid-column: span 1">Итого:</div>
                <div class="flex_col_item_sum" id="cart_sum" style="grid-column: span 2">0 ₽</div>
            </div>
            <input type="text" id="order_name" placeholder="Введите ваше имя" class="cart_input" />
            <input type="text" id="order_phone" placeholder="Введите номер телефона" class="cart_input" />
            <input type="email" id="order_email" placeholder="Введите e-mail" class="cart_input" />
            <div class="flex_row_item_button_order" style="margin-top: 1vw;">Заказать</div>
        </div>
    `);
        getSum();
        renderSum();
    }
}

function renderRowSum(button, item) {
    const parentRow = $(button).closest('.cart_row');
    const itemPrice = parseFloat(item.price);
    parentRow.find('.cart_total').text((item.amount * itemPrice).toFixed(2));
    parentRow.find('.flex_row_item_button_value').text(item.amount);
}

function renderSum() {
    $('.flex_col_item_sum').html(sum + ' ₽');
}

function renderData(category) {
    $("#main_table").html("");

    // Add the "Add new item" card first
    const addNewItemCard = `
        <div class="flex_row_item add-new-item-card">
            <div class="add-new-item-icon">+</div>
            <div class="add-new-item-text">Добавить товар</div>
        </div>
    `;
    $("#main_table").append(addNewItemCard);

    items.forEach((item, index) => {
        if (category == 0 || item.type == category) {
            renderCard(item);
            renderAddButton(inCart(item.id));
        }
    });
}



function renderCard(item) {
    const in_cart = cart.find(item_in_cart => item_in_cart.id == item.id);
    $("#main_table").append(' ' + 
            '<div class="flex_row_item">' +
                '<div class="flex_row_item_img"><img src="media/images/items/' + item.id + ((item.timestamp)?('_' + item.timestamp):'') + '.jpg" alt=""></div>' + 
                '<div class="flex_row_item_name" item-id="' + item.id + '"><span>' + item.name + '</span></div>' + 
                '<div class="flex_row_item_amount">' + item.amount + '</div>' + 
                '<div class="flex_row_item_price">' + item.price + '</div>' + 
                '<div class="flex_row_item_unit">Цена за 1 ' + item.unit + '</div>' +
                '<div class="flex_row_item_button">' + 
                    '<div class="flex_row_item_button flex_row_item_button_minus">-</div>' + 
                    '<div class="flex_row_item_button_value" item-id="' + item.id + '">' + ((in_cart)?in_cart.amount:'В корзину') + '</div>' + 
                    '<div class="flex_row_item_button flex_row_item_button_plus">+</div>' + 
                '</div>' + 
            '</div>');
}

function renderItem(item) {
    $("#main_table").html(' ' + 
    '<div class="item" item-id="' + item.id + ((item.timestamp)?('_' + item.timestamp):'') + '">' +
        '<div class="item_img"><img src="media/images/items/' + item.id + '.jpg" alt=""></div>' + 
        '<div class="item_name" item-id="' + item.id + '">' + item.name + '</div>' + 
        '<div class="item_amount">' + item.amount + '</div>' + 
        '<div class="item_price">' + item.price + '</div>' + 
        '<div class="item_button">В корзину</div>' +
    '</div>');
}           

function renderItemEdit(item) {
    const itemId = item ? item.id : 0;
    const timestamp = item ? (item.timestamp ? '_' + item.timestamp : '') : '';
    const imageUrl = `media/images/items/${itemId}${timestamp}.jpg`;

    // Загружаем список единиц измерения и категорий параллельно
    const unitsPromise = smartAjaxCall({
        classFile: 'app.class',
        class: 'App',
        method: 'getUnits',
        data: {}
    });
    const typesPromise = getTypes(); // Эта функция уже есть и возвращает промис

    Promise.all([unitsPromise, typesPromise]).then(([units, types]) => {
        // Опции для единиц измерения
        const unitsOptions = units.map(unit => 
            `<option value="${unit.id}" ${item && unit.id == item.unit_id ? 'selected' : ''}>${unit.name}</option>`
        ).join('');

        // Опции для категорий
        const typeOptions = types.map(t => 
            `<option value="${t.id}" ${item && t.id == item.type ? 'selected' : (t.id == currentCategory ? 'selected' : '')}>${t.name}</option>`
        ).join('');

        const formHtml = `
            <div class="edit-form-container">
                <div class="form-row">
                    <div class="form-group form-group-image">
                        <label class="form-label">Изображение</label>
                        <label for="item_img_input" class="item_img edit_img">
                            <img src="${imageUrl}" alt="Изображение товара">
                        </label>
                        <input type="file" id="item_img_input" accept=".jpg,.jpeg,.webp,.png" style="display: none;">
                    </div>
                    <div class="form-group-fields">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_name" class="form-label">Название</label>
                                <input type="text" id="edit_name" class="form-input edit_name" value="${item ? item.name : ''}">
                            </div>
                            <div class="form-group">
                                <label for="edit_vendor_code" class="form-label">Артикул</label>
                                <input type="text" id="edit_vendor_code" class="form-input edit_vendor_code" value="${item ? (item.vendor_code || '') : ''}">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_price" class="form-label">Цена</label>
                                <input type="number" id="edit_price" class="form-input edit_price" value="${item ? item.price : '0.00'}">
                            </div>
                            <div class="form-group">
                                <label for="edit_unit_id" class="form-label">Единица измерения</label>
                                <select id="edit_unit_id" class="form-input">${unitsOptions}</select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_type_id" class="form-label">Категория</label>
                                <select id="edit_type_id" class="form-input">${typeOptions}</select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <div class="item_button save_button" data-id="${itemId}">Сохранить</div>
                </div>
            </div>
        `;
        $("#main_table").html(formHtml);
    }).catch(error => {
        console.error("Ошибка при загрузке данных для формы:", error);
        // Можно показать пользователю сообщение об ошибке
    });
}

function renderTypeEdit(type) {
    const typeId = type ? type.id : 0;
    const typeName = type ? type.name : '';
    const parentId = type ? type.parent_id : 0;
    const imageUrl = type ? `media/images/menu/${typeId}.png?${new Date().getTime()}` : '';

    // Загружаем список всех категорий для выбора родительской
    getTypes().then(types => {
        const typeOptions = types.map(t => 
            `<option value="${t.id}" ${parentId == t.id ? 'selected' : ''}>${t.name}</option>`
        ).join('');

        const formHtml = `
            <div class="edit-form-container">
                <div class="form-row">
                    <div class="form-group form-group-image">
                        <label class="form-label">Изображение</label>
                        <label for="type_img_input" class="item_img edit_img">
                            <img src="${imageUrl}" alt="Изображение категории">
                        </label>
                        <input type="file" id="type_img_input" accept=".jpg,.jpeg,.webp,.png" style="display: none;">
                    </div>
                    <div class="form-group-fields">
                        <div class="form-group">
                            <label for="edit_type_name" class="form-label">Название категории</label>
                            <input type="text" id="edit_type_name" class="form-input" value="${typeName}">
                        </div>
                        <div class="form-group">
                            <label for="edit_type_parent_id" class="form-label">Родительская категория</label>
                            <select id="edit_type_parent_id" class="form-input">
                                <option value="0">-- Нет --</option>
                                ${typeOptions}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <div class="item_button save_type_button" data-id="${typeId}">Сохранить</div>
                </div>
            </div>
        `;
        $("#main_table").html(formHtml);
    }).catch(error => {
        console.error("Ошибка при загрузке списка категорий:", error);
        // Можно показать пользователю сообщение об ошибке
    });
}

function renderAddButton(item) {
    let button = $('.flex_row_item_button_value[item-id="' + item.id + '"]');
    if (item.amount > 0) {
        $(button).html(item.amount);
        $(button).parent().parent().find('.flex_col_item_price').html((item.amount * parseInt(item.price)) + ' ₽');
        $(button).addClass('active_button');
        $(button).parent().find('.flex_row_item_button_minus').addClass('active_button');
        $(button).parent().find('.flex_row_item_button_plus').addClass('active_button');
    }
    else if ($(button).parent().hasClass('cart_qty_control')) {
        $(button).parent().parent().remove();
        renderCart();
    }
    else {
        $(button).html("В корзину");
        $(button).removeClass('active_button');
        $(button).parent().find('.flex_row_item_button_minus').removeClass('active_button');
        $(button).parent().find('.flex_row_item_button_plus').removeClass('active_button');
    }
    getSum();
    renderSum();
    renderRowSum(button, item);
    
}