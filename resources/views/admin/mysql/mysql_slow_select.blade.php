@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">MySql慢查询排行榜</h6>
            </div>
            <div class="card-body">
                <p>SB Admin 2 makes extensive use of Bootstrap 4 utility classes in order to reduce CSS bloat and poor page performance. Custom CSS classes are used to create custom components and custom utility classes.</p>
                <p>Before working with this theme, you should become familiar with the Bootstrap framework, especially the utility classes.</p>
            </div>
        </div>
    </div>



@endsection