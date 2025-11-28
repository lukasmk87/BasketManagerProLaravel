<?php

namespace App\Http\Requests\ClubAdmin;

use App\Models\ClubSubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateClubSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only super_admin and admin can change subscription plans
        return Auth::user()?->hasAnyRole(['super_admin', 'admin']);
    }

    public function rules(): array
    {
        return [
            'club_subscription_plan_id' => [
                'nullable',
                'exists:club_subscription_plans,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $user = Auth::user();
                        $adminClubs = $user->getAdministeredClubs(false);

                        if ($adminClubs->isEmpty()) {
                            $fail('Sie sind kein Administrator eines Clubs.');
                            return;
                        }

                        $primaryClub = $adminClubs->first();
                        $plan = ClubSubscriptionPlan::find($value);

                        if ($plan && $plan->tenant_id !== $primaryClub->tenant_id) {
                            $fail('Der ausgewählte Plan gehört nicht zum selben Tenant wie der Club.');
                        }
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'club_subscription_plan_id' => 'Subscription Plan',
        ];
    }

    public function messages(): array
    {
        return [
            'club_subscription_plan_id.exists' => 'Der ausgewählte Plan existiert nicht.',
        ];
    }
}
