

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
        class: 'App',
        method: 'editProducts',
        data: { id: selectedImageId, name: $('.edit_name').val() }
    }
    if (selectedImageFile && selectedImageId) {
        ajax_smart_data.files = { image: selectedImageFile };
    }
    console.log(ajax_smart_data);
    smartAjaxCall(ajax_smart_data)
    .then(res => {
        alert("Сохранено успешно (v2)!");
        console.log(res);
        // можешь сбросить буфер, если нужно:
        selectedImageFile = null;
        selectedImageId = null;
    })
    .catch(err => console.log(err));
}



function loadData(category) {
    smartAjaxCall({
        classFile: 'app.class',
        class: 'App',
        method: 'getData',
        data: { category: category }
    }).then(data => {
        items = data;             // сохраняем глобально
        console.log(items);
        renderData(category);     // отрисовка как раньше
    })
    .catch(err => {
        // Теперь в консоль выводится весь объект с ошибкой для удобства отладки
        console.error("Ошибка при загрузке данных:", err); 
        const errorMessage = err.descr || err.error || "Не удалось загрузить данные";
        showError(errorMessage); // showError - это ваша функция, она должна быть где-то определена
    });
        
}

function smartAjaxCall(options) {
    return new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('classFile', options.classFile);
        formData.append('class', options.class);
        formData.append('method', options.method);
        formData.append('data', JSON.stringify(options.data));

        if (options.files) {
            for (const key in options.files) {
                formData.append(key, options.files[key]);
            }
        }

        $.ajax({
            type: "POST",
            url: "server/core/_ajaxListener.class.php",
            data: formData,
            processData: false,
            contentType: false,
        }).done(function (result) {
            try {
                const data = JSON.parse(result);
                if (data.result === "Ok") {
                    resolve(data.data);
                } else {
                    // Отклоняем Promise со всем объектом ответа для детальной отладки
                    reject(data);
                }
            } catch (e) {
                // Если ответ сервера - не JSON, тоже отклоняем с подробностями
                reject({ error: "Ошибка парсинга ответа", responseText: result });
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            // Ошибка сети
            reject({ error: `Ошибка сети: ${textStatus}`, details: errorThrown });
        });
    });
}