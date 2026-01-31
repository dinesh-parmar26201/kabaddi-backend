<?php

namespace App\Services\Tournament;

use Exception;
use App\Models\Tournament;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;

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

            $tournament = Tournament::create([
                'name' => $request->input('name'),
                'ground' => $request->input('ground'),
                'organizer_name' => $request->input('organizer_name'),
                'organizer_phone' => $request->input('organizer_phone'),
                'banner' => $path ?? null,
                'organizer_email' => $request->input('organizer_email'),
                'city' => $request->input('city'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'category' => $request->input('category'),
                'status' => $request->input('status'),
                'created_by' => $request->user()->id,
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
            $tournament->update([
                'name' => $request->input('name'),
                'ground' => $request->input('ground'),
                'organizer_name' => $request->input('organizer_name'),
                'organizer_phone' => $request->input('organizer_phone'),
                'organizer_email' => $request->input('organizer_email'),
                'banner' => $tournament->banner,
                'city' => $request->input('city'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'category' => $request->input('category'),
                'status' => $request->input('status'),
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

    public function list()
    {
        return Tournament::latest()->get();
    }

    public function addTeams(int $tournamentId, array $teamIds)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        $tournament->teams()->syncWithoutDetaching($teamIds);

        return $tournament;
    }

    public function getTeams(int $tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        return $tournament->teams;
    }

    public function getMatches(int $tournamentId)
    {
        $tournament = Tournament::findOrFail($tournamentId);

        return $tournament->matches;
    }
}
