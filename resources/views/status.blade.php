<x-layouts::app :title="__('status')">
@section('content')
<div class="flex flex-wrap">
@foreach ($mamaducks as $mamaduck)
<div class="h-70 w-70 outline outline-1 -outline-offset-1 outline-white/10 mx-2 my-2 rounded-base shadow-xs px-4 gap-4 p-4">
  <div class="-ml-2 -mt-2 flex flex-wrap items-center justify-between sm:flex-nowrap">
    <div class="ml-3 mt-4">
      <h3 class="text-base font-semibold text-white">{{ $mamaduck->duck_id }}</h3>
      <p class="mt-1 text-xs text-gray-400">{{ $mamaduck->message_id }}</p>
      <p class="mt-1 text-xs text-gray-400">{{ $mamaduck->payload }}</p>
    </div>
    <div class="ml-4 mt-4 shrink-0">
   @if($mamaduck->created_at->diffInMinutes(now()) > 15)
      <button type="button" class="relative inline-flex items-center rounded-md bg-red-500 px-3 py-2 text-sm font-semibold text-white hover:bg-red-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">Offline</button>
   @else
      <button type="button" class="relative inline-flex items-center rounded-md bg-green-500 px-3 py-2 text-sm font-semibold text-white hover:bg-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-500">Online</button>
    @endif
    </div>
  </div>

  <div class="w-full absolute top-70 justify-end ">
    <div class="text-xs font-semibold text-white">{{ $mamaduck->created_at }}</div>
  </div>

</div>
@endforeach
@endsection
</x-layouts::app>
