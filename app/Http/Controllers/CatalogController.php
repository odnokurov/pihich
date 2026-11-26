<?php

namespace App\Http\Controllers;

use App\Models\Gym;
use App\Models\Plan;
use App\Models\Trainer;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home()
    {
        return view('index', [
            'gyms' => Gym::where('is_active', true)->orderBy('id')->get(),
            'plans' => Plan::where('is_active', true)->where('kind', 'fixed')->orderBy('id')->limit(3)->get(),
            'trainers' => Trainer::where('is_active', true)->whereHas('activeGyms')
                ->with(['user', 'activeGyms'])->orderBy('id')->limit(3)->get(),
        ]);
    }

    public function gyms(Request $request)
    {
        $gyms = Gym::where('is_active', true)->orderBy('id')->get();

        return view('gyms', [
            'gyms' => $gyms,
            'selectedGym' => $gyms->firstWhere('id', $request->integer('gym')) ?? $gyms->first(),
        ]);
    }

    public function trainers(Request $request)
    {
        $gyms = Gym::where('is_active', true)->orderBy('id')->get();
        $gym = $gyms->firstWhere('id', $request->integer('gym'));

        return view('coaches', [
            'gyms' => $gyms,
            'selectedGym' => $gym,
            'trainers' => Trainer::where('is_active', true)->with(['user', 'activeGyms'])
                ->whereHas('activeGyms', fn ($query) => $query->when($gym, fn ($query) => $query->whereKey($gym->id)))
                ->orderBy('id')->get(),
        ]);
    }

    public function plans(Request $request)
    {
        $gyms = Gym::where('is_active', true)->orderBy('id')->get();
        $plans = Plan::where('is_active', true)->orderBy('id')->get();

        return view('subscription', [
            'gyms' => $gyms, 'plans' => $plans,
            'selectedGym' => $gyms->firstWhere('id', (int) old('gym_id', $request->integer('gym_id'))) ?? $gyms->first(),
            'selectedPlan' => $plans->firstWhere('id', (int) old('plan_id', $request->integer('plan_id'))) ?? $plans->first(),
            'months' => max(1, min(12, (int) old('duration_months', $request->integer('duration_months', 1)))),
            'visits' => max(1, min(24, (int) old('visits_per_month', $request->integer('visits_per_month', 4)))),
        ]);
    }

    public function continueToLogin(Request $request)
    {
        $data = $request->validate([
            'gym_id' => 'required|integer|min:1', 'plan_id' => 'required|integer|min:1',
            'duration_months' => 'nullable|integer|between:1,12',
            'visits_per_month' => 'nullable|integer|between:1,24',
        ]);
        $request->session()->put('flexclub.plan_selection', $data);

        return redirect()->route('login');
    }
}
