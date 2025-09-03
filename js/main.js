let cart = [];
let items = [];
let sum = 0;
let currentCategory = 0;

let selectedImageFile = null;
let selectedImageId = null;

function getItem(id) {
    let item = items.find(item => item.id == id);
    return item;
}

function buildPayload({ classFile, className, method, data = {}, files = {} }) {
    const formData = new FormData();

    formData.append('classFile', classFile);
    formData.append('class', className);
    formData.append('method', method);
    formData.append('data', JSON.stringify(data));

    for (const key in files) {
        if (files[key]) {
            formData.append(key, files[key]);
        }
    }

    return formData;
}

function ajaxCall({ payload, uploadUrl = "server/core/_ajaxListener.class.php" }) {
    const isFormData = payload instanceof FormData;

    return new Promise((resolve, reject) => {
        $.ajax({
            type: "POST",
            url: uploadUrl,
            data: payload,
            processData: !isFormData ? true : false,
            contentType: !isFormData ? "application/x-www-form-urlencoded" : false,
            success: (result) => {
                console.log(result);
                try {
                    const data = JSON.parse(result);
                    if (data.result === "Ok") {
                        resolve(data.data);
                    } else {
                        reject(data.descr || "Ошибка от сервера");
                    }
                } catch (e) {
                    reject("Ошибка парсинга JSON");
                }
            },
            error: (xhr) => {
                reject("Ошибка AJAX: " + xhr.status);
            }
        });
    });
}

function ajaxJsonCall({ classFile, className, method, data = {}, url = "server/core/_ajaxListener.class.php" }) {
    const payload = {
        classFile,
        class: className,
        method,
        ...data
    };

    return new Promise((resolve, reject) => {
        $.ajax({
            type: "POST",
            url,
            data: payload,
            success: (result) => {
                
                try {
                    const parsed = JSON.parse(result);
                    if (parsed.result === "Ok") {
                        resolve(parsed.data);
                    } else {
                        reject(parsed.descr || "Ошибка от сервера");
                    }
                } catch (e) {
                    reject("Ошибка парсинга JSON");
                }
            },
            error: (xhr) => {
                reject("Ошибка AJAX: " + xhr.status);
            }
        });
    });
}
function smartAjaxCall({ 
    classFile, 
    className, 
    method, 
    data = {}, 
    files = null, 
    url = "server/core/_ajaxListener.class.php" 
}) {
    if (files && Object.keys(files).length > 0) {
        const payload = buildPayload({
            classFile,
            className,
            method,
            data,
            files
        });

        return ajaxCall({ payload, uploadUrl: url });
    } else {
        return ajaxJsonCall({
            classFile,
            className,
            method,
            data,
            url
        });
    }
}




