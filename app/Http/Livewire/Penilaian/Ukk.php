<?php

namespace App\Http\Livewire\Penilaian;

use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Models\Rencana_ukk;
use App\Models\Peserta_didik;
use App\Models\Nilai_ukk;

class Ukk extends Component
{
    use LivewireAlert;
    public $semester_id;
    public $rencana_ukk_id;
    public $rencana_ukk = [];
    public $data_siswa = [];
    public $nilai_ukk = [];
    public $anggota_rombel = [];
    public function render()
    {
        $this->semester_id = session('semester_id');
        $breadcrumbs = [
            ['link' => "/", 'name' => "Beranda"], ['link' => '#', 'name' => 'Penilaian'], ['name' => "UKK"]
        ];
        if(!status_penilaian()){
            return view('components.non-aktif', [
                'breadcrumbs' => $breadcrumbs,
            ]);
        }
        $this->rencana_ukk = Rencana_ukk::where('internal', $this->loggedUser()->guru_id)->with(['paket_ukk'])->get();
        //$this->dispatchBrowserEvent('paket_ukk', ['paket_ukk' => $this->paket_ukk]);
        return view('livewire.penilaian.ukk', [
            'breadcrumbs' => $breadcrumbs
        ]);
    }
    private function loggedUser(){
        return auth()->user();
    }
    public function updatedRencanaUkkId($value){
        if($value){
            $this->reset(['data_siswa', 'nilai_ukk', 'rencana_ukk_id', 'anggota_rombel']);
            $this->rencana_ukk_id = $value;
            $this->data_siswa = Peserta_didik::with([
                'anggota_rombel' => function($query){
                    $query->whereHas('nilai_ukk_satuan', function($query){
                        $query->where('rencana_ukk_id', $this->rencana_ukk_id);
                    });
                    $query->with(['nilai_ukk_satuan' => function($query){
                        $query->where('rencana_ukk_id', $this->rencana_ukk_id);
                    }]);
                }
            ])->orderBy('nama', 'ASC')->where(function($query){
                $query->whereHas('nilai_ukk', function($query){
                    $query->where('rencana_ukk_id', $this->rencana_ukk_id);
                });
            })->get();
            $nilai_ukk = [];
            $anggota_rombel = [];
            foreach($this->data_siswa as $siswa){
                $nilai_ukk[$siswa->anggota_rombel->anggota_rombel_id] = ($siswa->anggota_rombel->nilai_ukk_satuan) ? $siswa->anggota_rombel->nilai_ukk_satuan->nilai : 0;
                $anggota_rombel[$siswa->peserta_didik_id] = $siswa->anggota_rombel->anggota_rombel_id;
            }
            $this->nilai_ukk = $nilai_ukk;
            $this->anggota_rombel = $anggota_rombel;
        }
    }
    public function store(){
        /*
         "0a6ae7e4-651a-49fe-a097-ad6a24dd508d" => "7c8b0914-5260-41cf-9db8-02eab83d4a8a"
        "anggota_rombel_id" => "bdf14ce6-c067-44c5-beaf-a7003c7d05c6"
            "peserta_didik_id" => "0a6ae7e4-651a-49fe-a097-ad6a24dd508d"
        "anggota_rombel_id" => "bdf14ce6-c067-44c5-beaf-a7003c7d05c6"
            "peserta_didik_id" => "0a6ae7e4-651a-49fe-a097-ad6a24dd508d"
        */
        foreach($this->anggota_rombel as $peserta_didik_id => $anggota_rombel_id){
            //dump($peserta_didik_id);
            //dump($anggota_rombel_id);
            if($this->nilai_ukk[$anggota_rombel_id]){
                Nilai_ukk::updateOrCreate(
                    [
                        'sekolah_id' => session('sekolah_id'),
                        'rencana_ukk_id' => $this->rencana_ukk_id,
                        'anggota_rombel_id' => $anggota_rombel_id,
                        'peserta_didik_id' => $peserta_didik_id,
                    ],
                    [
                        'nilai' => $this->nilai_ukk[$anggota_rombel_id],
                        'last_sync' => now(),
                    ]
                );
                //dump($a);
            }
        }
        //dump($this->anggota_rombel);
        //dd($this->nilai_ukk);
        $this->flash('success', 'Nilai UKK berhasil disimpan', [], '/penilaian/ukk');
    }
}
