<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberDue;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index()
    {
        $today = now();
        $chartStart = $today->copy()->subMonths(5)->startOfMonth();

        $totalMembers = User::count();
        $verifiedMembers = User::whereNotNull('email_verified_at')->count();
        $unverifiedMembers = max($totalMembers - $verifiedMembers, 0);
        $verificationRate = $totalMembers > 0
            ? (int) round(($verifiedMembers / $totalMembers) * 100)
            : 0;

        $newMembersThisMonth = User::whereBetween('created_at', [
            $today->copy()->startOfMonth(),
            $today->copy()->endOfMonth(),
        ])->count();

        $newMembersLastMonth = User::whereBetween('created_at', [
            $today->copy()->subMonthNoOverflow()->startOfMonth(),
            $today->copy()->subMonthNoOverflow()->endOfMonth(),
        ])->count();

        if ($newMembersLastMonth > 0) {
            $monthlyGrowth = (int) round(
                (($newMembersThisMonth - $newMembersLastMonth) / $newMembersLastMonth) * 100
            );
        } else {
            $monthlyGrowth = $newMembersThisMonth > 0 ? 100 : 0;
        }

        $registrationsByMonth = User::whereNotNull('created_at')
            ->where('created_at', '>=', $chartStart)
            ->get(['created_at'])
            ->groupBy(function ($member) {
                return $member->created_at->format('Y-m');
            })
            ->map(function ($members) {
                return $members->count();
            });

        $growthLabels = [];
        $growthValues = [];

        for ($monthOffset = 0; $monthOffset < 6; $monthOffset++) {
            $month = $chartStart->copy()->addMonths($monthOffset);
            $growthLabels[] = $month->format('M Y');
            $growthValues[] = (int) $registrationsByMonth->get($month->format('Y-m'), 0);
        }

        $pendingDuesCount = null;
        $outstandingDues = null;

        if (Schema::hasTable('member_dues')) {
            $pendingDues = MemberDue::whereIn('status', ['pending', 'partial']);
            $pendingDuesCount = (clone $pendingDues)->count();
            $outstandingDues = (float) $pendingDues
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN outstanding_balance > paid_amount
                        THEN outstanding_balance - paid_amount ELSE 0 END), 0) AS total'
                )
                ->value('total');
        }

        $recentMembers = User::latest('created_at')
            ->take(6)
            ->get(['id', 'name', 'email', 'user_code', 'email_verified_at', 'created_at']);

        return view('home', compact(
            'totalMembers',
            'verifiedMembers',
            'unverifiedMembers',
            'verificationRate',
            'newMembersThisMonth',
            'monthlyGrowth',
            'growthLabels',
            'growthValues',
            'pendingDuesCount',
            'outstandingDues',
            'recentMembers'
        ));
    }
}
