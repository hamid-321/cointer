function initPriceSync() {
    const form = document.querySelector('[data-price-sync]');
    if (!form) return;

    const quantityField = document.getElementById(form.dataset.quantityId);
    const perCoinField = document.getElementById(form.dataset.perCoinId);
    const totalField = document.getElementById(form.dataset.totalId);

    if (!quantityField || !perCoinField || !totalField) return;

    let lastEdited = null;

    perCoinField.addEventListener('input', function () {
        lastEdited = 'perCoin';
        const quantity = parseFloat(quantityField.value);
        const pricePerCoin = parseFloat(perCoinField.value);
        if (!isNaN(quantity) && !isNaN(pricePerCoin) && quantity > 0) {
            totalField.value = (quantity * pricePerCoin).toFixed(2);
        }
    });

    totalField.addEventListener('input', function () {
        lastEdited = 'total';
        const quantity = parseFloat(quantityField.value);
        const totalCost = parseFloat(totalField.value);
        if (!isNaN(quantity) && !isNaN(totalCost) && quantity > 0) {
            perCoinField.value = (totalCost / quantity).toFixed(2);
        }
    });

    qtyField.addEventListener('input', function () {
        const quantity = parseFloat(quantityField.value);
        if (isNaN(quantity) || quantity <= 0) return;

        if (lastEdited === 'perCoin' || (!lastEdited && perCoinField.value)) {
            const pricePerCoin = parseFloat(perCoinField.value);
            if (!isNaN(pricePerCoin)) {
                totalField.value = (quantity * pricePerCoin).toFixed(2);
            }
        } else if (lastEdited === 'total' || (!lastEdited && totalField.value)) {
            const totalCost = parseFloat(totalField.value);
            if (!isNaN(totalCost)) {
                perCoinField.value = (totalCost / quantity).toFixed(2);
            }
        }
    });
}

document.addEventListener('turbo:load', initPriceSync);
document.addEventListener('DOMContentLoaded', initPriceSync);
