<table class="table table-striped table-hover">
    <thead class="table-dark">
        <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">Email</th>
            <th scope="col">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($roleRequests as $user)
            <tr>
                <th scope="row">{{$user->id}}</th>
                <td>{{$user->name}}</td>
                <td>{{$user->email}}</td>
                <td>
                    @switch($role)
                        @case('admin')
                            {{-- Form protetto da CSRF - sostituisce il link vulnerabile in GET --}}
                            <form action="{{route('admin.setAdmin', $user)}}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
                            </form>
                            @break
                        @case('revisor')
                            {{-- Form protetto da CSRF - sostituisce il link vulnerabile in GET --}}
                            <form action="{{route('admin.setRevisor', $user)}}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
                            </form>
                            @break
                        @case('writer')
                            {{-- Form protetto da CSRF - sostituisce il link vulnerabile in GET --}}
                            <form action="{{route('admin.setWriter', $user)}}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-secondary">Enable {{$role}}</button>
                            </form>
                            @break
                    @endswitch
                </td>
            </tr>
        @endforeach
    </tbody>
</table>