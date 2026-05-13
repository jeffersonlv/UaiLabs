<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);

        $stations = Station::orderBy('order')->orderBy('name')->get();

        return view('stations.index', compact('stations'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);

        $data = $request->validate([
            'name'  => 'required|string|max:60',
            'color' => 'required|string|size:7|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        Station::create($data + [
            'company_id' => auth()->user()->company_id,
            'order'      => Station::max('order') + 1,
        ]);

        return back()->with('success', 'Estação criada.');
    }

    public function update(Request $request, Station $station)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);

        $data = $request->validate([
            'name'   => 'required|string|max:60',
            'color'  => 'required|string|size:7|regex:/^#[0-9a-fA-F]{6}$/',
            'active' => 'boolean',
        ]);

        $station->update($data);

        return back()->with('success', 'Estação atualizada.');
    }

    public function destroy(Station $station)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);

        $station->delete();

        return back()->with('success', 'Estação removida.');
    }

    public function reorder(Request $request)
    {
        abort_unless(auth()->user()->isManagerOrAbove(), 403);

        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        foreach ($request->ids as $order => $id) {
            Station::where('id', $id)->where('company_id', auth()->user()->company_id)
                ->update(['order' => $order]);
        }

        return response()->json(['ok' => true]);
    }
}
