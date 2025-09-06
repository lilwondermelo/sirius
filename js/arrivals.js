
function renderArrivalForm(arrivalData = null, container = null) {
    const isEditMode = arrivalData !== null;
    const targetContainer = container || $('#main_table');
    targetContainer.empty();

    Promise.all([
        getSuppliers(),
        getProductsList()
    ]).then(([suppliers, products]) => {
        const supplierOptions = suppliers.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
        const arrivalIdDataAttr = isEditMode ? `data-arrival-id="${arrivalData.id}"` : '';

        const formHtml = `
            <div class="arrival-form-container" ${arrivalIdDataAttr}>
                <h2>${isEditMode ? 'Редактирование поступления' : 'Новое поступление'}</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label for="arrival-supplier" class="form-label">Поставщик:</label>
                        <select id="arrival-supplier" class="form-input">${supplierOptions}</select>
                    </div>
                    <div class="form-group">
                        <label for="arrival-comment" class="form-label">Комментарий:</label>
                        <textarea id="arrival-comment" class="form-input"></textarea>
                    </div>
                </div>

                <hr>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="excel-upload-input" class="form-label">Загрузить из Excel:</label>
                        <input type="file" id="excel-upload-input" class="form-input" accept=".xls,.xlsx">
                    </div>
                    <div class="form-group">
                        <button id="upload-excel-btn" class="btn">Загрузить</button>
                    </div>
                </div>

                <h3>Товары</h3>
                <div id="arrival-items-container" class="suppliers-table">
                    <!-- Product rows will be added here -->
                </div>
                <button id="add-arrival-item-btn" class="btn" style="margin-top: 1vw;">Добавить товар</button>
                
                <div class="form-actions">
                    <button id="save-arrival-btn" class="btn btn-primary">Сохранить</button>
                    <button id="cancel-arrival-btn" class="btn btn-secondary">Отмена</button>
                </div>
            </div>
            <hr style="margin: 2vw 0;">
            <div class="arrival-form-container">
                <h3>Последние поступления</h3>
                <div id="recent-arrivals-list"></div>
            </div>
        `;

        targetContainer.html(formHtml);
        const recentArrivalsContainer = targetContainer.find('#recent-arrivals-list');

        // Use delegated event listeners on the container
        targetContainer.off('click', '#add-arrival-item-btn').on('click', '#add-arrival-item-btn', () => addArrivalItemRow(products, null, targetContainer));
        targetContainer.off('click', '#save-arrival-btn').on('click', '#save-arrival-btn', () => saveOrUpdateArrival(products, recentArrivalsContainer));
        targetContainer.off('click', '#cancel-arrival-btn').on('click', '#cancel-arrival-btn', () => renderArrivalForm(null, targetContainer));
        targetContainer.off('click', '.remove-arrival-item-btn').on('click', '.remove-arrival-item-btn', function() {
            $(this).closest('.arrival-item-row').remove();
        });
        targetContainer.off('click', '#upload-excel-btn').on('click', '#upload-excel-btn', () => handleExcelUpload(products, targetContainer));


        if (isEditMode) {
            // Pre-fill form with arrival data
            targetContainer.find('#arrival-supplier').val(arrivalData.supplier_id);
            targetContainer.find('#arrival-comment').val(arrivalData.comment);

            // Populate items
            if (arrivalData.items && arrivalData.items.length > 0) {
                arrivalData.items.forEach(item => addArrivalItemRow(products, item, targetContainer));
            }
        } else {
            // Add the first empty product row for new arrivals
            addArrivalItemRow(products, null, targetContainer);
        }

        // Load the list of recent arrivals
        loadAndRenderRecentArrivals(recentArrivalsContainer);

    }).catch(error => {
        console.error("Error fetching data for arrival form:", error);
        targetContainer.html('<p>Ошибка загрузки данных для формы. Пожалуйста, попробуйте еще раз.</p>');
    });
}

function handleExcelUpload(products, container) {
    const fileInput = container.find('#excel-upload-input')[0];
    const supplierId = container.find('#arrival-supplier').val();
    const file = fileInput.files[0];

    if (!file) {
        alert('Пожалуйста, выберите файл для загрузки.');
        return;
    }
    if (!supplierId) {
        alert('Пожалуйста, выберите поставщика.');
        return;
    }

    const formData = new FormData();
    formData.append('excel_file', file);
    formData.append('supplier_id', supplierId);
    formData.append('class', 'App');
    formData.append('method', 'uploadArrivalExcel');

    // Show a loading indicator
    const uploadBtn = container.find('#upload-excel-btn');
    uploadBtn.text('Загрузка...').prop('disabled', true);

    $.ajax({
        url: 'server/core/_ajaxListener.class.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.result === 'Ok' && response.data) {
                // Clear existing items
                container.find('#arrival-items-container').empty();
                
                let notFoundCount = 0;
                let notFoundVendorCodes = [];

                response.data.forEach(item => {
                    if (item.product_id) {
                        addArrivalItemRow(products, item, container);
                    } else {
                        notFoundCount++;
                        notFoundVendorCodes.push(item.vendor_code);
                    }
                });

                if (notFoundCount > 0) {
                    alert(`Не найдено ${notFoundCount} товаров в базе данных со следующими артикулами:
${notFoundVendorCodes.join(', ')}

Остальные товары были добавлены в форму.`);
                } else {
                    alert('Все товары из файла успешно добавлены в форму.');
                }


            } else {
                alert('Ошибка при обработке файла: ' + (response.descr || 'Неизвестная ошибка'));
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("Excel upload error:", textStatus, errorThrown);
            alert('Произошла ошибка при загрузке файла.');
        },
        complete: function() {
            // Restore button
            uploadBtn.text('Загрузить').prop('disabled', false);
        }
    });
}

function addArrivalItemRow(products, itemData = null, container) {
    const itemsContainer = container.find('#arrival-items-container');
    const productOptions = products.map(p => `<option value="${p.id}">${p.name}</option>`).join('');
    const itemHtml = `
        <div class="arrival-item-row">
            <div class="form-group">
                <select class="arrival-product-select form-input" name="product_id[]">${productOptions}</select>
            </div>
             <div class="form-group">
                <input type="number" class="arrival-quantity form-input" name="quantity[]" placeholder="Кол-во" min="0.001" step="0.001">
            </div>
            <div class="form-group">
                <input type="number" class="arrival-price form-input" name="purchase_price[]" placeholder="Цена закупки" min="0.00" step="0.01">
            </div>
            <div class="form-group">
                <button class="remove-arrival-item-btn btn btn-danger">❌</button>
            </div>
        </div>
    `;
    const newRow = $(itemHtml);
    itemsContainer.append(newRow);

    if (itemData) {
        newRow.find('.arrival-product-select').val(itemData.product_id);
        newRow.find('.arrival-quantity').val(itemData.quantity);
        newRow.find('.arrival-price').val(itemData.purchase_price);
    }

    // The remove button listener is now delegated in renderArrivalForm
}

function saveOrUpdateArrival(products, listContainer) {
    const formContainer = listContainer.closest('.admin-panel-tab-content').find('.arrival-form-container:first');
    const arrivalId = formContainer.data('arrival-id');

    const arrivalData = {
        supplier_id: formContainer.find('#arrival-supplier').val(),
        comment: formContainer.find('#arrival-comment').val(),
        items: []
    };

    formContainer.find('.arrival-item-row').each(function() {
        const row = $(this);
        const item = {
            product_id: row.find('.arrival-product-select').val(),
            quantity: row.find('.arrival-quantity').val(),
            purchase_price: row.find('.arrival-price').val()
        };
        if (item.product_id && item.quantity > 0 && item.purchase_price) {
            arrivalData.items.push(item);
        }
    });

    if (arrivalData.items.length === 0) {
        alert("Добавьте хотя бы один товар с количеством и ценой.");
        return;
    }

    let promise;
    if (arrivalId) {
        // Update existing arrival
        arrivalData.arrival_id = arrivalId;
        promise = updateProductArrival(arrivalData);
    } else {
        // Create new arrival
        promise = createProductArrival(arrivalData);
    }

    promise.then(response => {
        alert(arrivalId ? "Поступление успешно обновлено!" : "Поступление успешно сохранено!");
        loadAndRenderRecentArrivals(listContainer); // Refresh the list
        if (!arrivalId) {
            // Clear form only on create
            formContainer.find('#arrival-comment').val('');
            const itemsContainer = formContainer.find('#arrival-items-container');
            itemsContainer.empty();
            addArrivalItemRow(products, null, formContainer);
        }
    }).catch(error => {
        console.error("Error saving arrival:", error);
        alert("Ошибка при сохранении поступления. " + (error.descr || 'Пожалуйста, проверьте введенные данные.'));
    });
}

function loadAndRenderRecentArrivals(container) {
    container.html('<p>Загрузка...</p>');

    getRecentArrivals().then(arrivals => {
        if (!arrivals || arrivals.length === 0) {
            container.html('<p>Поступлений пока нет.</p>');
            return;
        }

        const table = $('<table class="suppliers-table"></table>');
        const thead = `
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Поставщик</th>
                    <th>Комментарий</th>
                    <th>Кол-во позиций</th>
                    <th>Сумма</th>
                </tr>
            </thead>
        `;
        table.append(thead);

        const tbody = $('<tbody></tbody>');
        arrivals.forEach(arrival => {
            const row = `
                <tr class="clickable-row arrival-row" data-arrival-id="${arrival.id}">
                    <td>${new Date(arrival.arrival_date).toLocaleString()}</td>
                    <td>${arrival.supplier_name || '-'}</td>
                    <td>${arrival.comment || '-'}</td>
                    <td>${arrival.item_count}</td>
                    <td>${parseFloat(arrival.total_sum || 0).toFixed(2)}</td>
                </tr>
            `;
            tbody.append(row);
        });

        table.append(tbody);
        container.html(table);

    }).catch(error => {
        console.error("Error loading recent arrivals:", error);
        container.html('<p>Не удалось загрузить список поступлений.</p>');
    });
}
