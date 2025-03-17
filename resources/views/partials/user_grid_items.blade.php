@foreach ($users as $user)
    <div class="gap-4 p-6 bg-white dark:bg-gray-800/50 dark:bg-gradient-to-bl from-gray-700/50 via-transparent dark:ring-1 dark:ring-inset dark:ring-white/5 rounded-lg shadow-2xl shadow-gray-500/20 flex flex-col items-center">
        @if($user->photo)
            <img src="{{ asset($user->photo) }}" alt="{{ $user->name }}" class="h-16 w-16 rounded-full mb-2">
        @else
            <img src="{{ asset('img/default.jpg') }}" alt="Default Avatar" class="h-16 w-16 rounded-full mb-2">
        @endif
        <div>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $user->name }}</h2>
            <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">{{ $user->email }}</p>
            <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">{{ $user->phone }}</p>
            <p class="mt-2 text-gray-500 dark:text-gray-400 text-sm">{{ \App\Models\User::getPosition($user->position_id) }}</p>
        </div>
    </div>
@endforeach
