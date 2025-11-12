<div>
    <form action="{{ route('locale.update') }}" method="POST" id="locale-switcher-form">
        @csrf
        <select name="locale" onchange="document.getElementById('locale-switcher-form').submit()" class="block w-full px-4 py-2 pr-8 leading-tight text-gray-700 bg-white border border-gray-400 rounded appearance-none focus:outline-none focus:bg-white focus:border-gray-500">
            <option value="id" {{ $currentLocale === 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
            <option value="en" {{ $currentLocale === 'en' ? 'selected' : '' }}>English</option>
        </select>
    </form>
</div>