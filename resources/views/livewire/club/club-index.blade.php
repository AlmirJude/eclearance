<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Clubs') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your all clubs') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>
    
    <a href="{{  route("club.add")  }}" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
        Add Club
    </a>    

    
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-3">Club Name</th>
                    <th scope="col" class="px-6 py-3">Abbreviation</th>
                    <th scope="col" class="px-6 py-3">Moderator</th>
                    <th scope="col" class="px-6 py-3 w-80">Actions</th>
                </tr>   
                </thead>
                <tbody>
                @foreach ($clubs as $club)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">{{$club -> name}}</td>
                        <td class="px-6 py-2">{{$club -> Abbreviation}}</td>
                        <td class="px-6 py-2">{{$club -> moderator_name}}</td>
                        <td class="px-6 py-2 ">
                        <a href="" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">Show</a>
                        <a href="{{route ('club.edit', $club->id)}}" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</a>
                        <button wire:click='delete({{$club->id}})' wire:confirm='Are you sure you want to delete this club?' class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
