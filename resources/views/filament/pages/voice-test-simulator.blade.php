<x-filament-panels::page>
    <div class="space-y-6">
        @if (!$started)
            <div class="max-w-md mx-auto mt-8">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <x-heroicon-o-phone class="w-8 h-8 text-primary-600" />
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Simulador de llamada</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Ingresa el numero de telefono del paciente para iniciar una conversacion de prueba con Pity Voice.
                    </p>
                </div>

                {{ $this->form }}

                <div class="mt-4">
                    <x-filament::button wire:click="startCall" color="primary" class="w-full">
                        Iniciar llamada
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Chat panel --}}
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full {{ $ended ? 'bg-gray-400' : 'bg-green-500' }} {{ $ended ? '' : 'animate-pulse' }}"></div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    Llamada #{{ $callId }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $ended ? 'Finalizada' : 'En curso' }}
                                </span>
                            </div>
                            <x-filament::button wire:click="resetCall" color="gray" size="xs">
                                Nueva llamada
                            </x-filament::button>
                        </div>

                        <div class="h-96 overflow-y-auto p-4 space-y-4" x-data x-init="$el.scrollTop = $el.scrollHeight" x-on:conversation-updated.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)">
                            @forelse ($conversation as $msg)
                                <div class="flex {{ $msg['type'] === 'user' ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[80%] {{ $msg['type'] === 'user'
                                        ? 'bg-primary-500 text-white rounded-2xl rounded-br-sm px-4 py-2.5'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-2xl rounded-bl-sm px-4 py-2.5' }}">
                                        <p class="text-sm whitespace-pre-wrap">{{ $msg['text'] }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-gray-400 py-12">
                                    <p>Conversacion vacia</p>
                                </div>
                            @endforelse

                            @if ($sending)
                                <div class="flex justify-start">
                                    <div class="bg-gray-100 dark:bg-gray-700 rounded-2xl rounded-bl-sm px-4 py-2.5">
                                        <div class="flex gap-1">
                                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0s"></span>
                                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.15s"></span>
                                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.3s"></span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if (!$ended)
                            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                                <form wire:submit="sendMessage" class="flex gap-2">
                                    <input
                                        wire:model="message"
                                        type="text"
                                        placeholder="Escribe tu mensaje..."
                                        class="flex-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500"
                                    />
                                    <x-filament::button type="submit" color="primary" :disabled="$sending">
                                        <x-heroicon-m-paper-airplane class="w-5 h-5" />
                                    </x-filament::button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Tool calls panel --}}
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Tools ejecutadas</h3>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
                            @forelse ($toolCalls as $tc)
                                <div class="px-4 py-3">
                                    <div class="flex items-center gap-2 mb-1">
                                        <x-heroicon-m-code-bracket class="w-4 h-4 text-primary-500" />
                                        <code class="text-xs font-mono text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/30 px-1.5 py-0.5 rounded">
                                            {{ $tc['tool'] }}
                                        </code>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                        <div>
                                            <span class="font-medium">Argumentos:</span>
                                            <pre class="mt-0.5 text-[10px] bg-gray-50 dark:bg-gray-900/50 p-1.5 rounded overflow-x-auto">{{ json_encode($tc['arguments'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                        <div>
                                            <span class="font-medium">Resultado:</span>
                                            <pre class="mt-0.5 text-[10px] bg-gray-50 dark:bg-gray-900/50 p-1.5 rounded overflow-x-auto {{ isset($tc['result']['error']) ? 'text-red-500' : 'text-green-600' }}">{{ json_encode($tc['result'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-8 text-center text-gray-400 text-sm">
                                    <x-heroicon-o-cpu-chip class="w-8 h-8 mx-auto mb-2 opacity-50" />
                                    <p>Aun no se han ejecutado tools</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Call info --}}
                    @php $call = $this->call; @endphp
                    @if ($call)
                        <div class="mt-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">Informacion de la llamada</h3>
                            </div>
                            <div class="px-4 py-3 text-xs space-y-2 text-gray-600 dark:text-gray-400">
                                <div class="flex justify-between">
                                    <span>Estado</span>
                                    <span class="font-medium">{{ $call->status?->label() }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Duracion</span>
                                    <span class="font-medium">{{ $call->duration_seconds ? $call->duration_seconds . 's' : '—' }}</span>
                                </div>
                                @if ($call->handoff_reason)
                                    <div class="flex justify-between">
                                        <span>Handoff</span>
                                        <span class="font-medium text-amber-600">{{ $call->handoff_reason?->label() }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span>Telefono</span>
                                    <span class="font-mono">{{ $call->from_phone }}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
