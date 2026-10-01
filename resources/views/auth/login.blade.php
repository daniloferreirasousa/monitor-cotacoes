<form method="POST" action="{{ route('login.store') }}">
	@csrf
	<input type="email" name="email" value="{{ old('email') }}" required>
	<input type="password" name="password" required>
	<button type="submit">Entrar</button>
</form>
