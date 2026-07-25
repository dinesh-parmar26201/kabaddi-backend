<?php

namespace App\Services\Tournament;

use App\Enums\MatchStatus;
use Exception;
use App\Models\Tournament;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TournamentService implements TournamentServiceInterface
{
    public function store(StoreTournamentRequest $request)
    {
        try {
            if (isset($request->banner) && $request->hasFile('banner')) {
                $path = $request->file('banner')->store('images/TournamentBanners', 'public');
                if (!$path) {
                    throw new Exception('Banner upload failed');
                }
            }

            if (isset($request->qr_code) && $request->hasFile('qr_code')) {
                $qr_code_path = $request->file('qr_code')->store('images/TournamentBanners/QR', 'public');
                if (!$qr_code_path) {
                    throw new Exception('QR code upload failed');
                }
            }

            $tournament = Tournament::create([
                'name' => $request->input('name'),
                'gender' => $request->input('gender'),
                'type' => $request->input('type'),
                'age_group' => $request->input('age_group'),
                'ground' => $request->input('ground'),
                'ground_type' => $request->input('ground_type'),
                'organizer_name' => $request->input('organizer_name'),
                'organizer_phone' => $request->input('organizer_phone'),
                'banner' => $path ?? null,
                'organizer_email' => $request->input('organizer_email'),
                'city' => $request->input('city'),
                'country' => $request->input('country'),
                'state' => $request->input('state'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'category' => $request->input('category'),
                'status' => $request->input('status'),
                'created_by' => $request->user()->id,
                'qr_code' => $qr_code_path ?? null,
            ]);
            return $tournament;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function update(int $id, UpdateTournamentRequest $request)
    {
        try {
            $tournament = Tournament::findOrFail($id);

            if (isset($request->banner) && $request->hasFile('banner')) {
                // Upload new banner
                $path = $request->file('banner')->store('images/TournamentBanners', 'public');

                if (!$path) {
                    throw new Exception('Banner upload failed');
                }
                // Delete old banner if exists
                if ($tournament->banner) {
                    Storage::disk('public')->delete($tournament->banner);
                }

                $tournament->banner = $path;
            }

            if (isset($request->qr_code) && $request->hasFile('qr_code')) {
                // Upload new qr_code
                $qr_code_path = $request->file('qr_code')->store('images/TournamentBanners/QR', 'public');

                if (!$qr_code_path) {
                    throw new Exception('QR code upload failed');
                }
                // Delete old qr_code if exists
                if ($tournament->qr_code) {
                    Storage::disk('public')->delete($tournament->qr_code);
                }

                $tournament->qr_code = $qr_code_path;
            }

            $tournament->update([
                'name' => $request->input('name'),
                'gender' => $request->input('gender'),
                'type' => $request->input('type'),
                'age_group' => $request->input('age_group'),
                'ground' => $request->input('ground'),
                'ground_type' => $request->input('ground_type'),
                'organizer_name' => $request->input('organizer_name'),
                'organizer_phone' => $request->input('organizer_phone'),
                'organizer_email' => $request->input('organizer_email'),
                'banner' => $tournament->banner,
                'city' => $request->input('city'),
                'country' => $request->input('country'),
                'state' => $request->input('state'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'category' => $request->input('category'),
                'status' => $request->input('status'),
                'qr_code' => $tournament->qr_code,
            ]);
            return $tournament;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function delete(int $id): void
    {
        Tournament::findOrFail($id)->delete();
    }

    public function find(int $id)
    {
        return Tournament::findOrFail($id);
    }

    public function list(?string $search = null): LengthAwarePaginator
    {
        if (request()->get('all', false)) {
            $query = Tournament::query();
        } else {
            $query = Tournament::where('created_by', Auth::id());
        }

        if (strtolower(request()->get('status')) == 'live') {
            $query->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now());
        } else if (strtolower(request()->get('status')) == 'upcoming') {
            $query->whereDate('start_date', '>', now());
        } else if (strtolower(request()->get('status')) == 'completed') {
            $query->whereDate('end_date', '<', now());
        }

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $perPage = (int) request()->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $data = $query->latest()->paginate($perPage);

        $statusFilter = strtolower(request()->get('status'));
        if (in_array($statusFilter, ['live', 'upcoming', 'completed'])) {
            foreach ($data as $tournament) {
                if ($tournament->status !== $statusFilter) {
                    $tournament->update(['status' => $statusFilter]);
                }
            }
        }

        return $data;
    }

    public function addTeams(int $tournamentId, array $teamIds)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        $tournament->teams()->syncWithoutDetaching($teamIds);

        return $tournament;
    }

    public function getTeams(int $tournamentId, int $perPage): LengthAwarePaginator
    {
        $tournament = Tournament::findOrFail($tournamentId);

        $perPage = (int) request()->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;
        
        $teams = $tournament->teams()->paginate($perPage);

        return $teams;
    }

    public function getMatches(int $tournamentId, int $perPage): LengthAwarePaginator
    {
        $tournament = Tournament::findOrFail($tournamentId);

        if (strtolower(request()->get('status')) == 'live') {
            $matches = $tournament->matches()->whereIn('status', MatchStatus::isLive());
        } else if (strtolower(request()->get('status')) == MatchStatus::UPCOMING->value) {
            $matches = $tournament->matches()->where('status', MatchStatus::UPCOMING->value);
        } else if (strtolower(request()->get('status')) == MatchStatus::COMPLETED->value) {
            $matches = $tournament->matches()->where('status', MatchStatus::COMPLETED->value);
        } else {
            $matches = $tournament->matches();
        }

        $perPage = (int) request()->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        return $matches->latest()->paginate($perPage);
    }

    public function removeTeam(int $tournamentId, int $teamId): void
    {
        $tournament = Tournament::findOrFail($tournamentId);
        $tournament->teams()->detach($teamId);
    }
}
