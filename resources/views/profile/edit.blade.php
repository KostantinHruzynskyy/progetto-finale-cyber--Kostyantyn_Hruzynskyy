<x-layout>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2>Edit Profile</h2>
                    </div>
                    <div class="card-body">
                        @if(session('message'))
                            <div class="alert alert-success">
                                {{ session('message') }}
                            </div>
                        @endif

                        <form action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" name="name" id="name" class="form-control" 
                                       value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" name="email" id="email" class="form-control" 
                                       value="{{ old('email', $user->email) }}" required>
                                @error('email')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                                <input type="password" name="password" id="password" class="form-control">
                                @error('password')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                            </div>

                            <div class="alert alert-warning">
                                <strong>⚠️ Security Notice:</strong> 
                                This form is intentionally vulnerable to demonstrate Mass Assignment attacks. 
                                Try adding hidden fields like <code>is_admin</code> or <code>is_revisor</code> to the form!
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                            <a href="{{ route('homepage') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout>