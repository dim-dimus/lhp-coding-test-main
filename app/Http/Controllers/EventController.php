<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventListResource;
use App\Models\Event;
use App\Support\Geocoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    private const STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];

    private const PER_PAGE = 24;

    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
            ],
            'statuses' => self::STATUSES,
        ]);
    }

    public function visualOne(Request $request): Response
    {
        return Inertia::render('Events/VisualOne', $this->visualProps($request));
    }

    public function visualTwo(Request $request): Response
    {
        return Inertia::render('Events/VisualTwo', $this->visualProps($request));
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->loadListing($request));
    }

    public function show(Event $event): Response
    {
        $event->load('user');

        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }

    /**
     * Shared props for the visual pages: filter options and current filters.
     *
     * @return array<string, mixed>
     */
    private function visualProps(Request $request): array
    {
        return [
            'filters' => [
                'status' => $request->input('status'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'city' => $request->input('city'),
            ],
            'statuses' => self::STATUSES,
            'cities' => Geocoder::cities(),
        ];
    }

    /**
     * Keyset-paginated, filtered listing. No COUNT(*) and no payload blob on the
     * wire — see CLAUDE.md (Approach A).
     *
     * @return array<string, mixed>
     */
    private function loadListing(Request $request): array
    {
        $start = microtime(true);
        $direction = $request->input('sort') === 'asc' ? 'asc' : 'desc';

        $query = Event::query()
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->filled('from'), function ($q) use ($request) {
                $ts = strtotime((string) $request->input('from'));
                if ($ts !== false) {
                    $q->where('created_time', '>=', $ts);
                }
            })
            ->when($request->filled('to'), function ($q) use ($request) {
                $ts = strtotime((string) $request->input('to').' 23:59:59');
                if ($ts !== false) {
                    $q->where('created_time', '<=', $ts);
                }
            })
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->input('city')))
            ->orderBy('created_time', $direction)
            ->orderBy('id', $direction);

        if ($request->filled('cursor')) {
            $cursor = $this->decodeCursor((string) $request->input('cursor'));
            if ($cursor !== null) {
                // Canonical keyset predicate. The explicit OR form lets MySQL use
                // the (created_time, id) index as a range seek; the row-value form
                // `(created_time, id) > (?, ?)` is not optimised and degrades to a
                // full ordered scan (O(offset)).
                $operator = $direction === 'asc' ? '>' : '<';
                $query->where(function ($q) use ($operator, $cursor) {
                    $q->where('created_time', $operator, $cursor[0])
                        ->orWhere(function ($q) use ($operator, $cursor) {
                            $q->where('created_time', $cursor[0])
                                ->where('id', $operator, $cursor[1]);
                        });
                });
            }
        }

        // Fetch one extra row to know whether another page exists, without COUNT.
        $rows = $query->limit(self::PER_PAGE + 1)->get();
        $hasMore = $rows->count() > self::PER_PAGE;
        $rows = $rows->take(self::PER_PAGE)->values();

        $last = $rows->last();
        $nextCursor = $hasMore && $last !== null
            ? $this->encodeCursor((int) $last->created_time, (string) $last->id)
            : null;

        $data = EventListResource::collection($rows)->resolve($request);

        return [
            'data' => $data,
            'next_cursor' => $nextCursor,
            'has_more' => $hasMore,
            'stats' => [
                'ms' => (int) round((microtime(true) - $start) * 1000),
                'bytes' => strlen((string) json_encode($data)),
            ],
        ];
    }

    private function encodeCursor(int $createdTime, string $id): string
    {
        return base64_encode($createdTime.'|'.$id);
    }

    /**
     * @return array{0: int, 1: string}|null
     */
    private function decodeCursor(string $cursor): ?array
    {
        $decoded = base64_decode($cursor, true);

        if ($decoded === false || ! str_contains($decoded, '|')) {
            return null;
        }

        [$createdTime, $id] = explode('|', $decoded, 2);

        if (! is_numeric($createdTime)) {
            return null;
        }

        return [(int) $createdTime, $id];
    }
}
