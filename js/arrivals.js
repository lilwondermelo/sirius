
function renderArrivalForm(arrivalData = null) {
    const isEditMode = arrivalData !== null;
    const mainContainer = $('#main_table');
    mainContainer.empty();

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

        mainContainer.html(formHtml);

        // Attach event listeners for the form
        $('#add-arrival-item-btn').on('click', () => addArrivalItemRow(products));
        $('#save-arrival-btn').on('click', () => saveOrUpdateArrival(products));
        $('#cancel-arrival-btn').on('click', () => renderArrivalForm()); // Re-render the clean form

        if (isEditMode) {
            // Pre-fill form with arrival data
            $('#arrival-supplier').val(arrivalData.supplier_id);
            $('#arrival-comment').val(arrivalData.comment);

            // Populate items
            if (arrivalData.items && arrivalData.items.length > 0) {
                arrivalData.items.forEach(item => addArrivalItemRow(products, item));
            }
        } else {
            // Add the first empty product row for new arrivals
            addArrivalItemRow(products);
        }

        // Load the list of recent arrivals
        loadAndRenderRecentArrivals();

    }).catch(error => {
        console.error("Error fetching data for arrival form:", error);
        mainContainer.html('<p>Ошибка загрузки данных для формы. Пожалуйста, попробуйте еще раз.</p>');
    });
}

function addArrivalItemRow(products, itemData = null) {
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
    const newRow = $(itemHtml)
    $('#arrival-items-container').append(newRow);

    if (itemData) {
        newRow.find('.arrival-product-select').val(itemData.product_id);
        newRow.find('.arrival-quantity').val(itemData.quantity);
        newRow.find('.arrival-price').val(itemData.purchase_price);
    }

    // Attach listener to the new remove button
    newRow.find('.remove-arrival-item-btn').on('click', function() {
        $(this).closest('.arrival-item-row').remove();
    });
}

function saveOrUpdateArrival(products) {
    const formContainer = $('.arrival-form-container');
    const arrivalId = formContainer.data('arrival-id');

    const arrivalData = {
        supplier_id: $('#arrival-supplier').val(),
        comment: $('#arrival-comment').val(),
        items: []
    };

    $('.arrival-item-row').each(function() {
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
        loadAndRenderRecentArrivals(); // Refresh the list
        if (!arrivalId) {
            // Clear form only on create
            $('#arrival-comment').val('');
            $('#arrival-items-container').empty();
            addArrivalItemRow(products);
        }
    }).catch(error => {
        console.error("Error saving arrival:", error);
        alert("Ошибка при сохранении поступления. " + (error.descr || 'Пожалуйста, проверьте введенные данные.'));
    });
}

function loadAndRenderRecentArrivals() {
    const container = $('#recent-arrivals-list');
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
