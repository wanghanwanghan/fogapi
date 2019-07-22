@extends('admin.layout.index')

@section('content')

    <link href="//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">
    <script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js" type="text/javascript"></script>

    <div class="container-fluid">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"></h6>
            </div>
            <div class="card-body">

                <table id="myTable" style="width: 100%;text-align: center">
                    <thead>
                    <tr>
                        <th>格子坐标</th>
                        <th>格子价值</th>
                        <th>最高价值</th>
                        <th>总交易次</th>
                        <th>当前属于</th>
                        <th>当前属于</th>
                        <th>最近交易</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($res as $one)

                        <tr>
                            <th>{{$one['name']}}</th>
                            <th>{{$one['price']}}</th>
                            <th>{{$one['hightPrice']}}</th>
                            <th>{{$one['totle']}}</th>
                            <th>{{$one['belong']}}</th>
                            <th>{{$one['uname']}}</th>
                            <th>{{$one['updated_at']}}</th>
                        </tr>

                    @endforeach

                    </tbody>
                    <tfoot>
                    <tr>
                        <th>格子坐标</th>
                        <th>格子价值</th>
                        <th>最高价值</th>
                        <th>总交易次</th>
                        <th>当前属于</th>
                        <th>当前属于</th>
                        <th>最近交易</th>
                    </tr>
                    </tfoot>
                </table>

            </div>
        </div>
    </div>

    <script>

        $(document).ready( function () {
            $('#myTable').DataTable({
                "language":{
                    "lengthMenu": "每页显示 _MENU_ 记录",
                    "zeroRecords": "无记录",
                    "info": "第 _PAGE_ 页，共 _PAGES_ 页",
                    "infoEmpty": "无记录",
                    "infoFiltered": "无记录",
                    "sSearch":"搜索",
                    "sLoadingRecords": 	"正在加载，请稍等...",
                    "sProcessing":   	"正在加载，请稍等...",
                    "oPaginate": {
                        "sFirst":    	"开始页",
                        "sPrevious": 	"上一页",
                        "sNext":     	"下一页",
                        "sLast":     	"最后页"
                    },
                }
            });
        } );
    </script>

@endsection