<div>
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Users') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Manage your all users') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    
    <div class="mt-4 overflow-x-auto rounded-2xl shadow-md bg-white">
        <table class="min-w-full border-collapse text-sm text-left text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-semibold text-gray-600">
                <tr>
                    <th scope="col" class="px-6 py-3">User ID</th>
                    <th scope="col" class="px-6 py-3">Email</th>
                    <th scope="col" class="px-6 py-3">Roles</th>
                    <th scope="col" class="px-6 py-3 w-80">Actions</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-2">{{$user -> user_id}}</td>
                        <td class="px-6 py-2">{{$user -> email}}</td>
                        <td class="px-6 py-2">{{$user -> role}}</td>

                        <td class="px-6 py-2 ">
                        <a href="" class="px-3 py-2 text-xs text-white bg-gray-600 rounded hover:bg-gray-700">Show</a>
                        <a href="" class="px-3 py-2 text-xs text-white bg-blue-600 rounded hover:bg-blue-700">Edit</a>
                        <button wire:click='delete({{$user->id}})' wire:confirm='Are you sure you want to delete this user?' class="px-3 py-2 text-xs text-white bg-red-600 rounded hover:bg-red-700">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
