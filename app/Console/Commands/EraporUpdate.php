<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;
use App\Models\Setting;
use App\Models\Tahun_ajaran;
use App\Models\Semester;
use App\Models\User;
use App\Models\Team;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Jabatan_ptk;

class EraporUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'erapor:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $roles = Role::get();
        foreach($roles as $role){
            $permissions = Permission::firstOrCreate([
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
            ])->id;
            $role->permissions()->sync($permissions);
        }
        $version = File::get(base_path().'/app_version.txt');
        $db_version = File::get(base_path().'/db_version.txt');
        $this->call('migrate');
        if(!Jabatan_ptk::count()){
            $this->call('db:seed', ['class' => 'JabatanPtkSeeder']);
        }
        $this->call('cache:clear');
        $this->call('view:clear');
        $this->call('config:cache');
        $this->call('ref:cp');
        $this->call('update:guru');
        $this->call('update:siswa');
        $this->call('hapus:ganda');
        if(!File::isDirectory(public_path('storage'))){
            $this->call('storage:link');
        }
        $ajaran = [
            [
                'tahun_ajaran_id' => 2020,
                'nama' => '2020/2021',
                'periode_aktif' => 1,   
                'semester' => [
                    [
                        'semester_id' => 20201,
                        'nama' => '2020/2021 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 0,
                    ],
                    [
                        'semester_id' => 20202,
                        'nama' => '2020/2021 Genap',
                        'semester' => 2,
                        'periode_aktif' => 0,
                    ]
                ],
            ],
            [
                'tahun_ajaran_id' => 2021,
                'nama' => '2021/2022',
                'periode_aktif' => 1,   
                'semester' => [
                    [
                        'semester_id' => 20211,
                        'nama' => '2021/2022 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 1,
                    ],
                    [
                        'semester_id' => 20212,
                        'nama' => '2021/2022 Genap',
                        'semester' => 2,
                        'periode_aktif' => 0,
                    ]
                ],
            ],
            [
                'tahun_ajaran_id' => 2022,
                'nama' => '2022/2023',
                'periode_aktif' => 1,   
                'semester' => [
                    [
                        'semester_id' => 20221,
                        'nama' => '2022/2023 Ganjil',
                        'semester' => 1,
                        'periode_aktif' => 1,
                    ],
                    [
                        'semester_id' => 20222,
                        'nama' => '2022/2023 Genap',
                        'semester' => 2,
                        'periode_aktif' => 0,
                    ]
                ],
            ]
        ];
        $adminRole = Role::where('name', 'admin')->first();
        $users = User::whereNull('nuptk')->whereNull('nisn')->get();
        foreach($ajaran as $a){
            Tahun_ajaran::updateOrCreate(
                [
                    'tahun_ajaran_id' => $a['tahun_ajaran_id'],
                ],
                [
                    'nama' => $a['nama'],
                    'periode_aktif' => $a['periode_aktif'],
                    'tanggal_mulai' => '2020-07-20',
                    'tanggal_selesai' => '2021-06-01',
                    'last_sync' => now(),
                ]
            );
            foreach($a['semester'] as $semester){
                Semester::updateOrCreate(
                    [
                        'semester_id' => $semester['semester_id'],
                    ],
                    [
                        'tahun_ajaran_id' => $a['tahun_ajaran_id'],
                        'nama' => $semester['nama'],
                        'semester' => $semester['semester'],
                        'periode_aktif' => $semester['periode_aktif'],
                        'tanggal_mulai' => '2020-07-01',
                        'tanggal_selesai' => '2021-12-31',
                        'last_sync' => date('Y-m-d H:i:s'),
                    ]
                );
            }
        }
        $all_semester = Semester::whereHas('tahun_ajaran', function($query){
            $query->where('periode_aktif', 1);
        })->get();
        foreach($all_semester as $semester){
            $team = Team::updateOrCreate([
                'name' => $semester->nama,
                'display_name' => $semester->nama,
                'description' => $semester->nama,
            ]);
            foreach($users as $user){
                if(!$user->hasRole($adminRole, $team)){
                    $user->attachRole($adminRole, $team);
                }
            }
        }
        Semester::where('semester_id', '<>', '20221')->update(['periode_aktif' => 0]);
        Setting::updateOrCreate(
            [
                'key' => 'app_version',
            ],
            [
                'value' => $version,
            ]
        );
        Setting::updateOrCreate(
            [
                'key' => 'db_version',
            ],
            [
                'value' => $db_version,
            ]
        );
        $this->info('Berhasil memperbaharui aplikasi e-Rapor SMK ke versi '.$version);
    }
}
