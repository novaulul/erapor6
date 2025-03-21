<?php

namespace App\Http\Livewire\Sinkronisasi;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Http;
use Livewire\Component;
use App\Models\Sekolah;
use App\Models\Semester;
use App\Models\Kompetensi_dasar;
use App\Models\Mst_wilayah;
use App\Models\Jurusan;
use App\Models\Kurikulum;
use App\Models\Mata_pelajaran;
use App\Models\Mata_pelajaran_kurikulum;
use Storage;
use Artisan;
use DB;
class Dapodik extends Component
{
    use LivewireAlert;
    public $online = FALSE;
    public $showSyncButton = TRUE;
    public $syncText = 'Menyiapkan data sinkronisasi';
    public $server = 'dapodik';
    public $satuan = 'all';
    public $prosesSync = FALSE;
    public $sekolah_id;

    public function getListeners()
    {
        return [
            //'confirmed' => '$refresh',
            'prosesSync',
            'delaySync',
            'finishSync',
        ];
    }
    private function url_server($server, $ep){
        return config('erapor.'.$server).$ep;
    }
    public function data_dapodik(){
        try {
            $semester = Semester::find(session('semester_aktif'));
            $user = auth()->user();
            $data_sync = [
                'username_dapo'		=> $user->email,
                'password_dapo'		=> $user->password,
                'npsn'				=> $user->sekolah->npsn,
                'tahun_ajaran_id'	=> $semester->tahun_ajaran_id,
                'semester_id'		=> $semester->semester_id,
                'sekolah_id'		=> $user->sekolah->sekolah_id,
            ];
            $response = Http::withHeaders([
                'x-api-key' => $user->sekolah->sekolah_id,
            ])->withBasicAuth('admin', '1234')->asForm()->post($this->url_server('dapodik', 'api/status'), $data_sync);
            $return = $response->object();
            $this->online = ($return) ? TRUE : FALSE;
            return $return;
        } catch (\Exception $e){
            $this->online = FALSE;
        }
    }
    private function referensi(){
        $response = Http::get($this->url_server('dashboard', 'api/referensi'));
        return $response->object();
    }
    public function render()
    {
        $dapodik = ($this->data_dapodik()) ?? NULL;
        $referensi = $this->referensi();
        $erapor = $this->ref_erapor();
        $this->sekolah_id = auth()->user()->sekolah_id;
        return view('livewire.sinkronisasi.dapodik', [
            'data_sinkron' => [
                [
                    'nama' => 'Jurusan',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->jurusan : 0,
                    'erapor' => $erapor['jurusan'],
                    'sinkron' => $erapor['jurusan'],
                    'aksi' => 'jurusan',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Kurikulum',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->kurikulum : 0,
                    'erapor' => $erapor['kurikulum'],
                    'sinkron' => $erapor['kurikulum'],
                    'aksi' => 'kurikulum',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Mata Pelajaran',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->mata_pelajaran : 0,
                    'erapor' => $erapor['mata_pelajaran'],
                    'sinkron' => $erapor['mata_pelajaran'],
                    'aksi' => 'mata_pelajaran',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Mata Pelajaran Kurikulum',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->mata_pelajaran_kurikulum : 0,
                    'erapor' => $erapor['mata_pelajaran_kurikulum'],
                    'sinkron' => $erapor['mata_pelajaran_kurikulum'],
                    'aksi' => 'mata_pelajaran_kurikulum',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Wilayah',
                    'dapodik' => ($referensi) ? $referensi->wilayah : 0,
                    'erapor' => $erapor['wilayah'],
                    'sinkron' => $erapor['wilayah'],
                    'aksi' => 'wilayah',
                    'server' => 'erapor',
                ],
                [
                    'nama' => 'Ref. Kompetensi Dasar',
                    'dapodik' => ($referensi) ? $referensi->ref_kd : 0,
                    'erapor' => $erapor['ref_kd'],
                    'sinkron' => $erapor['ref_kd'],
                    'aksi' => 'get-kd',
                    'server' => 'erapor',
                ],
                [
                    'nama' => 'Sekolah',
                    'dapodik' => 1,
                    'erapor' => $erapor['sekolah'],
                    'sinkron' => $erapor['sekolah'],
                    'aksi' => 'sekolah',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'GTK',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->ptk_terdaftar : 0,
                    'erapor' => $erapor['ptk'],
                    'sinkron' => $erapor['ptk'],
                    'aksi' => 'ptk',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Rombongan Belajar',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->rombongan_belajar : 0,
                    'erapor' => $erapor['rombongan_belajar'],
                    'sinkron' => $erapor['rombongan_belajar'],
                    'aksi' => 'rombongan_belajar',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Peserta Didik Aktif',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->registrasi_peserta_didik : 0,
                    'erapor' => $erapor['peserta_didik_aktif'],
                    'sinkron' => $erapor['peserta_didik_aktif'],
                    'aksi' => 'peserta_didik_aktif',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Peserta Didik Keluar',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->siswa_keluar_dapodik : 0,
                    'erapor' => $erapor['peserta_didik_keluar'],
                    'sinkron' => $erapor['peserta_didik_keluar'],
                    'aksi' => 'peserta_didik_keluar',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Anggota Rombel Matpel Pilihan',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->anggota_rombel_pilihan : 0,
                    'erapor' => $erapor['anggota_rombel_pilihan'],
                    'sinkron' => $erapor['anggota_rombel_pilihan'],
                    'aksi' => 'anggota_rombel_pilihan',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Pembelajaran (Reguler)',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->pembelajaran_dapodik : 0,
                    'erapor' => $erapor['pembelajaran'],
                    'sinkron' => $erapor['pembelajaran'],
                    'aksi' => 'pembelajaran',
                    'server' => 'dapodik',
                ],
                /*[
                    'nama' => 'Pembelajaran (Sub Mapel/Tema P5)',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->sub_pembelajaran : 0,
                    'erapor' => $erapor['sub_pembelajaran'],
                    'sinkron' => $erapor['sub_pembelajaran'],
                    'aksi' => 'pembelajaran',
                    'server' => 'dapodik',
                ],*/
                [
                    'nama' => 'Ekstrakurikuler',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->ekskul_dapodik : 0,
                    'erapor' => $erapor['ekstrakurikuler'],
                    'sinkron' => $erapor['ekstrakurikuler'],
                    'aksi' => 'ekstrakurikuler',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Anggota Ekstrakurikuler',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->anggota_ekskul_dapodik : 0,
                    'erapor' => $erapor['anggota_ekskul'],
                    'sinkron' => $erapor['anggota_ekskul'],
                    'aksi' => 'anggota_ekskul',
                    'server' => 'dapodik',
                ],
                [
                    'nama' => 'Relasi Dunia Usaha & Industri',
                    'dapodik' => ($dapodik) ? $dapodik->dapodik->dudi_dapodik : 0,
                    'erapor' => $erapor['dudi'],
                    'sinkron' => $erapor['dudi'],
                    'aksi' => 'dudi',
                    'server' => 'dapodik',
                ],
            ],
            'breadcrumbs' => [
                ['link' => "/", 'name' => "Beranda"], ['link' => '#', 'name' => 'Sinkronisasi'], ['name' => 'Ambil Data Dapodik']
            ]
        ]);
    }
    private function ref_erapor(){
        $sekolah = Sekolah::withCount([
            'ptk', 
            'rombongan_belajar' => function($query){
                $query->where('semester_id', session('semester_aktif'));
            },
            'peserta_didik as pd_aktif_count' => function($query){
                $query->whereHas('anggota_rombel', function($query){
                    $query->where('semester_id', session('semester_aktif'));
                    $query->whereHas('rombongan_belajar', function($query){
                        $query->where('jenis_rombel', 1);
                    });
                });
            },
            'peserta_didik as pd_keluar_count' => function($query){
                $query->whereHas('pd_keluar', function($query){
                    $query->where('semester_id', session('semester_aktif'));
                });
            },
            'peserta_didik as anggota_rombel_pilihan' => function($query){
                $query->whereHas('anggota_rombel', function($query){
                    $query->where('semester_id', session('semester_aktif'));
                    $query->whereHas('rombongan_belajar', function($query){
                        $query->where('jenis_rombel', 16);
                    });
                });
            },
            'pembelajaran' => function($query){
                $query->where('semester_id', session('semester_aktif'));
            },
            'ekstrakurikuler' => function($query){
                $query->where('semester_id', session('semester_aktif'));
            },
            'anggota_ekskul' => function($query){
                $query->where('semester_id', session('semester_aktif'));
            },
            'mou'
        ])->find(session('sekolah_id'));
        return [
            'sekolah' => $sekolah->sinkron,
            'ptk' => $sekolah->ptk_count,
            'rombongan_belajar' => $sekolah->rombongan_belajar_count,
            'peserta_didik_aktif' => $sekolah->pd_aktif_count,
            'peserta_didik_keluar' => $sekolah->pd_keluar_count,
            'anggota_rombel_pilihan' => $sekolah->anggota_rombel_pilihan,
            'pembelajaran' => $sekolah->pembelajaran_count,
            'ekstrakurikuler' => $sekolah->ekstrakurikuler_count,
            'anggota_ekskul' => $sekolah->anggota_ekskul_count,
            'dudi' => $sekolah->mou_count,
            'jurusan' => Jurusan::count(),
            'kurikulum' => Kurikulum::count(),
            'mata_pelajaran' => Mata_pelajaran::count(),
            'mata_pelajaran_kurikulum' => Mata_pelajaran_kurikulum::count(),
            'wilayah' => Mst_wilayah::count(),
            'ref_kd' => Kompetensi_dasar::withTrashed()->count(),
            'mata_pelajaran_kurikulum' => Mata_pelajaran_kurikulum::count(),
        ];
    }
    public function clickSync()
    {
        $this->showSyncButton =! $this->showSyncButton;
        $this->emit('delaySync');
    }
    public function delaySync(){
        $this->hapus_file();
        if(!$this->prosesSync){
            $this->emit('prosesSync');
        }
    }
    public function syncSatuan($server, $satuan){
        $this->server = $server;
        $this->satuan = $satuan;
        $this->emit('delaySync');
        //Artisan::call('sinkron:'.$server, ['satuan' => $aksi]);
    }
    public function prosesSync(){
        $this->prosesSync = TRUE;
        if($this->satuan == 'all'){
            $list_data = [
                'jurusan', 
                'kurikulum', 
                'mata_pelajaran', 
                'mata_pelajaran_kurikulum', 
                'sekolah',
                'ptk', 
                'rombongan_belajar', 
                'peserta_didik_aktif', 
                'peserta_didik_keluar', 
                'pembelajaran', 
                'ekstrakurikuler', 
                'anggota_ekskul', 
                'dudi'
            ];
            foreach($list_data as $data){
                Artisan::call('sinkron:dapodik', ['satuan' => $data]);
            }
        } else {
            Artisan::call('sinkron:'.$this->server, ['satuan' => $this->satuan]);
        }
        $this->emit('finishSync');
    }
    private function hapus_file(){
        Storage::disk('public')->delete('proses_sync.json');
		/*$json_files = Storage::disk('public')->files('kd');
		Storage::disk('public')->delete($json_files);*/
    }
    public function finishSync(){
        $this->reset(['prosesSync', 'server', 'satuan']);
        $this->hapus_file();
        $this->alert('success', 'Proses sinkronisasi selesai!', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'OK',
            'onConfirmed' => 'confirmed',
            'allowOutsideClick' => false,
            'timer' => null
        ]);
    }
    /*
    public function syncSatuan($server, $aksi){
        $this->server = $server;
        $this->aksi = $aksi;
        $this->showSyncButton =! $this->showSyncButton;
    }
    
    */
}
