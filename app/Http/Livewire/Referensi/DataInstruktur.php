<?php

namespace App\Http\Livewire\Referensi;

use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Rap2hpoutre\FastExcel\FastExcel;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\Component;
use App\Models\Guru;
use App\Models\Gelar;
use App\Models\Gelar_ptk;
use App\Models\Agama;
use App\Models\Jenis_ptk;
use App\Models\Status_kepegawaian;
use Carbon\Carbon;
use Helper;

class DataInstruktur extends Component
{
    use WithPagination, WithFileUploads, LivewireAlert;
    protected $paginationTheme = 'bootstrap';
    public $search = '';
    public function updatingSearch()
    {
        $this->resetPage();
    }
    public function loadPerPage(){
        $this->resetPage();
    }
    public $sortby = 'nama';
    public $sortbydesc = 'ASC';
    public $per_page = 10;
    public $data = 'Instruktur';
    public $file_excel;
    public $imported_data;
    public $hapus = TRUE;
    public $guru_id;
    public $readonly;
    public $disabled;
    public $gelar_depan = [];
    public $gelar_belakang = [];
    public $ref_gelar_depan = [];
    public $ref_gelar_belakang = [];
    public $ref_agama = [];
    public $ref_jenis_ptk = [];
    public $ref_status_kepegawaian = [];
    public $nama;
    public $nuptk;
    public $nip;
    public $nik;
    public $jenis_kelamin;
    public $tempat_lahir;
    public $tanggal_lahir;
    public $agama;
    public $agama_id;
    public $alamat_jalan;
    public $rt;
    public $rw;
    public $desa_kelurahan;
    public $kecamatan;
    public $kodepos;
    public $kode_pos;
    public $telp_hp;
    public $no_hp;
    public $email;
    public $jenis_ptk_id;
    public $status_kepegawaian_id;
    public $dudi_id;
    public $opsi_dudi = FALSE;

    protected $rules = [
        'file_excel' => 'required|mimes:xlsx',
    ];
    protected $messages = [
        'file_excel.required' => 'File Excel tidak boleh kosong',
        'file_excel.mimes' => 'File harus berupa file dengan tipe: xlsx.',
    ];

    public function render()
    {
        return view('livewire.referensi.data-instruktur', [
            'data_ptk' => Guru::where(function($query){
                $query->whereIn('jenis_ptk_id', Helper::jenis_gtk('instruktur'));
                $query->where('sekolah_id', session('sekolah_id'));
            })->with(['sekolah' => function($query){
                $query->select('sekolah_id', 'nama');
            }])->orderBy($this->sortby, $this->sortbydesc)
                ->when($this->search, function($ptk) {
                    $ptk->where('nama', 'ILIKE', '%' . $this->search . '%')
                    ->orWhere('nuptk', 'ILIKE', '%' . $this->search . '%');
            })->paginate($this->per_page),
            'breadcrumbs' => [
                ['link' => "/", 'name' => "Beranda"], ['link' => '#', 'name' => 'Referensi'], ['name' => "Data Instruktur"]
            ],
            'tombol_add' => [
                'wire' => 'addModal',
                'color' => 'primary',
                'text' => 'Tambah Data',
            ],
        ]);
    }
    public function addModal(){
        $this->emit('showModal');
    }
    public function updatedFileExcel()
    {
        $this->validate();
        $file_path = $this->file_excel->store('files', 'public');
        $imported_data = (new FastExcel)->import(storage_path('/app/public/'.$file_path));
        $collection = collect($imported_data);
        $multiplied = $collection->map(function ($items, $key) {
            foreach($items as $k => $v){
                $k = str_replace('.','',$k);
                $k = str_replace(' ','_',$k);
                $k = str_replace('/','_',$k);
                $k = strtolower($k);
                $item[$k] = $v;
            }
            return $item;
        });
        foreach($multiplied->all() as $urut => $data){
            $this->nama[$urut] = $data['nama'];
            $this->nuptk[$urut] = $data['nuptk'];
            $this->nip[$urut] = $data['nip'];
            $this->nik[$urut] = $data['nik'];
            $this->jenis_kelamin[$urut] = $data['jenis_kelamin'];
            $this->tempat_lahir[$urut] = $data['tempat_lahir'];
            //$this->tanggal_lahir[$urut] = $data['tanggal_lahir']->format('Y-m-d');
            $this->tanggal_lahir[$urut] = (is_int($data['tanggal_lahir'])) ? $data['tanggal_lahir']->format('Y-m-d') : now()->format('Y-m-d');
            $this->agama[$urut] = $data['agama'];
            $this->alamat_jalan[$urut] = $data['alamat_jalan'];
            $this->rt[$urut] = $data['rt'];
            $this->rw[$urut] = $data['rw'];
            $this->desa_kelurahan[$urut] = $data['desa_kelurahan'];
            $this->kecamatan[$urut] = $data['kecamatan'];
            $this->kodepos[$urut] = $data['kodepos'];
            $this->telp_hp[$urut] = $data['telp_hp'];
            $this->email[$urut] = $data['email'];
        }
        $this->imported_data = $multiplied->all();
    }
    public function store(){
        foreach($this->nama as $urut => $nama){
            $agama = Agama::where('nama', $this->agama[$urut])->first();
            if($agama){
                Guru::updateOrcreate(
                    [
                        'nik' => $this->nik[$urut],
                    ],
                    [
                        'guru_id' => Str::uuid(),
                        'sekolah_id' => session('sekolah_id'),
                        'status_kepegawaian_id' => 0,
                        'kode_wilayah' => '016001AA',
                        'nama' => $nama,
                        'nuptk' => $this->nuptk[$urut],
                        'nip' => $this->nip[$urut],
                        'jenis_kelamin' => $this->jenis_kelamin[$urut],
                        'tempat_lahir' => $this->tempat_lahir[$urut],
                        'tanggal_lahir' => $this->tanggal_lahir[$urut],
                        'agama_id' => $agama->agama_id,
                        'alamat' => $this->alamat_jalan[$urut],
                        'rt' => $this->rt[$urut],
                        'rw' => $this->rw[$urut],
                        'desa_kelurahan' => $this->desa_kelurahan[$urut],
                        'kecamatan' => $this->kecamatan[$urut],
                        'kode_pos' => $this->kodepos[$urut],
                        'no_hp' => $this->telp_hp[$urut],
                        'email' => $this->email[$urut],
                        'jenis_ptk_id' => 97,
                        'last_sync' => now(),
                    ]
                );
            }
        }
        $this->emit('close-modal');
        $this->alert('success', 'Berhasil', [
            'text' => 'Data Instruktur berhasil disimpan'
        ]);
    }
    public function detil($id){
        $this->reset(['guru_id', 'gelar_depan', 'gelar_belakang', 'nuptk', 'nip', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama_id', 'rt', 'rw', 'desa_kelurahan', 'kecamatan', 'kode_pos', 'no_hp', 'email', 'jenis_ptk_id', 'status_kepegawaian_id']);
        $this->guru_id = $id;
        $this->guru = Guru::with(['gelar_depan', 'gelar_belakang'])->find($id);
        foreach($this->guru->gelar_depan->unique() as $gelar_depan){
            $this->gelar_depan[] = $gelar_depan->gelar_akademik_id;
        }
        //->unique()->implode('display_name', ', ')
        foreach($this->guru->gelar_belakang->unique() as $gelar_belakang){
            $this->gelar_belakang[] = $gelar_belakang->gelar_akademik_id;
        }
        $this->nama = $this->guru->nama;
        $this->nuptk = $this->guru->nuptk;
        $this->nip = $this->guru->nip;
        $this->nik = $this->guru->nik;
        $this->jenis_kelamin = $this->guru->jenis_kelamin;
        $this->tempat_lahir = $this->guru->tempat_lahir;
        $this->tanggal_lahir = $this->guru->tanggal_lahir_indo;
        $this->agama_id = $this->guru->agama_id;
        $this->alamat = $this->guru->alamat;
        $this->rt = $this->guru->rt;
        $this->rw = $this->guru->rw;
        $this->desa_kelurahan = $this->guru->desa_kelurahan;
        $this->kecamatan = $this->guru->kecamatan;
        $this->kode_pos = $this->guru->kode_pos;
        $this->no_hp = $this->guru->no_hp;
        $this->email = $this->guru->email;
        $this->jenis_ptk_id = $this->guru->jenis_ptk_id;
        $this->status_kepegawaian_id = $this->guru->status_kepegawaian_id;
        $this->ref_gelar_depan = Gelar::where('posisi_gelar', 1)->get();
		$this->ref_gelar_belakang = Gelar::where('posisi_gelar', 2)->get();
        $this->ref_agama = Agama::get();
        $this->ref_jenis_ptk = Jenis_ptk::get();
        $this->ref_status_kepegawaian = Status_kepegawaian::get();
        $this->dispatchBrowserEvent('ref_gelar_depan', ['ref_gelar_depan' => $this->ref_gelar_depan]);
        $this->dispatchBrowserEvent('ref_gelar_belakang', ['ref_gelar_belakang' => $this->ref_gelar_belakang]);
        $this->dispatchBrowserEvent('gelar_depan', ['gelar_depan' => $this->gelar_depan]);
        $this->dispatchBrowserEvent('gelar_belakang', ['gelar_belakang' => $this->gelar_belakang]);
        $this->dispatchBrowserEvent('pharaonic.select2.init');
        $this->emit('detilGuru');
    }
    private function updateGelar($data){
        $find = Gelar_ptk::where(function($query){
            $query->where('sekolah_id', session('sekolah_id'));
            $query->where('guru_id', $this->guru_id);
        })->first();
        if($find){
            $find->gelar_akademik_id = $data;
            $find->ptk_id = $this->guru_id;
            $find->last_sync = now();
            $find->save();
        } else {
            Gelar_ptk::create(
                [
                    'gelar_ptk_id' => Str::uuid(),
                    'sekolah_id' => session('sekolah_id'),
                    'guru_id' => $this->guru_id,
                    'gelar_akademik_id' => $data,
                    'ptk_id' => $this->guru_id,
                    'last_sync' => now(),
                ]
            );
        }
    }
    public function perbaharui(){
        Gelar_ptk::where(function($query){
            $query->has('gelar_depan');
            $query->where('guru_id', $this->guru_id);
            $query->whereNotIn('gelar_akademik_id', $this->gelar_depan);
        })->delete();
        if($this->gelar_depan){
            foreach($this->gelar_depan as $depan){
                $this->updateGelar($depan);
            }
        }
        Gelar_ptk::where(function($query){
            $query->has('gelar_belakang');
            $query->where('guru_id', $this->guru_id);
            $query->whereNotIn('gelar_akademik_id', $this->gelar_belakang);
        })->delete();
        if($this->gelar_belakang){
            foreach($this->gelar_belakang as $belakang){
                $this->updateGelar($belakang);
            }
        }
        $this->emit('close-modal');
        $this->alert('success', 'Data '.$this->data.' berhasil diperbaharui', [
            'position' => 'center'
        ]);
    }
}
