import { Controller } from '@hotwired/stimulus';


export default class extends Controller
{
    static targets = ['display'];
    static values =
    {
        url: String,
        excludeTransactionId: { type: String, default: '' },
    };

    connect()
    {
        if (!this.urlValue) return;
        // If a coin is already selected show its balance
        const select = this.element.querySelector('select');
        if (select && select.value)
        {
            this.updateBalance({ target: select });
        }
    }

    async updateBalance(event)
    {
        if (!this.urlValue) return;
        const coinId = event.target.value;
        if (!coinId)
        {
            this.displayTarget.textContent = 'Select a coin to see current owned quantity';
            return;
        }

        try
        {
            let url = `${this.urlValue}?coinId=${encodeURIComponent(coinId)}`;
            if (this.excludeTransactionIdValue)
            {
                url += `&excludeTransactionId=${encodeURIComponent(this.excludeTransactionIdValue)}`;
            }
            const response = await fetch(url);
            const data = await response.json();
            const balance = data.balance ?? 0;
            this.displayTarget.textContent = this.formatQuantity(parseFloat(balance));
        }
        catch (e)
        {
            this.displayTarget.textContent = '0';
        }
    }


    formatQuantity(num)
    {
        if (Number.isNaN(num)) return '0';
        if (Number.isInteger(num) || num === Math.floor(num)) return String(Math.floor(num));
        const s = num.toFixed(8).replace(/\.?0+$/, '');
        return s || '0';
    }
}

