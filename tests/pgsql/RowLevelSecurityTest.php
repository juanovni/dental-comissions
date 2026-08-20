<?php

namespace Tests\Pgsql;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RowLevelSecurityTest extends TestCase
{
    use RefreshDatabase;

    private int $clinicA;
    private int $clinicB;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('RLS tests require PostgreSQL.');
        }

        $this->setupRlsEnvironment();
    }

    private function setupRlsEnvironment(): void
    {
        $this->clinicA = (int) DB::table('clinics')->insertGetId([
            'name' => 'Clinic A', 'slug' => 'rls-a-' . Str::random(8),
            'subdomain' => 'rls-a-' . Str::random(8),
            'primary_domain' => 'rls-a.localhost',
            'settings' => '{}', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->clinicB = (int) DB::table('clinics')->insertGetId([
            'name' => 'Clinic B', 'slug' => 'rls-b-' . Str::random(8),
            'subdomain' => 'rls-b-' . Str::random(8),
            'primary_domain' => 'rls-b.localhost',
            'settings' => '{}', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::statement("
            CREATE OR REPLACE FUNCTION current_clinic_id() RETURNS bigint AS \$\$
                SELECT nullif(current_setting('app.current_clinic_id', true), '')::bigint;
            \$\$ LANGUAGE sql STABLE
        ");

        DB::statement("ALTER TABLE social_crm_settings ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE social_crm_settings FORCE ROW LEVEL SECURITY");
        DB::statement("DROP POLICY IF EXISTS tenant_isolation_social_crm_settings ON social_crm_settings");
        DB::statement("
            CREATE POLICY tenant_isolation_social_crm_settings ON social_crm_settings
                USING (clinic_id = current_clinic_id() OR current_clinic_id() IS NULL)
        ");

        DB::statement("DO \$\$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'dental_app') THEN
                CREATE ROLE dental_app LOGIN PASSWORD 'dental_app';
            END IF;
            IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'dental_bypass') THEN
                CREATE ROLE dental_bypass LOGIN PASSWORD 'dental_bypass';
            END IF;
        END \$\$");
        DB::statement("ALTER ROLE dental_bypass BYPASSRLS");

        DB::statement("GRANT SELECT, INSERT, UPDATE, DELETE ON social_crm_settings TO dental_app");
        DB::statement("GRANT ALL PRIVILEGES ON social_crm_settings TO dental_bypass");
        DB::statement("GRANT USAGE ON SCHEMA public TO dental_app");
        DB::statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO dental_app");
        DB::statement("GRANT USAGE ON SCHEMA public TO dental_bypass");
        DB::statement("GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO dental_bypass");

        $now = now();
        DB::table('social_crm_settings')->insert([
            ['clinic_id' => $this->clinicA, 'setting_group' => 'general', 'key' => 'test_a1', 'label' => 'A1', 'value_type' => 'string', 'value' => '{}', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['clinic_id' => $this->clinicA, 'setting_group' => 'general', 'key' => 'test_a2', 'label' => 'A2', 'value_type' => 'string', 'value' => '{}', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['clinic_id' => $this->clinicB, 'setting_group' => 'general', 'key' => 'test_b1', 'label' => 'B1', 'value_type' => 'string', 'value' => '{}', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function test_function_exists(): void
    {
        $r = DB::selectOne("SELECT proname FROM pg_proc WHERE proname = 'current_clinic_id'");
        $this->assertNotNull($r);
    }

    public function test_rls_enabled(): void
    {
        $r = DB::selectOne("SELECT rowsecurity FROM pg_tables WHERE tablename = 'social_crm_settings' AND schemaname = 'public'");
        $this->assertTrue((bool) $r->rowsecurity);
    }

    public function test_policy_exists(): void
    {
        $r = DB::selectOne("SELECT policyname FROM pg_policies WHERE tablename = 'social_crm_settings' AND policyname = 'tenant_isolation_social_crm_settings' AND schemaname = 'public'");
        $this->assertNotNull($r);
    }

    public function test_dental_app_not_superadmin(): void
    {
        $role = DB::selectOne("SELECT rolsuper, rolbypassrls FROM pg_roles WHERE rolname = 'dental_app'");
        $this->assertNotNull($role);
        $this->assertFalse((bool) $role->rolsuper);
        $this->assertFalse((bool) $role->rolbypassrls);
    }

    public function test_dental_bypass_has_bypassrls(): void
    {
        $role = DB::selectOne("SELECT rolbypassrls FROM pg_roles WHERE rolname = 'dental_bypass'");
        $this->assertNotNull($role);
        $this->assertTrue((bool) $role->rolbypassrls);
    }

    public function test_clinic_a_sees_only_own_data(): void
    {
        DB::statement("SET app.current_clinic_id = '{$this->clinicA}'");
        DB::statement("SET ROLE dental_app");
        $keys = DB::table('social_crm_settings')->pluck('key')->toArray();
        DB::statement("RESET ROLE");
        DB::statement("SET app.current_clinic_id = ''");

        $this->assertContains('test_a1', $keys);
        $this->assertContains('test_a2', $keys);
        $this->assertNotContains('test_b1', $keys);
    }

    public function test_clinic_b_sees_only_own_data(): void
    {
        DB::statement("SET app.current_clinic_id = '{$this->clinicB}'");
        DB::statement("SET ROLE dental_app");
        $keys = DB::table('social_crm_settings')->pluck('key')->toArray();
        DB::statement("RESET ROLE");
        DB::statement("SET app.current_clinic_id = ''");

        $this->assertContains('test_b1', $keys);
        $this->assertNotContains('test_a1', $keys);
    }

    public function test_nonexistent_clinic_sees_nothing(): void
    {
        DB::statement("SET app.current_clinic_id = '99999'");
        DB::statement("SET ROLE dental_app");
        $count = DB::table('social_crm_settings')->count();
        DB::statement("RESET ROLE");
        DB::statement("SET app.current_clinic_id = ''");

        $this->assertEquals(0, $count);
    }

    public function test_bypass_sees_all(): void
    {
        $total = DB::table('social_crm_settings')->count();

        DB::statement("SET app.current_clinic_id = '99999'");
        DB::statement("SET ROLE dental_bypass");
        $count = DB::table('social_crm_settings')->count();
        DB::statement("RESET ROLE");
        DB::statement("SET app.current_clinic_id = ''");

        $this->assertEquals($total, $count);
    }
}