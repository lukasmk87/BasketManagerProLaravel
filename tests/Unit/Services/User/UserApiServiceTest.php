<?php

namespace Tests\Unit\Services\User;

use App\Models\Subscription;
use App\Models\User;
use App\Services\User\UserApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserApiServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserApiService();
    }

    // ============================================
    // API Key Tests
    // ============================================

    public function test_generate_api_key_returns_key_with_prefix(): void
    {
        $user = User::factory()->create();

        $apiKey = $this->service->generateApiKey($user);

        $this->assertStringStartsWith('bmp_', $apiKey);
        $this->assertEquals(44, strlen($apiKey)); // 4 (prefix) + 40 (hex)
    }

    public function test_generate_api_key_enables_api_access(): void
    {
        $user = User::factory()->create(['api_access_enabled' => false]);

        $this->service->generateApiKey($user);

        $user->refresh();
        $this->assertTrue($user->api_access_enabled);
    }

    public function test_generate_api_key_stores_hash(): void
    {
        $user = User::factory()->create();

        $apiKey = $this->service->generateApiKey($user);

        $user->refresh();
        $this->assertNotNull($user->api_key_hash);
        $this->assertEquals(hash('sha256', $apiKey), $user->api_key_hash);
    }

    public function test_verify_api_key_returns_true_for_valid_key(): void
    {
        $user = User::factory()->create();
        $apiKey = $this->service->generateApiKey($user);

        $result = $this->service->verifyApiKey($user, $apiKey);

        $this->assertTrue($result);
    }

    public function test_verify_api_key_returns_false_for_invalid_key(): void
    {
        $user = User::factory()->create();
        $this->service->generateApiKey($user);

        $result = $this->service->verifyApiKey($user, 'bmp_invalid_key');

        $this->assertFalse($result);
    }

    public function test_verify_api_key_returns_false_when_api_disabled(): void
    {
        $user = User::factory()->create();
        $apiKey = $this->service->generateApiKey($user);
        $user->update(['api_access_enabled' => false]);

        $result = $this->service->verifyApiKey($user, $apiKey);

        $this->assertFalse($result);
    }

    public function test_revoke_api_key_clears_all_api_data(): void
    {
        $user = User::factory()->create();
        $this->service->generateApiKey($user);
        $this->service->updateApiKeyUsage($user);

        $this->service->revokeApiKey($user);

        $user->refresh();
        $this->assertNull($user->api_key_hash);
        $this->assertFalse($user->api_access_enabled);
        $this->assertNull($user->api_key_last_used_at);
    }

    public function test_update_api_key_usage_sets_timestamp(): void
    {
        $user = User::factory()->create();

        $this->service->updateApiKeyUsage($user);

        $user->refresh();
        $this->assertNotNull($user->api_key_last_used_at);
    }

    public function test_rotate_api_key_generates_new_key(): void
    {
        $user = User::factory()->create();
        $oldKey = $this->service->generateApiKey($user);
        $oldHash = $user->fresh()->api_key_hash;

        $newKey = $this->service->rotateApiKey($user);

        $this->assertNotEquals($oldKey, $newKey);
        $this->assertNotEquals($oldHash, $user->fresh()->api_key_hash);
    }

    // ============================================
    // API Access Tests
    // ============================================

    public function test_is_api_access_enabled_returns_correct_value(): void
    {
        $userEnabled = User::factory()->create(['api_access_enabled' => true]);
        $userDisabled = User::factory()->create(['api_access_enabled' => false]);

        $this->assertTrue($this->service->isApiAccessEnabled($userEnabled));
        $this->assertFalse($this->service->isApiAccessEnabled($userDisabled));
    }

    public function test_enable_api_access_sets_flag(): void
    {
        $user = User::factory()->create(['api_access_enabled' => false]);

        $this->service->enableApiAccess($user);

        $this->assertTrue($user->fresh()->api_access_enabled);
    }

    public function test_disable_api_access_clears_flag(): void
    {
        $user = User::factory()->create(['api_access_enabled' => true]);

        $this->service->disableApiAccess($user);

        $this->assertFalse($user->fresh()->api_access_enabled);
    }

    // ============================================
    // Subscription Tier Tests
    // ============================================

    public function test_get_subscription_tier_name_returns_correct_names(): void
    {
        $tiers = [
            'free' => 'Free',
            'basic' => 'Basic',
            'premium' => 'Premium',
            'enterprise' => 'Enterprise',
            'unlimited' => 'Unlimited',
            'unknown' => 'Free',
        ];

        foreach ($tiers as $tier => $expectedName) {
            $user = User::factory()->create(['subscription_tier' => $tier]);
            $this->assertEquals($expectedName, $this->service->getSubscriptionTierName($user));
        }
    }

    public function test_is_premium_user_returns_true_for_premium_tiers(): void
    {
        $premiumUser = User::factory()->create(['subscription_tier' => 'premium']);
        $enterpriseUser = User::factory()->create(['subscription_tier' => 'enterprise']);
        $unlimitedUser = User::factory()->create(['subscription_tier' => 'unlimited']);
        $freeUser = User::factory()->create(['subscription_tier' => 'free']);
        $basicUser = User::factory()->create(['subscription_tier' => 'basic']);

        $this->assertTrue($this->service->isPremiumUser($premiumUser));
        $this->assertTrue($this->service->isPremiumUser($enterpriseUser));
        $this->assertTrue($this->service->isPremiumUser($unlimitedUser));
        $this->assertFalse($this->service->isPremiumUser($freeUser));
        $this->assertFalse($this->service->isPremiumUser($basicUser));
    }

    public function test_is_enterprise_user_returns_true_for_enterprise_tiers(): void
    {
        $enterpriseUser = User::factory()->create(['subscription_tier' => 'enterprise']);
        $unlimitedUser = User::factory()->create(['subscription_tier' => 'unlimited']);
        $premiumUser = User::factory()->create(['subscription_tier' => 'premium']);
        $freeUser = User::factory()->create(['subscription_tier' => 'free']);

        $this->assertTrue($this->service->isEnterpriseUser($enterpriseUser));
        $this->assertTrue($this->service->isEnterpriseUser($unlimitedUser));
        $this->assertFalse($this->service->isEnterpriseUser($premiumUser));
        $this->assertFalse($this->service->isEnterpriseUser($freeUser));
    }

    public function test_is_unlimited_user_returns_true_only_for_unlimited(): void
    {
        $unlimitedUser = User::factory()->create(['subscription_tier' => 'unlimited']);
        $enterpriseUser = User::factory()->create(['subscription_tier' => 'enterprise']);

        $this->assertTrue($this->service->isUnlimitedUser($unlimitedUser));
        $this->assertFalse($this->service->isUnlimitedUser($enterpriseUser));
    }

    public function test_update_subscription_tier_changes_user_tier(): void
    {
        $user = User::factory()->create(['subscription_tier' => 'free']);

        $this->service->updateSubscriptionTier($user, 'premium');

        $this->assertEquals('premium', $user->fresh()->subscription_tier);
    }

    // ============================================
    // API Key Status Tests
    // ============================================

    public function test_get_api_key_status_returns_correct_status(): void
    {
        $user = User::factory()->create([
            'api_key_hash' => null,
            'api_access_enabled' => false,
        ]);

        $status = $this->service->getApiKeyStatus($user);

        $this->assertFalse($status['has_key']);
        $this->assertFalse($status['enabled']);
        $this->assertNull($status['last_used_at']);
    }

    public function test_get_api_key_status_with_active_key(): void
    {
        $user = User::factory()->create();
        $this->service->generateApiKey($user);
        $this->service->updateApiKeyUsage($user);
        $user->refresh();

        $status = $this->service->getApiKeyStatus($user);

        $this->assertTrue($status['has_key']);
        $this->assertTrue($status['enabled']);
        $this->assertNotNull($status['last_used_at']);
    }

    // ============================================
    // Subscription Creation Tests
    // ============================================

    public function test_get_subscription_creates_free_subscription_if_none_exists(): void
    {
        $user = User::factory()->create();

        $subscription = $this->service->getSubscription($user);

        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals('free', $subscription->tier);
        $this->assertEquals('Free Plan', $subscription->plan_name);
        $this->assertEquals('active', $subscription->status);
    }

    public function test_get_subscription_returns_existing_subscription(): void
    {
        $user = User::factory()->create();
        $existingSubscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'tier' => 'premium',
            'plan_name' => 'Premium Plan',
        ]);

        $subscription = $this->service->getSubscription($user);

        $this->assertEquals($existingSubscription->id, $subscription->id);
        $this->assertEquals('premium', $subscription->tier);
    }

    // ============================================
    // API Quota Tests
    // ============================================

    public function test_reset_api_quota_clears_usage_and_sets_reset_time(): void
    {
        $user = User::factory()->create([
            'current_api_usage' => 500,
            'api_quota_reset_at' => now()->subHour(),
        ]);

        $this->service->resetApiQuota($user);

        $user->refresh();
        $this->assertEquals(0, $user->current_api_usage);
        $this->assertTrue($user->api_quota_reset_at->isAfter(now()));
    }
}
