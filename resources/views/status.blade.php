<x-layouts::app :title="__('status')">
@section('content')
<style>
  @keyframes critical-glow {
    0%, 100% { box-shadow: 0 0 10px 2px rgba(239,68,68,0.25), 0 0 0 2px rgba(239,68,68,0.6); }
    50%       { box-shadow: 0 0 24px 6px rgba(239,68,68,0.5), 0 0 0 2px rgba(239,68,68,1); }
  }
  .critical-card { animation: critical-glow 2s ease-in-out infinite; }
  .gps-toggle-map {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    margin-left: 0.75rem;
    border-radius: 0.25rem;
    background-color: #16a34a;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1rem;
    font-weight: 600;
    color: #fff;
    cursor: pointer;
    border: none;
    text-decoration: none;
  }
  .gps-toggle-map:hover { background-color: #15803d; color: #fff; }
  .gps-copy-coords {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    padding: 2px;
    border-radius: 4px;
  }
  .gps-copy-coords:hover { color: #fff; background: rgba(255,255,255,0.1); }
</style>
<div class="flex flex-col">
  <div class="mb-4 flex items-center justify-between">
    <h1 class="text-base font-semibold text-white">Duck Status</h1>
    <div class="flex items-center gap-2">
      <select id="urgency-filter" class="rounded-md bg-gray-800 py-1.5 pl-3 pr-8 text-sm text-white outline outline-1 -outline-offset-1 outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500">
        <option value="">All Urgency</option>
        <option value="0">Low</option>
        <option value="1">Medium</option>
        <option value="2">Critical</option>
      </select>
      <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-gray-400">
            <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
          </svg>
        </div>
        <input id="duck-search" type="text" placeholder="Search duck ID…"
          class="w-56 rounded-md bg-gray-800 py-1.5 pl-9 pr-3 text-sm text-white placeholder-gray-500 outline outline-1 -outline-offset-1 outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500">
      </div>
    </div>
  </div>
  <div id="duck-cards-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
@foreach ($mamaducks as $mamaduck)
<div class="{{ $mamaduck->urgency === \App\Enums\Urgency::Critical ? 'critical-card flex flex-col divide-y divide-red-500/30 overflow-hidden rounded-lg bg-red-950/40 outline outline-2 -outline-offset-2 outline-red-500' : 'flex flex-col divide-y divide-white/10 overflow-hidden rounded-lg bg-gray-800/50 outline outline-1 -outline-offset-1 outline-white/10' }}" data-duck-id="{{ $mamaduck->duck_id }}" data-urgency="{{ $mamaduck->urgency !== null ? $mamaduck->urgency->value : '' }}">
  <!-- Header -->
  <div class="{{ $mamaduck->urgency === \App\Enums\Urgency::Critical ? 'px-4 py-4 sm:px-6 flex flex-col gap-2 bg-red-900/50' : 'px-4 py-4 sm:px-6 flex flex-col gap-2' }}">
    <div class="flex items-center justify-between">
      <span class="{{ $mamaduck->urgency === \App\Enums\Urgency::Critical ? 'text-sm font-bold text-red-300 tracking-wide' : 'text-sm font-semibold text-white' }}">
        {{ $mamaduck->duck_id }}
      </span>
      <button type="button" data-status-duck="{{ $mamaduck->duck_id }}" class="rounded bg-green-500 px-2 py-1 text-xs font-semibold text-white hover:bg-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-500">Online</button>
    </div>
  </div>
  <!-- Body -->
  <div class="flex-1 px-4 py-5 sm:p-6">
    <p class="text-sm text-gray-400 text-wrap break-words" data-payload-duck="{{ $mamaduck->duck_id }}">{{ $mamaduck->display_text }}</p>
    <div data-urgency-notice-duck="{{ $mamaduck->duck_id }}">
      @if (str_starts_with(strtoupper($mamaduck->payload ?? ''), 'MSG') && $mamaduck->urgency === \App\Enums\Urgency::Critical)
        <div class="mt-2 flex items-start gap-2 rounded-md bg-red-950 px-3 py-2 ring-2 ring-inset ring-red-500 animate-pulse">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-red-400">
            <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
          </svg>
          <div>
            <p class="text-xs font-bold text-red-400 uppercase tracking-wide">Critical &mdash; Immediate Attention Required</p>
            <p class="text-xs text-red-300/80">This message has been marked as critical urgency and must be attended to immediately.</p>
          </div>
        </div>
      @endif
    </div>
    @if ($mamaduck->sos_from_device)
      <div class="mt-2 flex items-start gap-2 rounded-md bg-red-900/50 px-3 py-2 ring-1 ring-inset ring-red-500/40">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-red-400">
          <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="text-xs font-semibold text-red-400">SOS &mdash; Hardware Button Triggered</p>
          <p class="text-xs text-red-300/80">This SOS was sent because the physical SOS button on the device was pressed.</p>
        </div>
      </div>
    @elseif ($mamaduck->sos_from_mobile)
      <div class="mt-2 flex items-start gap-2 rounded-md bg-orange-900/50 px-3 py-2 ring-1 ring-inset ring-orange-500/40">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-orange-400">
          <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="text-xs font-semibold text-orange-400">SOS &mdash; Mobile Phone Triggered</p>
          <p class="text-xs text-orange-300/80">This SOS was sent from the user&rsquo;s mobile phone application and should include GPS coordinates.</p>
        </div>
      </div>
    @endif
    @if ($mamaduck->gps_unavailable)
      <p class="mt-2 inline-flex items-center gap-1.5 text-xs text-yellow-400">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0">
          <path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" />
        </svg>
        GPS location unavailable
      </p>
    @endif
    @if ($mamaduck->id === $latestCoordsId && $mamaduck->map_url)
      @php $mapDialogId = 'map-dialog-' . $mamaduck->id; @endphp
      <button command="show-modal" commandfor="{{ $mapDialogId }}"
         class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-green-500">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
          <path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" />
        </svg>
        View on Map
      </button>
      <el-dialog>
        <dialog id="{{ $mapDialogId }}" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
          <el-dialog-backdrop class="fixed inset-0 bg-gray-900/75 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>
          <div tabindex="0" class="flex min-h-full items-center justify-center p-4 focus:outline focus:outline-0">
            <el-dialog-panel class="relative w-full max-w-2xl overflow-hidden rounded-lg bg-gray-800 shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in data-[closed]:scale-95">
              <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
                <h3 class="text-sm font-semibold text-white">{{ $mamaduck->duck_id }} — Location</h3>
                <button command="close" commandfor="{{ $mapDialogId }}" class="text-gray-400 hover:text-white text-lg leading-none">&times;</button>
              </div>
              <div class="w-full h-96">
                <iframe
                  src="{{ $mamaduck->map_embed_url }}"
                  class="w-full h-full border-0"
                  allowfullscreen
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade">
                </iframe>
              </div>
              <div class="flex justify-end gap-3 px-4 py-3 border-t border-white/10">
                <a href="{{ $mamaduck->map_url }}" target="_blank" rel="noopener noreferrer" class="text-xs text-indigo-400 hover:text-indigo-300">Open in Google Maps &rarr;</a>
                <button command="close" commandfor="{{ $mapDialogId }}" class="rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/20">Close</button>
              </div>
            </el-dialog-panel>
          </div>
        </dialog>
      </el-dialog>
    @endif
  </div>
  <!-- Footer -->
  <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
    <span class="text-sm text-white" data-timestamp-duck="{{ $mamaduck->duck_id }}">{{ $mamaduck->created_at->diffForHumans() }}</span>
    <div>
      <!-- Include this script tag or install `@tailwindplus/elements` via npm: -->
      <!-- <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script> -->
      <button command="show-modal" commandfor="msg-dialog-{{ $mamaduck->duck_id }}" class="rounded-md bg-white/10 px-2.5 py-1.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/5 hover:bg-white/20">Message</button>
      <el-dialog>
        <dialog id="msg-dialog-{{ $mamaduck->duck_id }}" aria-labelledby="dialog-title" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/50 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline focus:outline-0 sm:items-center sm:p-0">
          <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:translate-y-4 data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in sm:my-8 sm:w-full sm:max-w-sm sm:p-6 data-[closed]:sm:translate-y-0 data-[closed]:sm:scale-95">

<form id="message-form-{{ $mamaduck->duck_id }}" class="duck-message-form" action="/status/send">
  @csrf
  <input type="hidden" name="duck_id" value="{{ $mamaduck->duck_id }}">
  <div class="space-y-12">
    <div class="border-b border-white/10 pb-3">
      <h2 class="text-base/7 font-semibold text-white">Messaging</h2>
      <p class="mt-1 text-sm/6 text-gray-400">This messaging is on a best-effort basis</p>

        <!-- Last known GPS location — updated by pollHistory() -->
        <div data-gps-duck="{{ $mamaduck->duck_id }}" class="mt-3"></div>

        <!-- Conversation history — populated by pollHistory() in app.js -->
        <div class="mt-3 h-48 overflow-y-auto rounded-md bg-white/5 p-3 space-y-2 outline outline-1 -outline-offset-1 outline-white/10"
             data-history-duck="{{ $mamaduck->duck_id }}">
          <p class="text-center text-xs text-gray-500">Loading…</p>
        </div>

        <div class="col-span-full mt-4">
          <label for="about" class="block text-sm/6 font-medium text-white">New message</label>
          <div class="mt-2">
            <textarea id="about" name="message" rows="3" class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-500 sm:text-sm/6"></textarea>
          </div>
          <p class="mt-3 text-sm/6 text-gray-400">Your have maximum of 256 characters (not words)</p>
        </div>
    </div>
  </div>

  <div class="mt-2">
            <button type="submit" command="close" commandfor="msg-dialog-{{ $mamaduck->duck_id }}" class="duck-send-message inline-flex w-full justify-center rounded-md bg-yellow-500 px-3 py-2 text-sm font-semibold text-white hover:bg-yellow-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500">Send Message</button>
  </div>
</form>
        </el-dialog-panel>
  </div>
    </dialog>
  </el-dialog>
    </div>
  </div>
</div>
@endforeach
    <!-- Empty state -->
    <div id="duck-empty-state" class="col-span-full hidden py-16 text-center">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mx-auto mb-3 size-10 text-gray-600">
        <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
      </svg>
      <p id="duck-empty-title" class="text-sm font-semibold text-gray-400">No ducks found</p>
      <p id="duck-empty-sub" class="mt-1 text-xs text-gray-600">Try adjusting the search or urgency filter.</p>
    </div>
</div>
</div>

<script>
  var urgencyLabels = { '0': 'Low', '1': 'Medium', '2': 'Critical' };

  function applyFilters() {
    var q = document.getElementById('duck-search').value.trim().toLowerCase();
    var u = document.getElementById('urgency-filter').value;
    var visible = 0;

    document.querySelectorAll('#duck-cards-container [data-duck-id]').forEach(function (card) {
      var id      = card.getAttribute('data-duck-id').toLowerCase();
      var urgency = card.getAttribute('data-urgency');

      // Neither filter active — show all
      if (q === '' && u === '') { card.style.display = ''; visible++; return; }

      // Only urgency active
      if (q === '' && u !== '') { var show = urgency === u; card.style.display = show ? '' : 'none'; if (show) visible++; return; }

      // Only search active
      if (q !== '' && u === '') { var show = id.includes(q); card.style.display = show ? '' : 'none'; if (show) visible++; return; }

      // Both active — OR
      var show = id.includes(q) || urgency === u; card.style.display = show ? '' : 'none'; if (show) visible++;
    });

    var empty = document.getElementById('duck-empty-state');
    var title = document.getElementById('duck-empty-title');
    var sub   = document.getElementById('duck-empty-sub');

    if (visible === 0) {
      empty.classList.remove('hidden');
      if (q !== '' && u === '') {
        title.textContent = 'No ducks matching "' + q + '"';
        sub.textContent   = 'Try a different duck ID.';
      } else if (q === '' && u !== '') {
        title.textContent = 'No ducks with ' + (urgencyLabels[u] || u) + ' urgency';
        sub.textContent   = 'There are currently no active ducks at this urgency level.';
      } else {
        title.textContent = 'No ducks found';
        sub.textContent   = 'Try adjusting the search or urgency filter.';
      }
    } else {
      empty.classList.add('hidden');
    }
  }

  document.getElementById('duck-search').addEventListener('input', applyFilters);
  document.getElementById('urgency-filter').addEventListener('change', applyFilters);
</script>
@endsection
</x-layouts::app>
