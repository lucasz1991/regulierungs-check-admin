/*
 * ApexCharts und GSAP werden erst beim Mounten des Dashboards nachgeladen.
 * Als statische Importe landeten sie im Haupt-Bundle und damit auf jeder
 * Adminseite - rund 650 kB, die dort niemand braucht.
 */

/**
 * Alpine-Komponente der Adminstartseite.
 *
 * Zeichnet die beiden Diagramme und animiert den Einstieg. Die Charts liegen
 * hinter wire:ignore, damit Livewire sie beim Neurendern nicht anfasst; neue
 * Werte kommen ueber das Attribut data-chart-payload am Wurzelelement herein.
 */
const BRAND = {
    primary: '#0b5879',
    secondary: '#0c968e',
    grid: '#eef2f6',
    text: '#64748b',
};

const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const numberFormat = (value, decimals) =>
    new Intl.NumberFormat('de-DE', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    }).format(value);

export default function adminDashboard(initialPayload) {
    return {
        payload: initialPayload,
        timelineChart: null,
        statusChart: null,
        ApexCharts: null,
        gsap: null,

        async init() {
            const [{ default: ApexCharts }, { gsap }] = await Promise.all([
                import('apexcharts'),
                import('gsap'),
            ]);

            this.ApexCharts = ApexCharts;
            this.gsap = gsap;

            this.renderCharts();
            this.animateEntrance();

            // Nach einem Livewire-Update die frischen Werte nachziehen.
            this.$el.addEventListener('dashboard-refreshed', () => this.applyPayload());
            document.addEventListener('livewire:navigated', () => this.destroyCharts(), { once: true });
        },

        /** Liest die aktualisierten Werte aus dem Attribut am Wurzelelement. */
        applyPayload() {
            const raw = this.$el.dataset.chartPayload;

            if (! raw) return;

            try {
                this.payload = JSON.parse(raw);
            } catch (error) {
                return;
            }

            this.timelineChart?.updateOptions(this.timelineOptions(), false, true);
            this.statusChart?.updateOptions(this.statusOptions(), false, true);
            this.animateCounters();
        },

        // ------------------------------------------------------------ Charts

        renderCharts() {
            if (this.$refs.timelineChart) {
                this.timelineChart = new this.ApexCharts(this.$refs.timelineChart, this.timelineOptions());
                this.timelineChart.render();
            }

            if (this.$refs.statusChart) {
                this.statusChart = new this.ApexCharts(this.$refs.statusChart, this.statusOptions());
                this.statusChart.render();
            }
        },

        destroyCharts() {
            this.timelineChart?.destroy();
            this.statusChart?.destroy();
            this.timelineChart = null;
            this.statusChart = null;
        },

        timelineOptions() {
            return {
                chart: {
                    type: 'area',
                    height: 280,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    animations: { enabled: ! reduceMotion(), easing: 'easeout', speed: 700 },
                },
                series: [
                    { name: 'Eingegangen', data: this.payload.eingang ?? [] },
                    { name: 'Veröffentlicht', data: this.payload.veroeffentlicht ?? [] },
                ],
                colors: [BRAND.primary, BRAND.secondary],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2.5 },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90, 100] },
                },
                grid: { borderColor: BRAND.grid, strokeDashArray: 4, padding: { left: 4, right: 4 } },
                xaxis: {
                    categories: this.payload.labels ?? [],
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: BRAND.text, fontSize: '11px' } },
                },
                yaxis: {
                    labels: {
                        style: { colors: BRAND.text, fontSize: '11px' },
                        formatter: (value) => numberFormat(Math.round(value), 0),
                    },
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    markers: { radius: 12 },
                    fontSize: '12px',
                    labels: { colors: BRAND.text },
                },
                tooltip: { theme: 'light', y: { formatter: (value) => numberFormat(value, 0) } },
            };
        },

        statusOptions() {
            const entries = this.payload.status ?? [];

            return {
                chart: {
                    type: 'donut',
                    height: 240,
                    fontFamily: 'inherit',
                    animations: { enabled: ! reduceMotion() },
                },
                series: entries.map((entry) => entry.count),
                labels: entries.map((entry) => entry.label),
                colors: entries.map((entry) => entry.color),
                dataLabels: { enabled: false },
                legend: { show: false },
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
                                    fontSize: '11px',
                                    color: BRAND.text,
                                    formatter: (w) =>
                                        numberFormat(
                                            w.globals.seriesTotals.reduce((sum, value) => sum + value, 0),
                                            0
                                        ),
                                },
                            },
                        },
                    },
                },
                tooltip: { y: { formatter: (value) => numberFormat(value, 0) } },
            };
        },

        // --------------------------------------------------------- Animation

        animateEntrance() {
            const mm = this.gsap.matchMedia();

            mm.add(
                {
                    motion: '(prefers-reduced-motion: no-preference)',
                    reduced: '(prefers-reduced-motion: reduce)',
                },
                (context) => {
                    const { reduced } = context.conditions;

                    if (reduced) {
                        // Nur der Endzustand, keine Bewegung.
                        this.gsap.set('[data-dash="head"], [data-dash="tile"]', { autoAlpha: 1, y: 0 });
                        this.setCountersToTarget();

                        return;
                    }

                    this.gsap.from('[data-dash="head"]', {
                        autoAlpha: 0,
                        y: -12,
                        duration: 0.5,
                        ease: 'power2.out',
                    });

                    this.gsap.from('[data-dash="tile"]', {
                        autoAlpha: 0,
                        y: 18,
                        duration: 0.55,
                        ease: 'power3.out',
                        stagger: { each: 0.06, from: 'start' },
                        onComplete: () => this.animateCounters(),
                    });
                }
            );
        },

        /** Zaehlt die Kennzahlen von 0 auf ihren Zielwert hoch. */
        animateCounters() {
            if (! this.gsap || reduceMotion()) {
                this.setCountersToTarget();

                return;
            }

            this.$el.querySelectorAll('[data-countup]').forEach((el) => {
                const target = parseFloat(el.dataset.countup ?? '0');
                const decimals = parseInt(el.dataset.decimals ?? '0', 10);

                if (! Number.isFinite(target)) return;

                const state = { value: 0 };

                this.gsap.to(state, {
                    value: target,
                    duration: 0.9,
                    ease: 'power2.out',
                    onUpdate: () => {
                        el.textContent = numberFormat(state.value, decimals);
                    },
                    onComplete: () => {
                        el.textContent = numberFormat(target, decimals);
                    },
                });
            });
        },

        setCountersToTarget() {
            this.$el.querySelectorAll('[data-countup]').forEach((el) => {
                const target = parseFloat(el.dataset.countup ?? '0');
                const decimals = parseInt(el.dataset.decimals ?? '0', 10);

                if (Number.isFinite(target)) {
                    el.textContent = numberFormat(target, decimals);
                }
            });
        },
    };
}
