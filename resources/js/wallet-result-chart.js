const VIEWBOX_WIDTH = 100;
const VIEWBOX_HEIGHT = 40;

function normalizeWalletResultChartDots(svg) {
    if (! svg.classList.contains('user-results-chart--full')) {
        return;
    }

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

function bindChartTooltips(svg) {
    if (svg.dataset.chartTooltipsBound === 'true') {
        return;
    }

    svg.dataset.chartTooltipsBound = 'true';

    const tooltips = new Map();
    svg.querySelectorAll('.user-results-chart-tooltip[data-chart-point]').forEach((tooltip) => {
        tooltips.set(tooltip.dataset.chartPoint, tooltip);
    });

    let activePoint = null;
    let activeTooltip = null;

    const hide = () => {
        activePoint?.classList.remove('is-active');
        activeTooltip?.classList.remove('is-visible');
        activePoint = null;
        activeTooltip = null;
    };

    const show = (point) => {
        const index = point.dataset.chartPoint;
        const tooltip = tooltips.get(index);

        if (activePoint === point && activeTooltip === tooltip) {
            return;
        }

        hide();
        activePoint = point;
        activeTooltip = tooltip ?? null;
        activePoint.classList.add('is-active');
        activeTooltip?.classList.add('is-visible');
    };

    svg.querySelectorAll('.user-results-chart-point[data-chart-point]').forEach((point) => {
        point.addEventListener('mouseenter', () => show(point));
        point.addEventListener('mouseleave', hide);
        point.addEventListener('focus', () => show(point));
        point.addEventListener('blur', hide);
    });
}

function observeWalletResultChart(svg) {
    normalizeWalletResultChartDots(svg);
    bindChartTooltips(svg);

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
