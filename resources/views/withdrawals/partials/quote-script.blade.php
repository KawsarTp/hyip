<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form[action="{{ route('withdrawals.store') }}"]');
        const amount = document.getElementById('amount');
        const currency = form?.querySelector('input[name="currency"]');
        const submit = form?.querySelector('button');

        if (!form || !amount || !currency || !submit) {
            return;
        }

        const summary = document.createElement('div');
        summary.className = 'rounded-2xl border border-[#e2e7ef] bg-[#f8f7ff] px-4 py-4 text-xs text-[#536078]';
        summary.textContent = 'Enter an amount to preview the fee, tax, and net payout.';
        form.insertBefore(summary, submit);

        let timer;
        const refreshQuote = async () => {
            const selectedMethod = form.querySelector('input[name="withdrawal_method_id"]:checked');
            if (!selectedMethod || amount.value.trim() === '') {
                summary.textContent = 'Enter an amount to preview the fee, tax, and net payout.';
                return;
            }

            const query = new URLSearchParams({
                amount: amount.value,
                currency: currency.value,
                withdrawal_method_id: selectedMethod.value,
            });
            summary.textContent = 'Calculating your locked quote…';

            try {
                const response = await fetch(`{{ route('withdrawals.quote') }}?${query}`);
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Quote unavailable.');
                }
                summary.innerHTML = `<div class="grid gap-2 sm:grid-cols-3"><span>Fee <strong>${data.currency} ${(data.fee_minor / 100).toFixed(2)}</strong></span><span>Tax <strong>${data.currency} ${(data.tax_minor / 100).toFixed(2)}</strong></span><span>Net payout <strong>${data.currency} ${(data.net_minor / 100).toFixed(2)}</strong></span></div>`;
            } catch (error) {
                summary.textContent = error.message;
            }
        };

        amount.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(refreshQuote, 300);
        });
        form.querySelectorAll('input[name="withdrawal_method_id"]').forEach((method) => method.addEventListener('change', refreshQuote));
    });
</script>
