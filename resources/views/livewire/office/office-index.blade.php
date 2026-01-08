<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Offices') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage all your Offices') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <a href="{{route('office.add')}}" class="px-3 py-2 text-xs text-white bg-green-600 rounded hover:bg-green-700">
        Add Office
    </a>    

    
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-3">Office Name</th>
                    <th scope="col" class="px-6 py-3">Manager</th>
                    <th scope="col" class="px-6 py-3 w-80">Actions</th>
                </tr>   
                </thead>
                <tbody>
                @foreach ($offices as $office)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">{{$office -> name}}</td>
                        <td class="px-6 py-2">{{$office -> manager_name}}</td>
                        <td class="px-6 py-2 ">
                        <a href="" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">Show</a>
                        <a href="{{route('office.edit', $office->id)}}" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</a>
                        <button wire:click='delete({{$office->id}})' wire:confirm='Are you sure you want to delete this office?' class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
