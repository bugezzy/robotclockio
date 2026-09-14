<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user — along with the brand
     * new organization and team they own. This is the only self-serve way
     * a tenant comes into being; the admin panel's own organization
     * creation (system-only) is a separate path.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'team_name' => [
                'required', 'string', 'max:255', 'regex:/\S/',
                Rule::unique(Organization::class, 'name'),
            ],
        ])->validate();

        return DB::connection((new Organization)->getConnectionName())->transaction(function () use ($input) {
            $organization = Organization::create([
                'name' => $input['team_name'],
            ]);

            $team = Team::create([
                'name' => $input['team_name'],
                'organization_id' => $organization->id,
            ]);

            return User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => 'owner',
                'organization_id' => $organization->id,
                'team_id' => $team->id,
            ]);
        });
    }
}
