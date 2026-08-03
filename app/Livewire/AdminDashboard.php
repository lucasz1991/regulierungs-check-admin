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
 * Massgeblich fuer "oeffentlich sichtbar" ist ClaimRating::publiclyVisible()
 * - also is_public = true UND status in (rated, published). Das ist dieselbe
 * Regel, nach der die Base-Installation die Bewertungen ausliefert. Wer nur
 * auf status = published filtert, sieht einen Bruchteil der tatsaechlich
 * veroeffentlichten Bewertungen.
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
    public array $latestPublished = [];

    public function mount(): void
    {
        $this->loadDashboard();
    }

    public function refreshDashboard(): void
    {
        $this->loadDashboard();
        $this->dispatch('dashboard-refreshed');
    }

    private function loadDashboard(): void
    {
        $this->kpis = $this->buildKpis();
        $this->timeline = $this->buildTimeline();
        $this->statusBreakdown = $this->buildVisibilityBreakdown();
        $this->topInsurances = $this->buildTopInsurances();
        $this->latestPublished = $this->buildLatestPublished();
    }

    // ------------------------------------------------------------ Kennzahlen

    private function buildKpis(): array
    {
        $now = Carbon::now();
        $total = ClaimRating::count();

        $thisMonth = ClaimRating::whereBetween('created_at', [$now->copy()->startOfMonth(), $now])->count();
        $lastMonth = ClaimRating::whereBetween('created_at', [
            $now->copy()->subMonthNoOverflow()->startOfMonth(),
            $now->copy()->subMonthNoOverflow()->endOfMonth(),
        ])->count();

        $visible = ClaimRating::publiclyVisible()->count();
        $share = $total > 0 ? (int) round($visible / $total * 100) : 0;

        $avgScore = ClaimRating::publiclyVisible()->whereNotNull('rating_score')->avg('rating_score');

        $ratedInsurances = ClaimRating::publiclyVisible()
            ->whereNotNull('insurance_id')
            ->distinct()
            ->count('insurance_id');

        return [
            'ratings_total' => [
                'label' => 'Bewertungen gesamt',
                'value' => $total,
                'suffix' => '',
                'hint' => $thisMonth.' in diesem Monat',
                'trend' => $this->trend($thisMonth, $lastMonth),
                'icon' => 'clipboard',
                'alert' => false,
            ],
            'visible' => [
                'label' => 'Öffentlich sichtbar',
                'value' => $visible,
                'suffix' => '',
                'hint' => $share.' % aller Bewertungen',
                'trend' => null,
                'icon' => 'eye',
                'alert' => false,
            ],
            'avg_score' => [
                'label' => 'Ø Bewertung',
                'value' => $avgScore ? round((float) $avgScore, 2) : 0,
                'suffix' => ' / 5',
                'hint' => 'nur sichtbare Bewertungen',
                'trend' => null,
                'icon' => 'star',
                'alert' => false,
            ],
            'insurances' => [
                'label' => 'Bewertete Anbieter',
                'value' => $ratedInsurances,
                'suffix' => '',
                'hint' => 'von '.Insurance::count().' insgesamt',
                'trend' => null,
                'icon' => 'building',
                'alert' => false,
            ],
        ];
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

    private function buildTimeline(): array
    {
        $start = Carbon::now()->subMonths(self::MONTHS - 1)->startOfMonth();

        $created = $this->monthlyCounts(ClaimRating::query(), $start);
        $visible = $this->monthlyCounts(ClaimRating::query()->publiclyVisible(), $start);

        $labels = [];
        $eingang = [];
        $veroeffentlicht = [];

        for ($i = 0; $i < self::MONTHS; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');

            $labels[] = $month->translatedFormat('M y');
            $eingang[] = (int) ($created[$key] ?? 0);
            $veroeffentlicht[] = (int) ($visible[$key] ?? 0);
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

    /**
     * Sichtbarkeit statt Rohstatus. Der Rohstatus allein sagt wenig, weil
     * sowohl `rated` als auch `published` oeffentlich sind und zusaetzlich
     * is_public greift.
     */
    private function buildVisibilityBreakdown(): array
    {
        $visible = ClaimRating::publiclyVisible()->count();

        $hiddenByStatus = ClaimRating::query()
            ->where(fn ($q) => $q->where('is_public', false)
                ->orWhereNotIn('status', ClaimRating::publicVisibleStatuses()))
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [
            ClaimRating::STATUS_PENDING_VALIDATION => ['In Prüfung', '#f59e0b'],
            ClaimRating::STATUS_PENDING => ['Offen', '#94a3b8'],
            ClaimRating::STATUS_APPROVED => ['Freigegeben, nicht sichtbar', '#1488b9'],
            ClaimRating::STATUS_REJECTED => ['Abgelehnt', '#ef4444'],
            ClaimRating::STATUS_RATED => ['Bewertet, nicht sichtbar', '#f6c238'],
            ClaimRating::STATUS_PUBLISHED => ['Veröffentlicht, nicht sichtbar', '#c084fc'],
        ];

        $result = [];

        if ($visible > 0) {
            $result[] = ['label' => 'Öffentlich sichtbar', 'count' => $visible, 'color' => '#0c968e'];
        }

        foreach ($labels as $status => [$label, $color]) {
            $count = (int) ($hiddenByStatus[$status] ?? 0);

            if ($count > 0) {
                $result[] = ['label' => $label, 'count' => $count, 'color' => $color];
            }
        }

        return $result;
    }

    // --------------------------------------------------------------- Tabellen

    private function buildTopInsurances(): array
    {
        return ClaimRating::query()
            ->publiclyVisible()
            ->select('insurance_id', DB::raw('COUNT(*) as total'), DB::raw('AVG(rating_score) as avg_score'))
            ->whereNotNull('insurance_id')
            ->whereNotNull('rating_score')
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

    /** Zuletzt oeffentlich gewordene Bewertungen. */
    private function buildLatestPublished(): array
    {
        return ClaimRating::query()
            ->publiclyVisible()
            ->with(['insurance:id,name'])
            ->latest('updated_at')
            ->limit(7)
            ->get(['id', 'insurance_id', 'status', 'rating_score', 'updated_at'])
            ->map(fn (ClaimRating $rating): array => [
                'id' => $rating->id,
                'insurance' => $rating->insurance->name ?? 'Ohne Anbieter',
                'score' => $rating->rating_score !== null ? round((float) $rating->rating_score, 1) : null,
                'seit' => Carbon::parse($rating->updated_at)->diffForHumans(),
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
