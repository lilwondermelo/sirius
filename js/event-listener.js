$(document).on('click', '.flex_row_item_button_plus', function() {
    let button_value = $(this).parent().find('.flex_row_item_button_value');
    let item_id = button_value.attr('item-id');
    const item = addToCart(item_id, 1);
    renderAddButton(item);
})
$(document).on('click', '.main_title_cart', function() {
    renderCart();
})

$(document).on('click', '.cart_remove', function() {
    const item_id = $(this).data('id');
    removeFromCart(item_id);
    renderCart();
});


$(document).on('click', '.main_title_logo', function() {
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
})


$(document).on('click', '.flex_row_item_button_minus', function() {
    const $valueBtn = $(this).siblings('.flex_row_item_button_value');
    const item_id = $valueBtn.attr('item-id');
    const item = addToCart(item_id, -1);
    if (item.amount < 1) {
        removeFromCart(item_id);
    }
    renderAddButton(item);
})

$(document).on('click', '.flex_row_item_name', function() {
    let item = getItem($(this).attr('item-id'));
    renderItemEdit(item);
})

$(document).on('click', '.menu_item', function() {
    let category = $(this).attr('cat-id');
    loadData(category);
})

$(document).on('click', '.flex_row_item_button_order', function() {
    sendOrderToTelegram();
    clearCart();
    renderCart();
});


$(document).on('click', '.item_img', function () {
    const id = $(this).closest('.item').attr('item-id');
    selectedImageId = id;

    $('#item_img_input').off('change').on('change', function () {
        const file = this.files[0];
        const id = $(this).data('item-id') || $('.item_img').closest('.item').attr('item-id'); // подстраховка
    
        if (!file) return;
    
        const allowedTypes = ['image/jpeg', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert("Выберите .jpg или .webp файл");
            return;
        }
    
        selectedImageFile = file;
    
        // Обновляем превью
        const reader = new FileReader();
        reader.onload = function (e) {
            $(`.item[item-id="${id}"] .item_img img`).attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
    });
    
    $('#item_img_input').click(); // Запуск выбора
});


$(document).on('click', '.save_button', function () {
    uploadData(selectedImageFile, selectedImageId)
    
});
