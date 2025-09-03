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

$(document).on('click', '.menu_item:not(.add-new-type-card)', function() {
    const clickedItem = $(this);
    const catId = clickedItem.attr('cat-id');

    // --- Style Update ---
    $('.menu_item').removeClass('current-cat active-parent');
    clickedItem.addClass('current-cat');
    // --- End of Style Update ---

    if (catId == '0') {
        // Show all top-level items, hide all sub-items
        $('.dynamic-category:not(.subcategory-item)').show();
        $('.subcategory-item').hide();
        currentCategory = 0;
        loadData(0);
        return;
    }

    // 1. & 2. Create a list of IDs to show
    const visibleIds = [];

    // 3. Add the clicked item
    visibleIds.push(catId);

    // 4. Add all ancestors and style them
    let parentId = clickedItem.attr('data-type-parent-id');
    while (parentId && parseInt(parentId) != 0) {
        visibleIds.push(parentId);
        const parent = $('.menu_item[cat-id="' + parentId + '"]');
        if (parent.length) {
            parent.addClass('active-parent'); // Style parent
            parentId = parent.attr('data-type-parent-id');
        } else {
            parentId = null; // stop if parent not found
        }
    }

    // 5. Add all direct children
    $('.subcategory-item[data-type-parent-id="' + catId + '"]').each(function() {
        visibleIds.push($(this).attr('cat-id'));
    });

    // 6. Hide ALL dynamic items
    $('.dynamic-category').hide();

    // 7. Show the items from the list
    visibleIds.forEach(id => {
        $('.menu_item[cat-id="' + id + '"]').show();
    });

    // Load data for the clicked category
    currentCategory = catId;
    loadData(catId);
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

$(document).on('change', '#type_img_input', function () {
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
    const parentId = $(this).data('type-parent-id');
    renderTypeEdit({ id: typeId, name: typeName, parent_id: parentId });
});

// Сохранение категории
$(document).on('click', '.save_type_button', function () {
    const typeId = $(this).data('id');
    const typeName = $('#edit_type_name').val();
    const parentId = $('#edit_type_parent_id').val();
    uploadType(typeId, typeName, parentId);
});
