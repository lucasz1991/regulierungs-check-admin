import gsap from 'gsap';

/**
 * Alpine-Komponente der Adminstartseite.
 *
 * Zeichnet die beiden ApexCharts und laesst Kacheln sowie Kennzahlen einmalig
 * einlaufen. Die Charts liegen hinter wire:ignore, damit Livewire sie bei
 * einem Re-Render nicht aus dem DOM morpht.
 */
export default function adminDashboard(payload) {
    return {
        timelineChart: null,
        statusChart: null,

        init() {
            this.$nextTick(() => {
                this.renderCharts(payload);
                this.animate();
            });

            // Nach "Aktualisieren" die Diagramme mit den neuen Werten fuellen.
            this.$wire?.on?.('dashboard-refreshed', () => {
                this.$nextTick(() => this.updateCharts());
            });
        },

        // ------------------------------------------------------------ Charts

        renderCharts(data) {
            if (typeof window.ApexCharts === 'undefined') {
                console.warn('ApexCharts ist nicht geladen, Diagramme werden übersprungen.');
                return;
            }

            const hasTimeline = (data.eingang || []).some((value) => value > 0)
                || (data.veroeffentlicht || []).some((value) => value > 0);

            if (this.$refs.timelineChart && hasTimeline) {
                this.timelineChart = new window.ApexCharts(this.$refs.timelineChart, {
                    chart: {
                        type: 'area',
                        height: 280,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                        animations: { enabled: !this.prefersReducedMotion() },
                    },
                    series: [
                        { name: 'Eingegangen', data: data.eingang || [] },
                        { name: 'Veröffentlicht', data: data.veroeffentlicht || [] },
                    ],
                    colors: ['#0b5879', '#0c968e'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2.5 },
                    fill: {
                        type: 'gradient',
                        gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90, 100] },
                    },
                    xaxis: {
                        categories: data.labels || [],
                        axisBorder: { show: false },
                        axisTicks: { show: false },
                        labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
                    },
                    yaxis: { labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
                    grid: { borderColor: '#eef2f6', strokeDashArray: 4 },
                    legend: { position: 'top', horizontalAlign: 'right', markers: { radius: 12 } },
                    tooltip: { theme: 'light' },
                });
                this.timelineChart.render();
            }

            const status = data.status || [];

            if (this.$refs.statusChart && status.length > 0) {
                this.statusChart = new window.ApexCharts(this.$refs.statusChart, {
                    chart: {
                        type: 'donut',
                        height: 240,
                        fontFamily: 'inherit',
                        animations: { enabled: !this.prefersReducedMotion() },
                    },
                    series: status.map((entry) => entry.count),
                    labels: status.map((entry) => entry.label),
                    colors: status.map((entry) => entry.color),
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    stroke: { width: 0 },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '72%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Gesamt',
                                        fontSize: '12px',
                                        color: '#64748b',
                                        formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('de-DE'),
                                    },
                                },
                            },
                        },
                    },
                    tooltip: { theme: 'light' },
                });
                this.statusChart.render();
            }
        },

        updateCharts() {
            const fresh = JSON.parse(this.$el.getAttribute('data-chart-payload') || 'null');

            if (!fresh) return;

            this.timelineChart?.updateOptions({
                series: [
                    { name: 'Eingegangen', data: fresh.eingang || [] },
                    { name: 'Veröffentlicht', data: fresh.veroeffentlicht || [] },
                ],
                xaxis: { categories: fresh.labels || [] },
            });

            const status = fresh.status || [];
            this.statusChart?.updateOptions({
                series: status.map((entry) => entry.count),
                labels: status.map((entry) => entry.label),
                colors: status.map((entry) => entry.color),
            });
        },

        // --------------------------------------------------------- Animation

        prefersReducedMotion() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },

        animate() {
            const mm = gsap.matchMedia();

            mm.add(
                { reduceMotion: '(prefers-reduced-motion: reduce)', fullMotion: '(prefers-reduced-motion: no-preference)' },
                (context) => {
                    const { reduceMotion } = context.conditions;

                    // Bei reduzierter Bewegung nur einblenden, nicht verschieben.
                    gsap.from(this.$el.querySelectorAll('[data-dash="head"], [data-dash="tile"]'), {
                        autoAlpha: 0,
                        y: reduceMotion ? 0 : 18,
                        duration: reduceMotion ? 0.01 : 0.5,
                        ease: 'power2.out',
                        stagger: reduceMotion ? 0 : 0.06,
                    });

                    this.countUp(reduceMotion);
                }
            );
        },

        /** Zaehlt die Kennzahlen von 0 auf ihren Wert hoch. */
        countUp(reduceMotion) {
            this.$el.querySelectorAll('[data-countup]').forEach((el) => {
                const target = parseFloat(el.dataset.countup || '0');
                const decimals = parseInt(el.dataset.decimals || '0', 10);

                if (!Number.isFinite(target)) return;

                if (reduceMotion) {
                    el.textContent = this.format(target, decimals);
                    return;
                }

                const state = { value: 0 };

                gsap.to(state, {
                    value: target,
                    duration: 0.9,
                    ease: 'power2.out',
                    onUpdate: () => {
                        el.textContent = this.format(state.value, decimals);
                    },
                });
            });
        },

        format(value, decimals) {
            return value.toLocaleString('de-DE', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals,
            });
        },
    };
}
