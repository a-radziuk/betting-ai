const VIEWBOX_WIDTH = 100;
const VIEWBOX_HEIGHT = 40;

function normalizeWalletResultChartDots(svg) {
    const rect = svg.getBoundingClientRect();
    if (rect.width <= 0 || rect.height <= 0) {
        return;
    }

    const scaleX = rect.width / VIEWBOX_WIDTH;
    const scaleY = rect.height / VIEWBOX_HEIGHT;
    const uniformScale = Math.min(scaleX, scaleY);

    svg.querySelectorAll('[data-chart-radius]').forEach((element) => {
        const baseRadius = Number.parseFloat(element.dataset.chartRadius);
        if (!Number.isFinite(baseRadius)) {
            return;
        }

        const pixelRadius = baseRadius * uniformScale;
        element.setAttribute('rx', String(pixelRadius / scaleX));
        element.setAttribute('ry', String(pixelRadius / scaleY));
    });
}

function observeWalletResultChart(svg) {
    normalizeWalletResultChartDots(svg);

    if (svg.dataset.chartDotsObserved === 'true') {
        return;
    }

    svg.dataset.chartDotsObserved = 'true';

    if (typeof ResizeObserver === 'undefined') {
        return;
    }

    const observer = new ResizeObserver(() => normalizeWalletResultChartDots(svg));
    observer.observe(svg);
}

export function initWalletResultCharts() {
    document.querySelectorAll('svg.user-results-chart').forEach(observeWalletResultChart);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWalletResultCharts);
} else {
    initWalletResultCharts();
}
