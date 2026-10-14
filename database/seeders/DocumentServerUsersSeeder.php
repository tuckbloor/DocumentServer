<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DocumentServerUsersSeeder extends Seeder
{
    /** IDs 2-6 are deliberately fixed. We never overwrite other existing users. */
    private const DEMO_USERS = [
        2 => ['name' => 'Sarah Morgan', 'email' => 'sarah.morgan@example.test', 'job_title' => 'IT Manager', 'department' => 'IT Management', 'role' => 'manager'],
        3 => ['name' => 'Daniel Reeves', 'email' => 'daniel.reeves@example.test', 'job_title' => 'Infrastructure Engineer', 'department' => 'Infrastructure', 'role' => 'member'],
        4 => ['name' => 'Priya Shah', 'email' => 'priya.shah@example.test', 'job_title' => 'Security Analyst', 'department' => 'Cybersecurity', 'role' => 'member'],
        5 => ['name' => 'Oliver Hughes', 'email' => 'oliver.hughes@example.test', 'job_title' => 'Service Desk Lead', 'department' => 'Service Desk', 'role' => 'member'],
        6 => ['name' => 'Emily Carter', 'email' => 'emily.carter@example.test', 'job_title' => 'IT Project Coordinator', 'department' => 'Projects', 'role' => 'member'],
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo users may only be seeded in local or testing.');
        }

        $existingOwner = DB::table('users')->where('id', 1)->first();
        if (! $existingOwner) {
            throw new RuntimeException('Expected your existing registered account to have ID 1. No account has been changed.');
        }

        foreach (self::DEMO_USERS as $id => $data) {
            $occupant = DB::table('users')->where('id', $id)->first();
            if ($occupant && $occupant->email !== $data['email']) {
                throw new RuntimeException("User ID {$id} is already used by {$occupant->email}. Refusing to overwrite an existing account.");
            }
            $emailOwner = DB::table('users')->where('email', $data['email'])->first();
            if ($emailOwner && (int) $emailOwner->id !== $id) {
                throw new RuntimeException("Email {$data['email']} belongs to a different user ID. Seeding cancelled.");
            }
        }

        $now = now();
        // Optional .env override; only used when a demo account is FIRST created.
        $password = (string) env('DOCUMENT_SERVER_DEMO_PASSWORD', 'DemoOnly!2026');
        if (strlen($password) < 12) {
            throw new RuntimeException('DOCUMENT_SERVER_DEMO_PASSWORD must be at least 12 characters.');
        }

        $roles = [
            'administrator' => ['name' => 'Administrator', 'description' => 'Manages the document server and its users.'],
            'manager' => ['name' => 'Manager', 'description' => 'Manages department documents and users.'],
            'member' => ['name' => 'Member', 'description' => 'Uses documents they own or are authorised to access.'],
        ];
        $roleIds = [];
        foreach ($roles as $slug => $row) {
            $role = DB::table('roles')->where('slug', $slug)->first();
            $roleIds[$slug] = $role?->id ?? DB::table('roles')->insertGetId([
                'name' => $row['name'],
                'slug' => $slug,
                'description' => $row['description'],
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach (self::DEMO_USERS as $id => $data) {
            if (! DB::table('users')->where('id', $id)->exists()) {
                DB::table('users')->insert([
                    'id' => $id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'email_verified_at' => $now,
                    'password' => Hash::make($password),
                    'account_status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $userRole = [1 => 'administrator'];
        foreach (self::DEMO_USERS as $id => $data) {
            $userRole[$id] = $data['role'];
        }
        foreach ($userRole as $id => $role) {
            $exists = DB::table('role_user')->where('role_id', $roleIds[$role])->where('user_id', $id)->exists();
            if (! $exists) {
                DB::table('role_user')->insert([
                    'role_id' => $roleIds[$role],
                    'user_id' => $id,
                    'assigned_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // Avatars are safe to expose; the DOCUMENTS remain exclusively on the private disk.
        $userData = [1 => [
            'name' => $existingOwner->name,
            'job_title' => 'Document Server Administrator',
            'department' => 'IT Department',
        ]];
        foreach (self::DEMO_USERS as $id => $data) {
            $userData[$id] = $data;
        }
        foreach ($userData as $id => $data) {
            $avatarPath = sprintf('avatars/document-server-demo-%02d.png', $id);
            $avatarSource = __DIR__.'/assets/avatars/'.sprintf('avatar-user-%02d.png', $id);
            if (! is_file($avatarSource)) {
                throw new RuntimeException("Bundled avatar is missing: {$avatarSource}");
            }
            if (! Storage::disk('public')->exists($avatarPath)) {
                Storage::disk('public')->put($avatarPath, file_get_contents($avatarSource));
            }
            if (! DB::table('user_profiles')->where('user_id', $id)->exists()) {
                DB::table('user_profiles')->insert([
                    'user_id' => $id,
                    'display_name' => $data['name'],
                    'avatar_path' => $avatarPath,
                    'avatar_disk' => 'public',
                    'job_title' => $data['job_title'],
                    'department' => $data['department'],
                    'location' => 'United Kingdom',
                    'bio' => $id === 1 ? null : 'Fictional IT department demonstration account.',
                    'timezone' => 'Europe/London',
                    'locale' => 'en_GB',
                    'show_email_on_profile' => false,
                    'show_phone_on_profile' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            if (! DB::table('user_preferences')->where('user_id', $id)->exists()) {
                DB::table('user_preferences')->insert([
                    'user_id' => $id,
                    'theme' => 'light',
                    'document_view' => 'grid',
                    'documents_per_page' => 15,
                    'dashboard_widgets' => json_encode(['all_documents', 'shared_documents', 'recent_activity', 'storage_usage']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}
