
@if(session('success'))
    <div class="filament-notifications">
        <div class="filament-notification success">
            {{ session('success') }}
        </div>
    </div>
@endif

<form action="{{ route('filament.pages.ImportExcel') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="filament-form">
        <input type="file" name="file" required>
        <button type="submit" class="filament-button primary">Upload</button>
    </div>
</form>
