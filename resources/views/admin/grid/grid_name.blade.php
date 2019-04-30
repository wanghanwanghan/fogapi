@extends('admin.layout.index')

@section('content')

    {{csrf_field()}}

    <div class="container-fluid">

        <!-- Page Heading -->
        <h1 class="h3 mb-2 text-gray-800">说明</h1>
        <p class="mb-3">审核用户自定义格子名称，<span style="color: red">没有</span>通过审核的名称<span style="color: red">不显示</span>到app上.</p>

        <!-- DataTales Example -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                        <tr>

                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            不用审了
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>

    </script>

@endsection