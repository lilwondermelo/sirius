$(document).on('click', '.flex_row_item_button_plus', function() {
    let button_value = $(this).parent().find('.flex_row_item_button_value');
    let item_id = button_value.attr('item-id');
    const item = addToCart(item_id, 1);
    renderAddButton(item);
});

$(document).on('click', '.main_title_cart', function() {
    renderCart();
});

$(document).on('click', '.cart_remove', function() {
    const item_id = $(this).data('id');
    removeFromCart(item_id);
    renderCart();
});

$(document).on('click', '.main_title_logo', function() {
    selectedImageId = 0;
    let item = null;
    renderItemEdit(item);
});

$(document).on('click', '.flex_row_item_button_value', function() {
    const $btn = $(this);
    if (!$btn.hasClass('active_button')) {
        const item_id = $btn.attr('item-id');
        const item = addToCart(item_id, 1);
        renderAddButton(item);
    }
});

$(document).on('click', '.flex_row_item_button_minus', function() {
    const $valueBtn = $(this).siblings('.flex_row_item_button_value');
    const item_id = $valueBtn.attr('item-id');
    const item = addToCart(item_id, -1);
    if (item.amount < 1) {
        removeFromCart(item_id);
    }
    renderAddButton(item);
});

$(document).on('click', '.flex_row_item_name', function() {
    let item = getItem($(this).attr('item-id'));
    selectedImageId = item.id;
    renderItemEdit(item);
});

$(document).on('click', '.menu_item', function() {
    let category = $(this).attr('cat-id');
    currentCategory = category;
    loadData(category);
});

$(document).on('click', '.flex_row_item_button_order', function() {
    sendOrderToTelegram();
    clearCart();
    renderCart();
});

$(document).on('change', '#item_img_input', function () {
    const file = this.files[0];
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/webp', 'image/png'];
    if (!allowedTypes.includes(file.type)) {
        alert("Выберите .jpg, .webp или .png файл");
        return;
    }

    selectedImageFile = file;

    const reader = new FileReader();
    reader.onload = function (e) {
        $('.item_img.edit_img img').attr('src', e.target.result);
    };
    reader.readAsDataURL(file);
});

$(document).on('click', '.add-new-item-card', function() {
    selectedImageId = 0;
    let item = null;
    renderItemEdit(item);
});

$(document).on('click', '.save_button', function () {
    const id = $(this).data('id');
    uploadData(id);
});

// Открытие формы для новой категории
$(document).on('click', '.add-new-type-card', function() {
    renderTypeEdit(null);
});

// Открытие формы для редактирования категории
$(document).on('click', '.menu_item_edit_icon', function(e) {
    e.stopPropagation(); // Предотвращаем всплытие события до .menu_item
    const typeId = $(this).data('type-id');
    const typeName = $(this).data('type-name');
    renderTypeEdit({ id: typeId, name: typeName });
});

// Сохранение категории
$(document).on('click', '.save_type_button', function () {
    const typeId = $(this).data('id');
    const typeName = $('#edit_type_name').val();
    uploadType(typeId, typeName);
});
