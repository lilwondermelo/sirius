

function sendOrderToTelegram() {
    const name = $('#order_name').val();
    const phone = $('#order_phone').val();
    const email = $('#order_email').val();
    $.ajax({
        type: "POST",
        url: "server/core/_ajaxListener.class.php",
        data: {
            classFile: "app.class",
            class: "App",
            method: "sendOrderToTelegram",
            cart: localStorage.cart,
            name: name,
            phone: phone,
            email: email
        }}).done(function (result) {
        var data = JSON.parse(result);
        console.log(data);
        if (data.result === "Ok") {
            
        }
        else {
        }
    });
}


function uploadData(selectedImageFile, selectedImageId) {
    let ajax_smart_data = {
        classFile: 'app.class',
        className: 'App',
        method: 'editProducts',
        data: { id: selectedImageId, name: $('.edit_name').val() }
    }
    if (selectedImageFile && selectedImageId) {
        ajax_smart_data.files = { image: selectedImageFile };
    }
    console.log(ajax_smart_data);
    smartAjaxCall(ajax_smart_data)
    .then(res => {
        alert("Сохранено!");
        console.log(res);
        // можешь сбросить буфер, если нужно:
        selectedImageFile = null;
        selectedImageId = null;
    })
    .catch(err => alert("Ошибка: " + err));
}



function loadData(category) {
    smartAjaxCall({
        classFile: 'app.class',
        className: 'App',
        method: 'getData',
        data: { category: category }
    }).then(data => {
        items = data;             // сохраняем глобально
        console.log(items);
        renderData(category);     // отрисовка как раньше
    })
    .catch(err => {
        console.error("Ошибка при загрузке данных:", err);
        showError("Не удалось загрузить данные"); // можешь реализовать отображение
    });
        
}