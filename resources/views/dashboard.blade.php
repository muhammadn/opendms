<x-layouts::app :title="__('Dashboard')">
@section('content')
<div class="max-w w-full bg-transparent border border-white/10 rounded-base shadow-xs p-4 md:p-6">
  <div class="flex justify-between items-start">
    <div>
      <h5 class="text-2xl font-semibold text-heading text-white">{{ Number::forHumans($count) }}</h5>
      <p class="text-body text-gray-400">Messages today</p>
    </div>
    <div class="flex items-center px-2.5 py-0.5 font-medium text-fg-success text-center">
      <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"/></svg>
      12%
    </div>
  </div>
  <div id="area-chart"></div>
  <div class="grid grid-cols-1 items-center border-transparent border-t justify-between">
    <div class="flex justify-between items-center pt-4 md:pt-6">
      <!-- Button -->
      <!--
      <button id="dropdownDefaultButton" data-dropdown-toggle="lastDaysdropdown" data-dropdown-placement="bottom" class="text-sm font-medium text-body text-gray-400 hover:text-heading text-center inline-flex items-center" type="button">
          Last 7 days
          <svg class="w-4 h-4 ms-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
      </button>
      -->
      <!-- Dropdown menu -->
      <div id="lastDaysdropdown" class="z-10 hidden bg-neutral-primary-medium border border-default-medium rounded-base shadow-lg w-44">
          <ul class="p-2 text-sm text-body font-medium" aria-labelledby="dropdownDefaultButton">
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Yesterday</a>
            </li>
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Today</a>
            </li>
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Last 7 days</a>
            </li>
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Last 30 days</a>
            </li>
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Last 90 days</a>
            </li>
          </ul>
      </div>
      <!--
      <a href="#" class="inline-flex items-center text-fg-brand bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded-base text-sm px-3 py-2 focus:outline-none">
        Users Report
        <svg class="w-4 h-4 ms-1.5 -me-0.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"/></svg>
      </a>
      -->
    </div>
  </div>
</div>






        <div>
          <h3 class="text-base font-semibold text-white mt-6">Last 24 hours</h3>
          <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-white/10 sm:p-6">
              <dt class="truncate text-sm font-medium text-gray-400">PapaDucks</dt>
              <dd class="mt-1 text-3xl font-semibold tracking-tight text-white">1</dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-white/10 sm:p-6">
              <dt class="truncate text-sm font-medium text-gray-400">MamaDucks</dt>
              <dd class="mt-1 text-3xl font-semibold tracking-tight text-white">{{ $mamaducks }}</dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-white/10 sm:p-6">
              <dt class="truncate text-sm font-medium text-gray-400">Total Messages</dt>
              <dd class="mt-1 text-3xl font-semibold tracking-tight text-white">{{ $count }}</dd>
            </div>
          </dl>
        </div>

<div class="px-4 sm:px-6 lg:px-8">
  <div class="-mx-4 mt-10 ring-1 ring-white/15 sm:mx-0 sm:rounded-lg">
    <table class="relative min-w-full divide-y divide-white/15">
      <thead>
        <tr>
          <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6">DuckID</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Timestamp</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Topic</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">MessageID</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Path</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Payload</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Hops</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Type</th>
        </tr>
      </thead>
      <tbody>
       @foreach ($clusters as $cluster) 
        <tr>
          <td class="relative border-t border-transparent py-4 pl-4 pr-3 text-sm sm:pl-6">
            <div class="font-medium text-white">{{ $cluster->duck_id }}</div>
            <div class="mt-1 flex flex-col text-gray-400 sm:block lg:hidden">
              <span>16 GB RAM / 8 CPUs</span>
              <span class="hidden sm:inline">·</span>
              <span>512 GB SSD disk</span>
            </div>
            <div class="absolute -top-px left-6 right-0 h-px bg-white/10"></div>
          </td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->created_at }}</td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->topic }}</td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->message_id }}</td>
          <td class="border-t border-white/10 px-3 py-3.5 text-sm text-gray-400">
            <div class="sm:hidden">{{ $cluster-> path }}</div>
            <div class="hidden sm:block">{{ $cluster->path }}</div>
          </td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->payload }}</td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->hops }}</td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->duck_type }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
</x-layouts::app>
