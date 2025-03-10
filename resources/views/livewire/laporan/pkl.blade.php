<div>
    @include('panels.breadcrumb')
    <div class="content-body">
        <div class="card">
            @if($allowed)
            <form wire:ignore.self wire:submit.prevent="store">
                <div class="card-body">
                    <div class="row mb-2">
                        <label for="dudi_id" class="col-sm-3 col-form-label">Pilih DUDI</label>
                        <div class="col-sm-9" wire:ignore>
                            <select id="dudi_id" class="form-select" wire:model="dudi_id" data-pharaonic="select2" data-component-id="{{ $this->id }}" data-placeholder="== Pilih DUDI ==">
                                <option value="">== Pilih DU/DI ==</option>
                                @foreach ($data_dudi as $dudi)
                                <option value="{{$dudi->dudi_id}}">{{$dudi->nama}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($show)
                    @include('livewire.laporan.pkl-pd')
                    @endif
                </div>
                <div class="card-footer{{($show) ? '' : ' d-none'}}">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
            @else
            <div class="card-body">
                <div class="alert alert-danger alert-block">
                    <div class="alert-body">
                        <p><i class="fas fa-ban"></i> <strong>Akses Ditutup!</strong></p>
                        <p>Kurikulum <strong>{{$nama_kurikulum}}</strong>, Praktik Kerja Lapangan hanya untuk kelas <strong>{{$tingkat}}</strong></p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
