<div>
    <!-- Modal -->
    <div class="modal fade" id="aioModal" tabindex="-1" aria-labelledby="aioModalLabel" aria-hidden="true" 
         wire:ignore.self data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="aioModalLabel">
                        @if($keyword)
                            AI Overview Results for: "{{ $keyword }}"
                        @else
                            AI Overview Results
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" 
                            @if(!$loading) wire:click="closeModal" @endif></button>
                </div>
                
                <!-- Modal Body -->
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Loading State -->
                    @if($loading)
                        <div class="text-center py-8">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-primary fw-medium">{{ $status }}</p>
                        </div>
                    @endif
                    
                    <!-- Status Messages -->
                    @if($status && !$loading)
                        <div class="alert alert-info mb-4">
                            <p class="mb-0">{{ $status }}</p>
                        </div>
                    @endif
                    
                    <!-- AI Overview Data -->
                    @if($aioData && !$loading)
                        <div class="space-y-4">
                            @if(isset($aioData['markdown']))
                                <div>
                                    <h6 class="fw-semibold mb-2">AI Overview:</h6>
                                    <div class="p-3 bg-light rounded border">
                                        {!! Str::markdown($aioData['markdown']) !!}
                                    </div>
                                </div>
                            @endif
                            
                            @if(isset($aioData['text_blocks']) && is_array($aioData['text_blocks']))
                                <div>
                                    <h6 class="fw-semibold mb-2">Text Blocks:</h6>
                                    <div class="space-y-3">
                                        @foreach($aioData['text_blocks'] as $block)
                                            <div class="p-3 border rounded">
                                                <p class="text-muted small mb-1">Type: {{ $block['type'] ?? 'Unknown' }}</p>
                                                <p>{{ $block['text'] ?? '' }}</p>
                                                @if(isset($block['links']) && is_array($block['links']))
                                                    <div class="mt-2">
                                                        <p class="small fw-medium">References:</p>
                                                        <ul class="list-unstyled ps-3 small">
                                                            @foreach($block['links'] as $link)
                                                                <li><a href="{{ $link['link'] }}" target="_blank" class="text-primary text-decoration-none">{{ $link['title'] ?? $link['link'] }}</a></li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Raw JSON View (Optional) -->
                            <div class="mt-4">
                                <details>
                                    <summary class="cursor-pointer small fw-medium text-muted">
                                        View Raw JSON Data
                                    </summary>
                                    <pre class="mt-2 p-3 bg-dark text-light rounded small overflow-auto"><code>{{ json_encode($aioData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </details>
                            </div>
                        </div>
                    @elseif(!$loading && !$aioData && $status)
                        <div class="text-center py-8 text-muted">
                            <p>{{ $status }}</p>
                        </div>
                    @endif
                </div>
                
                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" 
                            @if(!$loading) wire:click="closeModal" @endif>
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Initialize Bootstrap Modal with proper cleanup -->
    <script>
        document.addEventListener('livewire:init', () => {
            let aioModal = null;
            
            // Initialize modal only once
            const initModal = () => {
                const modalElement = document.getElementById('aioModal');
                if (modalElement && !aioModal) {
                    aioModal = new bootstrap.Modal(modalElement, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    
                    // Clear modal backdrop on hide
                    modalElement.addEventListener('hidden.bs.modal', function () {
                        // Force remove any extra backdrops
                        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                            backdrop.remove();
                        });
                        
                        // Remove modal-open class from body
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    });
                }
            };
            
            // Initialize modal when component mounts
            initModal();
            
            // Listen for show modal event
            Livewire.on('showAioModal', () => {
                initModal();
                if (aioModal) {
                    aioModal.show();
                }
            });
            
            // Listen for hide modal event
            Livewire.on('hideAioModal', () => {
                if (aioModal) {
                    aioModal.hide();
                    
                    // Clean up extra backdrops
                    setTimeout(() => {
                        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                            if (backdrop.parentNode) {
                                backdrop.remove();
                            }
                        });
                        
                        // Remove modal-open class
                        document.body.classList.remove('modal-open');
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                    }, 300);
                }
            });
            
            // Clean up on component unmount
            Livewire.on('component-unmount', () => {
                if (aioModal) {
                    aioModal.hide();
                    aioModal = null;
                }
            });
        });
    </script>
</div>