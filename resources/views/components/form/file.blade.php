<div>
    <div class="form-group">
        <label for="{{ $name }}" class="form-label">{{ $label }}:</label>

        @if($file && file_exists(public_path($file)) && preg_match('/\.(mp4|avi|mov)$/i', $file))
            @php
                // Encode each path segment for the video URL
                $videoPath = collect(explode('/', $file))
                    ->map(fn($segment) => urlencode($segment))
                    ->implode('/');
            @endphp
            <video controls style="max-width: 100%; height:50%; margin-bottom: 15px;">
                <source src="{{ asset($videoPath) }}" type="video/mp4" />
                Your browser does not support the video tag.
            </video>
        @endif

        <input
            type="file"
            class="dropify @error($name) is-invalid @enderror"
            name="{{ $name }}" id="{{ $name }}"
            data-default-file="{{ 
                // Show existing image preview only if file is an image
                $file && file_exists(public_path($file)) && preg_match('/\.(jpeg|jpg|png)$/i', $file) 
                ? asset($file) 
                : '' 
            }}" 
        />

        {{ $slot }}

        @error($name)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>
