<?php

namespace Tests\Unit;

use App\Services\ClubmanProfileResponse;
use PHPUnit\Framework\TestCase;

class ClubmanProfileResponseTest extends TestCase
{
    public function test_it_preserves_the_first_email_from_the_legacy_duplicate_key_payload(): void
    {
        $body = <<<'JSON'
{"Result":"success","data":{"MEMBER_NAME":"Member","EMAIL":"member@example.com","SPOUSE_NAME":"Spouse","EMAIL":""}}
JSON;

        $this->assertSame(
            'member@example.com',
            ClubmanProfileResponse::primaryEmail($body)
        );
    }

    public function test_it_decodes_an_escaped_email_value(): void
    {
        $body = '{"data":{"EMAIL":"member\u0040example.com"}}';

        $this->assertSame(
            'member@example.com',
            ClubmanProfileResponse::primaryEmail($body)
        );
    }
}
