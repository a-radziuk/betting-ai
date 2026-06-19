document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    document.querySelectorAll('[data-admin-metrics-toggle]').forEach((checkbox) => {
        checkbox.addEventListener('change', async () => {
            const url = checkbox.dataset.url;
            const previousChecked = !checkbox.checked;

            checkbox.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        is_metrics_available: checkbox.checked,
                    }),
                });

                const body = await response.json();

                if (!response.ok) {
                    throw new Error(body.message ?? 'Unable to update metrics availability.');
                }

                checkbox.checked = Boolean(body.is_metrics_available);
            } catch {
                checkbox.checked = previousChecked;
            } finally {
                checkbox.disabled = false;
            }
        });
    });
});
