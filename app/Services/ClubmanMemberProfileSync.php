<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ClubmanMemberProfileSync
{
    private $accessToken;

    public function __construct(ClubmanAccessToken $accessToken = null)
    {
        $this->accessToken = $accessToken ?: new ClubmanAccessToken();
    }

    public function syncByMemberCode(string $memberCode): array
    {
        $user = User::where('user_code', $memberCode)->first();

        if (!$user) {
            throw new RuntimeException('The selected member no longer exists.');
        }

        return $this->sync($user);
    }

    public function sync(User $user): array
    {
        if (!$user->user_code) {
            throw new RuntimeException('This member does not have a Clubman member code.');
        }

        $profile = $this->fetchProfile($user->user_code);
        $userValues = $this->userValues($profile);
        $detailValues = $this->detailValues($profile);

        return DB::transaction(function () use ($user, $userValues, $detailValues) {
            $databaseUser = User::whereKey($user->id)->lockForUpdate()->first();

            if (!$databaseUser) {
                throw new RuntimeException('The selected member no longer exists.');
            }

            $databaseUser->fill($userValues);
            $databaseUser->save();

            $userDetail = UserDetail::withTrashed()
                ->where('user_code_id', $databaseUser->id)
                ->lockForUpdate()
                ->first();

            if (!$userDetail) {
                $userDetail = new UserDetail();
                $userDetail->user_code_id = $databaseUser->id;
            } elseif ($userDetail->trashed()) {
                $userDetail->restore();
            }

            // forceFill is intentional: the legacy model did not include its
            // children columns in $fillable, although they are valid columns.
            $userDetail->forceFill($detailValues);
            $userDetail->save();

            return [
                'user' => $databaseUser->fresh(),
                'detail' => $userDetail->fresh(),
            ];
        }, 3);
    }

    public function syncEmail(User $user): User
    {
        if (!$user->user_code) {
            throw new RuntimeException('This member does not have a Clubman member code.');
        }

        $profile = $this->fetchProfile($user->user_code);
        $email = $this->nullableString($this->value($profile, ['EMAIL']));

        if ($email === null) {
            return $user;
        }

        return DB::transaction(function () use ($user, $email) {
            $databaseUser = User::whereKey($user->id)->lockForUpdate()->first();

            if (!$databaseUser) {
                throw new RuntimeException('The selected member no longer exists.');
            }

            $databaseUser->email = $email;
            $databaseUser->save();

            return $databaseUser->fresh();
        }, 3);
    }

    private function fetchProfile(string $memberCode): array
    {
        $endpoint = config(
            'services.clubman.member_profile_url',
            'https://ccfcmemberdata.in/Api/MemberProfile'
        );
        $url = $endpoint . '?' . http_build_query(['MCODE' => $memberCode]);

        try {
            $response = $this->sendProfileRequest($url, $this->accessToken->get());

            if (in_array($response->status(), [401, 403], true)
                && $this->accessToken->canRefresh()) {
                $this->accessToken->forget();
                $response = $this->sendProfileRequest($url, $this->accessToken->get(true));
            }
        } catch (Throwable $exception) {
            if ($exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException(
                'Clubman could not be reached. Please try again in a moment.',
                0,
                $exception
            );
        }

        if (!$response->successful()) {
            throw new RuntimeException(
                'Clubman rejected the request (HTTP ' . $response->status() . '). Check the API token in Settings.'
            );
        }

        $payload = $response->json();
        $profile = is_array($payload) ? ($payload['data'] ?? null) : null;

        if (isset($profile[0]) && is_array($profile[0])) {
            $profile = $profile[0];
        }

        if (!is_array($profile) || empty($profile)) {
            throw new RuntimeException('Clubman returned no profile for member code ' . $memberCode . '.');
        }

        // Clubman's legacy profile JSON repeats EMAIL for the member and
        // spouse. json_decode() keeps the last occurrence, which can erase the
        // member email when the spouse email is blank.
        $primaryEmail = ClubmanProfileResponse::primaryEmail($response->body());

        if ($primaryEmail !== null) {
            $profile['EMAIL'] = $primaryEmail;
        }

        if ($this->nullableString($this->value($profile, ['MEMBER_NAME', 'MEMBERNAME'])) === null) {
            throw new RuntimeException('Clubman returned an incomplete profile for member code ' . $memberCode . '.');
        }

        $returnedMemberCode = $this->nullableString(
            $this->value($profile, ['MCODE', 'MEMBER_CODE', 'MEMBERCODE'])
        );

        if ($returnedMemberCode !== null && strcasecmp($returnedMemberCode, $memberCode) !== 0) {
            throw new RuntimeException('Clubman returned a profile for a different member code.');
        }

        return $profile;
    }

    private function sendProfileRequest(string $url, string $token)
    {
        $request = Http::acceptJson()
            ->withToken($token)
            ->timeout(45)
            ->withOptions([
                'connect_timeout' => (int) config('services.clubman.connect_timeout', 5),
            ]);

        if (! filter_var(config('services.clubman.verify_ssl', false), FILTER_VALIDATE_BOOLEAN)) {
            $request->withoutVerifying();
        }

        return $request->post($url);
    }

    private function userValues(array $profile): array
    {
        $mobile = preg_replace('/\D+/', '', (string) $this->value($profile, ['MOBILENO'], ''));

        return [
            'email' => $this->nullableString($this->value($profile, ['EMAIL'])),
            'name' => $this->nullableString($this->value($profile, ['MEMBER_NAME', 'MEMBERNAME'])),
            'phone_number_1' => strlen($mobile) === 10 ? $mobile : null,
            'status' => $this->nullableString($this->value($profile, ['CURENTSTATUS', 'CURRENTSTATUS'])),
        ];
    }

    private function detailValues(array $profile): array
    {
        $values = [
            'member_type_code' => $this->value($profile, ['MEMBERTYPECODE']),
            'member_type' => $this->value($profile, ['MEMBERTYPE']),
            'date_of_birth' => $this->dateValue($this->value($profile, ['DOB'])),
            'member_since' => $this->dateValue($this->value($profile, ['MEMBER_SINCE'])),
            'sex' => $this->value($profile, ['SEX']),
            'address_1' => $this->value($profile, ['ADDRESS1']),
            'address_2' => $this->value($profile, ['ADDRESS2']),
            'address_3' => $this->value($profile, ['ADDRESS3']),
            'city' => $this->value($profile, ['CITY']),
            'state' => $this->value($profile, ['STATE']),
            'pin' => $this->value($profile, ['PIN']),
            'phone_1' => $this->value($profile, ['PHONE1']),
            'phone_2' => $this->value($profile, ['PHONE2']),
            'mobile_no' => $this->value($profile, ['MOBILENO']),
            'email' => $this->value($profile, ['EMAIL']),
            'current_status' => $this->value($profile, ['CURENTSTATUS', 'CURRENTSTATUS']),
            'represented_club_in' => $this->value($profile, ['REPRESENTED_CLUB_IN']),
            'hobbies_interest' => $this->value($profile, ['HOBBIES/ INTERESTS', 'HOBBIES_INTERESTS']),
            'business_profession' => $this->value(
                $profile,
                ['BUSINESS/ PROFESSION', 'BUSINESS_PROFESSION']
            ),
            'category' => $this->value($profile, ['CATEGORY']),
            'business_address_1' => $this->value($profile, ['BUSINESS_ADDRESS1', 'ADDRESS1']),
            'business_address_2' => $this->value($profile, ['BUSINESS_ADDRESS2', 'ADDRESS2']),
            'business_address_3' => $this->value($profile, ['BUSINESS_ADDRESS3', 'ADDRESS3']),
            'business_city' => $this->value($profile, ['BUSINESS_CITY', 'CITY']),
            'business_state' => $this->value($profile, ['BUSINESS_STATE', 'STATE']),
            'business_pin' => $this->value($profile, ['BUSINESS_PIN', 'PIN']),
            'business_phone_1' => $this->value($profile, ['BUSINESS_PHONE1', 'PHONE1']),
            'business_phone_2' => $this->value($profile, ['BUSINESS_PHONE2', 'PHONE2']),
            'business_email' => $this->value($profile, ['BUSINESS_EMAIL', 'EMAIL']),
            'spouse_name' => $this->value($profile, ['SPOUSE_NAME']),
            'spouse_dob' => $this->dateValue($this->value($profile, ['SPOUSE_DOB'])),
            'spouse_sex' => $this->value($profile, ['SPOUSE_SEX', 'SEX']),
            'spouse_phone_1' => $this->value($profile, ['SPOUSE_PHONE1', 'PHONE1']),
            'spouse_phone_2' => $this->value($profile, ['SPOUSE_PHONE2', 'PHONE2']),
            'spouse_mobile_no' => $this->value($profile, ['SPOUSEMOBILENO', 'SPOUSE_MOBILE']),
            'spouse_email' => $this->value($profile, ['SPOUSE_EMAIL', 'EMAIL']),
            'spouse_business_profession' => $this->value(
                $profile,
                ['SPOUSE_BUSINESS/ PROFESSION', 'SPOUSE_BUSINESS_PROFESSION']
            ),
            'spouse_business_category' => $this->value(
                $profile,
                ['SPOUSE_BUSINESS_CATEGORY', 'CATEGORY']
            ),
            'spouse_business_address_1' => $this->value(
                $profile,
                ['SPOUSE_BUSINESS_ADDRESS1', 'ADDRESS1']
            ),
            'spouse_business_address_2' => $this->value(
                $profile,
                ['SPOUSE_BUSINESS_ADDRESS2', 'ADDRESS2']
            ),
            'spouse_business_address_3' => $this->value(
                $profile,
                ['SPOUSE_BUSINESS_ADDRESS3', 'ADDRESS3']
            ),
            'spouse_business_city' => $this->value($profile, ['SPOUSE_BUSINESS_CITY', 'CITY']),
            'spouse_business_state' => $this->value($profile, ['SPOUSE_BUSINESS_STATE', 'STATE']),
            'spouse_business_pin' => $this->value($profile, ['SPOUSE_BUSINESS_PIN', 'PIN']),
            'spouse_business_phone_1' => $this->value(
                $profile,
                ['SPOUSE_BUSINESS_PHONE1', 'PHONE1']
            ),
            'spouse_business_phone_2' => $this->value(
                $profile,
                ['SPOUSE_BUSINESS_PHONE2', 'PHONE2']
            ),
            'spouse_business_email' => $this->value(
                $profile,
                ['SPOUSE_BUSINESS_EMAIL', 'SPOUSE_EMAIL', 'EMAIL']
            ),
            'member_image' => $this->value($profile, ['MemberImage', 'MEMBER_IMAGE']),
            'spouse_image' => $this->value($profile, ['SpouseImage', 'SPOUSE_IMAGE']),
        ];

        $children = $this->value($profile, ['children', 'CHILDREN'], []);
        $children = is_array($children) ? array_values($children) : [];

        for ($position = 1; $position <= 3; $position++) {
            $child = isset($children[$position - 1]) && is_array($children[$position - 1])
                ? $children[$position - 1]
                : [];

            $values['children' . $position . '_name'] = $this->value(
                $child,
                ['CHILDREN1_NAME', 'CHILD_NAME', 'NAME']
            );
            $values['children' . $position . '_dob'] = $this->dateValue(
                $this->value($child, ['DOB'])
            );
            $values['children' . $position . '_sex'] = $this->value($child, ['SEX']);
            $values['children' . $position . '_phone1'] = $this->value($child, ['PHONE1']);
            $values['children' . $position . '_phone2'] = $this->value($child, ['PHONE2']);
            $values['children' . $position . '_mobileno'] = $this->value(
                $child,
                ['MOBILENO', 'MOBILE']
            );
        }

        return $values;
    }

    private function value(array $data, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== '') {
                return $data[$key];
            }
        }

        return $default;
    }

    private function dateValue($value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format(config('panel.date_format', 'd-m-Y'));
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
