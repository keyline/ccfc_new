<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MassDestroyUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ClubmanMemberProfileSync;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Http;
use Log;
use Carbon\Carbon;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Gate::denies('user_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $search = trim((string) $request->input('search', ''));
        $verification = $request->input('verification');
        $perPage = (int) $request->input('per_page', 50);

        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 50;
        }

        $usersQuery = User::query()
            ->select([
                'id',
                'name',
                'email',
                'email_verified_at',
                'two_factor',
                'user_code',
                'status',
                'phone_number_1',
                'updated_at',
            ])
            ->with([
                'roles' => function ($query) {
                    $query->select(['roles.id', 'roles.title']);
                },
            ])
            ->withCount('userCodeUserDetails');

        if ($search !== '') {
            $usersQuery->where(function ($query) use ($search) {
                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }

                $query->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('user_code', 'like', '%' . $search . '%')
                    ->orWhere('phone_number_1', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%');
            });
        }

        if ($verification === 'verified') {
            $usersQuery->whereNotNull('email_verified_at');
        } elseif ($verification === 'pending') {
            $usersQuery->whereNull('email_verified_at');
        }

        $users = $usersQuery
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.index', compact(
            'users',
            'search',
            'verification',
            'perPage'
        ));
    }

    public function create()
    {
        abort_if(Gate::denies('user_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::pluck('title', 'id');

        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        //dd($request);
        //$user = User::create($request->all());
        try {
            
            $user = User::create($request->all());

            $user->roles()->sync($request->input('roles', []));

            //Dispatching the Job here
            \App\Jobs\MemberProfileUpdate::dispatch($user->user_code)->onQueue('memberprofile');

            return redirect()->route('admin.users.index');
        } catch (\Illuminate\Database\QueryException $ex) {
            //throw $th;
            
            //return redirect()->back()->with('error', $ex->getMessage());
            return redirect()->back()->withErrors(['user_code' => ['Duplicate user code provided']])->withInput($request->except('password'));
        }
        
    }

    // public function edit(User $user)
    // {
    //     abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

    //     $roles = Role::pluck('title', 'id');

    //     $user->load('roles');

    //     return view('admin.users.edit', compact('roles', 'user'));
    // }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->all());
        $user->roles()->sync($request->input('roles', []));



        return redirect()->route('admin.users.index');
    }

    public function show(User $user)
    {
        abort_if(Gate::denies('user_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user->load('roles', 'userCodeUserDetails');

        return view('admin.users.show', compact('user'));
    }

    public function destroy(User $user)
    {
        abort_if(Gate::denies('user_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user->delete();

        $user->roles()->detach();

        $user->userCodeUserDetails()->delete();

        return back();
    }

    public function massDestroy(MassDestroyUserRequest $request)
    {
        User::whereIn('id', request('ids'))->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }


    public function updatedetails(User $user)
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $roles = Role::pluck('title', 'id');

        $user->load('roles');

        return view('admin.users.edit', compact('roles', 'user'));
    }



    public function edit(User $user)
    {
        {
            abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

            $roles = Role::pluck('title', 'id');

            $user->load('roles');



            //   return view('admin.users.edit', compact('roles', 'user'));



            $user = User::where('id', '=', session('LoggedMember'))->first();



            //get member profile
            //$token= "YyHqs47HJOhJUM5Kf1pi5Jz_N8Ss573cxqE2clymSK5G4QLGWsfcxZY8HIKAVvM4vSRsXxCCde4lNfrPvvh93hlLbffZiTwqd_mAu1kAKN6YZWSKd6RDiya8lX50yRIUgaDfeITNUwGWWil3aUlOl3Is-6FFL1Dk8PcJT2iezWOPRYXNVg0TwG1H85v-QT17f1z2Vwr3nhBEfFsUbij0CLRKJwXEoMN4yovVY0QakIHxikwt2lvgibtMnJNZOawklBkpQtC87PcXuG-aGtCqATl0UgjwYr61_oIpRmbuiEk";
            // $token = "N3bwPrgB4wzHytcBkrvd6duSAX46ksfh9zOGPGnzwL8YladUpD-XH0DD_ZVBfdktfuPvgMbHg4uvBNBzibf2qEvPWh-HlzMFwnWJCfI8uW7-RBbpBj5oPlL9KPj7jxL8kaHDB6Fvl1fc8KZfYpZlRKRRTXIqsOkWt4Wenzz8I-D42AQzY5u-4FF1lDN3pepkwSL6xxXEb6wHExSHYlqT_9mKOB-6P-h6uWeqLETbFnft0CBvzwo9rJ14Gvu1YesR_Yte88Xg9R1K4_2mlY93YxYJGI7I3LkPSsVBfPW1SkzmdWo3HRJci6nRl36U_Llc";
            $token = "5tdpn6yeoycRKbWd0311m1B5S-ZKMfU2syAD50kiquOX20GbmXF89Z1-vvsN01WTAIRWHdRESd8nRWZJrC7xuHkClh63BPg1PCpZHKpDOjmtvgJL8ErYrup7PLG2LZHkbjDh6bFb54VyUsvZm4OzzIPI9QVKhTf2ui5Pmd8CzHJZUK-4Jd-aOmQFfhuertA5KuIRrNdHTzA7w1hEYHO9Hq9J_pkME7BhNpjWp44Z3R2YeLuQbskl_rMypzLj5icdoPWgCsxA1bU9iGo5x3heaP8lHliiSx3SeeYpBMe22DRaarXJYc5pxFJ1tuEKDoxn";


            $fields= [
                  'MCODE' => $user->user_code
                ];

            $url= "https://ccfcmemberdata.in/Api/MemberProfile/?".http_build_query($fields);




            $profile = Http::withoutVerifying()
                    ->withHeaders(['Authorization' => 'Bearer ' . $token, 'Cache-Control' => 'no-cache', 'Accept' => '/',
                                    'Content-Type' => 'application/json',])
                    ->withOptions(["verify"=>false])
                    ->post($url)->json()['data'];




            //    dd($profile);

            return view('admin.users.edit', compact('roles', 'user'), [

                'userProfile'       => $profile,
                // 'userTransactions'  => $transactions,
            ]);
        }
    }

    public function saveUserJson(
        Request $request,
        string $code,
        ClubmanMemberProfileSync $profileSync
    )
    {
        abort_if(Gate::denies('user_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            $result = $profileSync->syncByMemberCode($code);
            $user = $result['user'];
            $message = 'Clubman details for ' . ($user->name ?: $user->user_code) . ' were updated successfully.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'has_profile' => true,
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone_number' => $user->phone_number_1,
                        'status' => $user->status,
                        'updated_at' => optional($user->updated_at)->format('d M Y'),
                    ],
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Throwable $exception) {
            Log::warning('Manual Clubman member sync failed.', [
                'member_code' => $code,
                'admin_user_id' => auth()->id(),
                'error' => $exception->getMessage(),
            ]);

            $message = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The Clubman profile could not be updated. Please try again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    public function exportToCSV(Request $request)
    {
        $current = Carbon::now()->format('YmdHs');

        $fileName= 'user_list'. '_' .$current;

        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0'
        ,   'Content-type'        => 'text/csv'
        ,   'Content-Disposition' => 'attachment; filename='. $fileName . '.csv'
        ,   'Expires'             => '0'
        ,   'Pragma'              => 'public'
    ];

        $list = User::all()->toArray();


        # add headers for each column in the CSV download
        array_unshift($list, array_keys($list[0]));

        $callback = function () use ($list) {
            $FH = fopen('php://output', 'w');
            foreach ($list as $row) {
                fputcsv($FH, $row);
            }
            fclose($FH);
        };

        return response()->stream($callback, 200, $headers);
    }
}
