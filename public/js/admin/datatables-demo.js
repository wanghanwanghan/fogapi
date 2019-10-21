get_data1();

function get_data1() {

  //真实用户发布印象数由多到少排序
  var url ='/admin/security/ajax';

  var data=
      {
        _token:$("input[name=_token]").val(),
        type  :'user_publish_article_totle_datatables_1'
      };

  $.post(url,data,function (response) {

    $.each(response,function(key,value)
    {
      var tr=$("<tr></tr>");

      tr.append("<th>"+value.publishTotal+"</th>");
      tr.append("<th>"+value.userName+"</th>");
      tr.append("<th>"+value.uid+"</th>");

      $("#userPublishArticleTotleDataTables1_fillData").append(tr);
    });

    $('#userPublishArticleTotleDataTables1').DataTable({
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
      },
      "pageLength": 5,
      "lengthMenu": [5],
    });

  },'json');
}

$(document).ready(function() {





});

