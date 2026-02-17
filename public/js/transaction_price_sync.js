// Qty formatting stripping unnecessary zeros and decimal places
function formatQuantity(num) {
    if (Number.isNaN(num)) return '0';
    if (Number.isInteger(num) || num === Math.floor(num)) return String(Math.floor(num));
    const s = num.toFixed(8).replace(/\.?0+$/, '');
    return s || '0';
}

// Price formatting to 2dp unless more is needed
function formatPrice(num) {
    if (Number.isNaN(num)) return '0.00';
    const trimmed = parseFloat(num.toFixed(8)).toString();
    const hasDecimal = trimmed.includes('.');
    const decimalPart = hasDecimal ? trimmed.split('.')[1] : '';
    if (!hasDecimal || decimalPart.length < 2) {
        return Number(trimmed).toFixed(2);
    }
    return trimmed;
}

function initPriceSync() {
    const form = document.querySelector('[data-price-sync]');
    if (!form) return;

    const quantityField = document.getElementById(form.dataset.quantityId);
    const perCoinField = document.getElementById(form.dataset.perCoinId);
    const totalField = document.getElementById(form.dataset.totalId);

    if (!quantityField || !perCoinField || !totalField) return;

    // Format initial values
    if (quantityField.value) {
        const q = parseFloat(quantityField.value);
        if (!isNaN(q)) quantityField.value = formatQuantity(q);
    }
    if (perCoinField.value) {
        const p = parseFloat(perCoinField.value);
        if (!isNaN(p)) perCoinField.value = formatPrice(p);
    }
    if (totalField.value) {
        const t = parseFloat(totalField.value);
        if (!isNaN(t)) totalField.value = formatPrice(t);
    }

    let lastEdited = null;

    perCoinField.addEventListener('input', function () {
        lastEdited = 'perCoin';
        const quantity = parseFloat(quantityField.value);
        const pricePerCoin = parseFloat(perCoinField.value);
        if (!isNaN(quantity) && !isNaN(pricePerCoin) && quantity > 0) {
            totalField.value = formatPrice(quantity * pricePerCoin);
        }
    });

    totalField.addEventListener('input', function () {
        lastEdited = 'total';
        const quantity = parseFloat(quantityField.value);
        const totalCost = parseFloat(totalField.value);
        if (!isNaN(quantity) && !isNaN(totalCost) && quantity > 0) {
            perCoinField.value = formatPrice(totalCost / quantity);
        }
    });

    quantityField.addEventListener('input', function () {
        const quantity = parseFloat(quantityField.value);
        if (isNaN(quantity) || quantity <= 0) return;

        if (lastEdited === 'perCoin' || (!lastEdited && perCoinField.value)) {
            const pricePerCoin = parseFloat(perCoinField.value);
            if (!isNaN(pricePerCoin)) {
                totalField.value = formatPrice(quantity * pricePerCoin);
            }
        } else if (lastEdited === 'total' || (!lastEdited && totalField.value)) {
            const totalCost = parseFloat(totalField.value);
            if (!isNaN(totalCost)) {
                perCoinField.value = formatPrice(totalCost / quantity);
            }
        }
    });
}

document.addEventListener('turbo:load', initPriceSync);
// After invalid submissions, need this to reload the JS
document.documentElement.addEventListener('turbo:render', initPriceSync);
document.addEventListener('DOMContentLoaded', initPriceSync);
