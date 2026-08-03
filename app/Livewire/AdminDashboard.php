<?php

namespace App\Livewire;

use App\Models\ClaimRating;
use App\Models\Insurance;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * Adminstartseite.
 *
 * Zeigt den Zustand des Bewertungsbetriebs: Eingang, Moderationsstau,
 * Regulierungsdauer und Qualitaet der Anbieter. Alle Kennzahlen kommen aus
 * den echten Bewertungsdaten, es gibt keine Platzhalter.
 */
class AdminDashboard extends Component
{
    /** Fenster fuer die Zeitreihen. */
    private const MONTHS = 12;

    /** Ab so vielen Bewertungen taucht ein Anbieter in der Rangliste auf. */
    private const MIN_RATINGS_FOR_RANKING = 3;

    public array $kpis = [];

    /** @var array{labels: list<string>, eingang: list<int>, veroeffentlicht: list<int>} */
    public array $timeline = [];

    /** @var list<array{label: string, count: int, color: string}> */
    public array $statusBreakdown = [];

    /** @var list<array<string, mixed>> */
    public array $topInsurances = [];

    /** @var list<array<string, mixed>> */
    public array $moderationQueue = [];

    public function mount(): void
    {
        $this->loadDashboard();
    }

    /** Kennzahlen neu berechnen, ohne die Seite zu wechseln. */
    public function refreshDashboard(): void
    {
        $this->loadDashboard();
        $this->dispatch('dashboard-refreshed');
    }

    private function loadDashboard(): void
    {
        $this->kpis = $this->buildKpis();
        $this->timeline = $this->buildTimeline();
        $this->statusBreakdown = $this->buildStatusBreakdown();
        $this->topInsurances = $this->buildTopInsurances();
        $this->moderationQueue = $this->buildModerationQueue();
    }

    // ------------------------------------------------------------ Kennzahlen

    private function buildKpis(): array
    {
        $now = Carbon::now();
        $thisMonth = ClaimRating::whereBetween('created_at', [$now->copy()->startOfMonth(), $now])->count();
        $lastMonth = ClaimRating::whereBetween('created_at', [
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->subMonthNoOverflow()->endOfMonth(),
        ])->count();

        $openStatuses = [ClaimRating::STATUS_PENDING_VALIDATION, ClaimRating::STATUS_RATED];
        $open = ClaimRating::whereIn('status', $openStatuses)->count();
        $oldestOpen = ClaimRating::whereIn('status', $openStatuses)->min('created_at');

        $avgScore = ClaimRating::where('status', ClaimRating::STATUS_PUBLISHED)
            ->whereNotNull('rating_score')
            ->avg('rating_score');

        return [
            'ratings_total' => [
                'label' => 'Bewertungen gesamt',
                'value' => ClaimRating::count(),
                'suffix' => '',
                'hint' => $thisMonth.' in diesem Monat',
                'trend' => $this->trend($thisMonth, $lastMonth),
                'icon' => 'clipboard',
                'alert' => false,
            ],
            'moderation' => [
                'label' => 'Wartet auf Moderation',
                'value' => $open,
                'suffix' => '',
                'hint' => $oldestOpen
                    ? 'ältester Fall seit '.Carbon::parse($oldestOpen)->diffInDays($now).' Tagen'
                    : 'nichts offen',
                'trend' => null,
                'icon' => 'inbox',
                'alert' => $open > 0,
            ],
            'avg_duration' => [
                'label' => 'Ø Regulierungsdauer',
                'value' => $this->averageRegulationDays(),
                'suffix' => ' Tage',
                'hint' => 'aus veröffentlichten Fällen',
                'trend' => null,
                'icon' => 'clock',
                'alert' => false,
            ],
            'avg_score' => [
                'label' => 'Ø Bewertung',
                'value' => $avgScore ? round((float) $avgScore, 2) : 0,
                'suffix' => ' / 5',
                'hint' => ClaimRating::where('status', ClaimRating::STATUS_PUBLISHED)->count().' veröffentlicht',
                'trend' => null,
                'icon' => 'star',
                'alert' => false,
            ],
        ];
    }

    /**
     * Die Dauer steckt im JSON-Feld `answers` und laesst sich nicht in SQL
     * mitteln. Deshalb nur die veroeffentlichten Faelle des Zeitfensters laden
     * und ausschliesslich die benoetigten Spalten selektieren.
     */
    private function averageRegulationDays(): float
    {
        $durations = ClaimRating::query()
            ->where('status', ClaimRating::STATUS_PUBLISHED)
            ->where('created_at', '>=', Carbon::now()->subMonths(self::MONTHS)->startOfMonth())
            ->get(['id', 'answers'])
            ->map(fn (ClaimRating $rating): ?int => $rating->ratingDuration())
            ->filter(fn (?int $days): bool => $days !== null && $days >= 0);

        return $durations->isEmpty() ? 0.0 : round((float) $durations->avg(), 1);
    }

    private function trend(int $current, int $previous): ?array
    {
        if ($previous === 0) {
            return $current > 0 ? ['direction' => 'up', 'percent' => 100] : null;
        }

        $change = (int) round((($current - $previous) / $previous) * 100);

        return ['direction' => $change >= 0 ? 'up' : 'down', 'percent' => abs($change)];
    }

    // -------------------------------------------------------------- Diagramme

    /** Eingang und Veroeffentlichungen der letzten zwoelf Monate. */
    private function buildTimeline(): array
    {
        $start = Carbon::now()->subMonths(self::MONTHS - 1)->startOfMonth();

        $created = $this->monthlyCounts(ClaimRating::query(), $start);
        $published = $this->monthlyCounts(
            ClaimRating::query()->where('status', ClaimRating::STATUS_PUBLISHED),
            $start
        );

        $labels = [];
        $eingang = [];
        $veroeffentlicht = [];

        for ($i = 0; $i < self::MONTHS; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');

            $labels[] = $month->translatedFormat('M y');
            $eingang[] = (int) ($created[$key] ?? 0);
            $veroeffentlicht[] = (int) ($published[$key] ?? 0);
        }

        return ['labels' => $labels, 'eingang' => $eingang, 'veroeffentlicht' => $veroeffentlicht];
    }

    /** @return array<string,int> */
    private function monthlyCounts($query, Carbon $start): array
    {
        return $query
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total")
            ->groupBy('ym')
            ->pluck('total', 'ym')
            ->all();
    }

    private function buildStatusBreakdown(): array
    {
        $counts = ClaimRating::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $map = [
            ClaimRating::STATUS_PUBLISHED => ['Veröffentlicht', '#0c968e'],
            ClaimRating::STATUS_APPROVED => ['Freigegeben', '#1488b9'],
            ClaimRating::STATUS_RATED => ['Bewertet', '#f6c238'],
            ClaimRating::STATUS_PENDING_VALIDATION => ['In Prüfung', '#f59e0b'],
            ClaimRating::STATUS_PENDING => ['Offen', '#94a3b8'],
            ClaimRating::STATUS_REJECTED => ['Abgelehnt', '#ef4444'],
        ];

        $result = [];

        foreach ($map as $status => [$label, $color]) {
            $count = (int) ($counts[$status] ?? 0);

            if ($count > 0) {
                $result[] = ['label' => $label, 'count' => $count, 'color' => $color];
            }
        }

        return $result;
    }

    // --------------------------------------------------------------- Tabellen

    /** Anbieter mit der besten Durchschnittsbewertung. */
    private function buildTopInsurances(): array
    {
        return ClaimRating::query()
            ->select('insurance_id', DB::raw('COUNT(*) as total'), DB::raw('AVG(rating_score) as avg_score'))
            ->where('status', ClaimRating::STATUS_PUBLISHED)
            ->whereNotNull('insurance_id')
            ->groupBy('insurance_id')
            ->havingRaw('COUNT(*) >= ?', [self::MIN_RATINGS_FOR_RANKING])
            ->orderByDesc('avg_score')
            ->limit(6)
            ->get()
            ->pipe(function (Collection $rows): Collection {
                $names = Insurance::whereIn('id', $rows->pluck('insurance_id'))->pluck('name', 'id');

                return $rows->map(fn ($row): array => [
                    'id' => (int) $row->insurance_id,
                    'name' => (string) ($names[$row->insurance_id] ?? 'Unbekannt'),
                    'total' => (int) $row->total,
                    'score' => round((float) $row->avg_score, 2),
                ]);
            })
            ->values()
            ->all();
    }

    /** Was als naechstes moderiert werden muss - aelteste zuerst. */
    private function buildModerationQueue(): array
    {
        $labels = [
            ClaimRating::STATUS_PENDING_VALIDATION => ['In Prüfung', 'bg-amber-50 text-amber-800 ring-amber-200'],
            ClaimRating::STATUS_RATED => ['Bewertet', 'bg-yellow-50 text-yellow-800 ring-yellow-200'],
            ClaimRating::STATUS_APPROVED => ['Freigegeben', 'bg-sky-50 text-sky-800 ring-sky-200'],
        ];

        return ClaimRating::query()
            ->with(['insurance:id,name'])
            ->whereIn('status', array_keys($labels))
            ->orderBy('created_at')
            ->limit(7)
            ->get(['id', 'insurance_id', 'status', 'rating_score', 'created_at'])
            ->map(fn (ClaimRating $rating): array => [
                'id' => $rating->id,
                'insurance' => $rating->insurance->name ?? 'Ohne Anbieter',
                'status' => $labels[$rating->status][0] ?? $rating->status,
                'badge' => $labels[$rating->status][1] ?? 'bg-gray-50 text-gray-700 ring-gray-200',
                'score' => $rating->rating_score !== null ? round((float) $rating->rating_score, 1) : null,
                'wartetSeit' => Carbon::parse($rating->created_at)->diffInDays(Carbon::now()),
            ])
            ->all();
    }

    public function render()
    {
        return view('livewire.admin-dashboard', [
            'redaktion' => [
                'newsVeroeffentlicht' => Post::where('type', 'news')->where('published', true)->count(),
                'newsEntwuerfe' => Post::where('type', 'news')->where('published', false)->count(),
                'anbieter' => Insurance::count(),
                'benutzer' => User::count(),
            ],
        ])->layout('layouts.master');
    }
}
