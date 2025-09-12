
import XLSX from 'xlsx';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

// Эта зависимость не была предоставлена, поэтому я создаю заглушку.
// В реальной среде это должно быть импортировано из './mapping.service.js'
// import { getMapping } from './mapping.service.js';
const getMapping = () => []; 

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(path.dirname(__filename));

// --- Конфигурация парсера --- //
const HEADER_SEARCH_TERMS = {
  sku: 'Код товара/работ, услуг',
  name: 'Наименование товара',
  quantity: 'Количество',
  price: 'Цена (тариф)',
  total: 'с налогом - всего'
};

// --- Вспомогательные функции --- //
function normalize(str) {
  if (typeof str !== 'string') return '';
  return str.replace(/[\s-]/g, '');
}

function findHeaderKey(allHeaders, searchTerm) {
  const cleanSearchTerm = normalize(searchTerm);
  return allHeaders.find(header => normalize(header).includes(cleanSearchTerm));
}

// --- Новая функция для отправки данных по API --- //
async function sendDataToApi(data) {
  const apiUrl = 'http://sirius-berdsk.ru/server/api.php';
  console.log(`Отправка данных на ${apiUrl}...`);

  try {
    const response = await fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
      const errorBody = await response.text();
      throw new Error(`HTTP ошибка! Статус: ${response.status}, Тело ответа: ${errorBody}`);
    }

    const result = await response.json();
    console.log('Данные успешно отправлены. Ответ API:', result);
    return result;
  } catch (error) {
    console.error('Ошибка при отправке данных в API:', error);
    throw error;
  }
}


/**
 * Основная функция парсинга, проверки и ОТПРАВКИ данных Excel.
 * @param {string} filename - Имя файла.
 * @param {string} supplierTin - ИНН поставщика.
 * @returns {object} - Объект с обработанными продуктами и результатом отправки.
 */
export async function checkDataAndSend(filename, supplierTin) {
  if (!filename || !supplierTin) {
    const error = new Error('Имя файла и ИНН поставщика должны быть предоставлены.');
    error.statusCode = 400;
    throw error;
  }

  const excelFilePath = path.join(__dirname, 'data', supplierTin, filename);
  if (!fs.existsSync(excelFilePath)) {
      const error = new Error(`Файл не найден: ${excelFilePath}`);
      error.statusCode = 404;
      throw error;
  }

  const workbook = XLSX.readFile(excelFilePath);
  const sheetName = workbook.SheetNames[0];
  const worksheet = workbook.Sheets[sheetName];
  const rawJsonData = XLSX.utils.sheet_to_json(worksheet, { header: 1 });

  let headerRowIndex = -1;
  let maxMatchCount = 0;
  const searchTerms = Object.values(HEADER_SEARCH_TERMS).map(term => normalize(term));

  for (let i = 0; i < rawJsonData.length; i++) {
    const row = rawJsonData[i];
    if (!row || row.length === 0) continue;
    const normalizedRowValues = row.map(cell => normalize(String(cell)));
    let currentMatchCount = 0;
    for (const term of searchTerms) {
      if (normalizedRowValues.some(cellValue => cellValue.includes(term))) {
        currentMatchCount++;
      }
    }
    if (currentMatchCount > maxMatchCount) {
      maxMatchCount = currentMatchCount;
      headerRowIndex = i;
    }
  }

  if (maxMatchCount < 3) {
    throw new Error('Не удалось найти строку с заголовками. Найдено слишком мало совпадений.');
  }

  const headerRow = rawJsonData[headerRowIndex];
  const allHeaders = headerRow.map(h => h ? String(h) : '').filter(Boolean);

  const keys = {
    sku: findHeaderKey(allHeaders, HEADER_SEARCH_TERMS.sku),
    name: findHeaderKey(allHeaders, HEADER_SEARCH_TERMS.name),
    quantity: findHeaderKey(allHeaders, HEADER_SEARCH_TERMS.quantity),
    price: findHeaderKey(allHeaders, HEADER_SEARCH_TERMS.price),
    total: findHeaderKey(allHeaders, HEADER_SEARCH_TERMS.total)
  };

  if (Object.values(keys).some(key => !key)) {
    console.log('Найденные ключи:', JSON.stringify(keys, null, 2));
    console.log('Все заголовки в файле:', allHeaders);
    throw new Error('Не удалось найти все необходимые столбцы в файле.');
  }

  const dataRows = rawJsonData.slice(headerRowIndex + 1);

  const productsWithInternalKeys = dataRows.map(rowArray => {
    const rowObj = {};
    headerRow.forEach((header, index) => {
      if (header) {
        rowObj[String(header)] = rowArray[index];
      }
    });
    const skuValue = rowObj[keys.sku];
    const nameValue = rowObj[keys.name];
    return {
      sku: typeof skuValue === 'string' ? skuValue.trim() : skuValue,
      name: typeof nameValue === 'string' ? nameValue.trim() : nameValue,
      quantity: rowObj[keys.quantity],
      price: rowObj[keys.price],
      total: rowObj[keys.total]
    };
  }).filter(p => {
    const isValidNumber = (val) => {
      if (val === null || val === undefined) return false;
      if (typeof val === 'string' && val.trim() === '') return false;
      const num = Number(val);
      return !isNaN(num) && isFinite(num);
    };
    const hasTextData = p.sku && String(p.sku).length > 2 && p.name && String(p.name).length > 2;
    const hasNumericData = isValidNumber(p.quantity) && isValidNumber(p.price);
    return !isHeader && !isNumericSubHeader && hasTextData;
  });

  const productMappings = getMapping();
  const missingMappings = [];

  const productsForDisplay = productsWithInternalKeys.map(p => {
    const matchedMapping = productMappings.find(m => m.supplierSku === p.sku && m.supplierInn === supplierTin);
    const ourSku = matchedMapping ? matchedMapping.sku : '';
    if (!ourSku) {
      missingMappings.push(p.sku);
    }
    return {
      'Артикул': p.sku,
      'Наименование товара': p.name,
      'Количество': p.quantity,
      'Цена': p.price,
      'Сумма с налогом': p.total,
      'Наш Артикул': ourSku
    };
  });

  const dataToSend = {
    products: productsForDisplay,
    missingMappings: [...new Set(missingMappings)],
    supplierTin: supplierTin
  };
  
  // --- Отправка данных --- //
  const apiResult = await sendDataToApi(dataToSend);

  return {
      parsedData: dataToSend,
      apiResponse: apiResult
  };
}
